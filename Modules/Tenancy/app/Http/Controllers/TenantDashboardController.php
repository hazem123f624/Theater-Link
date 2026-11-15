<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Modules\Tenancy\Resources\TenantTeamMemberResource;

class TenantDashboardController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TenantContext $context) {}

    public function theater()
    {
        return $this->summary([
            'halls_count' => 0,
            'events_count' => 0,
            'pending_reservations_count' => 0,
            'upcoming_reservations_count' => 0,
        ]);
    }

    public function group()
    {
        return $this->summary([
            'pending_reservations_count' => 0,
            'approved_reservations_count' => 0,
            'upcoming_reservations_count' => 0,
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
