import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';
import {
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Download,
    FileText,
    History,
    LayoutDashboard,
    Mail,
    MapPin,
    RefreshCw,
    ShieldCheck,
    Ticket,
    UploadCloud,
    Users,
    XCircle,
} from 'lucide-react';
import { getAdminCounts } from '@/api/admin';
import { downloadOwnGroupDocument, resubmitGroupDocument } from '@/api/admin-applications';
import { getGroupApplication, getGroupDashboard, getTheaterDashboard } from '@/api/tenant';
import { getUserDashboard } from '@/api/user';
import type { GroupDocument } from '@/api/types';
import { Alert, Badge, Button, EmptyState, Modal, PageHeader, Skeleton, StatCard, statusTone, TableShell, Td, Th } from '@/components/ui';
import { FileDropzone } from '@/components/forms';
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';
import { formatDate, formatDateTime, humanize } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';
import { cn } from '@/lib/cn';

export function TheaterDashboardPage() {
    const { t } = useTranslation();
    const session = useAuthStore((state) => state.session);
    const dashboard = useQuery({ queryKey: ['dashboard', 'theater'], queryFn: getTheaterDashboard });

    const counts = dashboard.data?.counts ?? {};
    const tenant = dashboard.data?.tenant ?? session?.tenant;

    return (
        <TenantPage
            title={t('dashboard.theaterTitle')}
            subtitle={t('dashboard.theaterSubtitle')}
            actions={<Badge tone="success">{t('status.active')}</Badge>}
        >
            <div className="mb-6 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-900 to-brand-800 p-6 text-white shadow-soft">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-gold-200">{t('roles.theater')}</p>
                <h2 className="mt-2 text-2xl font-bold">{tenant?.name ?? session?.tenant?.name}</h2>
                <p className="mt-1 text-sm text-white/60">{tenant?.subdomain}.theaterlink</p>
                <div className="mt-5 flex flex-wrap gap-2">
                    <Link to="/theater/halls" className="btn bg-white/10 text-white hover:bg-white/15">
                        <LayoutDashboard size={16} /> {t('nav.halls')}
                    </Link>
                    <Link to="/theater/events" className="btn bg-white/10 text-white hover:bg-white/15">
                        <CalendarDays size={16} /> {t('nav.events')}
                    </Link>
                    <Link to="/theater/team" className="btn bg-white/10 text-white hover:bg-white/15">
                        <Users size={16} /> {t('dashboard.manageTeam')}
                    </Link>
                    <Link to="/theater/roles" className="btn bg-white/10 text-white hover:bg-white/15">
                        <ShieldCheck size={16} /> {t('dashboard.manageRoles')}
                    </Link>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {dashboard.isLoading ? [1, 2, 3, 4].map((item) => <Skeleton key={item} className="h-32" />) : (
                    <>
                        <StatCard label={t('dashboard.halls')} value={counts.halls_count ?? 0} icon={<LayoutDashboard size={20} />} />
                        <StatCard label={t('dashboard.events')} value={counts.events_count ?? 0} icon={<CalendarDays size={20} />} />
                        <StatCard label={t('dashboard.upcomingShows')} value={counts.upcoming_shows_count ?? 0} icon={<Clock3 size={20} />} />
                        <StatCard label={t('dashboard.pendingReservations')} value={counts.pending_reservations_count ?? 0} icon={<Ticket size={20} />} />
                    </>
                )}
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <div className="card card-body">
                    <h3 className="font-semibold text-ink-950">{t('dashboard.quickActions')}</h3>
                    <div className="mt-4 grid gap-3 sm:grid-cols-2">
                        <QuickAction to="/theater/team" icon={<Users size={18} />} title={t('nav.team')} />
                        <QuickAction to="/theater/roles" icon={<ShieldCheck size={18} />} title={t('nav.roles')} />
                        <QuickAction to="/theater/invitations" icon={<Mail size={18} />} title={t('nav.invitations')} />
                        <QuickAction to="/theater/account" icon={<FileText size={18} />} title={t('nav.account')} />
                    </div>
                </div>
                <Alert tone="info" title={t('common.comingSoon')}>
                    {t('dashboard.phaseNote')}
                </Alert>
            </div>
        </TenantPage>
    );
}

