import { ApiError } from '@/api/client';

export function errorMessage(error: unknown): string {
    if (error instanceof ApiError) {
        if (error.status === 0) {
            return 'Cannot reach the server. Check your connection and try again.';
        }

        return error.message || 'Something went wrong. Please try again.';
    }

    if (error instanceof Error) {
        return error.message;
    }

    return 'Something went wrong. Please try again.';
}

export function fieldError(error: unknown, field: string): string | undefined {
    if (error instanceof ApiError) {
        return error.errors[field]?.[0];
    }

    return undefined;
}

export function isUnauthorized(error: unknown): boolean {
    return error instanceof ApiError && error.status === 401;
}
