import { apiDelete, apiGet, apiPost, apiPut } from '@/api/client';
import type { EventRecord, PaginatedData, ShowRecord, TicketTierRecord } from '@/api/types';

export interface EventFilters {
    search?: string;
    status?: string;
    page?: number;
    per_page?: number;
}

export interface EventPayload {
    title_en: string;
    title_ar?: string | null;
    description_en?: string | null;
    description_ar?: string | null;
    slug?: string | null;
    category_id?: number | null;
}

function eventForm(payload: EventPayload, poster?: File | null): FormData {
    const form = new FormData();
    Object.entries(payload).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            form.append(key, String(value));
        }
    });
    if (poster) {
        form.append('poster', poster);
    }
    return form;
}

export function getEvents(params: EventFilters): Promise<PaginatedData<EventRecord>> {
    return apiGet<PaginatedData<EventRecord>>('/theater/dashboard/events', params);
}

export function getEvent(id: number): Promise<EventRecord> {
    return apiGet<EventRecord>(`/theater/dashboard/events/${id}`);
}

export function createEvent(payload: EventPayload, poster?: File | null): Promise<EventRecord> {
    return apiPost<EventRecord>('/theater/dashboard/events', eventForm(payload, poster));
}

export function updateEvent(id: number, payload: EventPayload, poster?: File | null): Promise<EventRecord> {
    const form = eventForm(payload, poster);
    form.append('_method', 'PUT');
    return apiPost<EventRecord>(`/theater/dashboard/events/${id}`, form);
}

export function publishEvent(id: number): Promise<EventRecord> {
    return apiPost<EventRecord>(`/theater/dashboard/events/${id}/publish`);
}

export function unpublishEvent(id: number): Promise<EventRecord> {
    return apiPost<EventRecord>(`/theater/dashboard/events/${id}/unpublish`);
}

export function cancelEvent(id: number): Promise<EventRecord> {
    return apiPost<EventRecord>(`/theater/dashboard/events/${id}/cancel`);
}

export function getShow(id: number): Promise<ShowRecord> {
    return apiGet<ShowRecord>(`/theater/dashboard/shows/${id}`);
}

export function getShows(eventId: number): Promise<ShowRecord[]> {
    return apiGet<ShowRecord[]>(`/theater/dashboard/events/${eventId}/shows`);
}

export function createShow(
    eventId: number,
    payload: { hall_id: number; starts_at: string; ends_at: string },
): Promise<ShowRecord> {
    return apiPost<ShowRecord>(`/theater/dashboard/events/${eventId}/shows`, payload);
}

export function updateShow(id: number, payload: Record<string, unknown>): Promise<ShowRecord> {
    return apiPut<ShowRecord>(`/theater/dashboard/shows/${id}`, payload);
}

export function cancelShow(id: number): Promise<ShowRecord> {
    return apiPost<ShowRecord>(`/theater/dashboard/shows/${id}/cancel`);
}

export function getTiers(showId: number): Promise<TicketTierRecord[]> {
    return apiGet<TicketTierRecord[]>(`/theater/dashboard/shows/${showId}/tiers`);
}

export function createTier(showId: number, payload: Record<string, unknown>): Promise<TicketTierRecord> {
    return apiPost<TicketTierRecord>(`/theater/dashboard/shows/${showId}/tiers`, payload);
}

export function updateTier(id: number, payload: Record<string, unknown>): Promise<TicketTierRecord> {
    return apiPut<TicketTierRecord>(`/theater/dashboard/tiers/${id}`, payload);
}

export function deactivateTier(id: number): Promise<TicketTierRecord> {
    return apiDelete<TicketTierRecord>(`/theater/dashboard/tiers/${id}`);
}
