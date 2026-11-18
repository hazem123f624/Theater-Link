<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\ActivityLog\ActivityLogService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Modules\Tenancy\Http\Requests\ResubmitGroupDocumentRequest;
use Modules\Tenancy\Models\GroupApplicationDocument;
use Modules\Tenancy\Resources\GroupApplicationResource;
use Modules\Tenancy\Resources\GroupDocumentResource;
use Modules\Tenancy\Services\GroupApplicationDocumentService;
use Modules\Tenancy\Services\GroupApplicationReviewService;

class GroupApplicationController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantContext $context,
        private GroupApplicationDocumentService $documents,
        private GroupApplicationReviewService $review,
    ) {}

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

    public function downloadDocument(Request $request, GroupApplicationDocument $document)
    {
        $application = $this->application();
        $document = $application->documents()->findOrFail($document->id);

        ActivityLogService::log(
            action: 'group_application.document_downloaded',
            description: __('tenancy::messages.activity_group_application_review', ['action' => 'document_downloaded']),
            subject: $application,
            properties: ['document_id' => $document->id, 'document_type' => $document->document_type],
            causer: $this->context->requireMember(),
            type: 'user',
        );

        return $this->documents->download($document, $request->boolean('inline'));
    }

    public function resubmitDocument(ResubmitGroupDocumentRequest $request, GroupApplicationDocument $document)
    {
        $application = $this->application();
        $document = $application->documents()->findOrFail($document->id);
        $member = $this->context->requireMember();

        $updated = $this->review->resubmitDocument(
            $application,
            $document,
            $member,
            $request->file('document')
        );

        return $this->success(
            __('tenancy::messages.document_resubmitted'),
            new GroupApplicationResource($updated->load([
                'documents',
                'histories.admin',
                'histories.member',
                'tenant.members',
            ]))
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
        $application = $tenant->groupApplication()
            ->with(['documents', 'histories.admin', 'histories.member', 'tenant.members'])
            ->first();

        if (! $application) {
            abort(404, __('messages.resource_not_found'));
        }

        return $application;
    }
}
