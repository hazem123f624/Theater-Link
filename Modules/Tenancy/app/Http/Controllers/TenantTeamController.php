<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Http\Requests\StoreTenantMemberRequest;
use Modules\Tenancy\Http\Requests\UpdateTenantMemberRequest;
use Modules\Tenancy\Models\TenantMember;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Resources\TenantTeamMemberResource;

class TenantTeamController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TenantContext $context) {}

    public function index(Request $request)
    {
        $tenant = $this->context->requireTenant();

        $query = TenantMember::query()
            ->where('tenant_id', $tenant->id)
            ->with('roles');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $members = $query->latest('id')->paginate($perPage);

        return $this->success(__('tenancy::messages.team_retrieved'), data: [
            'data' => TenantTeamMemberResource::collection($members->getCollection()),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ]);
    }

    public function store(StoreTenantMemberRequest $request)
    {
        $tenant = $this->context->requireTenant();
        $data = $request->validated();

        $member = DB::transaction(function () use ($tenant, $data): TenantMember {
            $member = TenantMember::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_owner' => false,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->syncRoles($member, $data['roles'] ?? []);

            return $member->load('roles');
        });

        return $this->success(__('tenancy::messages.team_member_created'), new TenantTeamMemberResource($member), 201);
    }

    public function trash(Request $request)
    {
        $tenant = $this->context->requireTenant();
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $members = TenantMember::onlyTrashed()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->latest('id')
            ->paginate($perPage);

        return $this->success(__('tenancy::messages.team_retrieved'), data: [
            'data' => TenantTeamMemberResource::collection($members->getCollection()),
            'pagination' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ]);
    }

    public function restore(TenantMember $member)
    {
        $tenant = $this->context->requireTenant();
        $this->ensureTenantMember($member, $tenant->id);

        $duplicate = TenantMember::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', $member->email)
            ->exists();

        if ($duplicate) {
            return $this->failure(__('tenancy::messages.member_email_conflict'), 422);
        }

        $member->restore();

        return $this->success(__('tenancy::messages.team_member_restored'), new TenantTeamMemberResource($member->fresh('roles')));
    }

    public function forceDelete(TenantMember $member)
    {
        $tenant = $this->context->requireTenant();
        $this->ensureTenantMember($member, $tenant->id);

        if ($member->is_owner
            && TenantMember::withTrashed()
                ->where('tenant_id', $tenant->id)
                ->where('is_owner', true)
                ->whereNull('deleted_at')
                ->count() <= 1) {
            return $this->failure(__('tenancy::messages.last_owner_protected'), 422);
        }

        $member->tokens()->delete();
        $member->forceDelete();

        return $this->success(__('tenancy::messages.team_member_removed'));
    }

    public function update(UpdateTenantMemberRequest $request, TenantMember $member)
    {
        $tenant = $this->context->requireTenant();
        $this->ensureTenantMember($member, $tenant->id);

        $data = $request->validated();

        DB::transaction(function () use ($member, $data): void {
            $member->fill(collect($data)->only(['name', 'email', 'phone', 'password'])->all());
            $member->save();

            if (array_key_exists('roles', $data)) {
                $this->syncRoles($member, $data['roles']);
            }
        });

        return $this->success(__('tenancy::messages.team_member_updated'), new TenantTeamMemberResource($member->fresh('roles')));
    }

    public function toggleActive(TenantMember $member)
    {
        $tenant = $this->context->requireTenant();
        $this->ensureTenantMember($member, $tenant->id);

        $blocked = DB::transaction(function () use ($member, $tenant): bool {
            $member = TenantMember::query()->lockForUpdate()->findOrFail($member->id);

            if ($member->is_active && $member->is_owner && $this->activeOwnerCount($tenant->id) <= 1) {
                return true;
            }

            $member->update(['is_active' => ! $member->is_active]);

            if (! $member->is_active) {
                $member->tokens()->delete();
            }

            return false;
        });

        if ($blocked) {
            return $this->failure(__('tenancy::messages.last_owner_protected'), 422);
        }

        return $this->success(__('tenancy::messages.team_member_updated'), new TenantTeamMemberResource($member->fresh('roles')));
    }

    public function destroy(TenantMember $member)
    {
        $tenant = $this->context->requireTenant();
        $this->ensureTenantMember($member, $tenant->id);

        $removed = DB::transaction(function () use ($member, $tenant): bool {
            $member = TenantMember::query()->lockForUpdate()->findOrFail($member->id);

            if ($member->is_owner && $this->activeOwnerCount($tenant->id) <= 1) {
                return false;
            }

            $member->tokens()->delete();
            $member->delete();

            return true;
        });

        if (! $removed) {
            return $this->failure(__('tenancy::messages.last_owner_protected'), 422);
        }

        return $this->success(__('tenancy::messages.team_member_removed'));
    }

    private function ensureTenantMember(TenantMember $member, int $tenantId): void
    {
        if ($member->tenant_id !== $tenantId) {
            abort(404, __('messages.resource_not_found'));
        }
    }

    private function syncRoles(TenantMember $member, array $roleIds): void
    {
        $roleIds = collect($roleIds)->map(fn ($id) => (int) $id)->unique()->values();

        $validRoleIds = TenantRole::withoutGlobalScopes()
            ->where('tenant_id', $member->tenant_id)
            ->whereIn('id', $roleIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($roleIds->diff($validRoleIds)->isNotEmpty()) {
            abort(422, __('tenancy::messages.role_not_found'));
        }

        if ($member->is_owner) {
            $ownerRoleId = TenantRole::withoutGlobalScopes()
                ->where('tenant_id', $member->tenant_id)
                ->where('template_key', 'owner')
                ->value('id');

            if ($ownerRoleId) {
                $validRoleIds->push((int) $ownerRoleId)->unique();
            }
        }

        $member->roles()->syncWithPivotValues(
            $validRoleIds->values()->all(),
            ['tenant_id' => $member->tenant_id]
        );
    }

    private function activeOwnerCount(int $tenantId): int
    {
        return TenantMember::query()
            ->where('tenant_id', $tenantId)
            ->where('is_owner', true)
            ->where('is_active', true)
            ->count();
    }
}
