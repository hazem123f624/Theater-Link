<?php

namespace Modules\Theaters\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\HallSchedule;

class HallScheduleService
{
    public function __construct(private HallSlotService $slots) {}

    public function create(Hall $hall, array $data): HallSchedule
    {
        $this->assertWindow($data);
        $this->assertNoOverlap($hall, $data);

        return DB::transaction(function () use ($hall, $data): HallSchedule {
            $schedule = HallSchedule::query()->create([
                'tenant_id' => $hall->tenant_id,
                'hall_id' => $hall->id,
                'day_of_week' => $data['day_of_week'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'slot_duration_minutes' => $data['slot_duration_minutes'],
                'price' => $data['price'] ?? 0,
                'currency' => strtoupper($data['currency'] ?? 'SAR'),
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->slots->generateForHall($hall->fresh());

            return $schedule->fresh();
        });
    }

    public function update(HallSchedule $schedule, array $data): HallSchedule
    {
        $candidate = array_merge([
            'day_of_week' => $schedule->day_of_week,
            'starts_at' => $schedule->starts_at,
            'ends_at' => $schedule->ends_at,
            'slot_duration_minutes' => $schedule->slot_duration_minutes,
        ], collect($data)->only(['day_of_week', 'starts_at', 'ends_at', 'slot_duration_minutes'])->all());

        $this->assertWindow($candidate);
        $this->assertNoOverlap($schedule->hall, $candidate, $schedule->id);

        return DB::transaction(function () use ($schedule, $data): HallSchedule {
            $schedule->update(collect($data)->only([
                'day_of_week',
                'starts_at',
                'ends_at',
                'slot_duration_minutes',
                'price',
                'currency',
                'is_active',
            ])->all());

            if (isset($data['currency'])) {
                $schedule->currency = strtoupper($data['currency']);
                $schedule->save();
            }

            $this->slots->generateForHall($schedule->hall->fresh());

            return $schedule->fresh();
        });
    }

    public function delete(HallSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule): void {
            $hall = $schedule->hall;
            $schedule->delete();
            $this->slots->generateForHall($hall->fresh());
        });
    }

    private function assertWindow(array $data): void
    {
        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['ends_at']);

        if ($start->greaterThanOrEqualTo($end)) {
            throw ValidationException::withMessages([
                'starts_at' => [__('theaters::messages.invalid_schedule_window')],
            ]);
        }

        $windowMinutes = $start->diffInMinutes($end);
        if ((int) $data['slot_duration_minutes'] <= 0 || (int) $data['slot_duration_minutes'] > $windowMinutes) {
            throw ValidationException::withMessages([
                'slot_duration_minutes' => [__('theaters::messages.invalid_slot_duration')],
            ]);
        }
    }

    private function assertNoOverlap(Hall $hall, array $data, ?int $ignoreId = null): void
    {
        $start = Carbon::parse($data['starts_at'])->format('H:i:s');
        $end = Carbon::parse($data['ends_at'])->format('H:i:s');

        $overlap = HallSchedule::query()
            ->where('hall_id', $hall->id)
            ->where('day_of_week', $data['day_of_week'])
            ->where('is_active', true)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_at' => [__('theaters::messages.schedule_overlap')],
            ]);
        }
    }
}
