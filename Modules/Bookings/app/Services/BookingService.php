<?php

namespace Modules\Bookings\Services;

use App\Services\ActivityLog\ActivityLogService;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Bookings\Enums\BookingStatus;
use Modules\Bookings\Enums\TicketStatus;
use Modules\Bookings\Models\Booking;
use Modules\Bookings\Models\BookingItem;
use Modules\Bookings\Models\BookingStatusHistory;
use Modules\Bookings\Models\Ticket;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\Show;
use Modules\Theaters\Models\TicketTier;
use Modules\Users\Models\User;

class BookingService
{
    public function __construct(private NotificationService $notifications) {}

    public function create(User $user, array $data): Booking
    {
        $items = collect($data['items'])
            ->map(fn (array $item): array => [
                'ticket_tier_id' => (int) $item['ticket_tier_id'],
                'quantity' => (int) $item['quantity'],
            ])
            ->sortBy('ticket_tier_id')
            ->values();

        $requestHash = $this->requestHash((int) $data['show_id'], $items->all());
        $existing = $this->findByIdempotency($user, (string) $data['idempotency_key']);

        if ($existing) {
            $this->assertSameRequest($existing, $requestHash);

            return $existing->load($this->detailRelations());
        }

        $show = $this->bookableShow((int) $data['show_id']);

        try {
            return DB::transaction(function () use ($user, $data, $items, $requestHash, $show): Booking {
                $tiers = TicketTier::withoutGlobalScope(TicketTier::CURRENT_TENANT_SCOPE)
                    ->with('translations')
                    ->whereIn('id', $items->pluck('ticket_tier_id')->all())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $existingAfterLock = $this->findByIdempotency($user, (string) $data['idempotency_key']);

                if ($existingAfterLock) {
                    $this->assertSameRequest($existingAfterLock, $requestHash);

                    return $existingAfterLock->load($this->detailRelations());
                }

                $this->assertTiers($show, $items, $tiers);

                $currency = (string) $tiers->first()->currency;
                $subtotalCents = 0;

                foreach ($items as $item) {
                    $tier = $this->requireTier($tiers, $item['ticket_tier_id']);
                    $subtotalCents += $this->priceCents($tier) * $item['quantity'];
                }

                $booking = Booking::query()->create([
                    'reference' => (string) Str::ulid(),
                    'user_id' => $user->id,
                    'tenant_id' => $show->tenant_id,
                    'event_id' => $show->event_id,
                    'show_id' => $show->id,
                    'hall_id' => $show->hall_id,
                    'status' => BookingStatus::CONFIRMED->value,
                    'subtotal' => $this->money($subtotalCents),
                    'total' => $this->money($subtotalCents),
                    'currency' => $currency,
                    'idempotency_key' => (string) $data['idempotency_key'],
                    'request_hash' => $requestHash,
                    'confirmed_at' => now(),
                ]);

                $booking->update([
                    'reference' => 'BKG-'.now()->year.'-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                ]);

                $ticketSequence = 1;

                foreach ($items as $item) {
                    $tier = $this->requireTier($tiers, $item['ticket_tier_id']);
                    $en = $tier->translations->firstWhere('lang', 'en');
                    $ar = $tier->translations->firstWhere('lang', 'ar');
                    $unitCents = $this->priceCents($tier);
                    $lineCents = $unitCents * $item['quantity'];

                    $bookingItem = BookingItem::query()->create([
                        'booking_id' => $booking->id,
                        'ticket_tier_id' => $tier->id,
                        'tier_name_en' => $en?->name,
                        'tier_name_ar' => $ar?->name,
                        'unit_price' => $this->money($unitCents),
                        'currency' => $currency,
                        'quantity' => $item['quantity'],
                        'line_total' => $this->money($lineCents),
                    ]);

                    $tier->increment('sold_quantity', $item['quantity']);

                    for ($index = 0; $index < $item['quantity']; $index++) {
                        $sequence = $ticketSequence++;

                        Ticket::query()->create([
                            'tenant_id' => $show->tenant_id,
                            'booking_id' => $booking->id,
                            'booking_item_id' => $bookingItem->id,
                            'ticket_tier_id' => $tier->id,
                            'show_id' => $show->id,
                            'sequence' => $sequence,
                            'ticket_number' => 'TKT-'.now()->year.'-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT).'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                            'qr_token' => bin2hex(random_bytes(32)),
                            'status' => TicketStatus::VALID->value,
                        ]);
                    }
                }

                $this->history($booking, 'confirmed', null, BookingStatus::CONFIRMED->value, 'user', $user->id);
                $this->audit($booking, 'booking_confirmed', $user, 'user');
                $this->notifyUser($booking, 'confirmed');
                $this->notifyTheater($booking, 'new');

                return $booking->fresh($this->detailRelations());
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateKey($exception)) {
                $existing = $this->findByIdempotency($user, (string) $data['idempotency_key']);

                if ($existing) {
                    $this->assertSameRequest($existing, $requestHash);

                    return $existing->load($this->detailRelations());
                }
            }

            throw $exception;
        } catch (ValidationException $exception) {
            $existing = $this->findByIdempotency($user, (string) $data['idempotency_key']);

            if ($existing && hash_equals($existing->request_hash, $requestHash)) {
                return $existing->load($this->detailRelations());
            }

            throw $exception;
        }
    }

    public function publicShow(int $showId): Show
    {
        $show = Show::withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
            ->with([
                'event' => fn ($query) => $query
                    ->withoutGlobalScope(Event::CURRENT_TENANT_SCOPE)
                    ->with('translations'),
                'hall' => fn ($query) => $query->withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE),
                'tiers' => fn ($query) => $query
                    ->withoutGlobalScope(TicketTier::CURRENT_TENANT_SCOPE)
                    ->where('is_active', true)
                    ->with('translations'),
            ])
            ->where('status', 'scheduled')
            ->where('starts_at', '>', now())
            ->whereHas('event', fn ($query) => $query
                ->withoutGlobalScope(Event::CURRENT_TENANT_SCOPE)
                ->where('status', 'published')
                ->whereNull('cancelled_at'))
            ->whereHas('tenant', fn ($query) => $query->where('status', 'active'))
            ->findOrFail($showId);

        $show->tiers->each(fn (TicketTier $tier) => $tier->setRelation('show', $show));

        return $show;
    }

    public function forUser(User $user, array $filters): LengthAwarePaginator
    {
        $query = Booking::query()
            ->where('user_id', $user->id)
            ->with($this->listRelations())
            ->latest('id');

        $this->applyFilters($query, $filters, true);

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function forTheater(Tenant $tenant, array $filters): LengthAwarePaginator
    {
        $query = Booking::query()
            ->where('tenant_id', $tenant->id)
            ->with($this->listRelations())
            ->latest('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if (($filters['scope'] ?? null) === 'upcoming') {
            $query->whereHas('show', fn ($show) => $show
                ->withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
                ->where('starts_at', '>', now()));
        } elseif (($filters['scope'] ?? null) === 'past') {
            $query->whereHas('show', fn ($show) => $show
                ->withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
                ->where('starts_at', '<=', now()));
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function upcomingForUser(User $user, int $limit = 5): Collection
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::CONFIRMED->value)
            ->whereHas('show', fn ($show) => $show
                ->withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
                ->where('starts_at', '>', now()))
            ->with($this->listRelations())
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function findForUser(int $bookingId, User $user): Booking
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->with($this->detailRelations())
            ->findOrFail($bookingId);
    }

    public function findForTheater(int $bookingId, Tenant $tenant): Booking
    {
        return Booking::query()
            ->where('tenant_id', $tenant->id)
            ->with($this->detailRelations())
            ->findOrFail($bookingId);
    }

    public function cancelByUser(Booking $booking, User $user, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $user, $reason): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->user_id !== $user->id) {
                abort(404, __('messages.resource_not_found'));
            }

            if ($booking->status === BookingStatus::CANCELLED->value) {
                return $booking->load($this->detailRelations());
            }

            if (! $booking->isConfirmed()) {
                throw ValidationException::withMessages(['booking' => [__('bookings::messages.booking_not_cancellable')]]);
            }

            $show = $this->showForBooking($booking);
            $cutoff = now()->addHours((int) config('tenancy.booking_cancellation_cutoff_hours', 24));

            if ($show->starts_at->lessThanOrEqualTo($cutoff)) {
                throw ValidationException::withMessages(['booking' => [__('bookings::messages.cancellation_cutoff')]]);
            }

            $this->assertNoUsedTickets($booking);

            return $this->cancelLocked($booking, $show, 'user', $user->id, 'cancelled_by_user', $reason, true);
        });
    }

    public function cancelByTheater(Booking $booking, TenantMember $member, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $member, $reason): Booking {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->tenant_id !== $member->tenant_id) {
                abort(404, __('messages.resource_not_found'));
            }

            $show = $this->showForBooking($booking);

            if ($booking->status === BookingStatus::CANCELLED->value) {
                return $booking->load($this->detailRelations());
            }

            if (! $booking->isConfirmed() || $show->starts_at->isPast()) {
                throw ValidationException::withMessages(['booking' => [__('bookings::messages.booking_not_cancellable')]]);
            }

            $this->assertNoUsedTickets($booking);

            return $this->cancelLocked($booking, $show, 'member', $member->id, 'cancelled_by_theater', $reason, true);
        });
    }

    public function cancelForShow(Show $show, string $reason, ?TenantMember $member = null): int
    {
        $bookingIds = Booking::query()
            ->where('show_id', $show->id)
            ->where('status', BookingStatus::CONFIRMED->value)
            ->orderBy('id')
            ->pluck('id');

        $count = 0;

        foreach ($bookingIds as $bookingId) {
            $cancelled = DB::transaction(function () use ($bookingId, $show, $reason, $member): bool {
                $booking = Booking::query()->lockForUpdate()->find($bookingId);

                if (! $booking || ! $booking->isConfirmed()) {
                    return false;
                }

                $this->cancelLocked(
                    $booking,
                    $show,
                    $member ? 'member' : 'system',
                    $member?->id,
                    'cancelled_by_show',
                    $reason,
                    true,
                );

                return true;
            });

            if ($cancelled) {
                $count++;
            }
        }

        return $count;
    }

    public function hasActiveBookingsForEvent(Event $event): bool
    {
        return Booking::query()
            ->where('event_id', $event->id)
            ->where('status', BookingStatus::CONFIRMED->value)
            ->exists();
    }

    public function validateTicket(Tenant $tenant, TenantMember $member, string $token): Ticket
    {
        return DB::transaction(function () use ($tenant, $member, $token): Ticket {
            $ticket = Ticket::query()
                ->where('tenant_id', $tenant->id)
                ->where('qr_token', $token)
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                abort(404, __('bookings::messages.ticket_not_found'));
            }

            if ($ticket->status === TicketStatus::USED->value) {
                throw ValidationException::withMessages(['qr_token' => [__('bookings::messages.ticket_already_used')]]);
            }

            if ($ticket->status === TicketStatus::CANCELLED->value) {
                throw ValidationException::withMessages(['qr_token' => [__('bookings::messages.ticket_cancelled')]]);
            }

            if ($ticket->status === TicketStatus::EXPIRED->value) {
                throw ValidationException::withMessages(['qr_token' => [__('bookings::messages.ticket_expired')]]);
            }

            $show = $ticket->show;

            if ($show->status !== 'scheduled' || $show->ends_at->isPast()) {
                throw ValidationException::withMessages(['qr_token' => [__('bookings::messages.ticket_show_ended')]]);
            }

            $ticket->update(['status' => TicketStatus::USED->value, 'used_at' => now()]);

            $this->audit($ticket->booking, 'ticket_validated', $member, 'member', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ]);

            return $ticket->fresh(['booking', 'bookingItem.tier', 'tier', 'show']);
        });
    }

    public function expireTickets(): int
    {
        $count = 0;

        Ticket::query()
            ->where('status', TicketStatus::VALID->value)
            ->whereHas('show', fn ($query) => $query
                ->withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
                ->where('ends_at', '<=', now()))
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use (&$count): void {
                foreach ($tickets as $ticket) {
                    $ticket->update(['status' => TicketStatus::EXPIRED->value, 'expired_at' => now()]);
                    $count++;
                }
            });

        return $count;
    }

    private function cancelLocked(
        Booking $booking,
        Show $show,
        string $actorType,
        ?int $actorId,
        string $action,
        ?string $reason,
        bool $restoreInventory,
    ): Booking {
        $items = $booking->items()->get();
        $tiers = TicketTier::withoutGlobalScope(TicketTier::CURRENT_TENANT_SCOPE)
            ->whereIn('id', $items->pluck('ticket_tier_id')->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($restoreInventory) {
            foreach ($items as $item) {
                $tier = $tiers->get($item->ticket_tier_id);

                if (! $tier) {
                    continue;
                }

                $tier->update([
                    'sold_quantity' => max(0, $tier->sold_quantity - $item->quantity),
                ]);
            }
        }

        $from = $booking->status;
        $booking->update([
            'status' => BookingStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by_type' => $actorType,
            'cancelled_by_id' => $actorId,
        ]);

        $booking->tickets()->where('status', TicketStatus::VALID->value)->update([
            'status' => TicketStatus::CANCELLED->value,
            'cancelled_at' => now(),
        ]);

        $this->history($booking, $action, $from, BookingStatus::CANCELLED->value, $actorType, $actorId, $reason);

        $this->audit(
            $booking,
            'booking_cancelled',
            $actorType === 'member' ? $booking->theaterTenant->members()->find($actorId) : null,
            $actorType,
            ['reason' => $reason, 'show_id' => $show->id],
        );

        $this->notifyUser($booking, 'cancelled');
        $this->notifyTheater($booking, 'cancelled');

        return $booking->fresh($this->detailRelations());
    }

    private function bookableShow(int $showId): Show
    {
        $show = Show::withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
            ->with([
                'event' => fn ($query) => $query
                    ->withoutGlobalScope(Event::CURRENT_TENANT_SCOPE)
                    ->with('translations'),
                'hall' => fn ($query) => $query->withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE),
                'tenant.theaterProfile',
            ])
            ->findOrFail($showId);

        $leadMinutes = (int) config('tenancy.booking_lead_minutes', 60);

        if ($show->tenant->status !== 'active') {
            throw ValidationException::withMessages(['show_id' => [__('bookings::messages.theater_inactive')]]);
        }

        if ($show->event->status !== 'published' || $show->event->cancelled_at) {
            throw ValidationException::withMessages(['show_id' => [__('bookings::messages.event_unavailable')]]);
        }

        if ($show->status !== 'scheduled' || $show->starts_at->lessThanOrEqualTo(now()->addMinutes($leadMinutes))) {
            throw ValidationException::withMessages(['show_id' => [__('bookings::messages.show_unavailable')]]);
        }

        if (! $show->hall->is_active) {
            throw ValidationException::withMessages(['show_id' => [__('bookings::messages.show_unavailable')]]);
        }

        return $show;
    }

    private function showForBooking(Booking $booking): Show
    {
        return Show::withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)->findOrFail($booking->show_id);
    }

    private function assertTiers(Show $show, SupportCollection $items, Collection $tiers): void
    {
        $currency = null;

        foreach ($items as $item) {
            $tier = $tiers->get($item['ticket_tier_id']);

            if (! $tier instanceof TicketTier || $tier->show_id !== $show->id || $tier->tenant_id !== $show->tenant_id) {
                throw ValidationException::withMessages(['items' => [__('bookings::messages.tier_wrong_show')]]);
            }

            if (! $tier->is_active) {
                throw ValidationException::withMessages(['items' => [__('bookings::messages.tier_inactive')]]);
            }

            if ($tier->sale_starts_at && $tier->sale_starts_at->isFuture()) {
                throw ValidationException::withMessages(['items' => [__('bookings::messages.sale_not_started')]]);
            }

            if ($tier->sale_ends_at && $tier->sale_ends_at->isPast()) {
                throw ValidationException::withMessages(['items' => [__('bookings::messages.sale_ended')]]);
            }

            if ($tier->per_booking_limit && $item['quantity'] > $tier->per_booking_limit) {
                throw ValidationException::withMessages(['items' => [__('bookings::messages.quantity_limit')]]);
            }

            if ($item['quantity'] > $tier->availableQuantity()) {
                throw ValidationException::withMessages(['items' => [__('bookings::messages.insufficient_inventory')]]);
            }

            if ($currency !== null && $tier->currency !== $currency) {
                throw ValidationException::withMessages(['items' => [__('bookings::messages.currency_mismatch')]]);
            }

            $currency = $tier->currency;
        }
    }

    private function requireTier(Collection $tiers, int $tierId): TicketTier
    {
        $tier = $tiers->get($tierId);

        if (! $tier instanceof TicketTier) {
            throw ValidationException::withMessages(['items' => [__('bookings::messages.tier_wrong_show')]]);
        }

        return $tier;
    }

    private function assertNoUsedTickets(Booking $booking): void
    {
        if ($booking->tickets()->where('status', TicketStatus::USED->value)->exists()) {
            throw ValidationException::withMessages(['booking' => [__('bookings::messages.ticket_used_cancellation')]]);
        }
    }

    private function findByIdempotency(User $user, string $key): ?Booking
    {
        return Booking::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $key)
            ->first();
    }

    private function assertSameRequest(Booking $booking, string $requestHash): void
    {
        if (! hash_equals($booking->request_hash, $requestHash)) {
            throw ValidationException::withMessages(['idempotency_key' => [__('bookings::messages.idempotency_conflict')]]);
        }
    }

    private function requestHash(int $showId, array $items): string
    {
        return hash('sha256', json_encode([
            'show_id' => $showId,
            'items' => $items,
        ], JSON_THROW_ON_ERROR));
    }

    private function isDuplicateKey(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1062
            || str_contains(strtolower($exception->getMessage()), 'duplicate');
    }

    private function priceCents(TicketTier $tier): int
    {
        return (int) round((float) $tier->price * 100);
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function applyFilters(Builder $query, array $filters, bool $includeUserSearch = false): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search, $includeUserSearch): void {
                $builder->where('reference', 'like', "%{$search}%");

                if ($includeUserSearch) {
                    $builder->orWhereHas('event', fn ($event) => $event
                        ->withoutGlobalScope(Event::CURRENT_TENANT_SCOPE)
                        ->whereHas('translations', fn ($translation) => $translation->where('title', 'like', "%{$search}%")));
                }
            });
        }

        if (($filters['scope'] ?? null) === 'upcoming') {
            $query->whereHas('show', fn ($show) => $show
                ->withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
                ->where('starts_at', '>', now()));
        } elseif (($filters['scope'] ?? null) === 'past') {
            $query->whereHas('show', fn ($show) => $show
                ->withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
                ->where('starts_at', '<=', now()));
        }
    }

    private function history(
        Booking $booking,
        string $action,
        ?string $from,
        string $to,
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $note = null,
    ): void {
        BookingStatusHistory::query()->create([
            'booking_id' => $booking->id,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'note' => $note,
        ]);
    }

    private function audit(
        Booking $booking,
        string $action,
        ?object $causer,
        string $type,
        array $properties = [],
    ): void {
        ActivityLogService::log(
            action: $action,
            description: __('bookings::messages.activity_'.$action),
            subject: $booking,
            properties: $properties,
            causer: $causer instanceof \Illuminate\Database\Eloquent\Model ? $causer : null,
            type: $type,
        );
    }

    private function notifyUser(Booking $booking, string $action): void
    {
        $booking->loadMissing(['user', 'event.translations']);
        $ticketCount = $booking->tickets()->count();

        $this->notifications->notify(
            $booking->user,
            'booking_'.$action,
            __('bookings::messages.notification_'.$action.'_title'),
            __('bookings::messages.notification_'.$action.'_body', [
                'reference' => $booking->reference,
                'quantity' => $ticketCount,
                'event' => $this->eventTitle($booking),
            ]),
            ['booking_id' => $booking->id, 'status' => $booking->status],
        );
    }

    private function notifyTheater(Booking $booking, string $action): void
    {
        $booking->loadMissing(['theaterTenant.members', 'event.translations']);
        $members = $booking->theaterTenant->members
            ->where('is_active', true)
            ->filter(fn (TenantMember $member): bool => $member->is_owner || $member->hasPermission('bookings.fetch'));

        foreach ($members as $member) {
            $this->notifications->notify(
                $member,
                'booking_'.$action,
                __('bookings::messages.notification_'.$action.'_title'),
                __('bookings::messages.notification_'.$action.'_body', [
                    'reference' => $booking->reference,
                    'quantity' => $booking->tickets()->count(),
                    'event' => $this->eventTitle($booking),
                ]),
                ['booking_id' => $booking->id, 'status' => $booking->status],
            );
        }
    }

    private function eventTitle(Booking $booking): string
    {
        $booking->loadMissing('event.translations');

        return $booking->event->translations->firstWhere('lang', app()->getLocale())->title
            ?? $booking->event->translations->firstWhere('lang', 'en')->title
            ?? 'event';
    }

    private function listRelations(): array
    {
        return [
            'event.translations',
            'show.hall',
            'hall',
            'user',
            'theaterTenant.theaterProfile.logo',
            'items.tier.translations',
        ];
    }

    private function detailRelations(): array
    {
        return [
            'event.translations',
            'show.hall',
            'hall',
            'user',
            'theaterTenant.theaterProfile.logo',
            'items.tier.translations',
            'tickets.bookingItem',
            'histories',
        ];
    }
}
