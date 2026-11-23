<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Modules\Bookings\Enums\BookingStatus;
use Modules\Bookings\Enums\TicketStatus;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\Ticket;
use Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;
use Modules\Tenancy\Models\TheaterProfile;
use Modules\Tenancy\Services\TenantProvisioningService;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\EventTranslation;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\Show;
use Modules\Theaters\Models\TicketTier;
use Modules\Theaters\Models\TicketTierTranslation;
use Modules\Theaters\Services\ShowService;
use Modules\Users\Models\User;
use Tests\TestCase;

class Phase6BookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        Queue::fake();
        $this->seed(TenancyDatabaseSeeder::class);
    }

    public function test_public_show_tiers_expose_bookable_availability(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-public');

        $this->getJson("/api/v1/public/shows/{$show->id}/tiers")
            ->assertOk()
            ->assertJsonPath('data.show.id', $show->id)
            ->assertJsonPath('data.tiers.0.id', $tier->id)
            ->assertJsonPath('data.tiers.0.available_quantity', 10)
            ->assertJsonPath('data.tiers.0.can_book', true)
            ->assertJsonPath('data.tiers.0.sale_state', 'available');
    }

    public function test_an_individual_can_book_tickets_and_receive_tickets(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-booking');
        $user = $this->createUser('booking');

        Sanctum::actingAs($user, ['users']);

        $response = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'booking-key-1',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertCreated()
            ->assertJsonPath('data.status', BookingStatus::CONFIRMED->value)
            ->assertJsonPath('data.ticket_count', 2)
            ->assertJsonCount(2, 'data.tickets');

        $bookingId = $response->json('data.id');
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'user_id' => $user->id,
            'status' => BookingStatus::CONFIRMED->value,
        ]);
        $this->assertDatabaseHas('ticket_tiers', ['id' => $tier->id, 'sold_quantity' => 2]);
        $this->assertSame(2, Ticket::query()->where('booking_id', $bookingId)->count());
        $this->assertNotEmpty($response->json('data.tickets.0.qr_token'));
        $this->assertDatabaseHas('app_notifications', ['notifiable_type' => User::class, 'notifiable_id' => $user->id, 'type' => 'booking_confirmed']);
    }

    public function test_booking_is_idempotent_and_rejects_a_changed_payload(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-idempotency');
        $user = $this->createUser('idempotency');
        Sanctum::actingAs($user, ['users']);

        $first = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'same-booking-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertCreated();

        $second = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'same-booking-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('ticket_tiers', ['id' => $tier->id, 'sold_quantity' => 2]);

        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'same-booking-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_multi_tier_booking_rolls_back_when_one_tier_is_unavailable(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-rollback');
        $secondTier = $this->createTier($show, 'Balcony', 1, 50);
        $user = $this->createUser('rollback');
        Sanctum::actingAs($user, ['users']);

        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'rollback-key',
            'items' => [
                ['ticket_tier_id' => $tier->id, 'quantity' => 1],
                ['ticket_tier_id' => $secondTier->id, 'quantity' => 2],
            ],
        ])->assertStatus(422);

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseHas('ticket_tiers', ['id' => $tier->id, 'sold_quantity' => 0]);
        $this->assertDatabaseHas('ticket_tiers', ['id' => $secondTier->id, 'sold_quantity' => 0]);
    }

    public function test_sold_out_and_inactive_tiers_cannot_be_booked(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-sold-out', 1);
        $user = $this->createUser('sold-out');
        Sanctum::actingAs($user, ['users']);

        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'sold-out-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertStatus(422);

        $tier->update(['total_quantity' => 5, 'is_active' => false]);

        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'inactive-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_sale_window_live_limit_and_currency_rules_are_enforced(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-sale-rules');
        $user = $this->createUser('sale-rules');
        Sanctum::actingAs($user, ['users']);

        $tier->update(['sale_starts_at' => now()->addDay()]);
        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'sale-not-started',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $tier->update(['sale_starts_at' => null, 'sale_ends_at' => now()->subMinute()]);
        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'sale-ended',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $tier->update(['sale_ends_at' => null, 'per_booking_limit' => 1]);
        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'limit-exceeded',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertStatus(422);

        $otherTier = $this->createTier($show, 'VIP', 5, 200);
        $otherTier->update(['currency' => 'USD']);
        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'currency-mismatch',
            'items' => [
                ['ticket_tier_id' => $tier->id, 'quantity' => 1],
                ['ticket_tier_id' => $otherTier->id, 'quantity' => 1],
            ],
        ])->assertStatus(422);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_price_is_snapshotted_and_fake_prices_are_ignored(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-price');
        $user = $this->createUser('price');
        Sanctum::actingAs($user, ['users']);

        $response = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'price-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2, 'unit_price' => 1]],
        ])->assertCreated()
            ->assertJsonPath('data.total', '200.00');

        $tier->update(['price' => 999]);

        $this->getJson('/api/v1/user/bookings/'.$response->json('data.id'))
            ->assertOk()
            ->assertJsonPath('data.items.0.unit_price', '100.00')
            ->assertJsonPath('data.total', '200.00');
    }

    public function test_ticket_expiration_command_marks_unused_tickets_expired(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-expire-ticket');
        $user = $this->createUser('expire-ticket');
        Sanctum::actingAs($user, ['users']);

        $bookingId = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'expire-ticket-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertCreated()->json('data.id');

        $show->update(['ends_at' => now()->subMinute()]);

        $this->artisan('bookings:expire-tickets')->assertExitCode(0);

        $this->assertDatabaseHas('tickets', ['booking_id' => $bookingId, 'status' => TicketStatus::EXPIRED->value]);
    }

    public function test_user_can_cancel_before_cutoff_and_inventory_is_restored(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-cancel');
        $user = $this->createUser('cancel');
        Sanctum::actingAs($user, ['users']);

        $id = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'cancel-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/user/bookings/{$id}/cancel", ['reason' => 'Plans changed.'])
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::CANCELLED->value);

        $this->assertDatabaseHas('ticket_tiers', ['id' => $tier->id, 'sold_quantity' => 0]);
        $this->assertDatabaseHas('tickets', ['booking_id' => $id, 'status' => TicketStatus::CANCELLED->value]);
    }

    public function test_user_cannot_cancel_inside_cutoff_or_access_another_users_booking(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-cutoff');
        $user = $this->createUser('cutoff');
        $other = $this->createUser('other');
        Sanctum::actingAs($user, ['users']);

        $id = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'cutoff-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
        ])->assertCreated()->json('data.id');

        $show->update(['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(3)]);

        $this->postJson("/api/v1/user/bookings/{$id}/cancel")->assertStatus(422);

        Sanctum::actingAs($other, ['users']);
        $this->getJson("/api/v1/user/bookings/{$id}")->assertNotFound();
        $this->postJson("/api/v1/user/bookings/{$id}/cancel")->assertNotFound();
    }

    public function test_theater_can_list_view_and_cancel_its_booking(): void
    {
        [$theater, $owner, , $show, , $tier] = $this->createShowWithTier('phase6-theater');
        $user = $this->createUser('theater-booking');
        Sanctum::actingAs($user, ['users']);

        $id = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'theater-booking-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($owner, ['tenant_members']);

        $this->getJson('/api/v1/theater/dashboard/bookings')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.user.name', $user->name);

        $this->getJson("/api/v1/theater/dashboard/bookings/{$id}")
            ->assertOk()
            ->assertJsonPath('data.tickets.0.status', TicketStatus::VALID->value);

        $this->postJson("/api/v1/theater/dashboard/bookings/{$id}/cancel", [
            'reason' => 'Emergency maintenance.',
        ])->assertOk()->assertJsonPath('data.status', BookingStatus::CANCELLED->value);

        $this->assertDatabaseHas('ticket_tiers', ['id' => $tier->id, 'sold_quantity' => 0]);
    }

    public function test_ticket_validation_marks_a_ticket_used_once_and_rejects_other_theaters(): void
    {
        [, $owner, , $show, , $tier] = $this->createShowWithTier('phase6-ticket');
        $user = $this->createUser('ticket');
        Sanctum::actingAs($user, ['users']);

        $bookingId = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'ticket-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
        ])->assertCreated()->json('data.id');

        $token = Ticket::query()->where('booking_id', $bookingId)->firstOrFail()->qr_token;

        Sanctum::actingAs($owner, ['tenant_members']);
        $this->postJson('/api/v1/theater/dashboard/tickets/validate', ['qr_token' => $token])
            ->assertOk()
            ->assertJsonPath('data.status', TicketStatus::USED->value);

        $this->postJson('/api/v1/theater/dashboard/tickets/validate', ['qr_token' => $token])
            ->assertStatus(422);

        [$otherTheater, $otherOwner] = $this->createTheater('phase6-ticket-other');
        Sanctum::actingAs($otherOwner, ['tenant_members']);
        $this->postJson('/api/v1/theater/dashboard/tickets/validate', ['qr_token' => $token])
            ->assertNotFound();
    }

    public function test_theater_cannot_view_another_theaters_booking(): void
    {
        [, , , $show, , $tier] = $this->createShowWithTier('phase6-tenant-isolation');
        $user = $this->createUser('tenant-isolation');
        Sanctum::actingAs($user, ['users']);

        $bookingId = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'tenant-isolation-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
        ])->assertCreated()->json('data.id');

        [, $otherOwner] = $this->createTheater('phase6-tenant-other');
        Sanctum::actingAs($otherOwner, ['tenant_members']);

        $this->getJson("/api/v1/theater/dashboard/bookings/{$bookingId}")->assertNotFound();
        $this->postJson("/api/v1/theater/dashboard/bookings/{$bookingId}/cancel", ['reason' => 'No.'])->assertNotFound();
    }

    public function test_show_cancellation_cancels_bookings_and_tickets(): void
    {
        [$theater, $owner, $event, $show, , $tier] = $this->createShowWithTier('phase6-show-cancel');
        $user = $this->createUser('show-cancel');
        Sanctum::actingAs($user, ['users']);

        $bookingId = $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'show-cancel-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertCreated()->json('data.id');

        app(\App\Services\Tenancy\TenantContext::class)->set($theater, $owner);
        app(ShowService::class)->cancel($show);

        $this->assertDatabaseHas('bookings', ['id' => $bookingId, 'status' => BookingStatus::CANCELLED->value]);
        $this->assertDatabaseHas('tickets', ['booking_id' => $bookingId, 'status' => TicketStatus::CANCELLED->value]);
    }

    public function test_public_show_tiers_are_not_available_for_a_draft_event(): void
    {
        [, , $event, $show] = $this->createShowWithTier('phase6-draft-public');
        $event->update(['status' => 'draft', 'published_at' => null]);

        $this->getJson("/api/v1/public/shows/{$show->id}/tiers")->assertNotFound();
    }

    public function test_published_event_with_bookings_cannot_be_unpublished(): void
    {
        [, $owner, $event, $show, , $tier] = $this->createShowWithTier('phase6-unpublish');
        $user = $this->createUser('unpublish');
        Sanctum::actingAs($user, ['users']);

        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'unpublish-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
        ])->assertCreated();

        app(\App\Services\Tenancy\TenantContext::class)->set($event->tenant, $owner);

        $this->expectException(ValidationException::class);
        app(\Modules\Theaters\Services\EventService::class)->unpublish($event);
    }

    public function test_user_and_theater_dashboard_counts_include_bookings(): void
    {
        [, $owner, , $show, , $tier] = $this->createShowWithTier('phase6-dashboard');
        $user = $this->createUser('dashboard');
        Sanctum::actingAs($user, ['users']);

        $this->postJson('/api/v1/user/bookings', [
            'show_id' => $show->id,
            'idempotency_key' => 'dashboard-key',
            'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 2]],
        ])->assertCreated();

        $this->getJson('/api/v1/user/dashboard')
            ->assertOk()
            ->assertJsonPath('data.bookings_count', 1);

        Sanctum::actingAs($owner, ['tenant_members']);
        $this->getJson('/api/v1/theater/dashboard')
            ->assertOk()
            ->assertJsonPath('data.counts.bookings_count', 1)
            ->assertJsonPath('data.counts.upcoming_bookings_count', 1);
    }

    /** @return array{0: Tenant, 1: TenantMember, 2: Event, 3: Show, 4: Hall, 5: TicketTier, 6: Event} */
    private function createShowWithTier(string $subdomain, int $total = 10, float $price = 100): array
    {
        [$theater, $owner] = $this->createTheater($subdomain);

        $hall = Hall::query()->create([
            'tenant_id' => $theater->id,
            'name' => 'Main Hall',
            'capacity' => 100,
        ]);

        $event = Event::query()->create([
            'tenant_id' => $theater->id,
            'slug' => $subdomain.'-event',
            'status' => 'published',
            'published_at' => now(),
        ]);

        EventTranslation::query()->create([
            'event_id' => $event->id,
            'lang' => 'en',
            'title' => 'Smoke Event',
            'description' => 'An event for booking tests.',
        ]);

        EventTranslation::query()->create([
            'event_id' => $event->id,
            'lang' => 'ar',
            'title' => 'حدث تجريبي',
        ]);

        $show = Show::query()->create([
            'tenant_id' => $theater->id,
            'event_id' => $event->id,
            'hall_id' => $hall->id,
            'starts_at' => now()->addDays(5)->setTime(18, 0)->utc(),
            'ends_at' => now()->addDays(5)->setTime(20, 0)->utc(),
            'status' => 'scheduled',
        ]);

        $tier = $this->createTier($show, 'General Admission', $total, $price);

        return [$theater, $owner, $event, $show, $hall, $tier, $event];
    }

    private function createTier(Show $show, string $name, int $total, float $price): TicketTier
    {
        $tier = TicketTier::query()->create([
            'tenant_id' => $show->tenant_id,
            'show_id' => $show->id,
            'price' => $price,
            'currency' => 'SAR',
            'total_quantity' => $total,
            'is_active' => true,
        ]);

        TicketTierTranslation::query()->create([
            'ticket_tier_id' => $tier->id,
            'lang' => 'en',
            'name' => $name,
        ]);

        TicketTierTranslation::query()->create([
            'ticket_tier_id' => $tier->id,
            'lang' => 'ar',
            'name' => $name,
        ]);

        return $tier;
    }

    private function createTheater(string $subdomain): array
    {
        $theater = app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => ucfirst($subdomain), 'subdomain' => $subdomain],
            [
                'name' => 'Theater Owner',
                'email' => 'owner@'.$subdomain.'.test',
                'phone' => '+9665000'.random_int(10000, 99999),
                'password' => 'P@ssw0rd123',
            ]
        );

        TheaterProfile::query()->create([
            'tenant_id' => $theater->id,
            'name' => ucfirst($subdomain),
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ]);

        return [$theater, $theater->members()->where('is_owner', true)->firstOrFail()];
    }

    private function createUser(string $suffix): User
    {
        return User::query()->create([
            'name' => 'Audience '.$suffix,
            'email' => 'audience-'.$suffix.'@example.test',
            'phone' => '+966500'.random_int(100000, 999999),
            'password' => 'P@ssw0rd123',
            'is_active' => true,
            'phone_verified_at' => now(),
        ]);
    }
}
