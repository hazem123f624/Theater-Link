<?php

namespace Modules\Theaters\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\HallBlock;
use Modules\Theaters\Models\HallSchedule;
use Modules\Theaters\Models\HallTimeSlot;
use Modules\Theaters\Models\Show;

class HallSlotService
{
    public function generateForHall(Hall $hall): int
    {
        $hall->loadMissing('tenant.theaterProfile');
        $timezone = $hall->tenant->theaterProfile?->timezone ?: config('tenancy.timezone', 'Asia/Riyadh');
        $now = CarbonImmutable::now('UTC');
        $startDate = CarbonImmutable::now($timezone)->startOfDay();
        $horizon = max(1, (int) config('tenancy.slot_horizon_days', 90));
        $schedules = $this->scheduleQuery()
            ->where('hall_id', $hall->id)
            ->where('is_active', true)
            ->get();

        $expected = [];
        $created = 0;

        for ($day = 0; $day <= $horizon; $day++) {
            $date = $startDate->addDays($day);

            foreach ($schedules->where('day_of_week', $date->dayOfWeekIso) as $schedule) {
                $cursor = $date->setTimeFromTimeString($schedule->starts_at);
                $localEnd = $date->setTimeFromTimeString($schedule->ends_at);
                $duration = max(1, (int) $schedule->slot_duration_minutes);

                while ($cursor->addMinutes($duration)->lessThanOrEqualTo($localEnd)) {
                    $slotStart = $cursor->utc();
                    $slotEnd = $cursor->addMinutes($duration)->utc();
                    $cursor = $cursor->addMinutes($duration);

                    if ($slotStart->lessThan($now)) {
                        continue;
                    }

                    $key = $slotStart->format('Y-m-d H:i:s');
                    $expected[$key] = true;

                    if ($this->isBlocked($hall, $slotStart, $slotEnd) || $this->isOccupiedByShow($hall, $slotStart, $slotEnd)) {
                        continue;
                    }

                    $slot = $this->slotQuery()
                        ->where('hall_id', $hall->id)
                        ->where('starts_at', $key)
                        ->first();

                    if ($slot) {
                        if ($slot->status === 'available') {
                            $slot->update([
                                'hall_schedule_id' => $schedule->id,
                                'price' => $schedule->price,
                                'currency' => $schedule->currency,
                            ]);
                        }

                        continue;
                    }

                    $this->slotQuery()->create([
                        'tenant_id' => $hall->tenant_id,
                        'hall_id' => $hall->id,
                        'hall_schedule_id' => $schedule->id,
                        'starts_at' => $slotStart,
                        'ends_at' => $slotEnd,
                        'status' => 'available',
                        'price' => $schedule->price,
                        'currency' => $schedule->currency,
                    ]);
                    $created++;
                }
            }
        }

        $cleanup = $this->slotQuery()
            ->where('hall_id', $hall->id)
            ->where('status', 'available')
            ->where('starts_at', '>=', $now);

        if ($expected === []) {
            $cleanup->delete();
        } else {
            $cleanup->whereNotIn('starts_at', array_keys($expected))->delete();
        }

        return $created;
    }

    public function deleteAvailableSlots(Hall $hall): void
    {
        $this->slotQuery()
            ->where('hall_id', $hall->id)
            ->where('status', 'available')
            ->where('starts_at', '>=', now())
            ->delete();
    }

    public function block(Hall $hall, array $data, ?int $createdBy = null): HallBlock
    {
        $startsAt = CarbonImmutable::parse($data['starts_at'])->utc();
        $endsAt = CarbonImmutable::parse($data['ends_at'])->utc();

        if ($startsAt->greaterThanOrEqualTo($endsAt)) {
            throw ValidationException::withMessages([
                'starts_at' => [__('theaters::messages.invalid_schedule_window')],
            ]);
        }

        $this->assertShowAvailable($hall, $startsAt, $endsAt);

        return DB::transaction(function () use ($hall, $data, $createdBy, $startsAt, $endsAt): HallBlock {
            $block = HallBlock::query()->create([
                'tenant_id' => $hall->tenant_id,
                'hall_id' => $hall->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'reason' => $data['reason'] ?? null,
                'created_by' => $createdBy,
            ]);

            $this->slotQuery()
                ->where('hall_id', $hall->id)
                ->where('status', 'available')
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->update([
                    'status' => 'blocked',
                    'block_id' => $block->id,
                ]);

            return $block->fresh();
        });
    }

