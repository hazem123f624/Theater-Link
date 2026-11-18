import { apiGet } from '@/api/client';
import type { EventCategory, HallSlot, PaginatedData, PublicEvent, PublicTheater } from '@/api/types';

export function getPublicTheaters(params: { page?: number; per_page?: number } = {}): Promise<PaginatedData<PublicTheater>> {
    return apiGet<PaginatedData<PublicTheater>>('/public/theaters', params);
}

export function getPublicTheater(id: number): Promise<PublicTheater> {
    return apiGet<PublicTheater>(`/public/theaters/${id}`);
}

export function getPublicEventCategories(): Promise<EventCategory[]> {
    return apiGet<EventCategory[]>('/public/event-categories');
}

export function getPublicEvents(params: {
    search?: string;
    theater_id?: number;
    category?: string;
    date?: string;
    page?: number;
    per_page?: number;
} = {}): Promise<PaginatedData<PublicEvent>> {
    return apiGet<PaginatedData<PublicEvent>>('/public/events', params);
}

export function getPublicEvent(slug: string): Promise<PublicEvent> {
    return apiGet<PublicEvent>(`/public/events/${slug}`);
}

export function getPublicAvailability(
    hallId: number,
    from: string,
    to: string,
): Promise<HallSlot[]> {
    return apiGet<HallSlot[]>(`/public/halls/${hallId}/availability`, { from, to });
}
