<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder;
use Modules\Tenancy\Models\TheaterProfile;
use Modules\Tenancy\Services\TenantProvisioningService;
use Modules\Theaters\Database\Seeders\EventCategorySeeder;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\HallTimeSlot;
use Tests\TestCase;

class Phase4TheaterOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        $this->seed(TenancyDatabaseSeeder::class);
        $this->seed(EventCategorySeeder::class);
    }

    public function test_theater_can_view_and_update_its_profile(): void
    {
        [$tenant, $token] = $this->theater('phase4-profile');

        $this->withToken($token)->getJson('/api/v1/theater/dashboard/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Phase4 Profile')
            ->assertJsonPath('data.timezone', 'Asia/Riyadh');

        $this->withToken($token)->putJson('/api/v1/theater/dashboard/profile', [
            'name' => 'Updated Theater',
            'description' => 'A newly updated theater.',
            'email' => 'updated@phase4-profile.test',
            'timezone' => 'Asia/Riyadh',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Theater');

        $this->assertDatabaseHas('theater_profiles', [
            'tenant_id' => $tenant->id,
            'name' => 'Updated Theater',
        ]);
    }

    public function test_theater_can_manage_halls_and_generate_weekly_slots(): void
    {
        [, $token] = $this->theater('phase4-halls');
        $day = CarbonImmutable::now('Asia/Riyadh')->addDay();

        $hallId = $this->withToken($token)->postJson('/api/v1/theater/dashboard/halls', [
            'name' => 'Main Hall',
            'description' => 'The main performance space.',
            'capacity' => 120,
        ])->assertCreated()
            ->json('data.id');

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/halls/{$hallId}/schedules", [
            'day_of_week' => $day->dayOfWeekIso,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'slot_duration_minutes' => 60,
            'price' => 500,
            'currency' => 'SAR',
        ])->assertCreated();

        $slots = $this->withToken($token)->getJson("/api/v1/theater/dashboard/halls/{$hallId}/slots")
            ->assertOk();
        $this->assertGreaterThanOrEqual(2, count($slots->json('data')));

        $this->assertDatabaseHas('hall_time_slots', [
            'hall_id' => $hallId,
            'status' => 'available',
        ]);
    }

    public function test_overlapping_schedules_are_rejected(): void
    {
        [, $token] = $this->theater('phase4-schedule-overlap');
        $day = CarbonImmutable::now('Asia/Riyadh')->addDay();
        $hallId = $this->createHall($token);

        $payload = [
            'day_of_week' => $day->dayOfWeekIso,
            'starts_at' => '10:00',
            'ends_at' => '13:00',
            'slot_duration_minutes' => 60,
            'price' => 300,
        ];

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/halls/{$hallId}/schedules", $payload)
            ->assertCreated();

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/halls/{$hallId}/schedules", [
            ...$payload,
            'starts_at' => '12:00',
            'ends_at' => '14:00',
        ])->assertStatus(422);
    }

    public function test_theater_can_block_slots_and_public_availability_excludes_them(): void
    {
        [, $token] = $this->theater('phase4-blocks');
        $day = CarbonImmutable::now('Asia/Riyadh')->addDay();
        $hallId = $this->createHall($token);

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/halls/{$hallId}/schedules", [
            'day_of_week' => $day->dayOfWeekIso,
            'starts_at' => '10:00',
            'ends_at' => '12:00',
            'slot_duration_minutes' => 60,
            'price' => 300,
        ])->assertCreated();

        $blockStart = $day->setTime(10, 0)->toIso8601String();
        $blockEnd = $day->setTime(10, 30)->toIso8601String();

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/halls/{$hallId}/blocks", [
            'starts_at' => $blockStart,
            'ends_at' => $blockEnd,
            'reason' => 'Maintenance',
        ])->assertCreated();

        $this->assertDatabaseHas('hall_time_slots', [
            'hall_id' => $hallId,
            'status' => 'blocked',
        ]);

        $query = http_build_query(['from' => $blockStart, 'to' => $blockEnd]);
        $this->getJson("/api/v1/public/halls/{$hallId}/availability?{$query}")
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_event_show_tier_and_public_catalog_flow(): void
    {
        [, $token] = $this->theater('phase4-events');
        $hallId = $this->createHall($token, 100);
        $day = CarbonImmutable::now('Asia/Riyadh')->addDays(2);

        $eventId = $this->withToken($token)->postJson('/api/v1/theater/dashboard/events', [
            'title_en' => 'The Night Show',
            'title_ar' => 'عرض الليل',
            'description_en' => 'An evening performance.',
            'description_ar' => 'عرض مسائي.',
            'category_id' => \Modules\Theaters\Models\EventCategory::query()->first()->id,
        ])->assertCreated()
            ->json('data.id');

        $showId = $this->withToken($token)->postJson("/api/v1/theater/dashboard/events/{$eventId}/shows", [
            'hall_id' => $hallId,
            'starts_at' => $day->setTime(18, 0)->toIso8601String(),
            'ends_at' => $day->setTime(20, 0)->toIso8601String(),
        ])->assertCreated()
            ->json('data.id');

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/shows/{$showId}/tiers", [
            'name_en' => 'General Admission',
            'name_ar' => 'دخول عام',
            'price' => 100,
            'currency' => 'SAR',
            'total_quantity' => 50,
            'per_booking_limit' => 4,
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/events/{$eventId}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $slug = Event::query()->findOrFail($eventId)->slug;

        $this->getJson('/api/v1/public/events')
            ->assertOk()
            ->assertJsonPath('data.0.slug', $slug);

        $this->getJson("/api/v1/public/events/{$slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $slug)
            ->assertJsonPath('data.shows.0.id', $showId)
            ->assertJsonPath('data.shows.0.tiers.0.name', 'General Admission');
    }

    public function test_overlapping_shows_are_rejected_and_cancelled_event_is_not_public(): void
    {
        [, $token] = $this->theater('phase4-show-overlap');
        $hallId = $this->createHall($token);
        $day = CarbonImmutable::now('Asia/Riyadh')->addDays(3);
        $eventId = $this->createEvent($token, 'Overlap Event');

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/events/{$eventId}/shows", [
            'hall_id' => $hallId,
            'starts_at' => $day->setTime(18, 0)->toIso8601String(),
            'ends_at' => $day->setTime(20, 0)->toIso8601String(),
        ])->assertCreated();

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/events/{$eventId}/shows", [
            'hall_id' => $hallId,
            'starts_at' => $day->setTime(19, 0)->toIso8601String(),
            'ends_at' => $day->setTime(21, 0)->toIso8601String(),
        ])->assertStatus(422);

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/events/{$eventId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->getJson('/api/v1/public/events')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_ticket_tier_capacity_cannot_exceed_hall_capacity(): void
    {
        [, $token] = $this->theater('phase4-capacity');
        $hallId = $this->createHall($token, 10);
        $day = CarbonImmutable::now('Asia/Riyadh')->addDays(4);
        $eventId = $this->createEvent($token, 'Capacity Event');
        $showId = $this->withToken($token)->postJson("/api/v1/theater/dashboard/events/{$eventId}/shows", [
            'hall_id' => $hallId,
            'starts_at' => $day->setTime(18, 0)->toIso8601String(),
            'ends_at' => $day->setTime(20, 0)->toIso8601String(),
        ])->assertCreated()->json('data.id');

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/shows/{$showId}/tiers", [
            'name_en' => 'Too Many',
            'price' => 100,
            'total_quantity' => 11,
        ])->assertStatus(422);

        $this->withToken($token)->postJson("/api/v1/theater/dashboard/shows/{$showId}/tiers", [
            'name_en' => 'Correct Capacity',
            'price' => 100,
            'total_quantity' => 10,
        ])->assertCreated();
    }

    public function test_theater_data_is_isolated_from_another_theater(): void
    {
        [$firstTenant] = $this->theater('phase4-isolation-a');
        $hallId = Hall::withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE)->create([
            'tenant_id' => $firstTenant->id,
            'name' => 'Private Hall',
            'capacity' => 50,
        ])->id;

        [, $secondToken] = $this->theater('phase4-isolation-b');

        $this->withToken($secondToken)->getJson("/api/v1/theater/dashboard/halls/{$hallId}")
            ->assertNotFound();
    }

    public function test_draft_events_and_inactive_halls_are_not_public(): void
    {
        [, $token] = $this->theater('phase4-public-visibility');
        $hallId = $this->createHall($token);
        $eventId = $this->createEvent($token, 'Draft Event');

        $this->getJson('/api/v1/public/events')->assertOk()->assertJsonPath('data', []);

        $this->withToken($token)->putJson("/api/v1/theater/dashboard/halls/{$hallId}", ['is_active' => false])->assertOk();

        $this->getJson('/api/v1/public/theaters/'.Hall::withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE)->findOrFail($hallId)->tenant_id)
            ->assertOk()
            ->assertJsonPath('data.halls', []);
    }

    /**
     * @return array{0: \Modules\Tenancy\Models\Tenant, 1: string}
     */
    private function theater(string $subdomain): array
    {
        $tenant = app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => \Illuminate\Support\Str::headline($subdomain), 'subdomain' => $subdomain],
            [
                'name' => 'Theater Owner',
                'email' => 'owner@'.$subdomain.'.test',
                'phone' => '+9665000'.random_int(10000, 99999),
                'password' => 'P@ssw0rd123',
            ]
        );

        TheaterProfile::query()->create([
            'tenant_id' => $tenant->id,
            'name' => \Illuminate\Support\Str::headline($subdomain),
            'email' => 'owner@'.$subdomain.'.test',
            'phone' => '+966500000000',
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ]);

        $token = $this->postJson('/api/v1/theater/login', [
            'subdomain' => $subdomain,
            'email' => 'owner@'.$subdomain.'.test',
            'password' => 'P@ssw0rd123',
        ])->assertOk()->json('data.token');

        return [$tenant, $token];
    }

    private function createHall(string $token, int $capacity = 100): int
    {
        return $this->withToken($token)->postJson('/api/v1/theater/dashboard/halls', [
            'name' => 'Main Hall',
            'capacity' => $capacity,
        ])->assertCreated()->json('data.id');
    }

    private function createEvent(string $token, string $title): int
    {
        return $this->withToken($token)->postJson('/api/v1/theater/dashboard/events', [
            'title_en' => $title,
            'description_en' => 'A test event.',
        ])->assertCreated()->json('data.id');
    }
}
