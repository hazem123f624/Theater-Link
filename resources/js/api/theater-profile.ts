import { apiGet, apiPost } from '@/api/client';
import type { TheaterProfileData } from '@/api/types';

export function getTheaterProfile(): Promise<TheaterProfileData> {
    return apiGet<TheaterProfileData>('/theater/dashboard/profile');
}

export function updateTheaterProfile(
    data: Record<string, unknown>,
    logo?: File | null,
    cover?: File | null,
): Promise<TheaterProfileData> {
    const form = new FormData();
    form.append('_method', 'PUT');

    Object.entries(data).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            form.append(key, String(value));
        }
    });

    if (logo) {
        form.append('logo', logo);
    }

    if (cover) {
        form.append('cover', cover);
    }

    return apiPost<TheaterProfileData>('/theater/dashboard/profile', form);
}
