import { useState, type ReactNode } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import {
    Building2,
    CalendarDays,
    ChevronDown,
    ClipboardList,
    Compass,
    FileText,
    Globe2,
    LayoutDashboard,
    LogOut,
    Menu,
    ShieldCheck,
    Sparkles,
    Ticket,
    UserRound,
    Users,
    X,
} from 'lucide-react';
import { logout } from '@/api/auth';
import { NotificationBell } from '@/components/notification-bell';
import { Badge, Button, ConfirmDialog } from '@/components/ui';
import { cn } from '@/lib/cn';
import { initials } from '@/lib/format';
import { setAppLanguage } from '@/lib/i18n';
import { getStoredLanguage, type Language } from '@/lib/language';
import { homePathForSession, loginPathForActor, useAuthStore } from '@/stores/auth-store';

interface NavItem {
    to: string;
    label: string;
    icon: ReactNode;
    permission?: string;
    end?: boolean;
}

function BrandMark({ compact = false }: { compact?: boolean }) {
    return (
        <span className="inline-flex items-center gap-2.5">
            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-700 text-gold-200 shadow-sm">
                <Sparkles size={19} aria-hidden="true" />
            </span>
            {!compact ? (
                <span className="text-lg font-bold tracking-tight text-ink-950">
                    Theater<span className="text-brand-700">Link</span>
                </span>
            ) : null}
        </span>
    );
}

export function LanguageSwitch({ dark = false }: { dark?: boolean }) {
    const { i18n } = useTranslation();
    const current = (i18n.language as Language) === 'ar' ? 'ar' : 'en';

    return (
        <div className={cn('inline-flex rounded-xl border p-0.5', dark ? 'border-white/20 bg-white/10' : 'border-ink-200 bg-white')}>
            {(['en', 'ar'] as Language[]).map((language) => (
                <button
                    key={language}
                    type="button"
                    onClick={() => setAppLanguage(language)}
                    className={cn(
                        'rounded-lg px-2.5 py-1.5 text-xs font-semibold transition',
                        current === language
                            ? dark ? 'bg-white text-brand-900' : 'bg-brand-700 text-white'
                            : dark ? 'text-white/75 hover:text-white' : 'text-ink-500 hover:text-ink-900',
                    )}
                >
                    {language === 'en' ? 'EN' : 'AR'}
                </button>
            ))}
        </div>
    );
}

export function AuthLayout({
    title,
    subtitle,
    children,
    footer,
}: {
    title: string;
    subtitle?: string;
    children: ReactNode;
    footer?: ReactNode;
}) {
    const { t } = useTranslation();
    const navigate = useNavigate();

    return (
        <div className="min-h-screen bg-ink-50 lg:grid lg:grid-cols-[1.05fr_1fr]">
            <aside className="relative hidden overflow-hidden bg-brand-900 px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div className="surface-grid absolute inset-0 opacity-60" aria-hidden="true" />
                <div className="relative">
                    <button type="button" onClick={() => navigate('/')} className="inline-flex items-center gap-2.5 text-start">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-gold-200">
                            <Sparkles size={21} />
                        </span>
                        <span className="text-xl font-bold tracking-tight">TheaterLink</span>
                    </button>
                    <div className="mt-20 max-w-lg">
                        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-gold-200">{t('landing.eyebrow')}</p>
                        <h1 className="mt-5 text-4xl font-bold leading-tight xl:text-5xl">{t('landing.title')}</h1>
                        <p className="mt-5 text-base leading-7 text-white/70">{t('landing.subtitle')}</p>
                    </div>
                </div>
                <div className="relative flex items-center justify-between text-xs text-white/60">
                    <span>© {new Date().getFullYear()} TheaterLink</span>
                    <a href="/docs" className="underline decoration-white/30 underline-offset-4 hover:text-white">{t('landing.docs')}</a>
                </div>
            </aside>

            <main className="flex min-h-screen flex-col">
                <div className="flex items-center justify-between px-5 py-5 sm:px-8">
                    <div className="lg:hidden"><BrandMark /></div>
                    <div className="ms-auto flex items-center gap-3">
                        <LanguageSwitch />
                        <button type="button" onClick={() => navigate('/')} className="text-sm font-semibold text-ink-500 hover:text-ink-900">
                            {t('common.back')}
                        </button>
                    </div>
                </div>
                <div className="flex flex-1 items-center justify-center px-5 pb-12 pt-4 sm:px-8">
                    <div className="w-full max-w-md">
                        <div className="mb-7">
                            <h1 className="text-2xl font-bold text-ink-950 sm:text-3xl">{title}</h1>
                            {subtitle ? <p className="mt-2 text-sm leading-6 text-ink-500">{subtitle}</p> : null}
                        </div>
                        {children}
                        {footer ? <div className="mt-6 text-center text-sm text-ink-500">{footer}</div> : null}
                    </div>
                </div>
            </main>
        </div>
    );
}

