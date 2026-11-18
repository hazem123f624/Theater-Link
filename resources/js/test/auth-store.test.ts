import { beforeEach, describe, expect, it } from 'vitest';
import type { TenantMember, UserProfile } from '@/api/types';
import { homePathForSession, tenantSession, userSession, useAuthStore } from '@/stores/auth-store';

const tenantMember: TenantMember = {
    id: 1,
    name: 'Owner',
    email: 'owner@example.test',
    phone: '+966500000001',
    is_owner: true,
    is_active: true,
    token: 'tenant-token',
    expires_in: 3600,
    tenant: {
        id: 1,
        ulid: '01JTESTTENANT',
        type: 'group',
        name: 'Smoke Group',
        subdomain: 'smoke-group',
        status: 'pending_review',
    },
    roles: [],
    permissions: ['team.fetch'],
    last_login_at: null,
};

const user: UserProfile = {
    id: 1,
    name: 'Audience',
    email: 'audience@example.test',
    phone: '+966500000002',
    country_code: '+966',
    avatar: null,
    postal_code: null,
    main_address: null,
    is_active: true,
    created_at: null,
    token: 'user-token',
    expires_in: 3600,
    last_login_at: null,
};

describe('auth session helpers', () => {
    beforeEach(() => {
        useAuthStore.getState().clearSession();
    });

    it('creates a tenant session with permissions and tenant context', () => {
        const session = tenantSession('group', tenantMember);
        expect(session.token).toBe('tenant-token');
        expect(session.tenant?.subdomain).toBe('smoke-group');
        expect(session.permissions).toContain('team.fetch');
    });

    it('routes a pending group to its application page', () => {
        const session = tenantSession('group', tenantMember);
        expect(homePathForSession(session)).toBe('/group/application');
    });

    it('stores and clears a user session', () => {
        useAuthStore.getState().setSession(userSession(user));
        expect(useAuthStore.getState().session?.actor).toBe('user');
        useAuthStore.getState().clearSession();
        expect(useAuthStore.getState().session).toBeNull();
    });
});
