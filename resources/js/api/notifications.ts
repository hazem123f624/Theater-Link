import { apiClient, apiPatch, apiPost } from '@/api/client';
import type { AppNotification, Pagination } from '@/api/types';

export interface NotificationResult {
    data: AppNotification[];
    unread_count: number;
    pagination: Pagination;
}

export async function getNotifications(): Promise<NotificationResult> {
    const response = await apiClient.get('/notifications', { params: { per_page: 20 } });
    const payload = response.data;

    return {
        data: payload.data ?? [],
        unread_count: payload.unread_count ?? 0,
        pagination: payload.pagination ?? {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: payload.data?.length ?? 0,
        },
    };
}

export function markNotificationRead(id: number): Promise<AppNotification> {
    return apiPatch<AppNotification>(`/notifications/${id}/read`);
}

export function markAllNotificationsRead(): Promise<unknown> {
    return apiPost('/notifications/read-all');
}
