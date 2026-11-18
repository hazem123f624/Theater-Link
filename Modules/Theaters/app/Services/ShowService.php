<?php

namespace Modules\Theaters\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\Show;

class ShowService
{
    public function __construct(private HallSlotService $slots) {}

    public function create(Event $event, array $data): Show
    {
        $hall = Hall::query()->findOrFail($data['hall_id']);
        $startsAt = CarbonImmutable::parse($data['starts_at'])->utc();
        $endsAt = CarbonImmutable::parse($data['ends_at'])->utc();

        if ($event->status === 'cancelled') {
            throw ValidationException::withMessages([
                'event' => [__('theaters::messages.event_cancelled')],
            ]);
        }

        $this->assertWindow($startsAt, $endsAt);
        $this->slots->assertShowAvailable($hall, $startsAt, $endsAt);

        return DB::transaction(function () use ($event, $hall, $startsAt, $endsAt): Show {
            $show = Show::query()->create([
                'tenant_id' => $event->tenant_id,
                'event_id' => $event->id,
                'hall_id' => $hall->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'scheduled',
            ]);

            $this->slots->attachShow($show);

            return $show->fresh(['event.translations', 'hall']);
        });
    }

    public function update(Show $show, array $data): Show
    {
        $hall = isset($data['hall_id'])
            ? Hall::query()->findOrFail($data['hall_id'])
            : $show->hall;
        $startsAt = CarbonImmutable::parse($data['starts_at'] ?? $show->starts_at)->utc();
        $endsAt = CarbonImmutable::parse($data['ends_at'] ?? $show->ends_at)->utc();

        if ($show->status === 'cancelled') {
            throw ValidationException::withMessages([
                'show' => [__('theaters::messages.event_cancelled')],
            ]);
        }

        $this->assertWindow($startsAt, $endsAt);
        $this->slots->assertShowAvailable($hall, $startsAt, $endsAt, $show);

        return DB::transaction(function () use ($show, $hall, $startsAt, $endsAt): Show {
            $this->slots->releaseShow($show);
            $show->update([
                'hall_id' => $hall->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
            $this->slots->attachShow($show);

            return $show->fresh(['event.translations', 'hall', 'tiers.translations']);
        });
    }

    public function cancel(Show $show): Show
    {
        return DB::transaction(function () use ($show): Show {
            $this->slots->releaseShow($show);
            $show->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return $show->fresh(['event.translations', 'hall']);
        });
    }

    public function cancelForEvent(Event $event): void
    {
        $event->shows()->where('status', 'scheduled')->get()->each(function (Show $show): void {
            $this->slots->releaseShow($show);
            $show->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        });
    }

    private function assertWindow(CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        if ($startsAt->greaterThanOrEqualTo($endsAt)) {
            throw ValidationException::withMessages([
                'starts_at' => [__('theaters::messages.invalid_schedule_window')],
            ]);
        }

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages([
                'starts_at' => [__('theaters::messages.invalid_schedule_window')],
            ]);
        }
    }
}
