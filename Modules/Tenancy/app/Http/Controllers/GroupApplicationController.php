<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Modules\Tenancy\Resources\GroupApplicationResource;
use Modules\Tenancy\Resources\GroupDocumentResource;

class GroupApplicationController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TenantContext $context) {}

    public function show()
    {
        $application = $this->application();

        return $this->success(__('tenancy::messages.application_retrieved'), new GroupApplicationResource($application));
    }

    public function documents()
    {
        $application = $this->application();

        return $this->success(
            __('tenancy::messages.documents_retrieved'),
            GroupDocumentResource::collection($application->documents)
        );
    }

    public function workspace()
    {
        $tenant = $this->context->requireTenant();
        $application = $tenant->groupApplication()->first();

        return $this->success(__('tenancy::messages.workspace_retrieved'), [
            'workspace_status' => $tenant->status,
            'application_status' => $application?->status,
            'subdomain' => $tenant->subdomain,
            'domain' => rtrim((string) config('tenancy.workspace.base_url'), '/').'/'.$tenant->subdomain,
        ]);
    }

    public function support()
    {
        return $this->success(__('tenancy::messages.support_retrieved'), [
            'email' => config('tenancy.support.email'),
            'phone' => config('tenancy.support.phone'),
        ]);
    }

    private function application()
    {
        $tenant = $this->context->requireTenant();
        $application = $tenant->groupApplication()->with('documents')->first();

        if (! $application) {
            abort(404, __('messages.resource_not_found'));
        }

        return $application;
    }
}