export function DashboardLayout() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const location = useLocation();
    const session = useAuthStore((state) => state.session);
    const clearSession = useAuthStore((state) => state.clearSession);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [profileOpen, setProfileOpen] = useState(false);
    const [logoutOpen, setLogoutOpen] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);
    const language = getStoredLanguage();

    if (!session) {
        return null;
    }

    const actor = session.actor;
    const permissions = session.permissions;
    const can = (permission?: string) => !permission || permissions.includes(permission);
    const isPendingGroup = actor === 'group' && session.tenant?.status !== 'active';

    const nav: NavItem[] = actor === 'admin'
        ? [
            { to: '/admin', label: t('nav.dashboard'), icon: <LayoutDashboard size={18} />, end: true },
            { to: '/admin/applications', label: t('nav.applications'), icon: <ClipboardList size={18} />, permission: 'group_applications-fetch' },
            { to: '/admin/profile', label: t('nav.profile'), icon: <ShieldCheck size={18} /> },
        ]
        : actor === 'user'
            ? [
                { to: '/user', label: t('nav.dashboard'), icon: <LayoutDashboard size={18} />, end: true },
                { to: '/user/bookings', label: t('nav.bookings'), icon: <Ticket size={18} /> },
                { to: '/user/profile', label: t('nav.profile'), icon: <UserRound size={18} /> },
                { to: '/user/security', label: t('nav.security'), icon: <ShieldCheck size={18} /> },
            ]
            : actor === 'group' && isPendingGroup
                ? [
                    { to: '/group/application', label: t('nav.application'), icon: <FileText size={18} />, end: true },
                    { to: '/group/account', label: t('nav.account'), icon: <UserRound size={18} /> },
                ]
                : [
                    { to: `/${actor}`, label: t('nav.dashboard'), icon: <LayoutDashboard size={18} />, end: true },
                    ...(actor === 'theater' ? [
                        { to: '/theater/halls', label: t('nav.halls'), icon: <Building2 size={18} />, permission: 'halls.fetch' },
                        { to: '/theater/events', label: t('nav.events'), icon: <CalendarDays size={18} />, permission: 'events.fetch' },
                        { to: '/theater/reservations', label: t('nav.reservations'), icon: <Ticket size={18} />, permission: 'reservations.fetch' },
                        { to: '/theater/bookings', label: t('nav.bookings'), icon: <Ticket size={18} />, permission: 'bookings.fetch' },
                    ] : []),
                    ...(actor === 'group' ? [
                        { to: '/group/theaters', label: t('nav.discover'), icon: <Compass size={18} />, permission: 'reservations.fetch' },
                        { to: '/group/reservations', label: t('nav.reservations'), icon: <Ticket size={18} />, permission: 'reservations.fetch' },
                    ] : []),
                    { to: `/${actor}/team`, label: t('nav.team'), icon: <Users size={18} />, permission: 'team.fetch' },
                    { to: `/${actor}/roles`, label: t('nav.roles'), icon: <ShieldCheck size={18} />, permission: 'roles.fetch' },
                    { to: `/${actor}/invitations`, label: t('nav.invitations'), icon: <CalendarDays size={18} />, permission: 'team.fetch' },
                    { to: `/${actor}/account`, label: t('nav.account'), icon: <UserRound size={18} /> },
                ];

    const visibleNav = nav.filter((item) => can(item.permission));
    const displayName = session.member?.name ?? session.admin?.name ?? session.user?.name ?? t('common.account');
    const subtitle = session.tenant?.name
        ?? (actor === 'admin' ? t('roles.admin') : actor === 'user' ? t('roles.user') : '');

    const onLogout = async () => {
        setLoggingOut(true);
        try {
            await logout(actor);
        } catch {
            // Local logout must still succeed if the token has already expired.
        } finally {
            clearSession();
            setLoggingOut(false);
            navigate(loginPathForActor(actor), { replace: true });
        }
    };

    const sidebar = (
        <div className="flex h-full flex-col">
            <div className="flex h-16 items-center justify-between border-b border-white/10 px-4">
                <button type="button" onClick={() => navigate(homePathForSession(session))} className="inline-flex items-center gap-2.5">
                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-gold-200">
                        <Sparkles size={18} />
                    </span>
                    <span className="text-base font-bold text-white">TheaterLink</span>
                </button>
                <button type="button" className="rounded-lg p-1.5 text-white/60 hover:bg-white/10 hover:text-white lg:hidden" onClick={() => setMobileOpen(false)} aria-label="Close menu">
                    <X size={18} />
                </button>
            </div>

            <div className="border-b border-white/10 px-4 py-4">
                <p className="truncate text-sm font-semibold text-white">{subtitle || displayName}</p>
                {session.tenant ? (
                    <p className="mt-0.5 truncate text-xs text-white/50">{session.tenant.subdomain}.theaterlink</p>
                ) : (
                    <p className="mt-0.5 text-xs text-white/50">{actor === 'admin' ? t('nav.administration') : t('roles.user')}</p>
                )}
            </div>

            <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Primary">
                {visibleNav.map((item) => (
                    <NavLink
                        key={item.to}
                        to={item.to}
                        end={item.end}
                        onClick={() => setMobileOpen(false)}
                        className={({ isActive }) => cn(
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                            isActive ? 'bg-white text-brand-900 shadow-sm' : 'text-white/70 hover:bg-white/10 hover:text-white',
                        )}
                    >
                        {item.icon}
                        <span>{item.label}</span>
                    </NavLink>
                ))}
            </nav>

            <div className="space-y-3 border-t border-white/10 p-4">
                <div className="flex items-center justify-between gap-2">
                    <span className="text-xs text-white/50">{t('common.language')}</span>
                    <LanguageSwitch dark />
                </div>
                <button
                    type="button"
                    onClick={() => setLogoutOpen(true)}
                    className="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-white/65 transition hover:bg-white/10 hover:text-white"
                >
                    <LogOut size={16} />
                    {t('common.logout')}
                </button>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-ink-50">
            <aside className="fixed inset-y-0 start-0 z-30 hidden w-64 bg-brand-950 lg:block">{sidebar}</aside>

            {mobileOpen ? (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div className="absolute inset-0 bg-ink-950/50" onClick={() => setMobileOpen(false)} />
                    <aside className="absolute inset-y-0 start-0 w-72 bg-brand-950 shadow-2xl">{sidebar}</aside>
                </div>
            ) : null}

            <div className="lg:ps-64">
                <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-ink-200/80 bg-white/85 px-4 backdrop-blur sm:px-6">
                    <button
                        type="button"
                        onClick={() => setMobileOpen(true)}
                        className="rounded-lg p-2 text-ink-500 hover:bg-ink-100 hover:text-ink-900 lg:hidden"
                        aria-label="Open menu"
                    >
                        <Menu size={20} />
                    </button>

                    <div className="hidden items-center gap-2 text-sm text-ink-500 sm:flex">
                        <Globe2 size={15} />
                        <span>{subtitle}</span>
                    </div>

                    <div className="ms-auto flex items-center gap-2">
                        <NotificationBell />
                        {session.tenant?.status ? (
                            <Badge tone={session.tenant.status === 'active' ? 'success' : 'warning'}>
                                {t(`status.${session.tenant.status}`, { defaultValue: session.tenant.status.replaceAll('_', ' ') })}
                            </Badge>
                        ) : null}
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setProfileOpen((open) => !open)}
                                className="flex items-center gap-2 rounded-xl px-1.5 py-1.5 hover:bg-ink-100"
                                aria-expanded={profileOpen}
                            >
                                <span className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-700 text-xs font-bold text-white">
                                    {initials(displayName)}
                                </span>
                                <span className="hidden max-w-36 truncate text-sm font-semibold text-ink-800 sm:block">{displayName}</span>
                                <ChevronDown size={14} className="hidden text-ink-400 sm:block" />
                            </button>
                            {profileOpen ? (
                                <>
                                    <button type="button" className="fixed inset-0 z-10 cursor-default" onClick={() => setProfileOpen(false)} aria-label="Close menu" />
                                    <div className="absolute end-0 z-20 mt-2 w-56 rounded-xl border border-ink-200 bg-white p-1.5 shadow-xl">
                                        <div className="border-b border-ink-100 px-3 py-2">
                                            <p className="truncate text-sm font-semibold text-ink-900">{displayName}</p>
                                            <p className="truncate text-xs text-ink-500">{session.member?.email ?? session.admin?.email ?? session.user?.phone}</p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setProfileOpen(false);
                                                navigate(actor === 'admin' ? '/admin/profile' : actor === 'user' ? '/user/profile' : `/${actor}/account`);
                                            }}
                                            className="mt-1 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-ink-700 hover:bg-ink-50"
                                        >
                                            <UserRound size={15} /> {t('common.profile')}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => { setProfileOpen(false); setLogoutOpen(true); }}
                                            className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50"
                                        >
                                            <LogOut size={15} /> {t('common.logout')}
                                        </button>
                                    </div>
                                </>
                            ) : null}
                        </div>
                    </div>
                </header>

                <main className={cn('min-h-[calc(100vh-4rem)]', location.pathname.includes('/login') && 'bg-ink-50')}>
                    <Outlet />
                </main>
            </div>

            <ConfirmDialog
                open={logoutOpen}
                title={t('common.logout')}
                description={t('auth.logoutConfirm')}
                confirmLabel={t('common.logout')}
                cancelLabel={t('common.cancel')}
                loading={loggingOut}
                onClose={() => setLogoutOpen(false)}
                onConfirm={() => void onLogout()}
            />
        </div>
    );
}