export function GroupDashboardPage() {
    const { t } = useTranslation();
    const session = useAuthStore((state) => state.session);
    const dashboard = useQuery({ queryKey: ['dashboard', 'group'], queryFn: getGroupDashboard });
    const counts = dashboard.data?.counts ?? {};
    const tenant = dashboard.data?.tenant ?? session?.tenant;

    return (
        <TenantPage
            title={t('dashboard.groupTitle')}
            subtitle={t('dashboard.groupSubtitle')}
            actions={<Badge tone="success">{t('status.active')}</Badge>}
        >
            <div className="mb-6 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-900 to-brand-800 p-6 text-white shadow-soft">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-gold-200">{t('roles.group')}</p>
                <h2 className="mt-2 text-2xl font-bold">{tenant?.name ?? session?.tenant?.name}</h2>
                <p className="mt-1 text-sm text-white/60">{tenant?.subdomain}.theaterlink</p>
                <div className="mt-5 flex flex-wrap gap-2">
                    <Link to="/group/team" className="btn bg-white/10 text-white hover:bg-white/15"><Users size={16} /> {t('dashboard.manageTeam')}</Link>
                    <Link to="/group/account" className="btn bg-white/10 text-white hover:bg-white/15"><FileText size={16} /> {t('nav.account')}</Link>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                {dashboard.isLoading ? [1, 2, 3].map((item) => <Skeleton key={item} className="h-32" />) : (
                    <>
                        <StatCard label={t('dashboard.pendingReservations')} value={counts.pending_reservations_count ?? 0} icon={<Clock3 size={20} />} />
                        <StatCard label={t('dashboard.approvedReservations')} value={counts.approved_reservations_count ?? 0} icon={<CheckCircle2 size={20} />} />
                        <StatCard label={t('dashboard.upcomingReservations')} value={counts.upcoming_reservations_count ?? 0} icon={<CalendarDays size={20} />} />
                    </>
                )}
            </div>

            <div className="mt-6">
                <Alert tone="info" title={t('common.comingSoon')}>{t('dashboard.phaseNote')}</Alert>
            </div>
        </TenantPage>
    );
}

function QuickAction({ to, icon, title }: { to: string; icon: React.ReactNode; title: string }) {
    return (
        <Link to={to} className="group flex items-center justify-between rounded-xl border border-ink-200 bg-white px-4 py-3 text-sm font-semibold text-ink-800 transition hover:border-brand-200 hover:bg-brand-50/40">
            <span className="flex items-center gap-2.5">
                <span className="text-brand-700">{icon}</span>
                {title}
            </span>
            <ArrowRight size={15} className="text-ink-400 transition group-hover:translate-x-0.5 group-hover:text-brand-700" />
        </Link>
    );
}

