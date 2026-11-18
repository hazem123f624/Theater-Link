import { apiDelete, apiGet, apiPatch, apiPost, apiPut } from '@/api/client';
import type {
    GroupApplication,
    GroupDocument,
    Invitation,
    PaginatedData,
    PermissionOption,
    TeamMember,
    TenantDashboard,
    TenantRole,
} from '@/api/types';

export interface TeamQuery {
    search?: string;
    page?: number;
    per_page?: number;
}

export function getTheaterDashboard(): Promise<TenantDashboard> {
    return apiGet<TenantDashboard>('/theater/dashboard');
}

export function getGroupDashboard(): Promise<TenantDashboard> {
    return apiGet<TenantDashboard>('/group/dashboard');
}

export function getTeam(params: TeamQuery): Promise<PaginatedData<TeamMember>> {
    return apiGet<PaginatedData<TeamMember>>('/tenant/team', params);
}

export function getTeamTrash(params: TeamQuery): Promise<PaginatedData<TeamMember>> {
    return apiGet<PaginatedData<TeamMember>>('/tenant/team/trash', params);
}

export interface TeamMemberPayload {
    name: string;
    email: string;
    phone?: string | null;
    password?: string;
    password_confirmation?: string;
    roles: number[];
    is_active?: boolean;
}

export function createTeamMember(payload: TeamMemberPayload): Promise<TeamMember> {
    return apiPost<TeamMember>('/tenant/team', payload);
}

export function updateTeamMember(id: number, payload: TeamMemberPayload): Promise<TeamMember> {
    return apiPut<TeamMember>(`/tenant/team/${id}`, payload);
}

export function toggleTeamMember(id: number): Promise<TeamMember> {
    return apiPatch<TeamMember>(`/tenant/team/${id}/toggle-active`);
}

export function deleteTeamMember(id: number): Promise<unknown> {
    return apiDelete(`/tenant/team/${id}`);
}

export function restoreTeamMember(id: number): Promise<TeamMember> {
    return apiPatch<TeamMember>(`/tenant/team/${id}/restore`);
}

export function forceDeleteTeamMember(id: number): Promise<unknown> {
    return apiDelete(`/tenant/team/${id}/force-delete`);
}

export function getRoles(): Promise<TenantRole[]> {
    return apiGet<TenantRole[]>('/tenant/roles');
}

export function getRole(id: number): Promise<TenantRole> {
    return apiGet<TenantRole>(`/tenant/roles/${id}`);
}

export function getPermissionCatalog(): Promise<PermissionOption[]> {
    return apiGet<PermissionOption[]>('/tenant/roles/permissions');
}

export function createRole(payload: { name: string; permission_ids: number[] }): Promise<TenantRole> {
    return apiPost<TenantRole>('/tenant/roles', payload);
}

export function updateRole(
    id: number,
    payload: { name: string; permission_ids: number[] },
): Promise<TenantRole> {
    return apiPut<TenantRole>(`/tenant/roles/${id}`, payload);
}

export function deleteRole(id: number): Promise<unknown> {
    return apiDelete(`/tenant/roles/${id}`);
}

export function getInvitations(params: { page?: number; per_page?: number }): Promise<PaginatedData<Invitation>> {
    return apiGet<PaginatedData<Invitation>>('/tenant/team/invitations', params);
}

export function createInvitation(payload: {
    email: string;
    name?: string;
    role_id?: number | null;
}): Promise<Invitation> {
    return apiPost<Invitation>('/tenant/team/invitations', payload);
}

export function getGroupApplication(): Promise<GroupApplication> {
    return apiGet<GroupApplication>('/group/application');
}

export function getGroupDocuments(): Promise<GroupDocument[]> {
    return apiGet<GroupDocument[]>('/group/application/documents');
}

export function getGroupWorkspace(): Promise<{
    workspace_status: string;
    application_status: string | null;
    subdomain: string;
    domain: string;
}> {
    return apiGet('/group/application/workspace');
}

export function getGroupSupport(): Promise<{ email: string; phone: string }> {
    return apiGet('/group/application/support');
}
