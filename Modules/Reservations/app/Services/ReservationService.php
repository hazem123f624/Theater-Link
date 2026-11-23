<?php

namespace Modules\Reservations\Services;

use App\Services\ActivityLog\ActivityLogService;
use App\Services\Notifications\NotificationService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Reservations\Enums\ReservationStatus;
use Modules\Reservations\Models\Reservation;
use Modules\Reservations\Models\ReservationStatusHistory;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\HallBlock;
use Modules\Theaters\Models\HallTimeSlot;
use Modules\Theaters\Models\Show;

class ReservationService
{
    public function __construct(private NotificationService $notifications) {}

    public function request(Tenant $groupTenant, TenantMember $member, array $data): Reservation
    {
        return DB::transaction(function () use ($groupTenant, $member, $data): Reservation {
            $existing = Reservation::query()
                ->where('group_tenant_id', $groupTenant->id)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing) {
                return $existing->load($this->relations());
            }

            if ($groupTenant->status !== 'active') {
                throw ValidationException::withMessages(['group' => [__('reservations::messages.group_inactive')]]);
            }

            $slot = HallTimeSlot::withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE)
                ->lockForUpdate()
                ->findOrFail($data['hall_time_slot_id']);

            $hall = Hall::withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE)
                ->with('tenant.theaterProfile')
                ->findOrFail($slot->hall_id);

            $theaterTenant = $hall->tenant;

            if ($theaterTenant->status !== 'active') {
                throw ValidationException::withMessages(['theater' => [__('reservations::messages.theater_inactive')]]);
            }

            if (! $hall->is_active) {
                throw ValidationException::withMessages(['slot' => [__('reservations::messages.slot_unavailable')]]);
            }

            if ($slot->status !== 'available' || $slot->starts_at->isPast()) {
                throw ValidationException::withMessages(['slot' => [__('reservations::messages.slot_unavailable')]]);
            }

            if ($this->activeReservationForSlot($slot->id)) {
                throw ValidationException::withMessages(['slot' => [__('reservations::messages.slot_unavailable')]]);
            }

            if ($this->hasShowConflict($hall, $slot->starts_at, $slot->ends_at)) {
                throw ValidationException::withMessages(['slot' => [__('reservations::messages.slot_unavailable')]]);
            }

            $reservation = Reservation::query()->create([
                'reference' => $this->reference(),
                'theater_tenant_id' => $theaterTenant->id,
                'group_tenant_id' => $groupTenant->id,
                'hall_id' => $hall->id,
                'hall_time_slot_id' => $slot->id,
                'requested_by' => $member->id,
                'status' => ReservationStatus::PENDING->value,
                'price' => $slot->price,
                'currency' => $slot->currency,
                'hold_expires_at' => now()->addMinutes((int) config('tenancy.reservation_hold_minutes', 2880)),
                'idempotency_key' => $data['idempotency_key'],
            ]);

            $slot->update(['status' => 'held', 'reservation_id' => $reservation->id]);

            $this->history($reservation, 'requested', null, ReservationStatus::PENDING->value, $member, 'member');
            $this->audit($reservation, 'reservation.requested', $member, 'member');
            $this->notifyTheaterApprovers($reservation, 'requested');

