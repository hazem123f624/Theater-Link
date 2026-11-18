import { apiDelete, apiGet, apiPost, apiPut } from '@/api/client';
import type { Hall, HallBlock, HallSchedule, HallSlot, PaginatedData } from '@/api/types';

export interface HallFilters {
    search?: string;
    active?: boolean;
    page?: number;
    per_page?: number;
}

export function getHalls(params: HallFilters): Promise<PaginatedData<Hall>> {
    return apiGet<PaginatedData<Hall>>('/theater/dashboard/halls', params);
}

export function getHall(id: number): Promise<Hall> {
    return apiGet<Hall>(`/theater/dashboard/halls/${id}`);
}

export function createHall(payload: {
    name: string;
    description?: string | null;
    capacity: number;
    is_active?: boolean;
}): Promise<Hall> {
    return apiPost<Hall>('/theater/dashboard/halls', payload);
}

export function updateHall(id: number, payload: Record<string, unknown>): Promise<Hall> {
    return apiPut<Hall>(`/theater/dashboard/halls/${id}`, payload);
}

export function deleteHall(id: number): Promise<unknown> {
    return apiDelete(`/theater/dashboard/halls/${id}`);
}

export function getSchedules(hallId: number): Promise<HallSchedule[]> {
    return apiGet<HallSchedule[]>(`/theater/dashboard/halls/${hallId}/schedules`);
}

export function createSchedule(hallId: number, payload: Record<string, unknown>): Promise<HallSchedule> {
    return apiPost<HallSchedule>(`/theater/dashboard/halls/${hallId}/schedules`, payload);
}

export function updateSchedule(id: number, payload: Record<string, unknown>): Promise<HallSchedule> {
    return apiPut<HallSchedule>(`/theater/dashboard/schedules/${id}`, payload);
}

export function deleteSchedule(id: number): Promise<unknown> {
    return apiDelete(`/theater/dashboard/schedules/${id}`);
}

export function getSlots(
    hallId: number,
    params: { from?: string; to?: string; status?: string } = {},
): Promise<HallSlot[]> {
    return apiGet<HallSlot[]>(`/theater/dashboard/halls/${hallId}/slots`, params);
}

export function generateSlots(hallId: number): Promise<{ generated: number }> {
    return apiPost<{ generated: number }>(`/theater/dashboard/halls/${hallId}/slots/generate`);
}

export function getBlocks(hallId: number): Promise<HallBlock[]> {
    return apiGet<HallBlock[]>(`/theater/dashboard/halls/${hallId}/blocks`);
}

export function createBlock(
    hallId: number,
    payload: { starts_at: string; ends_at: string; reason?: string | null },
): Promise<HallBlock> {
    return apiPost<HallBlock>(`/theater/dashboard/halls/${hallId}/blocks`, payload);
}

export function deleteBlock(id: number): Promise<unknown> {
    return apiDelete(`/theater/dashboard/blocks/${id}`);
}
