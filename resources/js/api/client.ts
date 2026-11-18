import axios, { AxiosError, type AxiosRequestConfig } from 'axios';
import { getStoredLanguage } from '@/lib/language';
import { loginPathForActor, useAuthStore } from '@/stores/auth-store';
import type { ApiErrorPayload, ApiEnvelope } from '@/api/types';

export class ApiError extends Error {
    status: number;
    errors: Record<string, string[]>;

    constructor(message: string, status = 500, errors: Record<string, string[]> = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }
}

export const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    timeout: 30_000,
});

apiClient.interceptors.request.use((config) => {
    const session = useAuthStore.getState().session;
    const language = getStoredLanguage();
    const headers = config.headers ?? {};

    headers['Accept-Language'] = language;

    if (session?.token) {
        headers.Authorization = `Bearer ${session.token}`;
    }

    if (session?.tenant) {
        headers['X-Subdomain'] = session.tenant.subdomain;
        headers['X-Tenant'] = session.tenant.ulid;
    }

    const apiToken = import.meta.env.VITE_API_TOKEN;
    if (apiToken) {
        headers['X-API-Token'] = apiToken;
    }

    config.headers = headers;
    return config;
});

apiClient.interceptors.response.use(
    (response) => response,
    (error: AxiosError<ApiErrorPayload>) => {
        const payload = error.response?.data;
        const status = error.response?.status ?? 500;
        const apiError = new ApiError(
            payload?.message ?? (status === 0 ? 'Network error. Please try again.' : 'Something went wrong.'),
            status,
            payload?.errors ?? {},
        );

        const requestUrl = error.config?.url ?? '';
        const isAuthRequest = /(?:login|register|verify|resend|forget|forgot|reset-password)/.test(requestUrl);
        const session = useAuthStore.getState().session;

        if (status === 401 && session && !isAuthRequest) {
            const actor = session.actor;
            useAuthStore.getState().clearSession();
            if (typeof window !== 'undefined' && !window.location.pathname.endsWith(loginPathForActor(actor))) {
                window.location.assign(loginPathForActor(actor));
            }
        }

        return Promise.reject(apiError);
    },
);

export async function apiRequest<T>(config: AxiosRequestConfig): Promise<T> {
    const response = await apiClient.request<ApiEnvelope<T> | T>(config);
    const payload = response.data;

    if (payload && typeof payload === 'object' && 'data' in payload) {
        const envelope = payload as ApiEnvelope<T> & {
            pagination?: {
                current_page: number;
                last_page: number;
                per_page: number;
                total: number;
            };
        };

        // The API exposes pagination as a top-level sibling of `data` because
        // of its response envelope. Normalize it for page-aware callers.
        if (Array.isArray(envelope.data) && envelope.pagination) {
            return {
                data: envelope.data,
                pagination: envelope.pagination,
            } as T;
        }

        return envelope.data;
    }

    return payload as T;
}

export function apiGet<T>(url: string, params?: object): Promise<T> {
    return apiRequest<T>({ method: 'GET', url, params });
}

export function apiPost<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    return apiRequest<T>({ method: 'POST', url, data, ...config });
}

export function apiPut<T>(url: string, data?: unknown): Promise<T> {
    return apiRequest<T>({ method: 'PUT', url, data });
}

export function apiPatch<T>(url: string, data?: unknown): Promise<T> {
    return apiRequest<T>({ method: 'PATCH', url, data });
}

export function apiDelete<T>(url: string): Promise<T> {
    return apiRequest<T>({ method: 'DELETE', url });
}
