<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Services\Tenancy\TenantContext;
use Laravel\Sanctum\Sanctum;
use Modules\Reservations\Enums\ReservationStatus;
use Modules\Reservations\Models\Reservation;
use Modules\Reservations\Services\ReservationService;
use Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder;
use Modules\Tenancy\Models\GroupProfile;
use Modules\Tenancy\Models\TheaterProfile;
use Modules\Tenancy\Services\TenantProvisioningService;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\HallBlock;
use Modules\Theaters\Models\HallTimeSlot;
use Modules\Theaters\Models\Show;
use Modules\Theaters\Services\ShowService;
use Tests\TestCase;

class Phase5ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        Queue::fake();
        $this->seed(TenancyDatabaseSeeder::class);
    }

    public function test_group_can_update_its_public_profile(): void
    {
        [$group, $groupOwner] = $this->createGroup('phase5-profile');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $this->getJson('/api/v1/group/dashboard/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Phase5-profile');

        $this->putJson('/api/v1/group/dashboard/profile', [
            'name' => 'Updated Group',
            'description' => 'A group profile.',
            'contact_person_name' => 'Group Contact',
            'email' => 'contact@phase5-profile.test',
            'phone' => '+966500000111',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Group')
            ->assertJsonPath('data.contact_person_name', 'Group Contact');
    }

    public function test_group_can_discover_theaters_and_request_an_available_slot(): void
    {
        [$theater, $theaterOwner, $hall, $slot] = $this->createTheaterWithSlot('phase5-discovery');
        [$group, $groupOwner] = $this->createGroup('phase5-discovery-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $this->getJson('/api/v1/group/dashboard/theaters')
            ->assertOk()
            ->assertJsonPath('data.0.id', $theater->id);

        $this->getJson("/api/v1/group/dashboard/theaters/{$theater->id}/halls")
            ->assertOk()
            ->assertJsonPath('data.0.id', $hall->id);

        $this->getJson("/api/v1/group/dashboard/halls/{$hall->id}/availability?from=".urlencode($slot->starts_at->toIso8601String()).'&to='.urlencode($slot->ends_at->toIso8601String()))
            ->assertOk()
            ->assertJsonPath('data.0.id', $slot->id);

        $response = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'phase5-request-1',
        ])->assertCreated()
            ->assertJsonPath('data.status', ReservationStatus::PENDING->value)
            ->assertJsonPath('data.price', '500.00');

        $reservationId = $response->json('data.id');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservationId,
            'theater_tenant_id' => $theater->id,
            'group_tenant_id' => $group->id,
            'hall_time_slot_id' => $slot->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('hall_time_slots', [
            'id' => $slot->id,
            'status' => 'held',
            'reservation_id' => $reservationId,
        ]);
    }

    public function test_duplicate_request_with_the_same_idempotency_key_is_idempotent(): void
    {
        [, , , $slot] = $this->createTheaterWithSlot('phase5-idempotent');
        [, $groupOwner] = $this->createGroup('phase5-idempotent-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $first = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'same-key',
        ])->assertCreated();

        $second = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'same-key',
        ])->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, Reservation::query()->count());
    }

    public function test_blocked_slot_cannot_be_requested(): void
    {
        [, , , $slot] = $this->createTheaterWithSlot('phase5-blocked');
        [, $groupOwner] = $this->createGroup('phase5-blocked-group');
        $slot->update(['status' => 'blocked']);
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'blocked-key',
        ])->assertStatus(422);
    }

    public function test_a_slot_in_an_inactive_hall_cannot_be_requested(): void
    {
        [, , $hall, $slot] = $this->createTheaterWithSlot('phase5-inactive-hall');
        [, $groupOwner] = $this->createGroup('phase5-inactive-hall-group');
        $hall->update(['is_active' => false]);
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'inactive-hall-key',
        ])->assertStatus(422);

        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'available']);
    }

    public function test_theater_can_approve_and_group_can_see_the_approved_reservation(): void
    {
        [, $theaterOwner, , $slot] = $this->createTheaterWithSlot('phase5-approve');
        [, $groupOwner] = $this->createGroup('phase5-approve-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'approve-key',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($theaterOwner, ['tenant_members']);

        $this->getJson('/api/v1/theater/dashboard/reservations')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationStatus::APPROVED->value);

        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'reserved']);

        Sanctum::actingAs($groupOwner, ['tenant_members']);
        $this->getJson("/api/v1/group/dashboard/reservations/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status', ReservationStatus::APPROVED->value);
    }

    public function test_another_theater_cannot_approve_the_reservation(): void
    {
        [, , , $slot] = $this->createTheaterWithSlot('phase5-owner');
        [, $groupOwner] = $this->createGroup('phase5-owner-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'foreign-theater-key',
        ])->assertCreated()->json('data.id');

        [$otherTheater, $otherOwner] = $this->createTheater('phase5-other');
        Sanctum::actingAs($otherOwner, ['tenant_members']);

        $this->getJson("/api/v1/theater/dashboard/reservations/{$id}")->assertNotFound();
        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/approve")->assertNotFound();
    }

    public function test_reject_requires_a_reason_and_releases_the_slot(): void
    {
        [, $theaterOwner, , $slot] = $this->createTheaterWithSlot('phase5-reject');
        [, $groupOwner] = $this->createGroup('phase5-reject-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);
        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'reject-key',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($theaterOwner, ['tenant_members']);

        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/reject", [])->assertStatus(422);
        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/reject", [
            'reason' => 'The hall is unavailable.',
        ])->assertOk()->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('hall_time_slots', [
            'id' => $slot->id,
            'status' => 'available',
            'reservation_id' => null,
        ]);
    }

    public function test_group_can_cancel_a_pending_reservation_and_cannot_cancel_inside_the_approved_cutoff(): void
    {
        [, , $hall, $slot] = $this->createTheaterWithSlot('phase5-cancel');
        [, $groupOwner] = $this->createGroup('phase5-cancel-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $pendingId = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'cancel-pending-key',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/group/dashboard/reservations/{$pendingId}/cancel", [
            'reason' => 'Plans changed.',
        ])->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'available']);

        $secondSlot = $this->createSlot($hall, $slot->starts_at->addDay(), $slot->ends_at->addDay());
        $approvedId = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $secondSlot->id,
            'idempotency_key' => 'cancel-approved-key',
        ])->assertCreated()->json('data.id');

        Reservation::query()->findOrFail($approvedId)->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $secondSlot->update(['status' => 'reserved', 'reservation_id' => $approvedId]);
        $secondSlot->update(['starts_at' => now()->addHour()]);

        $this->postJson("/api/v1/group/dashboard/reservations/{$approvedId}/cancel", [
            'reason' => 'Too late.',
        ])->assertStatus(422);
    }

    public function test_hold_expiry_and_reservation_completion_are_idempotent(): void
    {
        [, , $hall, $slot] = $this->createTheaterWithSlot('phase5-expiry');
        [, $groupOwner] = $this->createGroup('phase5-expiry-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'expiry-key',
        ])->assertCreated()->json('data.id');

        Reservation::query()->findOrFail($id)->update(['hold_expires_at' => now()->subMinute()]);

        $service = app(ReservationService::class);
        $this->assertSame(1, $service->expirePendingHolds());
        $this->assertSame(0, $service->expirePendingHolds());

        $this->assertDatabaseHas('reservations', ['id' => $id, 'status' => 'expired']);
        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'available']);

        $secondSlot = $this->createSlot($hall, now()->subHours(2), now()->subHour());
        $second = Reservation::query()->create([
            'reference' => 'RSV-COMPLETE-1',
            'theater_tenant_id' => $slot->tenant_id,
            'group_tenant_id' => $groupOwner->tenant_id,
            'hall_id' => $slot->hall_id,
            'hall_time_slot_id' => $secondSlot->id,
            'requested_by' => $groupOwner->id,
            'status' => 'approved',
            'price' => $secondSlot->price,
            'currency' => $secondSlot->currency,
            'hold_expires_at' => now()->subDay(),
            'approved_at' => now()->subDay(),
            'idempotency_key' => 'complete-key',
        ]);
        $secondSlot->update(['status' => 'reserved', 'reservation_id' => $second->id]);

        $this->assertSame(1, $service->completeFinishedReservations());

        $this->assertDatabaseHas('reservations', ['id' => $second->id, 'status' => 'completed']);
        $this->assertDatabaseHas('hall_time_slots', ['id' => $secondSlot->id, 'status' => 'completed']);
    }

    public function test_another_group_cannot_view_or_cancel_the_reservation_and_cannot_hold_the_same_slot(): void
    {
        [, , , $slot] = $this->createTheaterWithSlot('phase5-cross-group');
        [, $firstOwner] = $this->createGroup('phase5-cross-group-one');
        [, $secondOwner] = $this->createGroup('phase5-cross-group-two');

        Sanctum::actingAs($firstOwner, ['tenant_members']);
        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'cross-group-key',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($secondOwner, ['tenant_members']);
        $this->getJson("/api/v1/group/dashboard/reservations/{$id}")->assertNotFound();
        $this->postJson("/api/v1/group/dashboard/reservations/{$id}/cancel")->assertNotFound();

        $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'second-group-key',
        ])->assertStatus(422);
    }

    public function test_reservation_price_is_snapshotted_at_request_time(): void
    {
        [, , , $slot] = $this->createTheaterWithSlot('phase5-price');
        [, $groupOwner] = $this->createGroup('phase5-price-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'price-key',
        ])->assertCreated()->assertJsonPath('data.price', '500.00')->json('data.id');

        $slot->update(['price' => 900]);

        $this->getJson("/api/v1/group/dashboard/reservations/{$id}")
            ->assertOk()
            ->assertJsonPath('data.price', '500.00');
    }

    public function test_theater_cannot_approve_an_expired_hold(): void
    {
        [, $theaterOwner, , $slot] = $this->createTheaterWithSlot('phase5-expired-approval');
        [, $groupOwner] = $this->createGroup('phase5-expired-approval-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'expired-approval-key',
        ])->assertCreated()->json('data.id');

        Reservation::query()->findOrFail($id)->update(['hold_expires_at' => now()->subMinute()]);

        Sanctum::actingAs($theaterOwner, ['tenant_members']);
        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/approve")->assertStatus(422);

        $this->assertDatabaseHas('reservations', ['id' => $id, 'status' => 'expired']);
        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'available']);
    }

    public function test_theater_cannot_approve_after_the_slot_has_started(): void
    {
        [, $theaterOwner, , $slot] = $this->createTheaterWithSlot('phase5-started-approval');
        [, $groupOwner] = $this->createGroup('phase5-started-approval-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'started-approval-key',
        ])->assertCreated()->json('data.id');

        $slot->update(['starts_at' => now()->subMinutes(30), 'ends_at' => now()->addMinutes(30)]);

        Sanctum::actingAs($theaterOwner, ['tenant_members']);
        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/approve")->assertStatus(422);

        $this->assertDatabaseHas('reservations', ['id' => $id, 'status' => 'pending']);
        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'held']);
    }

    public function test_theater_can_cancel_an_approved_reservation_and_release_the_slot(): void
    {
        [, $theaterOwner, , $slot] = $this->createTheaterWithSlot('phase5-theater-cancel');
        [, $groupOwner] = $this->createGroup('phase5-theater-cancel-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'theater-cancel-key',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($theaterOwner, ['tenant_members']);
        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/approve")->assertOk();

        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/cancel", [
            'reason' => 'Emergency maintenance.',
        ])->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'available']);
        $this->assertDatabaseHas('reservations', ['id' => $id, 'cancellation_reason' => 'Emergency maintenance.']);
    }

    public function test_theater_cannot_cancel_after_the_reservation_has_started(): void
    {
        [, $theaterOwner, , $slot] = $this->createTheaterWithSlot('phase5-started-cancel');
        [, $groupOwner] = $this->createGroup('phase5-started-cancel-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'started-cancel-key',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($theaterOwner, ['tenant_members']);
        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/approve")->assertOk();

        $slot->update(['starts_at' => now()->subMinutes(30), 'ends_at' => now()->addMinutes(30)]);

        $this->postJson("/api/v1/theater/dashboard/reservations/{$id}/cancel", [
            'reason' => 'Too late.',
        ])->assertStatus(422);

        $this->assertDatabaseHas('reservations', ['id' => $id, 'status' => 'approved']);
        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'reserved']);
    }

    public function test_a_suspended_theater_cannot_receive_new_reservation_requests(): void
    {
        [$theater, , $hall, $slot] = $this->createTheaterWithSlot('phase5-suspended-theater');
        [, $groupOwner] = $this->createGroup('phase5-suspended-theater-group');
        $theater->update(['status' => 'suspended']);
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'suspended-theater-key',
        ])->assertStatus(422);

        $this->assertDatabaseCount('reservations', 0);
        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'available']);
    }

    public function test_a_show_over_a_held_slot_invalidates_the_hold(): void
    {
        [$theater, $theaterOwner, $hall, $slot] = $this->createTheaterWithSlot('phase5-show-conflict');
        [, $groupOwner] = $this->createGroup('phase5-show-conflict-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'show-conflict-key',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($theaterOwner, ['tenant_members']);
        app(TenantContext::class)->set($theater, $theaterOwner);

        $event = Event::query()->create([
            'tenant_id' => $theater->id,
            'slug' => 'show-conflict-event',
            'status' => 'draft',
        ]);
        app(ShowService::class)->create($event, [
            'hall_id' => $hall->id,
            'starts_at' => $slot->starts_at->toIso8601String(),
            'ends_at' => $slot->ends_at->toIso8601String(),
        ]);

        $this->assertDatabaseHas('reservations', ['id' => $id, 'status' => 'expired']);
        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'blocked']);
    }

    public function test_a_failed_show_scheduling_attempt_does_not_expire_the_hold(): void
    {
        [$theater, $theaterOwner, $hall, $slot] = $this->createTheaterWithSlot('phase5-failed-show');
        [, $groupOwner] = $this->createGroup('phase5-failed-show-group');
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $id = $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'failed-show-key',
        ])->assertCreated()->json('data.id');

        HallBlock::query()->create([
            'tenant_id' => $theater->id,
            'hall_id' => $hall->id,
            'starts_at' => $slot->starts_at,
            'ends_at' => $slot->ends_at,
            'reason' => 'Maintenance',
        ]);

        Sanctum::actingAs($theaterOwner, ['tenant_members']);
        app(TenantContext::class)->set($theater, $theaterOwner);
        $event = Event::query()->create([
            'tenant_id' => $theater->id,
            'slug' => 'failed-show-event',
            'status' => 'draft',
        ]);

        try {
            app(ShowService::class)->create($event, [
                'hall_id' => $hall->id,
                'starts_at' => $slot->starts_at->toIso8601String(),
                'ends_at' => $slot->ends_at->toIso8601String(),
            ]);
            $this->fail('Show creation should have failed because of the hall block.');
        } catch (ValidationException) {
            // Expected: the transactional hold invalidation must roll back.
        }

        $this->assertDatabaseHas('reservations', ['id' => $id, 'status' => 'pending']);
        $this->assertDatabaseHas('hall_time_slots', ['id' => $slot->id, 'status' => 'held']);
    }

    public function test_pending_group_cannot_request_a_reservation(): void
    {
        [, , , $slot] = $this->createTheaterWithSlot('phase5-pending-group');
        [$group, $groupOwner] = $this->createGroup('phase5-pending-group-owner', false);
        Sanctum::actingAs($groupOwner, ['tenant_members']);

        $this->postJson('/api/v1/group/dashboard/reservations', [
            'hall_time_slot_id' => $slot->id,
            'idempotency_key' => 'pending-group-key',
        ])->assertForbidden();
    }

    /**
     * @return array{0: \Modules\Tenancy\Models\Tenant, 1: \Modules\Tenancy\Models\TenantMember, 2: Hall, 3: HallTimeSlot}
     */
    private function createTheaterWithSlot(string $subdomain): array
    {
        [$theater, $owner] = $this->createTheater($subdomain);
        $hall = Hall::query()->create([
            'tenant_id' => $theater->id,
            'name' => 'Main Hall',
            'capacity' => 100,
        ]);

        $slot = $this->createSlot($hall, now()->addDays(5)->setTime(10, 0), now()->addDays(5)->setTime(11, 0));

        return [$theater, $owner, $hall, $slot];
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

    private function createGroup(string $subdomain, bool $approve = true): array
    {
        $group = app(TenantProvisioningService::class)->provision(
            ['type' => 'group', 'name' => ucfirst($subdomain), 'subdomain' => $subdomain],
            [
                'name' => 'Group Owner',
                'email' => 'owner@'.$subdomain.'.test',
                'phone' => '+9665000'.random_int(10000, 99999),
                'password' => 'P@ssw0rd123',
            ]
        );

        if ($approve) {
            app(TenantProvisioningService::class)->approve($group);
        }

        GroupProfile::query()->create([
            'tenant_id' => $group->id,
            'name' => ucfirst($subdomain),
            'contact_person_name' => 'Group Owner',
            'email' => 'owner@'.$subdomain.'.test',
            'phone' => '+966500000000',
            'is_active' => true,
        ]);

        return [$group->fresh(), $group->members()->where('is_owner', true)->firstOrFail()];
    }

    private function createSlot(Hall $hall, mixed $startsAt, mixed $endsAt): HallTimeSlot
    {
        return HallTimeSlot::query()->create([
            'tenant_id' => $hall->tenant_id,
            'hall_id' => $hall->id,
            'starts_at' => CarbonImmutable::parse($startsAt)->utc(),
            'ends_at' => CarbonImmutable::parse($endsAt)->utc(),
            'status' => 'available',
            'price' => 500,
            'currency' => 'SAR',
        ]);
    }
}
