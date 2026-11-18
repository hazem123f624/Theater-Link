<?php

namespace Modules\Theaters\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Tenancy\Models\Tenant;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\HallTimeSlot;
use Modules\Theaters\Models\Show;

class PublicCatalogService
{
    public function __construct(private HallSlotService $slots) {}

    public function theaters(int $perPage = 15): LengthAwarePaginator
    {
        return Tenant::query()
            ->where('type', 'theater')
            ->where('status', 'active')
            ->with(['theaterProfile.logo', 'theaterProfile.cover', 'theaterProfile.country', 'theaterProfile.city'])
            ->withCount(['halls' => fn ($query) => $query->withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE)->where('is_active', true)])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function theater(int $id): Tenant
    {
        return Tenant::query()
            ->where('type', 'theater')
            ->where('status', 'active')
            ->with([
                'theaterProfile.logo',
                'theaterProfile.cover',
                'theaterProfile.country',
                'theaterProfile.city',
                'halls' => fn ($query) => $query->withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE)->where('is_active', true)->orderBy('name'),
            ])
            ->findOrFail($id);
    }

    public function events(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->eventQuery();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->whereHas('translations', fn ($translation) => $translation->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('tenant', fn ($tenant) => $tenant->where('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['theater_id'])) {
            $query->where('tenant_id', $filters['theater_id']);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($category) => $category->where('slug', $filters['category']));
        }

        if (! empty($filters['date'])) {
            $date = $filters['date'];
            $query->whereHas('shows', fn ($show) => $show
                ->where('status', 'scheduled')
                ->whereDate('starts_at', $date));
        }

        return $query->orderByDesc('published_at')->paginate($perPage);
    }

    public function event(string $slug): Event
    {
        return $this->eventQuery()->where('slug', $slug)->firstOrFail();
    }

    public function findHall(int $id): Hall
    {
        return Hall::withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE)
            ->where('is_active', true)
            ->whereHas('tenant', fn ($query) => $query->where('status', 'active'))
            ->with('tenant')
            ->findOrFail($id);
    }

    /** @return Collection<int, HallTimeSlot> */
    public function availability(Hall $hall, string $from, string $to): Collection
    {
        return $this->slots->availability($hall, $from, $to);
    }

    private function eventQuery()
    {
        return Event::withoutGlobalScope(Event::CURRENT_TENANT_SCOPE)
            ->where('status', 'published')
            ->whereNull('cancelled_at')
            ->whereHas('tenant', fn ($query) => $query->where('status', 'active'))
            ->with([
                'translations',
                'category.translations',
                'poster',
                'tenant.theaterProfile',
                'shows' => fn ($query) => $query
                    ->withoutGlobalScope(Show::CURRENT_TENANT_SCOPE)
                    ->where('status', 'scheduled')
                    ->where('starts_at', '>', now())
                    ->orderBy('starts_at')
                    ->with([
                        'hall',
                        'tiers' => fn ($tiers) => $tiers->withoutGlobalScope(\Modules\Theaters\Models\TicketTier::CURRENT_TENANT_SCOPE)->where('is_active', true)->with('translations'),
                    ]),
            ]);
    }
}