export function TenantPage({
    children,
    title,
    subtitle,
    actions,
}: {
    children: ReactNode;
    title?: string;
    subtitle?: string;
    actions?: ReactNode;
}) {
    return (
        <div className="page-shell">
            {title ? (
                <header className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-ink-950 sm:text-3xl">{title}</h1>
                        {subtitle ? <p className="mt-1.5 text-sm text-ink-500">{subtitle}</p> : null}
                    </div>
                    {actions ? <div className="flex flex-wrap gap-2">{actions}</div> : null}
                </header>
            ) : null}
            {children}
        </div>
    );
}

export function CatalogLayout() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const session = useAuthStore((state) => state.session);

    return (
        <div className="min-h-screen bg-ink-50">
            <header className="sticky top-0 z-20 border-b border-ink-200/80 bg-white/90 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6">
                    <button type="button" onClick={() => navigate('/')} className="shrink-0">
                        <BrandMark />
                    </button>
                    <nav className="ms-4 hidden items-center gap-5 text-sm font-medium text-ink-600 sm:flex">
                        <NavLink to="/theaters" className={({ isActive }) => isActive ? 'text-brand-700' : 'hover:text-ink-950'}>{t('theaterOps.public.theatersTitle')}</NavLink>
                        <NavLink to="/events" className={({ isActive }) => isActive ? 'text-brand-700' : 'hover:text-ink-950'}>{t('theaterOps.public.eventsTitle')}</NavLink>
                    </nav>
                    <div className="ms-auto flex items-center gap-2">
                        <LanguageSwitch />
                        {session ? (
                            <Button size="sm" onClick={() => navigate(homePathForSession(session))}>{t('theaterOps.public.dashboard')}</Button>
                        ) : (
                            <Button size="sm" variant="secondary" onClick={() => navigate('/theater/login')}>{t('theaterOps.public.login')}</Button>
                        )}
                    </div>
                </div>
            </header>
            <main><Outlet /></main>
            <footer className="border-t border-ink-200 bg-white">
                <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-xs text-ink-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <span>© {new Date().getFullYear()} TheaterLink</span>
                    <div className="flex items-center gap-4">
                        <NavLink to="/theaters" className="hover:text-ink-800">{t('theaterOps.public.theatersTitle')}</NavLink>
                        <NavLink to="/events" className="hover:text-ink-800">{t('theaterOps.public.eventsTitle')}</NavLink>
                        <a href="/docs" className="hover:text-ink-800">{t('landing.docs')}</a>
                    </div>
                </div>
            </footer>
        </div>
    );
}

export function PublicHeader() {
    const { t } = useTranslation();
    return (
        <header className="absolute inset-x-0 top-0 z-20 flex items-center justify-between px-5 py-5 sm:px-8">
            <BrandMark />
            <div className="flex items-center gap-3">
                <LanguageSwitch dark />
                <a href="/docs" className="hidden text-sm font-semibold text-white/70 hover:text-white sm:inline">{t('landing.docs')}</a>
            </div>
        </header>
    );
}

export function TicketIcon() {
    return <Ticket size={20} />;
}
