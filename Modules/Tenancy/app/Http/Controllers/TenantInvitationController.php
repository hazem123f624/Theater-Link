<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Messages\MessageFactory;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Tenancy\Http\Requests\AcceptTenantInvitationRequest;
use Modules\Tenancy\Http\Requests\StoreTenantInvitationRequest;
use Modules\Tenancy\Models\TenantInvitation;
use Modules\Tenancy\Models\TenantMember;
use Modules\Tenancy\Models\TenantMemberRole;
use Modules\Tenancy\Resources\TenantInvitationResource;
use Modules\Tenancy\Resources\TenantMemberResource;

class TenantInvitationController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TenantContext $context) {}

    public function index(Request $request)
    {
        $tenant = $this->context->requireTenant();
        $invitations = TenantInvitation::query()
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->paginate(max(1, min((int) $request->query('per_page', 15), 100)));

        return $this->success(__('tenancy::messages.invitations_retrieved'), [
            'data' => TenantInvitationResource::collection($invitations->getCollection()),
            'pagination' => [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
            ],
        ]);
    }

    public function store(StoreTenantInvitationRequest $request)
    {
        $tenant = $this->context->requireTenant();
        $member = $this->context->requireMember();
        $data = $request->validated();

        $invitation = TenantInvitation::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $data['email']],
            [
                'name' => $data['name'] ?? null,
                'role_id' => $data['role_id'] ?? null,
                'invited_by' => $member->id,
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ]
        );

        MessageFactory::make('email')->send([
            'to' => $invitation->email,
            'subject' => __('tenancy::messages.invitation_subject', ['workspace' => $tenant->name]),
            'view' => 'emails.tenant-invitation',
            'body' => ['invitation' => $invitation, 'tenant' => $tenant],
        ]);

        return $this->success(__('tenancy::messages.invitation_sent'), new TenantInvitationResource($invitation), 201);
    }

    public function accept(AcceptTenantInvitationRequest $request)
    {
        $data = $request->validated();

        $invitation = TenantInvitation::query()
            ->where('token', $data['token'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invitation) {
            return $this->failure(__('tenancy::messages.invitation_invalid'), 422);
        }

        $existing = TenantMember::query()
            ->where('tenant_id', $invitation->tenant_id)
            ->where('email', $invitation->email)
            ->active()
            ->exists();

        if ($existing) {
            return $this->failure(__('tenancy::messages.member_email_conflict'), 422);
        }

        $member = DB::transaction(function () use ($invitation, $data): TenantMember {
            $member = TenantMember::create([
                'tenant_id' => $invitation->tenant_id,
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => $data['password'],
                'is_owner' => false,
                'is_active' => true,
            ]);

            if ($invitation->role_id) {
                TenantMemberRole::withoutGlobalScopes()->updateOrCreate(
                    ['member_id' => $member->id, 'role_id' => $invitation->role_id],
                    ['tenant_id' => $invitation->tenant_id]
                );
            }

            $invitation->update(['accepted_at' => now()]);

            return $member;
        });

        $tenant = $member->tenant;
        $this->context->set($tenant, $member);
        $member->load(['tenant', 'roles.permissions']);
        $token = $member->createToken('tenant-member', ['tenant_members'])->plainTextToken;
        $resource = new TenantMemberResource($member);
        $resource->token = $token;
        $resource->expires_in = config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null;

        return $this->success(__('tenancy::messages.invitation_accepted'), $resource);
    }
}