    public function deleteBlock(HallBlock $block): void
    {
        DB::transaction(function () use ($block): void {
            $slots = $this->slotQuery()
                ->where('block_id', $block->id)
                ->get();

            foreach ($slots as $slot) {
                $hasOtherBlock = $this->blockQuery()
                    ->where('hall_id', $block->hall_id)
                    ->where('id', '!=', $block->id)
                    ->where('starts_at', '<', $slot->ends_at)
                    ->where('ends_at', '>', $slot->starts_at)
                    ->exists();

                if (! $hasOtherBlock) {
                    $slot->update(['status' => 'available', 'block_id' => null]);
                }
            }

            $block->delete();
        });
    }

    /** @return Collection<int, HallTimeSlot> */
    public function availability(Hall $hall, CarbonInterface|string $from, CarbonInterface|string $to): Collection
    {
        $from = CarbonImmutable::parse($from)->utc();
        $to = CarbonImmutable::parse($to)->utc();

        return $this->slotQuery()
            ->with(['hall' => fn ($query) => $query->withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE)->with('tenant.theaterProfile')])
            ->where('hall_id', $hall->id)
            ->where('status', 'available')
            ->where('starts_at', '>=', $from->greaterThan(CarbonImmutable::now('UTC')) ? $from : CarbonImmutable::now('UTC'))
            ->where('starts_at', '<=', $to)
            ->orderBy('starts_at')
            ->get();
    }

    public function assertShowAvailable(Hall $hall, CarbonInterface $startsAt, CarbonInterface $endsAt, ?Show $ignore = null): void
    {
        $startsAt = CarbonImmutable::instance($startsAt)->utc();
        $endsAt = CarbonImmutable::instance($endsAt)->utc();

        $showOverlap = Show::withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
            ->where('hall_id', $hall->id)
            ->where('status', 'scheduled')
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($showOverlap) {
            throw ValidationException::withMessages([
                'starts_at' => [__('theaters::messages.show_overlap')],
            ]);
        }

        if ($this->blockQuery()->where('hall_id', $hall->id)->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->exists()) {
            throw ValidationException::withMessages([
                'starts_at' => [__('theaters::messages.show_block_conflict')],
            ]);
        }

        $reservedSlot = $this->slotQuery()
            ->where('hall_id', $hall->id)
            ->whereIn('status', ['held', 'reserved'])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($reservedSlot) {
            throw ValidationException::withMessages([
                'starts_at' => [__('theaters::messages.show_overlap')],
            ]);
        }
    }

    public function attachShow(Show $show): void
    {
        $this->slotQuery()
            ->where('hall_id', $show->hall_id)
            ->where('status', 'available')
            ->where('starts_at', '<', $show->ends_at)
            ->where('ends_at', '>', $show->starts_at)
            ->update(['status' => 'blocked', 'show_id' => $show->id]);
    }

    public function releaseShow(Show $show): void
    {
        $this->slotQuery()
            ->where('show_id', $show->id)
            ->update(['status' => 'available', 'show_id' => null]);
    }

    private function isBlocked(Hall $hall, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $this->blockQuery()
            ->where('hall_id', $hall->id)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }

    private function isOccupiedByShow(Hall $hall, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return Show::withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
            ->where('hall_id', $hall->id)
            ->where('status', 'scheduled')
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }

    private function slotQuery()
    {
        return HallTimeSlot::withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE);
    }

    private function blockQuery()
    {
        return HallBlock::withoutGlobalScope(HallBlock::CURRENT_TENANT_SCOPE);
    }

    private function scheduleQuery()
    {
        return HallSchedule::withoutGlobalScope(HallSchedule::CURRENT_TENANT_SCOPE);
    }
}
