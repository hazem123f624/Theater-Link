import { apiClient, apiGet, apiPost, apiPut } from '@/api/client';
import type { AdminCounts, AdminProfile } from '@/api/types';

export async function getAdminCounts(): Promise<AdminCounts> {
    const response = await apiClient.get<{ status: string; data: AdminCounts }>('/dashboard/counts');
    return response.data.data;
}

export function getAdminProfile(): Promise<AdminProfile> {
    return apiGet<AdminProfile>('/dashboard/profile');
}

export function updateAdminProfile(payload: Record<string, unknown>): Promise<AdminProfile> {
    return apiPut<AdminProfile>('/dashboard/profile', payload);
}

export function changeAdminPassword(payload: {
    old_password: string;
    new_password: string;
    password_confirmation: string;
}): Promise<unknown> {
    return apiPost('/dashboard/change-password', payload);
}
