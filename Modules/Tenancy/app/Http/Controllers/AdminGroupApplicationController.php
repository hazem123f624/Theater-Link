<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Http\Request;
use Modules\Admins\Models\Admins;
use Modules\Tenancy\Enums\GroupApplicationStatus;
use Modules\Tenancy\Http\Requests\AddGroupApplicationNoteRequest;
use Modules\Tenancy\Http\Requests\ListGroupApplicationsRequest;
use Modules\Tenancy\Http\Requests\RejectGroupApplicationRequest;
use Modules\Tenancy\Http\Requests\RejectGroupDocumentRequest;
use Modules\Tenancy\Http\Requests\RequestGroupDocumentResubmissionRequest;
use Modules\Tenancy\Http\Requests\SuspendGroupRequest;
use Modules\Tenancy\Models\GroupApplication;
use Modules\Tenancy\Models\GroupApplicationDocument;
use Modules\Tenancy\Resources\AdminGroupApplicationDetailResource;
use Modules\Tenancy\Resources\AdminGroupApplicationResource;
use Modules\Tenancy\Services\GroupApplicationDocumentService;
use Modules\Tenancy\Services\GroupApplicationReviewService;

class AdminGroupApplicationController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private GroupApplicationReviewService $review,
        private GroupApplicationDocumentService $documents,
    ) {}

    public function index(ListGroupApplicationsRequest $request)
    {
        $data = $request->validated();
        $query = GroupApplication::query()
            ->with(['tenant.groupProfile', 'tenant.members', 'documents']);

        if (! empty($data['search'])) {
            $search = trim($data['search']);
            $query->where(function ($builder) use ($search): void {
                $builder->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($tenantQuery) use ($search): void {
                        $tenantQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('subdomain', 'like', "%{$search}%")
                            ->orWhereHas('members', function ($memberQuery) use ($search): void {
                                $memberQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        $perPage = (int) ($data['per_page'] ?? 15);
        $applications = $query->latest('id')->paginate($perPage);

        return $this->success(__('tenancy::messages.applications_retrieved'), data: [
            'data' => AdminGroupApplicationResource::collection($applications->getCollection()),
            'pagination' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }

    public function statuses()
    {
        return $this->success(
            __('tenancy::messages.application_statuses_retrieved'),
            collect(GroupApplicationStatus::cases())->map(fn (GroupApplicationStatus $status): array => [
                'value' => $status->value,
                'label' => str($status->value)->replace('_', ' ')->title()->toString(),
            ])->values()
        );
    }

    public function show(GroupApplication $application)
    {
        return $this->success(
            __('tenancy::messages.application_retrieved'),
            new AdminGroupApplicationDetailResource($this->loadApplication($application))
        );
    }

    public function downloadDocument(Request $request, GroupApplication $application, GroupApplicationDocument $document)
    {
        $application = $this->loadApplication($application);
        $document = $application->documents()->findOrFail($document->id);

        ActivityLogService::log(
            action: 'group_application.document_downloaded',
            description: __('tenancy::messages.activity_group_application_review', ['action' => 'document_downloaded']),
            subject: $application,
            properties: ['document_id' => $document->id, 'document_type' => $document->document_type],
            causer: $this->admin(),
            type: 'admin',
        );

        return $this->documents->download($document, $request->boolean('inline'));
    }

    public function acceptDocument(GroupApplication $application, GroupApplicationDocument $document)
    {
        $application = $this->loadApplication($application);
        $document = $application->documents()->findOrFail($document->id);

        return $this->success(
            __('tenancy::messages.document_accepted'),
            new AdminGroupApplicationDetailResource(
                $this->review->acceptDocument($application, $document, $this->admin())
            )
        );
    }

    public function rejectDocument(RejectGroupDocumentRequest $request, GroupApplication $application, GroupApplicationDocument $document)
    {
        $application = $this->loadApplication($application);
        $document = $application->documents()->findOrFail($document->id);

        return $this->success(
            __('tenancy::messages.document_rejected'),
            new AdminGroupApplicationDetailResource(
                $this->review->rejectDocument($application, $document, $this->admin(), $request->validated('reason'))
            )
        );
    }

    public function requestDocumentResubmission(
        RequestGroupDocumentResubmissionRequest $request,
        GroupApplication $application,
        GroupApplicationDocument $document
    ) {
        $application = $this->loadApplication($application);
        $document = $application->documents()->findOrFail($document->id);

        return $this->success(
            __('tenancy::messages.document_resubmission_requested'),
            new AdminGroupApplicationDetailResource(
                $this->review->requestDocumentResubmission($application, $document, $this->admin(), $request->validated('feedback'))
            )
        );
    }

    public function approve(GroupApplication $application)
    {
        $application = $this->loadApplication($application);

        return $this->success(
            __('tenancy::messages.application_approved'),
            new AdminGroupApplicationDetailResource($this->review->approve($application, $this->admin()))
        );
    }

    public function reject(RejectGroupApplicationRequest $request, GroupApplication $application)
    {
        $application = $this->loadApplication($application);

        return $this->success(
            __('tenancy::messages.application_rejected'),
            new AdminGroupApplicationDetailResource(
                $this->review->reject(
                    $application,
                    $this->admin(),
                    $request->validated('reason'),
                    $request->validated('review_feedback')
                )
            )
        );
    }

    public function reopen(GroupApplication $application)
    {
        $application = $this->loadApplication($application);

        return $this->success(
            __('tenancy::messages.application_reopened'),
            new AdminGroupApplicationDetailResource($this->review->reopen($application, $this->admin()))
        );
    }

    public function note(AddGroupApplicationNoteRequest $request, GroupApplication $application)
    {
        $application = $this->loadApplication($application);

        return $this->success(
            __('tenancy::messages.application_note_added'),
            new AdminGroupApplicationDetailResource(
                $this->review->addNote($application, $this->admin(), $request->validated('note'))
            )
        );
    }

    public function suspend(SuspendGroupRequest $request, GroupApplication $application)
    {
        $application = $this->loadApplication($application);

        return $this->success(
            __('tenancy::messages.workspace_suspended'),
            new AdminGroupApplicationDetailResource(
                $this->review->suspend($application, $this->admin(), $request->validated('reason'))
            )
        );
    }

    public function reactivate(GroupApplication $application)
    {
        $application = $this->loadApplication($application);

        return $this->success(
            __('tenancy::messages.workspace_reactivated'),
            new AdminGroupApplicationDetailResource($this->review->reactivate($application, $this->admin()))
        );
    }

    private function loadApplication(GroupApplication $application): GroupApplication
    {
        return $application->load([
            'tenant.groupProfile',
            'tenant.members',
            'documents.reviewer',
            'histories.admin',
            'histories.member',
        ]);
    }

    private function admin(): Admins
    {
        /** @var Admins $admin */
        $admin = auth('sanctum')->user();

        return $admin;
    }
}
