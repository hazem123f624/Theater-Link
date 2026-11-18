<?php

namespace Modules\Theaters\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Tenancy\Models\Tenant;
use Modules\Theaters\Models\Hall;

class HallService
{
    public function __construct(private HallSlotService $slots) {}

    public function create(Tenant $tenant, array $data): Hall
    {
        return DB::transaction(function () use ($tenant, $data): Hall {
            $hall = Hall::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'capacity' => $data['capacity'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $hall->fresh();
        });
    }

    public function update(Hall $hall, array $data): Hall
    {
        return DB::transaction(function () use ($hall, $data): Hall {
            $hall->update(collect($data)->only([
                'name',
                'description',
                'capacity',
                'is_active',
            ])->all());

            if (array_key_exists('capacity', $data)) {
                $this->assertCapacityFitsTiers($hall);
            }

            return $hall->fresh();
        });
    }

    public function delete(Hall $hall): void
    {
        if ($hall->shows()->where('status', 'scheduled')->where('starts_at', '>', now())->exists()) {
            throw ValidationException::withMessages([
                'hall' => [__('theaters::messages.hall_has_future_shows')],
            ]);
        }

        DB::transaction(function () use ($hall): void {
            $this->slots->deleteAvailableSlots($hall);
            $hall->delete();
        });
    }

    private function assertCapacityFitsTiers(Hall $hall): void
    {
        $shows = $hall->shows()
            ->with(['tiers' => fn ($query) => $query->where('is_active', true)])
            ->get();

        foreach ($shows as $show) {
            if ($show->tiers->sum('total_quantity') > $hall->capacity) {
                throw ValidationException::withMessages([
                    'capacity' => [__('theaters::messages.tier_capacity_exceeded')],
                ]);
            }
        }
    }
}