export function GroupApplicationPage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const application = useQuery({ queryKey: ['group', 'application'], queryFn: getGroupApplication });
    const [resubmitDocument, setResubmitDocument] = useState<GroupDocument | null>(null);
    const [replacement, setReplacement] = useState<File | null>(null);
    const [resubmitError, setResubmitError] = useState<string | null>(null);
    const [resubmitting, setResubmitting] = useState(false);
    const canResubmit = session?.permissions.includes('group.application.resubmit') ?? false;

    const app = application.data;
    const status = app?.status ?? 'pending_review';
    const statusLabel = t(`status.${status}`, { defaultValue: humanize(status) });

    if (application.isLoading) {
        return <TenantPage><Skeleton className="h-64" /></TenantPage>;
    }

    if (!app) {
        return (
            <TenantPage>
                <EmptyState
                    title={t('errors.somethingWentWrong')}
                    description={t('application.subtitle')}
                    action={<Button variant="secondary" onClick={() => void application.refetch()}><RefreshCw size={16} /> {t('common.retry')}</Button>}
                />
            </TenantPage>
        );
    }

    const bannerTone = status === 'approved' ? 'success' : status === 'rejected' ? 'danger' : status === 'requires_resubmission' ? 'warning' : 'info';
    const bannerMessage = status === 'approved'
        ? t('application.approvedMessage')
        : status === 'rejected'
            ? t('application.rejectedMessage')
            : status === 'requires_resubmission'
                ? t('application.resubmissionMessage')
                : t('application.pendingMessage');

    const download = async (document: GroupDocument) => {
        try {
            const blob = await downloadOwnGroupDocument(document.id);
            const url = URL.createObjectURL(blob);
            const anchor = document_anchor(document.name ?? "document");
            anchor.href = url;
            anchor.download = document.name ?? "document";
            window.document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            URL.revokeObjectURL(url);
        } catch (error) {
            toast.error(t('groupApplicationPage.downloadFailed'));
        }
    };

    const openResubmit = (document: GroupDocument) => {
        setResubmitDocument(document);
        setReplacement(null);
        setResubmitError(null);
    };

    const submitResubmit = async () => {
        if (!resubmitDocument || !replacement) {
            setResubmitError(t('register.permitHint'));
            return;
        }

        if (replacement.size > 4 * 1024 * 1024) {
            setResubmitError(t('errors.fileSize'));
            return;
        }

        setResubmitting(true);
        setResubmitError(null);
        try {
            await resubmitGroupDocument(resubmitDocument.id, replacement);
            toast.success(t('groupApplicationPage.resubmitSuccess'));
            setResubmitDocument(null);
            setReplacement(null);
            void queryClient.invalidateQueries({ queryKey: ['group', 'application'] });
        } catch (error) {
            setResubmitError(errorMessage(error));
        } finally {
            setResubmitting(false);
        }
    };

    return (
        <TenantPage
            title={t('application.title')}
            subtitle={t('application.subtitle')}
            actions={
                <Button variant="secondary" onClick={() => void application.refetch()} disabled={application.isFetching}>
                    <RefreshCw size={16} className={cn(application.isFetching && 'animate-spin')} /> {t('application.refresh')}
                </Button>
            }
        >
            <Alert tone={bannerTone} title={statusLabel}>{bannerMessage}</Alert>

            <div className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Info label={t('application.reference')} value={app.reference} />
                <Info label={t('application.submitted')} value={formatDate(app.submitted_at)} />
                <Info label={t('application.status')} value={statusLabel} />
                <Info label={t('application.documents')} value={`${app.documents_accepted}/${app.documents_count}`} />
            </div>

            {app.required_actions.length > 0 ? (
                <div className="mt-5">
                    <Alert tone="warning" title={t('application.requiredActions')}>
                        <ul className="list-inside list-disc space-y-1">
                            {app.required_actions.map((action) => <li key={action}>{action}</li>)}
                        </ul>
                    </Alert>
                </div>
            ) : null}

            <div className="mt-6 grid gap-4 lg:grid-cols-[1.4fr_0.6fr]">
                <div className="card overflow-hidden">
                    <div className="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                        <h2 className="font-semibold text-ink-950">{t('application.documents')}</h2>
                        <Badge tone="neutral">{app.documents_count}</Badge>
                    </div>
                    {app.documents.length === 0 ? (
                        <div className="p-5"><EmptyState title={t('application.noDocuments')} icon={<FileText size={20} />} /></div>
                    ) : (
                        <TableShell className="rounded-none border-0">
                            <table className="min-w-full">
                                <thead className="bg-ink-50">
                                    <tr>
                                        <Th>{t('common.name')}</Th>
                                        <Th>{t('common.status')}</Th>
                                        <Th>{t('application.feedback')}</Th>
                                        <Th className="text-end">{t('common.actions')}</Th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-ink-100">
                                    {app.documents.map((document: GroupDocument) => (
                                        <tr key={document.id}>
                                            <Td>
                                                <div className="flex items-center gap-2">
                                                    <FileText size={16} className="text-ink-400" />
                                                    <div>
                                                        <span className="font-medium text-ink-900">{document.name ?? humanize(document.type)}</span>
                                                        <p className="text-xs text-ink-400">{t('groupApplicationPage.versionLabel', { version: document.version })}</p>
                                                    </div>
                                                </div>
                                            </Td>
                                            <Td><DocumentStatus document={document} /></Td>
                                            <Td className="max-w-xs text-xs text-ink-500">{document.rejection_reason ?? document.admin_note ?? '—'}</Td>
                                            <Td>
                                                <div className="flex flex-wrap justify-end gap-1">
                                                    <Button size="sm" variant="ghost" onClick={() => void download(document)}><Download size={14} /> {t('common.download')}</Button>
                                                    {canResubmit && document.can_resubmit ? (
                                                        <Button size="sm" variant="secondary" onClick={() => openResubmit(document)}><UploadCloud size={14} /> {t('groupApplicationPage.resubmit')}</Button>
                                                    ) : null}
                                                </div>
                                            </Td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </TableShell>
                    )}
                </div>

                <div className="space-y-4">
                    {app.rejection_reason ? (
                        <Alert tone="danger" title={t('application.rejectionReason')}>{app.rejection_reason}</Alert>
                    ) : null}
                    {app.review_feedback ? (
                        <Alert tone="info" title={t('application.feedback')}>{app.review_feedback}</Alert>
                    ) : null}
                    <div className="card card-body">
                        <h3 className="font-semibold text-ink-950">{t('application.support')}</h3>
                        <p className="mt-1.5 text-sm text-ink-500">{t('application.supportText')}</p>
                        <div className="mt-4 space-y-2 text-sm">
                            <p className="flex items-center gap-2 text-ink-700"><Mail size={15} className="text-ink-400" /> {app.support.email}</p>
                            <p className="flex items-center gap-2 text-ink-700"><Clock3 size={15} className="text-ink-400" /> {app.support.phone}</p>
                        </div>
                    </div>
                </div>
            </div>

            <section className="card card-body mt-4">
                <div className="mb-4 flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><History size={18} /></span>
                    <h2 className="font-semibold text-ink-950">{t('groupApplicationPage.historyTitle')}</h2>
                </div>
                {app.history.length === 0 ? (
                    <p className="text-sm text-ink-500">{t('groupApplicationPage.noHistory')}</p>
                ) : (
                    <ol className="relative space-y-4 border-s border-ink-200 ps-5">
                        {app.history.map((event) => (
                            <li key={event.id} className="relative">
                                <span className="absolute -start-[1.65rem] top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-brand-500" />
                                <div className="flex flex-wrap items-center gap-2">
                                    <p className="text-sm font-semibold text-ink-900">{humanize(event.action)}</p>
                                    <Badge tone={statusTone(event.to_status)}>{t(`status.${event.to_status}`, { defaultValue: humanize(event.to_status) })}</Badge>
                                </div>
                                {event.note ? <p className="mt-1 text-xs text-ink-500">{event.note}</p> : null}
                                <p className="mt-1 text-[11px] text-ink-400">{event.actor ?? t('roles.group')} · {formatDateTime(event.created_at)}</p>
                            </li>
                        ))}
                    </ol>
                )}
            </section>

            <Modal
                open={resubmitDocument !== null}
                onClose={() => setResubmitDocument(null)}
                title={t('groupApplicationPage.resubmitTitle')}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setResubmitDocument(null)} disabled={resubmitting}>{t('common.cancel')}</Button>
                        <Button onClick={() => void submitResubmit()} loading={resubmitting} disabled={!replacement}>{t('groupApplicationPage.resubmit')}</Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <p className="text-sm leading-6 text-ink-500">{t('groupApplicationPage.resubmitHelp')}</p>
                    {resubmitError ? <Alert tone="danger">{resubmitError}</Alert> : null}
                    <FileDropzone file={replacement} onChange={setReplacement} label={t('groupApplicationPage.replacementFile')} hint={t('register.permitHint')} />
                </div>
            </Modal>
        </TenantPage>
    );
}

function document_anchor(name: string): HTMLAnchorElement {
    const anchor = document.createElement("a");
    anchor.download = name;
    return anchor;
}

function Info({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-ink-200 bg-white p-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-ink-500">{label}</p>
            <p className="mt-1.5 truncate text-sm font-semibold text-ink-900">{value}</p>
        </div>
    );
}

function DocumentStatus({ document }: { document: GroupDocument }) {
    const { t } = useTranslation();
    const labels: Record<string, string> = {
        pending: t('application.documentPending'),
        accepted: t('application.documentAccepted'),
        rejected: t('application.documentRejected'),
        requires_resubmission: t('application.documentResubmission'),
    };

    return <Badge tone={statusTone(document.status)}>{labels[document.status] ?? humanize(document.status)}</Badge>;
}

export function UserDashboardPage() {
    const { t } = useTranslation();
    const session = useAuthStore((state) => state.session);
    const dashboard = useQuery({ queryKey: ['dashboard', 'user'], queryFn: getUserDashboard });
    const user = dashboard.data?.user ?? session?.user;

    return (
        <TenantPage
            title={t('dashboard.userTitle')}
            subtitle={t('dashboard.userSubtitle')}
            actions={<Link to="/user/profile" className="btn-secondary">{t('common.profile')}</Link>}
        >
            <div className="rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-900 to-brand-800 p-6 text-white shadow-soft">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-gold-200">{t('roles.user')}</p>
                <h2 className="mt-2 text-2xl font-bold">{user?.name ?? session?.user?.name}</h2>
                <p className="mt-1 text-sm text-white/60">{user?.phone ?? session?.user?.phone}</p>
                <div className="mt-5 flex flex-wrap gap-2">
                    <Link to="/user/profile" className="btn bg-white/10 text-white hover:bg-white/15">{t('common.profile')}</Link>
                    <Link to="/user/security" className="btn bg-white/10 text-white hover:bg-white/15">{t('nav.security')}</Link>
                </div>
            </div>

            <div className="mt-6 grid gap-4 sm:grid-cols-2">
                <StatCard label={t('dashboard.bookings')} value={dashboard.data?.bookings_count ?? 0} icon={<Ticket size={20} />} />
                <StatCard label={t('dashboard.upcomingBookings')} value={dashboard.data?.upcoming_bookings?.length ?? 0} icon={<CalendarDays size={20} />} />
            </div>

            <div className="mt-6">
                <EmptyState
                    title={t('dashboard.noBookings')}
                    description={t('dashboard.userSubtitle')}
                    icon={<Ticket size={22} />}
                    action={<Link to="/user/profile" className="btn-secondary">{t('common.profile')}</Link>}
                />
            </div>
        </TenantPage>
    );
}

export function AdminDashboardPage() {
    const { t } = useTranslation();
    const session = useAuthStore((state) => state.session);
    const counts = useQuery({ queryKey: ['dashboard', 'admin'], queryFn: getAdminCounts });

    return (
        <TenantPage
            title={t('dashboard.adminTitle')}
            subtitle={t('dashboard.adminSubtitle')}
            actions={
                <>
                    <Link to="/admin/applications" className="btn-primary">{t('nav.applications')}</Link>
                    <Link to="/admin/profile" className="btn-secondary">{t('common.profile')}</Link>
                </>
            }
        >
            <div className="mb-6 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-900 to-brand-800 p-6 text-white shadow-soft">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-gold-200">{t('roles.admin')}</p>
                <h2 className="mt-2 text-2xl font-bold">{session?.admin?.name ?? t('roles.admin')}</h2>
                <p className="mt-1 text-sm text-white/60">{session?.admin?.email}</p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {counts.isLoading ? [1, 2, 3, 4, 5, 6].map((item) => <Skeleton key={item} className="h-32" />) : (
                    <>
                        <StatCard label={t('dashboard.users')} value={counts.data?.users_count ?? 0} icon={<Users size={20} />} />
                        <StatCard label={t('dashboard.theaters')} value={counts.data?.theaters_count ?? 0} icon={<LayoutDashboard size={20} />} />
                        <StatCard label={t('dashboard.groups')} value={counts.data?.theater_groups_count ?? 0} icon={<Users size={20} />} />
                        <StatCard label={t('dashboard.pendingApplications')} value={counts.data?.pending_group_applications_count ?? 0} icon={<FileText size={20} />} />
                        <StatCard label={t('dashboard.reservations')} value={counts.data?.reservations_count ?? 0} icon={<CalendarDays size={20} />} />
                        <StatCard label={t('dashboard.bookings')} value={counts.data?.bookings_count ?? 0} icon={<Ticket size={20} />} />
                    </>
                )}
            </div>

            <div className="mt-6">
                <Alert tone="info" title={t('admin.phaseNote')}>{t('dashboard.adminPlaceholder')}</Alert>
            </div>
        </TenantPage>
    );
}
