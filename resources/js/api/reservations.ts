import { apiGet, apiPost } from '@/api/client';
import type { GroupProfileData, HallSlot, PaginatedData, PublicTheater, ReservationRecord } from '@/api/types';

export interface ReservationFilters {
    status?: string;
    search?: string;
    page?: number;
    per_page?: number;
}

export function getGroupProfile(): Promise<GroupProfileData> {
    return apiGet<GroupProfileData>('/group/dashboard/profile');
}

export function updateGroupProfile(
    data: Record<string, unknown>,
    logo?: File | null,
): Promise<GroupProfileData> {
    const form = new FormData();
    form.append('_method', 'PUT');
    Object.entries(data).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            form.append(key, String(value));
        }
    });
    if (logo) form.append('logo', logo);
    return apiPost<GroupProfileData>('/group/dashboard/profile', form);
}

export function getGroupTheaters(params: { page?: number; per_page?: number } = {}): Promise<PaginatedData<PublicTheater>> {
    return apiGet<PaginatedData<PublicTheater>>('/group/dashboard/theaters', params);
}

export function getGroupTheater(id: number): Promise<PublicTheater> {
    return apiGet<PublicTheater>(`/group/dashboard/theaters/${id}`);
}

export function getGroupTheaterHalls(id: number): Promise<Array<{ id: number; name: string; description: string | null; capacity: number }>> {
    return apiGet(`/group/dashboard/theaters/${id}/halls`);
}

export function getGroupAvailability(hallId: number, from: string, to: string): Promise<HallSlot[]> {
    return apiGet<HallSlot[]>(`/group/dashboard/halls/${hallId}/availability`, { from, to });
}

export function getGroupReservations(params: ReservationFilters): Promise<PaginatedData<ReservationRecord>> {
    return apiGet<PaginatedData<ReservationRecord>>('/group/dashboard/reservations', params);
}

export function getGroupReservation(id: number): Promise<ReservationRecord> {
    return apiGet<ReservationRecord>(`/group/dashboard/reservations/${id}`);
}

export function createGroupReservation(payload: { hall_time_slot_id: number; idempotency_key: string }): Promise<ReservationRecord> {
    return apiPost<ReservationRecord>('/group/dashboard/reservations', payload);
}

export function cancelGroupReservation(id: number, reason?: string | null): Promise<ReservationRecord> {
    return apiPost<ReservationRecord>(`/group/dashboard/reservations/${id}/cancel`, { reason: reason || null });
}

export function getTheaterReservations(params: ReservationFilters): Promise<PaginatedData<ReservationRecord>> {
    return apiGet<PaginatedData<ReservationRecord>>('/theater/dashboard/reservations', params);
}

export function getTheaterReservation(id: number): Promise<ReservationRecord> {
    return apiGet<ReservationRecord>(`/theater/dashboard/reservations/${id}`);
}

export function approveTheaterReservation(id: number): Promise<ReservationRecord> {
    return apiPost<ReservationRecord>(`/theater/dashboard/reservations/${id}/approve`);
}

export function rejectTheaterReservation(id: number, reason: string): Promise<ReservationRecord> {
    return apiPost<ReservationRecord>(`/theater/dashboard/reservations/${id}/reject`, { reason });
}

export function cancelTheaterReservation(id: number, reason?: string | null): Promise<ReservationRecord> {
    return apiPost<ReservationRecord>(`/theater/dashboard/reservations/${id}/cancel`, { reason: reason || null });
}
