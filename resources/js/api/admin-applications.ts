import { apiClient, apiGet, apiPatch, apiPost } from '@/api/client';
import type {
    AdminGroupApplication,
    AdminGroupApplicationDetail,
    PaginatedData,
} from '@/api/types';

export interface ApplicationFilters {
    search?: string;
    status?: string;
    page?: number;
    per_page?: number;
}

export interface ApplicationStatusOption {
    value: string;
    label: string;
}

export function listGroupApplications(params: ApplicationFilters): Promise<PaginatedData<AdminGroupApplication>> {
    return apiGet<PaginatedData<AdminGroupApplication>>('/dashboard/group-applications', params);
}

export function getApplicationStatuses(): Promise<ApplicationStatusOption[]> {
    return apiGet<ApplicationStatusOption[]>('/dashboard/group-applications/statuses');
}

export function getGroupApplication(id: number): Promise<AdminGroupApplicationDetail> {
    return apiGet<AdminGroupApplicationDetail>(`/dashboard/group-applications/${id}`);
}

export function acceptGroupDocument(applicationId: number, documentId: number): Promise<AdminGroupApplicationDetail> {
    return apiPost<AdminGroupApplicationDetail>(`/dashboard/group-applications/${applicationId}/documents/${documentId}/accept`);
}

export function rejectGroupDocument(applicationId: number, documentId: number, reason: string): Promise<AdminGroupApplicationDetail> {
    return apiPost<AdminGroupApplicationDetail>(`/dashboard/group-applications/${applicationId}/documents/${documentId}/reject`, { reason });
}

export function requestGroupDocumentResubmission(
    applicationId: number,
    documentId: number,
    feedback: string,
): Promise<AdminGroupApplicationDetail> {
    return apiPost<AdminGroupApplicationDetail>(`/dashboard/group-applications/${applicationId}/documents/${documentId}/request-resubmission`, { feedback });
}

export function approveGroupApplication(applicationId: number): Promise<AdminGroupApplicationDetail> {
    return apiPost<AdminGroupApplicationDetail>(`/dashboard/group-applications/${applicationId}/approve`);
}

export function rejectGroupApplication(
    applicationId: number,
    payload: { reason: string; review_feedback?: string },
): Promise<AdminGroupApplicationDetail> {
    return apiPost<AdminGroupApplicationDetail>(`/dashboard/group-applications/${applicationId}/reject`, payload);
}

export function reopenGroupApplication(applicationId: number): Promise<AdminGroupApplicationDetail> {
    return apiPost<AdminGroupApplicationDetail>(`/dashboard/group-applications/${applicationId}/reopen`);
}

export function addGroupApplicationNote(applicationId: number, note: string): Promise<AdminGroupApplicationDetail> {
    return apiPost<AdminGroupApplicationDetail>(`/dashboard/group-applications/${applicationId}/notes`, { note });
}

export function suspendGroupApplication(applicationId: number, reason: string): Promise<AdminGroupApplicationDetail> {
    return apiPatch<AdminGroupApplicationDetail>(`/dashboard/group-applications/${applicationId}/suspend`, { reason });
}

export function reactivateGroupApplication(applicationId: number): Promise<AdminGroupApplicationDetail> {
    return apiPatch<AdminGroupApplicationDetail>(`/dashboard/group-applications/${applicationId}/reactivate`);
}

export async function downloadGroupDocument(
    applicationId: number,
    documentId: number,
    inline = false,
): Promise<Blob> {
    const response = await apiClient.get(`/dashboard/group-applications/${applicationId}/documents/${documentId}/download`, {
        params: { inline: inline ? 1 : 0 },
        responseType: 'blob',
    });

    return response.data as Blob;
}

export async function downloadOwnGroupDocument(documentId: number, inline = false): Promise<Blob> {
    const response = await apiClient.get(`/group/application/documents/${documentId}/download`, {
        params: { inline: inline ? 1 : 0 },
        responseType: 'blob',
    });

    return response.data as Blob;
}

export function resubmitGroupDocument(documentId: number, file: File): Promise<unknown> {
    const data = new FormData();
    data.append('document', file);
    return apiPost(`/group/application/documents/${documentId}/resubmit`, data);
}
