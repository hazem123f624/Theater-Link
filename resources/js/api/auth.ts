import { apiGet, apiPost } from '@/api/client';
import type { AdminProfile, TenantMember, UserProfile } from '@/api/types';

export interface TenantLoginPayload {
    subdomain: string;
    email: string;
    password: string;
}

export interface UserLoginPayload {
    country_code: string;
    phone: string;
    password: string;
}

export interface AdminLoginPayload {
    email: string;
    password: string;
}

export interface TheaterRegistrationPayload {
    name: string;
    subdomain: string;
    owner_name: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
    address?: string;
    country_id?: number | null;
    city_id?: number | null;
}

export interface IndividualRegistrationPayload {
    name: string;
    country_code: string;
    phone: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export interface OtpResponse {
    otp: number | string | null;
    change_password_token?: string;
    token?: string;
}

export function loginTheater(payload: TenantLoginPayload): Promise<TenantMember> {
    return apiPost<TenantMember>('/theater/login', payload);
}

export function loginGroup(payload: TenantLoginPayload): Promise<TenantMember> {
    return apiPost<TenantMember>('/group/login', payload);
}

export function loginUser(payload: UserLoginPayload): Promise<UserProfile> {
    return apiPost<UserProfile>('/user/login', payload);
}

export function loginAdmin(payload: AdminLoginPayload): Promise<AdminProfile> {
    return apiPost<AdminProfile>('/login', { ...payload, step: 1 });
}

export function registerTheater(payload: TheaterRegistrationPayload): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/theater/register', payload);
}

export function verifyTheater(phone: string, otp: string): Promise<{
    member: TenantMember;
    profile?: Record<string, unknown>;
}> {
    return apiPost('/theater/verify', { phone, otp });
}

export function resendTheaterOtp(phone: string): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/theater/resend-otp', { phone });
}

export function registerGroup(formData: FormData): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/group/register', formData);
}

export function verifyGroup(phone: string, otp: string): Promise<{
    member: TenantMember;
    application?: Record<string, unknown>;
}> {
    return apiPost('/group/verify', { phone, otp });
}

export function resendGroupOtp(phone: string): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/group/resend-otp', { phone });
}

export function registerUser(payload: IndividualRegistrationPayload): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/user/register', payload);
}

export function verifyUser(payload: {
    country_code: string;
    phone: string;
    otp: string;
}): Promise<UserProfile> {
    return apiPost<UserProfile>('/user/verify', payload);
}

export function resendUserOtp(payload: { country_code: string; phone: string }): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/user/resend-otp', payload);
}

export function requestTenantPasswordReset(payload: {
    subdomain: string;
    email: string;
}): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/tenant/forgot-password', payload);
}

export function verifyTenantPasswordReset(payload: {
    subdomain: string;
    email: string;
    otp: string;
    change_password_token: string;
}): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/tenant/verify-forgot-password', payload);
}

export function resetTenantPassword(payload: {
    subdomain: string;
    email: string;
    otp: string;
    change_password_token: string;
    password: string;
    password_confirmation: string;
}): Promise<unknown> {
    return apiPost('/tenant/reset-password', payload);
}

export function requestUserPasswordReset(payload: {
    country_code: string;
    phone: string;
}): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/user/forget-password', payload);
}

export function verifyUserPasswordReset(payload: {
    country_code: string;
    phone: string;
    otp: string;
    change_password_token: string;
}): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/user/verify-forget-password', payload);
}

export function resetUserPassword(payload: {
    country_code: string;
    phone: string;
    password: string;
    password_confirmation: string;
    change_password_token: string;
}): Promise<unknown> {
    return apiPost('/user/reset-password', payload);
}

export function requestAdminPasswordReset(email: string): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/forget-password', { email });
}

export function verifyAdminPasswordReset(payload: {
    email: string;
    otp: string;
    change_password_token: string;
}): Promise<OtpResponse> {
    return apiPost<OtpResponse>('/verify-forget-password-otp', payload);
}

export function resetAdminPassword(payload: {
    email: string;
    password: string;
    new_password_confirmation: string;
    change_password_token: string;
}): Promise<unknown> {
    return apiPost('/reset-password', payload);
}

export function acceptInvitation(payload: {
    token: string;
    name: string;
    password: string;
    password_confirmation: string;
}): Promise<TenantMember> {
    return apiPost<TenantMember>('/tenant/team/invitations/accept', payload);
}

export function getTenantMe(): Promise<TenantMember> {
    return apiGet<TenantMember>('/tenant/me');
}

export function getUserProfile(): Promise<UserProfile> {
    return apiGet<UserProfile>('/user/profile');
}

export function getAdminProfile(): Promise<AdminProfile> {
    return apiGet<AdminProfile>('/dashboard/profile');
}

export function logout(actor: 'admin' | 'theater' | 'group' | 'user'): Promise<unknown> {
    if (actor === 'admin') {
        return apiPost('/logout');
    }

    if (actor === 'user') {
        return apiPost('/user/logout');
    }

    return apiPost('/tenant/logout');
}

export function refreshTenantToken(): Promise<{ token: string; expires_in?: number }> {
    return apiPost('/tenant/refresh-token');
}

export function refreshUserToken(): Promise<{ token: string; expires_in?: number }> {
    return apiPost('/user/refresh-token');
}