            return $reservation->fresh($this->relations());
        });
    }

    public function approve(Reservation $reservation, TenantMember $member): Reservation
    {
        $expired = false;

        $result = DB::transaction(function () use ($reservation, $member, &$expired): ?Reservation {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($reservation->theater_tenant_id !== $member->tenant_id) {
                abort(404, __('messages.resource_not_found'));
            }

            if ($reservation->status === ReservationStatus::APPROVED->value) {
                return $reservation->load($this->relations());
            }

            if ($reservation->status !== ReservationStatus::PENDING->value) {
                throw ValidationException::withMessages(['reservation' => [__('reservations::messages.reservation_not_pending')]]);
            }

            if ($reservation->hold_expires_at->isPast()) {
                $this->expireInside($reservation, 'expired');
                $expired = true;

                return null;
            }

            $slot = HallTimeSlot::withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE)
                ->lockForUpdate()
                ->findOrFail($reservation->hall_time_slot_id);

            if ($slot->status !== 'held' || $slot->reservation_id !== $reservation->id || $slot->starts_at->isPast()) {
                throw ValidationException::withMessages(['slot' => [__('reservations::messages.slot_unavailable')]]);
            }

            $hall = Hall::withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE)->findOrFail($slot->hall_id);

            if ($this->hasShowConflict($hall, $slot->starts_at, $slot->ends_at)) {
                throw ValidationException::withMessages(['slot' => [__('reservations::messages.slot_unavailable')]]);
            }

            $slot->update(['status' => 'reserved']);
            $reservation->update([
                'status' => ReservationStatus::APPROVED->value,
                'approved_at' => now(),
            ]);

            $this->history($reservation, 'approved', ReservationStatus::PENDING->value, ReservationStatus::APPROVED->value, $member, 'member');
            $this->audit($reservation, 'reservation.approved', $member, 'member');
            $this->notifyGroup($reservation, 'approved');

            return $reservation->fresh($this->relations());
        });

        if ($expired) {
            throw ValidationException::withMessages(['reservation' => [__('reservations::messages.reservation_hold_expired')]]);
        }

        return $result;
    }

    public function reject(Reservation $reservation, TenantMember $member, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservation, $member, $reason): Reservation {
            $reservation = $this->lockForTheater($reservation, $member);

            if ($reservation->status !== ReservationStatus::PENDING->value) {
                throw ValidationException::withMessages(['reservation' => [__('reservations::messages.reservation_not_pending')]]);
            }

            $this->releaseSlot($reservation);
            $reservation->update([
                'status' => ReservationStatus::REJECTED->value,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->history($reservation, 'rejected', ReservationStatus::PENDING->value, ReservationStatus::REJECTED->value, $member, 'member', $reason);
            $this->audit($reservation, 'reservation.rejected', $member, 'member', ['reason' => $reason]);
            $this->notifyGroup($reservation, 'rejected');

            return $reservation->fresh($this->relations());
        });
    }

    public function cancelByGroup(Reservation $reservation, TenantMember $member, ?string $reason = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $member, $reason): Reservation {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($reservation->group_tenant_id !== $member->tenant_id) {
                abort(404, __('messages.resource_not_found'));
            }

            if (! in_array($reservation->status, [ReservationStatus::PENDING->value, ReservationStatus::APPROVED->value], true)) {
                throw ValidationException::withMessages(['reservation' => [__('reservations::messages.cancellation_not_allowed')]]);
            }

            $slot = HallTimeSlot::withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE)->findOrFail($reservation->hall_time_slot_id);

            if ($reservation->status === ReservationStatus::APPROVED->value) {
                $cutoff = (int) config('tenancy.reservation_group_cancellation_cutoff_hours', 24);

                if ($slot->starts_at->lessThanOrEqualTo(now()->addHours($cutoff))) {
                    throw ValidationException::withMessages(['reservation' => [__('reservations::messages.cancellation_cutoff')]]);
                }
            }

            $from = $reservation->status;
            $this->releaseSlot($reservation);
            $reservation->update([
                'status' => ReservationStatus::CANCELLED->value,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->history($reservation, 'cancelled_by_group', $from, ReservationStatus::CANCELLED->value, $member, 'member', $reason);
            $this->audit($reservation, 'reservation.cancelled_by_group', $member, 'member', ['reason' => $reason]);
            $this->notifyTheaterApprovers($reservation, 'cancelled');

            return $reservation->fresh($this->relations());
        });
    }

    public function cancelByTheater(Reservation $reservation, TenantMember $member, ?string $reason = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $member, $reason): Reservation {
            $reservation = $this->lockForTheater($reservation, $member);

            if (! in_array($reservation->status, [ReservationStatus::PENDING->value, ReservationStatus::APPROVED->value], true)) {
                throw ValidationException::withMessages(['reservation' => [__('reservations::messages.cancellation_not_allowed')]]);
            }

            $slot = HallTimeSlot::withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE)->findOrFail($reservation->hall_time_slot_id);

            if ($slot->starts_at->isPast()) {
                throw ValidationException::withMessages(['reservation' => [__('reservations::messages.cancellation_not_allowed')]]);
            }

            $from = $reservation->status;
            $this->releaseSlot($reservation);
            $reservation->update([
                'status' => ReservationStatus::CANCELLED->value,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->history($reservation, 'cancelled_by_theater', $from, ReservationStatus::CANCELLED->value, $member, 'member', $reason);
            $this->audit($reservation, 'reservation.cancelled_by_theater', $member, 'member', ['reason' => $reason]);
            $this->notifyGroup($reservation, 'cancelled');

            return $reservation->fresh($this->relations());
        });
    }

    public function expirePendingHolds(): int
    {
        $count = 0;

        Reservation::query()
            ->where('status', ReservationStatus::PENDING->value)
            ->where('hold_expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use (&$count): void {
                foreach ($reservations as $reservation) {
                    if ($this->expireOne($reservation)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function completeFinishedReservations(): int
    {
        $count = 0;

        Reservation::query()
            ->where('status', ReservationStatus::APPROVED->value)
            ->with(['slot' => fn ($query) => $query->withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE)])
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use (&$count): void {
                foreach ($reservations as $reservation) {
                    if (! $reservation->slot || $reservation->slot->ends_at->isFuture()) {
                        continue;
                    }

                    if ($this->completeOne($reservation)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    public function invalidatePendingHoldsForShow(Hall $hall, CarbonInterface $startsAt, CarbonInterface $endsAt): int
    {
        $endsAt = CarbonImmutable::instance($endsAt);
        $startsAt = CarbonImmutable::instance($startsAt);
        $count = 0;

        $slots = HallTimeSlot::withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE)
            ->where('hall_id', $hall->id)
            ->where('status', 'held')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->get();

        foreach ($slots as $slot) {
            $reservation = Reservation::query()->where('hall_time_slot_id', $slot->id)->where('status', ReservationStatus::PENDING->value)->first();

            if ($reservation && $this->expireOne($reservation, 'show_conflict')) {
                $count++;
            }
        }

        return $count;
    }

    private function expireOne(Reservation $reservation, string $action = 'expired'): bool
    {
        return DB::transaction(function () use ($reservation, $action): bool {
            $reservation = Reservation::query()->lockForUpdate()->find($reservation->id);

            if (! $reservation || $reservation->status !== ReservationStatus::PENDING->value) {
                return false;
            }

            $this->expireInside($reservation, $action);

            return true;
        });
    }

    private function expireInside(Reservation $reservation, string $action): void
    {
        $this->releaseSlot($reservation);
        $reservation->update(['status' => ReservationStatus::EXPIRED->value]);
        $this->history($reservation, $action, ReservationStatus::PENDING->value, ReservationStatus::EXPIRED->value, null, 'system');
        $this->audit($reservation, 'reservation.'.$action, null, 'system');
        $this->notifyGroup($reservation, $action);
    }

    private function completeOne(Reservation $reservation): bool
    {
        return DB::transaction(function () use ($reservation): bool {
            $reservation = Reservation::query()->lockForUpdate()->find($reservation->id);

            if (! $reservation || $reservation->status !== ReservationStatus::APPROVED->value) {
                return false;
            }

            $slot = HallTimeSlot::withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE)->find($reservation->hall_time_slot_id);
            $slot?->update(['status' => 'completed', 'reservation_id' => null]);
            $reservation->update([
                'status' => ReservationStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);

            $this->history($reservation, 'completed', ReservationStatus::APPROVED->value, ReservationStatus::COMPLETED->value, null, 'system');
            $this->audit($reservation, 'reservation.completed', null, 'system');
            $this->notifyGroup($reservation, 'completed');

            return true;
        });
    }

    private function lockForTheater(Reservation $reservation, TenantMember $member): Reservation
    {
        $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

        if ($reservation->theater_tenant_id !== $member->tenant_id) {
            abort(404, __('messages.resource_not_found'));
        }

        return $reservation;
    }

    private function releaseSlot(Reservation $reservation): void
    {
        HallTimeSlot::withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE)
            ->where('id', $reservation->hall_time_slot_id)
            ->where('reservation_id', $reservation->id)
            ->update(['status' => 'available', 'reservation_id' => null]);
    }

    private function activeReservationForSlot(int $slotId): bool
    {
        return Reservation::query()
            ->where('hall_time_slot_id', $slotId)
            ->whereIn('status', [ReservationStatus::PENDING->value, ReservationStatus::APPROVED->value])
            ->exists();
    }

    private function hasShowConflict(Hall $hall, CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        $showConflict = Show::withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
            ->where('hall_id', $hall->id)
            ->where('status', 'scheduled')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        $blockConflict = HallBlock::withoutGlobalScope(HallBlock::CURRENT_TENANT_SCOPE)
            ->where('hall_id', $hall->id)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        return $showConflict || $blockConflict;
    }

    private function history(Reservation $reservation, string $action, ?string $from, string $to, ?TenantMember $member = null, string $actorType = 'system', ?string $note = null): void
    {
        ReservationStatusHistory::create([
            'reservation_id' => $reservation->id,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'actor_type' => $actorType,
            'actor_id' => $member?->id,
            'note' => $note,
        ]);
    }

    private function audit(Reservation $reservation, string $action, ?TenantMember $member, string $actorType, array $properties = []): void
    {
        ActivityLogService::log(
            action: $action,
            description: __('reservations::messages.'.$action),
            subject: $reservation,
            properties: $properties,
            causer: $member,
            type: $actorType,
        );
    }

    private function notifyTheaterApprovers(Reservation $reservation, string $action): void
    {
        $reservation->loadMissing('theaterTenant.members');
        $members = $reservation->theaterTenant->members
            ->where('is_active', true)
            ->filter(fn (TenantMember $member): bool => $member->is_owner || $member->hasPermission('reservations.approve'));

        foreach ($members as $member) {
            $this->notifications->notify(
                $member,
                'reservation_'.$action,
                __('reservations::messages.notification_'.$action.'_title'),
                __('reservations::messages.notification_'.$action.'_body', [
                    'hall' => $reservation->hall ? $reservation->hall->name : 'hall',
                    'slot' => $reservation->slot?->starts_at->toDayDateTimeString() ?? '',
                    'reference' => $reservation->reference,
                ]),
                ['reservation_id' => $reservation->id, 'status' => $reservation->status],
            );
        }
    }

    private function notifyGroup(Reservation $reservation, string $action): void
    {
        $reservation->loadMissing(['groupTenant.members', 'requester']);
        $notifiables = collect([$reservation->requester])
            ->merge($reservation->groupTenant->members->where('is_owner', true))
            ->filter()
            ->unique('id');

        foreach ($notifiables as $member) {
            $this->notifications->notify(
                $member,
                'reservation_'.$action,
                __('reservations::messages.notification_'.$action.'_title'),
                __('reservations::messages.notification_'.$action.'_body', [
                    'reference' => $reservation->reference,
                ]),
                ['reservation_id' => $reservation->id, 'status' => $reservation->status],
            );
        }
    }

    private function reference(): string
    {
        $prefix = 'RSV-'.now()->year.'-';
        $sequence = Reservation::withTrashed()->where('reference', 'like', $prefix.'%')->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Reservation::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    private function relations(): array
    {
        return [
            'theaterTenant.theaterProfile',
            'groupTenant.groupProfile',
            'hall',
            'slot',
            'requester',
            'histories',
        ];
    }
}
