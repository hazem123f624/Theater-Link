import { apiGet, apiPost } from '@/api/client';
import type {
    BookingRecord,
    PaginatedData,
    PublicShowTiersResponse,
} from '@/api/types';

export interface BookingFilters {
    status?: string;
    scope?: 'upcoming' | 'past';
    search?: string;
    page?: number;
    per_page?: number;
}

export interface CreateBookingPayload {
    show_id: number;
    idempotency_key: string;
    items: Array<{ ticket_tier_id: number; quantity: number }>;
}

export function getPublicShowTiers(showId: number): Promise<PublicShowTiersResponse> {
    return apiGet<PublicShowTiersResponse>(`/public/shows/${showId}/tiers`);
}

export function getUserBookings(params: BookingFilters = {}): Promise<PaginatedData<BookingRecord>> {
    return apiGet<PaginatedData<BookingRecord>>('/user/bookings', params);
}

export function getUserBooking(id: number): Promise<BookingRecord> {
    return apiGet<BookingRecord>(`/user/bookings/${id}`);
}

export function createUserBooking(payload: CreateBookingPayload): Promise<BookingRecord> {
    return apiPost<BookingRecord>('/user/bookings', payload);
}

export function cancelUserBooking(id: number, reason?: string | null): Promise<BookingRecord> {
    return apiPost<BookingRecord>(`/user/bookings/${id}/cancel`, { reason: reason || null });
}

export function getTheaterBookings(params: BookingFilters = {}): Promise<PaginatedData<BookingRecord>> {
    return apiGet<PaginatedData<BookingRecord>>('/theater/dashboard/bookings', params);
}

export function getTheaterBooking(id: number): Promise<BookingRecord> {
    return apiGet<BookingRecord>(`/theater/dashboard/bookings/${id}`);
}

export function cancelTheaterBooking(id: number, reason: string): Promise<BookingRecord> {
    return apiPost<BookingRecord>(`/theater/dashboard/bookings/${id}/cancel`, { reason });
}

export function validateTicket(qrToken: string): Promise<{ id: number; ticket_number: string; status: string; tier_name: string | null }> {
    return apiPost('/theater/dashboard/tickets/validate', { qr_token: qrToken });
}
