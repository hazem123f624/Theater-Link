import { create } from 'zustand';
import type { Actor, AdminProfile, TenantInfo, TenantMember, UserProfile } from '@/api/types';

const STORAGE_KEY = 'theaterlink.session';

export interface Session {
    actor: Actor;
    token: string;
    expiresAt: number | null;
    member?: TenantMember;
    tenant?: TenantInfo;
    permissions: string[];
    admin?: AdminProfile;
    user?: UserProfile;
}

interface AuthState {
    session: Session | null;
    setSession: (session: Session) => void;
    updateToken: (token: string, expiresIn?: number | null) => void;
    clearSession: () => void;
    hasPermission: (permission: string) => boolean;
}

function readStoredSession(): Session | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = window.sessionStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as Session;
        return parsed?.token ? parsed : null;
    } catch {
        return null;
    }
}

function persistSession(session: Session | null): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (!session) {
        window.sessionStorage.removeItem(STORAGE_KEY);
        return;
    }

    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(session));
}

export function tenantSession(actor: 'theater' | 'group', member: TenantMember): Session {
    return {
        actor,
        token: member.token ?? '',
        expiresAt: member.expires_in ? Date.now() + member.expires_in * 1000 : null,
        member,
        tenant: member.tenant ?? undefined,
        permissions: member.permissions ?? [],
    };
}

export function userSession(user: UserProfile): Session {
    return {
        actor: 'user',
        token: user.token ?? '',
        expiresAt: user.expires_in ? Date.now() + user.expires_in * 1000 : null,
        user,
        permissions: [],
    };
}

export function adminSession(admin: AdminProfile): Session {
    return {
        actor: 'admin',
        token: admin.token ?? '',
        expiresAt: admin.expires_in ? Date.now() + admin.expires_in * 1000 : null,
        admin,
        permissions: admin.permissions ?? [],
    };
}

export const useAuthStore = create<AuthState>((set, get) => ({
    session: readStoredSession(),
    setSession: (session) => {
        persistSession(session);
        set({ session });
    },
    updateToken: (token, expiresIn) => {
        const current = get().session;
        if (!current) {
            return;
        }

        const next: Session = {
            ...current,
            token,
            expiresAt: expiresIn ? Date.now() + expiresIn * 1000 : current.expiresAt,
        };
        persistSession(next);
        set({ session: next });
    },
    clearSession: () => {
        persistSession(null);
        set({ session: null });
    },
    hasPermission: (permission) => get().session?.permissions.includes(permission) ?? false,
}));

export function loginPathForActor(actor: Actor): string {
    return `/${actor}/login`;
}

export function homePathForSession(session: Session): string {
    if (session.actor === 'group' && session.tenant?.status !== 'active') {
        return '/group/application';
    }

    return `/${session.actor}`;
}
