import { apiDelete, apiGet, apiPost } from '@/api/client';
import type { UserDashboard, UserProfile } from '@/api/types';

export function getUserDashboard(): Promise<UserDashboard> {
    return apiGet<UserDashboard>('/user/dashboard');
}

export function updateUserProfile(payload: Record<string, unknown>): Promise<UserProfile> {
    return apiPost<UserProfile>('/user/profile', payload);
}

export function deleteUserAccount(): Promise<unknown> {
    return apiDelete('/user/profile');
}

export function changeUserPassword(payload: {
    old_password: string;
    new_password: string;
    new_password_confirmation: string;
}): Promise<unknown> {
    return apiPost('/user/change-password', payload);
}

export function verifyUserPhone(payload: {
    country_code: string;
    phone: string;
    otp: string;
}): Promise<UserProfile> {
    return apiPost<UserProfile>('/user/verify', payload);
}
