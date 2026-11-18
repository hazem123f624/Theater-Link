import { useEffect, useState, type ReactNode } from 'react';
import { QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'sonner';
import { getAdminProfile, getTenantMe, getUserProfile, refreshTenantToken, refreshUserToken } from '@/api/auth';
import { ApiError } from '@/api/client';
import { queryClient } from '@/app/query-client';
import { useAuthStore } from '@/stores/auth-store';

function AuthBootstrap({ children }: { children: ReactNode }) {
    const session = useAuthStore((state) => state.session);
    const setSession = useAuthStore((state) => state.setSession);
    const clearSession = useAuthStore((state) => state.clearSession);
    const updateToken = useAuthStore((state) => state.updateToken);
    const [ready, setReady] = useState(!session?.token);

    useEffect(() => {
        if (!session?.token) {
            setReady(true);
            return;
        }

        let cancelled = false;
        setReady(false);

        const revalidate = async () => {
            try {
                if (session.actor === 'theater' || session.actor === 'group') {
                    const member = await getTenantMe();
                    if (!cancelled) {
                        setSession({
                            ...session,
                            member,
                            tenant: member.tenant ?? session.tenant,
                            permissions: member.permissions ?? [],
                        });
                    }
                } else if (session.actor === 'user') {
                    const user = await getUserProfile();
                    if (!cancelled) {
                        setSession({ ...session, user });
                    }
                } else if (session.actor === 'admin') {
                    const admin = await getAdminProfile();
                    if (!cancelled) {
                        setSession({ ...session, admin, permissions: admin.permissions ?? session.permissions });
                    }
                }
            } catch (error) {
                if (!cancelled && error instanceof ApiError && error.status === 401) {
                    clearSession();
                }
            } finally {
                if (!cancelled) {
                    setReady(true);
                }
            }
        };

        void revalidate();

        return () => {
            cancelled = true;
        };
        // The token is the session identity; profile data is refreshed once per token.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [session?.token]);

    useEffect(() => {
        if (!session?.expiresAt || session.actor === 'admin') {
            return;
        }

        const delay = Math.max(10_000, session.expiresAt - Date.now() - 120_000);
        const timer = window.setTimeout(async () => {
            try {
                const result = session.actor === 'user'
                    ? await refreshUserToken()
                    : await refreshTenantToken();
                updateToken(result.token, result.expires_in);
            } catch {
                clearSession();
            }
        }, delay);

        return () => window.clearTimeout(timer);
    }, [session?.expiresAt, session?.actor, updateToken, clearSession]);

    if (!ready) {
        return (
            <div className="flex min-h-screen items-center justify-center bg-ink-50">
                <div className="flex items-center gap-2 text-sm font-semibold text-ink-500">
                    <span className="h-2.5 w-2.5 animate-pulse rounded-full bg-brand-600" />
                    TheaterLink
                </div>
            </div>
        );
    }

    return <>{children}</>;
}

export function AppProviders({ children }: { children: ReactNode }) {
    return (
        <QueryClientProvider client={queryClient}>
            <AuthBootstrap>
                {children}
                <Toaster position="top-center" richColors closeButton />
            </AuthBootstrap>
        </QueryClientProvider>
    );
}
