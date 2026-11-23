<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Modules\Bookings\Models\Booking;
use Modules\Reservations\Models\Reservation;
use Modules\Tenancy\Resources\TenantTeamMemberResource;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\Show;

class TenantDashboardController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TenantContext $context) {}

    public function theater()
    {
        $tenant = $this->context->requireTenant();

        return $this->summary([
            'halls_count' => Hall::query()->count(),
            'events_count' => Event::query()->count(),
            'published_events_count' => Event::query()->where('status', 'published')->count(),
            'upcoming_shows_count' => Show::query()
                ->where('status', 'scheduled')
                ->where('starts_at', '>', now())
                ->count(),
            'pending_reservations_count' => Reservation::query()->where('theater_tenant_id', $tenant->id)->where('status', 'pending')->count(),
            'upcoming_reservations_count' => Reservation::query()
                ->where('theater_tenant_id', $tenant->id)
                ->where('status', 'approved')
                ->whereHas('slot', fn ($query) => $query->where('starts_at', '>', now()))
                ->count(),
            'bookings_count' => Booking::query()->where('tenant_id', $tenant->id)->count(),
            'upcoming_bookings_count' => Booking::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'confirmed')
                ->whereHas('show', fn ($query) => $query
                    ->withoutGlobalScope(\Modules\Theaters\Models\Show::CURRENT_TENANT_SCOPE)
                    ->where('starts_at', '>', now()))
                ->count(),
        ]);
    }

    public function group()
    {
        $tenant = $this->context->requireTenant();

        return $this->summary([
            'pending_reservations_count' => Reservation::query()->where('group_tenant_id', $tenant->id)->where('status', 'pending')->count(),
            'approved_reservations_count' => Reservation::query()->where('group_tenant_id', $tenant->id)->where('status', 'approved')->count(),
            'upcoming_reservations_count' => Reservation::query()
                ->where('group_tenant_id', $tenant->id)
                ->where('status', 'approved')
                ->whereHas('slot', fn ($query) => $query->where('starts_at', '>', now()))
                ->count(),
        ]);
    }

    private function summary(array $counts)
    {
        $tenant = $this->context->requireTenant();
        $member = $this->context->requireMember()->load('roles');

        return $this->success(__('tenancy::messages.dashboard_retrieved'), [
            'tenant' => [
                'id' => $tenant->id,
                'ulid' => $tenant->ulid,
                'type' => $tenant->type,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'status' => $tenant->status,
            ],
            'member' => new TenantTeamMemberResource($member),
            'counts' => $counts,
        ]);
    }
}
