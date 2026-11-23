import { useEffect, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { toast } from 'sonner';
import {
    ArrowLeft,
    CalendarClock,
    CalendarDays,
    CheckCircle2,
    Image as ImageIcon,
    MapPin,
    Send,
    Users,
    XCircle,
} from 'lucide-react';
import {
    approveTheaterReservation,
    cancelGroupReservation,
    cancelTheaterReservation,
    createGroupReservation,
    getGroupAvailability,
    getGroupProfile,
    getGroupReservation,
    getGroupReservations,
    getGroupTheater,
    getGroupTheaters,
    getTheaterReservation,
    getTheaterReservations,
    rejectTheaterReservation,
    updateGroupProfile,
} from '@/api/reservations';
import type { HallSlot, ReservationRecord } from '@/api/types';
import {
    Alert,
    Badge,
    Button,
    EmptyState,
    Field,
    FormError,
    Input,
    Modal,
    Pagination,
    SearchInput,
    Select,
    Skeleton,
    TableShell,
    Td,
    Th,
    Textarea,
    statusTone,
} from '@/components/ui';
import { FileDropzone } from '@/components/forms';
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';
import { formatDateTime, humanize, localizedName } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';

const statuses = ['pending', 'approved', 'rejected', 'cancelled', 'expired', 'completed'];

function dateInput(days: number): string {
    const date = new Date();
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}

function idempotencyKey(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

export function GroupProfilePage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const profile = useQuery({ queryKey: ['group', 'profile'], queryFn: getGroupProfile });
    const [form, setForm] = useState({ name: '', description: '', contact_person_name: '', email: '', phone: '' });
    const [logo, setLogo] = useState<File | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!profile.data) return;
        setForm({
            name: profile.data.name ?? '',
            description: profile.data.description ?? '',
            contact_person_name: profile.data.contact_person_name ?? '',
            email: profile.data.email ?? '',
            phone: profile.data.phone ?? '',
        });
    }, [profile.data]);

    if (profile.isLoading) return <TenantPage><Skeleton className="h-80" /></TenantPage>;

    const save = async () => {
        setSaving(true); setError(null);
        try {
            await updateGroupProfile(form, logo);
            setLogo(null);
            toast.success(t('reservationOps.profile.saved'));
            void queryClient.invalidateQueries({ queryKey: ['group', 'profile'] });
        } catch (caught) { setError(errorMessage(caught)); } finally { setSaving(false); }
    };

    return (
        <TenantPage title={t('reservationOps.profile.title')} subtitle={t('reservationOps.profile.subtitle')}>
            <div className="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <section className="card card-body">
                    <div className="mb-5 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><Users size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('reservationOps.profile.title')}</h2>
                    </div>
                    <div className="space-y-4">
                        {error ? <FormError>{error}</FormError> : null}
                        <Field label={t('reservationOps.profile.name')}><Input value={form.name} onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))} /></Field>
                        <Field label={t('reservationOps.profile.description')}><Textarea value={form.description} onChange={(event) => setForm((current) => ({ ...current, description: event.target.value }))} /></Field>
                        <Field label={t('reservationOps.profile.contactPerson')}><Input value={form.contact_person_name} onChange={(event) => setForm((current) => ({ ...current, contact_person_name: event.target.value }))} /></Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label={t('reservationOps.profile.email')}><Input type="email" value={form.email} onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))} /></Field>
                            <Field label={t('reservationOps.profile.phone')}><Input value={form.phone} onChange={(event) => setForm((current) => ({ ...current, phone: event.target.value }))} /></Field>
                        </div>
                        <Button onClick={() => void save()} loading={saving}><Send size={15} /> {t('reservationOps.profile.save')}</Button>
                    </div>
                </section>
                <section className="card card-body">
                    <div className="mb-3 flex items-center gap-2 text-sm font-semibold text-ink-800"><ImageIcon size={16} /> {t('reservationOps.profile.logo')}</div>
                    {profile.data?.logo_url ? <img src={profile.data.logo_url} alt={t('reservationOps.profile.currentLogo')} className="mb-3 h-24 w-24 rounded-2xl border border-ink-200 object-cover" /> : null}
                    <FileDropzone file={logo} onChange={setLogo} label={t('reservationOps.profile.logo')} accept="image/jpeg,image/png,image/webp" hint={t('reservationOps.profile.imageHint')} />
                </section>
            </div>
        </TenantPage>
    );
}

export function GroupTheatersPage() {
    const { t } = useTranslation();
    const [page, setPage] = useState(1);
    const theaters = useQuery({ queryKey: ['group', 'theaters', page], queryFn: () => getGroupTheaters({ page, per_page: 9 }) });

    return (
        <TenantPage title={t('reservationOps.discovery.title')} subtitle={t('reservationOps.discovery.subtitle')}>
            {theaters.isLoading ? <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">{[1, 2, 3, 4, 5, 6].map((item) => <Skeleton key={item} className="h-64" />)}</div> : theaters.data && theaters.data.data.length > 0 ? (
                <>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {theaters.data.data.map((theater) => (
                            <Link key={theater.id} to={`/group/theaters/${theater.id}`} className="card overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
                                {theater.cover_url ? <img src={theater.cover_url} alt="" className="h-32 w-full object-cover" /> : <div className="flex h-32 items-center justify-center bg-brand-900 text-brand-200"><MapPin size={30} /></div>}
                                <div className="card-body">
                                    <h2 className="text-lg font-semibold text-ink-950">{theater.name}</h2>
                                    <p className="mt-1 line-clamp-2 text-sm text-ink-500">{theater.description ?? theater.address ?? '—'}</p>
                                    <div className="mt-4 flex items-center justify-between text-xs text-ink-400"><span>{theater.halls_count ?? 0} {t('reservationOps.discovery.halls')}</span><span className="font-semibold text-brand-700">{t('reservationOps.discovery.viewTheater')} →</span></div>
                                </div>
                            </Link>
                        ))}
                    </div>
                    <div className="mt-5"><Pagination page={theaters.data.pagination.current_page} lastPage={theaters.data.pagination.last_page} total={theaters.data.pagination.total} onChange={setPage} labels={{ previous: t('common.previous'), next: t('common.next'), showing: t('common.showing'), results: t('common.results'), page: t('common.page'), of: t('common.of') }} /></div>
                </>
            ) : <EmptyState title={t('reservationOps.discovery.noTheaters')} icon={<MapPin size={22} />} />}
        </TenantPage>
    );
}

export function GroupTheaterPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const { id } = useParams();
    const theaterId = Number(id);
    const [selectedHall, setSelectedHall] = useState<number | null>(null);
    const [from, setFrom] = useState(dateInput(0));
    const [to, setTo] = useState(dateInput(30));
    const [requiring, setRequiring] = useState<HallSlot | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const theater = useQuery({ queryKey: ['group', 'theater', theaterId], queryFn: () => getGroupTheater(theaterId), enabled: Number.isFinite(theaterId) });
    const availability = useQuery({
        queryKey: ['group', 'availability', selectedHall, from, to],
        queryFn: () => getGroupAvailability(selectedHall ?? 0, from, to),
        enabled: selectedHall !== null,
    });

    useEffect(() => {
        if (theater.data?.halls?.length && selectedHall === null) {
            setSelectedHall(theater.data.halls[0].id);
        }
    }, [theater.data, selectedHall]);

    if (theater.isLoading) return <TenantPage><Skeleton className="h-80" /></TenantPage>;
    if (!theater.data) return <TenantPage><EmptyState title={t('errors.notFound')} /></TenantPage>;

    const request = async () => {
        if (!requiring) return;
        setSubmitting(true);
        try {
            const reservation = await createGroupReservation({ hall_time_slot_id: requiring.id, idempotency_key: idempotencyKey() });
            toast.success(t('reservationOps.discovery.requestSuccess'));
            setRequiring(null);
            navigate(`/group/reservations/${reservation.id}`);
        } catch (error) { toast.error(errorMessage(error)); } finally { setSubmitting(false); }
    };

    return (
        <TenantPage
            title={theater.data.name}
            subtitle={[theater.data.address, localizedName(theater.data.city), localizedName(theater.data.country)].filter(Boolean).join(', ') || t('reservationOps.discovery.viewTheater')}
            actions={<Button variant="secondary" onClick={() => navigate('/group/theaters')}><ArrowLeft size={15} /> {t('reservationOps.discovery.back')}</Button>}
        >
            <div className="grid gap-4 lg:grid-cols-[0.7fr_1.3fr]">
                <section className="card card-body">
                    <h2 className="font-semibold text-ink-950">{t('reservationOps.discovery.halls')}</h2>
                    <div className="mt-3 space-y-2">
                        {theater.data.halls?.length ? theater.data.halls.map((hall) => (
                            <button key={hall.id} type="button" onClick={() => setSelectedHall(hall.id)} className={`w-full rounded-xl border p-3 text-start transition ${selectedHall === hall.id ? 'border-brand-400 bg-brand-50' : 'border-ink-200 hover:bg-ink-50'}`}>
                                <p className="font-semibold text-ink-900">{hall.name}</p>
                                <p className="text-xs text-ink-500">{hall.capacity} {t('theaterOps.halls.capacity')}</p>
                            </button>
                        )) : <p className="text-sm text-ink-500">{t('reservationOps.discovery.noHalls')}</p>}
                    </div>
                </section>
                <section className="card card-body">
                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <div><h2 className="font-semibold text-ink-950">{t('reservationOps.discovery.availabilityTitle')}</h2><p className="text-xs text-ink-500">{t('reservationOps.discovery.requestHelp')}</p></div>
                        <div className="flex gap-2"><Input type="date" value={from} onChange={(event) => setFrom(event.target.value)} className="w-auto" /><Input type="date" value={to} onChange={(event) => setTo(event.target.value)} className="w-auto" /></div>
                    </div>
                    <div className="mt-4">
                        {availability.isLoading ? <Skeleton className="h-32" /> : (availability.data?.length ?? 0) === 0 ? <EmptyState title={t('reservationOps.discovery.noSlots')} icon={<CalendarClock size={20} />} /> : (
                            <div className="grid gap-2 sm:grid-cols-2">
                                {availability.data?.map((slot) => (
                                    <div key={slot.id} className="rounded-xl border border-ink-200 p-3">
                                        <div className="flex items-center justify-between gap-2"><p className="text-sm font-semibold text-ink-900">{slot.starts_at_local}</p><span className="text-xs font-semibold text-brand-700">{slot.price} {slot.currency}</span></div>
                                        <p className="mt-1 text-xs text-ink-500">{slot.ends_at_local}</p>
                                        <Button size="sm" className="mt-3 w-full" onClick={() => setRequiring(slot)}>{t('reservationOps.discovery.request')}</Button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </section>
            </div>
            <Modal open={requiring !== null} onClose={() => setRequiring(null)} title={t('reservationOps.discovery.requestTitle')} footer={<><Button variant="secondary" onClick={() => setRequiring(null)} disabled={submitting}>{t('common.cancel')}</Button><Button onClick={() => void request()} loading={submitting}>{t('reservationOps.discovery.request')}</Button></>}>
                {requiring ? <div className="space-y-3"><Alert tone="info">{t('reservationOps.discovery.holdNotice')}</Alert><div className="rounded-xl bg-ink-50 p-4 text-sm text-ink-700"><p className="font-semibold">{requiring.starts_at_local} – {requiring.ends_at_local}</p><p className="mt-1">{t('reservationOps.discovery.price')}: {requiring.price} {requiring.currency}</p></div></div> : null}
            </Modal>
        </TenantPage>
    );
}

export function GroupReservationsPage() {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const reservations = useQuery({ queryKey: ['group', 'reservations', search, status, page], queryFn: () => getGroupReservations({ search: search || undefined, status: status || undefined, page }) });

    return (
        <TenantPage title={t('reservationOps.groupReservations.title')} subtitle={t('reservationOps.groupReservations.subtitle')} actions={<Link to="/group/theaters" className="btn-primary"><MapPin size={15} /> {t('reservationOps.discovery.title')}</Link>}>
            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div className="w-full sm:max-w-sm"><SearchInput value={search} onChange={(value) => { setSearch(value); setPage(1); }} placeholder={t('reservationOps.groupReservations.searchPlaceholder')} /></div>
                <Select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} className="w-full sm:w-44"><option value="">{t('reservationOps.groupReservations.allStatuses')}</option>{statuses.map((value) => <option key={value} value={value}>{t(`reservationOps.status.${value}`)}</option>)}</Select>
            </div>
            {reservations.isLoading ? <Skeleton className="h-64" /> : reservations.data && reservations.data.data.length > 0 ? <><ReservationTable rows={reservations.data.data} group /><div className="mt-4"><Pagination page={reservations.data.pagination.current_page} lastPage={reservations.data.pagination.last_page} total={reservations.data.pagination.total} onChange={setPage} labels={{ previous: t('common.previous'), next: t('common.next'), showing: t('common.showing'), results: t('common.results'), page: t('common.page'), of: t('common.of') }} /></div></> : <EmptyState title={t('reservationOps.groupReservations.empty')} icon={<CalendarDays size={22} />} action={<Link to="/group/theaters" className="btn-secondary">{t('reservationOps.discovery.title')}</Link>} />}
        </TenantPage>
    );
}

function ReservationTable({ rows, group = false }: { rows: ReservationRecord[]; group?: boolean }) {
    const { t } = useTranslation();
    return (
        <TableShell><table className="min-w-full"><thead className="bg-ink-50"><tr><Th>{t('reservationOps.detail.reference')}</Th><Th>{group ? t('reservationOps.groupReservations.theater') : t('reservationOps.detail.groupInfo')}</Th><Th>{t('reservationOps.groupReservations.hall')}</Th><Th>{t('reservationOps.groupReservations.slot')}</Th><Th>{t('reservationOps.groupReservations.price')}</Th><Th>{t('common.status')}</Th><Th className="text-end">{t('common.actions')}</Th></tr></thead><tbody className="divide-y divide-ink-100">{rows.map((reservation) => (
        <tr key={reservation.id} className="hover:bg-ink-50/60"><Td className="font-mono text-xs">{reservation.reference}</Td><Td><p className="font-semibold text-ink-900">{group ? reservation.theater.name : reservation.group.name}</p><p className="text-xs text-ink-500">{group ? reservation.theater.subdomain : reservation.group.contact_person_name ?? reservation.group.email}</p></Td><Td>{reservation.hall?.name ?? '—'}</Td><Td className="whitespace-nowrap text-xs">{reservation.slot?.starts_at_local ?? '—'}</Td><Td className="whitespace-nowrap text-xs">{reservation.price} {reservation.currency}</Td><Td><Badge tone={statusTone(reservation.status)}>{t(`reservationOps.status.${reservation.status}`, { defaultValue: humanize(reservation.status) })}</Badge></Td><Td><div className="flex justify-end"><Link to={group ? `/group/reservations/${reservation.id}` : `/theater/reservations/${reservation.id}`} className="btn-secondary btn-sm">{t('reservationOps.groupReservations.view')}</Link></div></Td></tr>
    ))}</tbody></table></TableShell>
    );
}

export function GroupReservationDetailPage() {
    const { t } = useTranslation();
    const { id } = useParams();
    const reservationId = Number(id);
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [cancelOpen, setCancelOpen] = useState(false);
    const [reason, setReason] = useState('');
    const [busy, setBusy] = useState(false);
    const reservation = useQuery({ queryKey: ['group', 'reservation', reservationId], queryFn: () => getGroupReservation(reservationId), enabled: Number.isFinite(reservationId) });

    if (reservation.isLoading) return <TenantPage><Skeleton className="h-80" /></TenantPage>;
    if (!reservation.data) return <TenantPage><EmptyState title={t('errors.notFound')} /></TenantPage>;
    const data = reservation.data;

    const cancel = async () => {
        setBusy(true);
        try { await cancelGroupReservation(reservationId, reason || null); toast.success(t('reservationOps.groupReservations.cancelled')); setCancelOpen(false); void queryClient.invalidateQueries({ queryKey: ['group', 'reservation', reservationId] }); }
        catch (error) { toast.error(errorMessage(error)); } finally { setBusy(false); }
    };

    return <ReservationDetailShell reservation={data} actions={['pending', 'approved'].includes(data.status) ? <Button variant="danger" onClick={() => setCancelOpen(true)}><XCircle size={15} /> {t('reservationOps.groupReservations.cancel')}</Button> : undefined} onBack={() => navigate('/group/reservations')}><CancelModal open={cancelOpen} reason={reason} setReason={setReason} busy={busy} onClose={() => setCancelOpen(false)} onConfirm={() => void cancel()} /></ReservationDetailShell>;
}

export function TheaterReservationsPage() {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const reservations = useQuery({ queryKey: ['theater', 'reservations', search, status, page], queryFn: () => getTheaterReservations({ search: search || undefined, status: status || undefined, page }) });

    return (
        <TenantPage title={t('reservationOps.groupReservations.title')} subtitle={t('reservationOps.groupReservations.subtitle')}>
            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div className="w-full sm:max-w-sm"><SearchInput value={search} onChange={(value) => { setSearch(value); setPage(1); }} placeholder={t('reservationOps.groupReservations.searchPlaceholder')} /></div>
                <Select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} className="w-full sm:w-44"><option value="">{t('reservationOps.groupReservations.allStatuses')}</option>{statuses.map((value) => <option key={value} value={value}>{t(`reservationOps.status.${value}`)}</option>)}</Select>
            </div>
            {reservations.isLoading ? <Skeleton className="h-64" /> : reservations.data && reservations.data.data.length > 0 ? <><ReservationTable rows={reservations.data.data} /><div className="mt-4"><Pagination page={reservations.data.pagination.current_page} lastPage={reservations.data.pagination.last_page} total={reservations.data.pagination.total} onChange={setPage} labels={{ previous: t('common.previous'), next: t('common.next'), showing: t('common.showing'), results: t('common.results'), page: t('common.page'), of: t('common.of') }} /></div></> : <EmptyState title={t('reservationOps.groupReservations.empty')} icon={<CalendarDays size={22} />} />}
        </TenantPage>
    );
}

export function TheaterReservationDetailPage() {
    const { t } = useTranslation();
    const { id } = useParams();
    const reservationId = Number(id);
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [dialog, setDialog] = useState<'approve' | 'reject' | 'cancel' | null>(null);
    const [reason, setReason] = useState('');
    const [busy, setBusy] = useState(false);
    const reservation = useQuery({ queryKey: ['theater', 'reservation', reservationId], queryFn: () => getTheaterReservation(reservationId), enabled: Number.isFinite(reservationId) });

    if (reservation.isLoading) return <TenantPage><Skeleton className="h-80" /></TenantPage>;
    if (!reservation.data) return <TenantPage><EmptyState title={t('errors.notFound')} /></TenantPage>;
    const data = reservation.data;
    const canApprove = session?.permissions.includes('reservations.approve') ?? false;
    const canReject = session?.permissions.includes('reservations.reject') ?? false;
    const canCancel = session?.permissions.includes('reservations.cancel') ?? false;

    const action = async () => {
        if (!dialog) return;
        if ((dialog === 'reject') && !reason.trim()) { toast.error(t('reservationOps.detail.reasonRequired')); return; }
        setBusy(true);
        try {
            if (dialog === 'approve') { await approveTheaterReservation(reservationId); toast.success(t('reservationOps.detail.approved')); }
            else if (dialog === 'reject') { await rejectTheaterReservation(reservationId, reason.trim()); toast.success(t('reservationOps.detail.rejected')); }
            else { await cancelTheaterReservation(reservationId, reason || null); toast.success(t('reservationOps.detail.cancelled')); }
            setDialog(null); setReason('');
            void queryClient.invalidateQueries({ queryKey: ['theater', 'reservation', reservationId] });
            void queryClient.invalidateQueries({ queryKey: ['theater', 'reservations'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard', 'theater'] });
        } catch (error) { toast.error(errorMessage(error)); } finally { setBusy(false); }
    };

    const actions = (
        <>
            {data.status === 'pending' && canApprove ? <Button onClick={() => setDialog('approve')}><CheckCircle2 size={15} /> {t('reservationOps.detail.approve')}</Button> : null}
            {data.status === 'pending' && canReject ? <Button variant="danger" onClick={() => setDialog('reject')}><XCircle size={15} /> {t('reservationOps.detail.reject')}</Button> : null}
            {['pending', 'approved'].includes(data.status) && canCancel ? <Button variant="secondary" onClick={() => setDialog('cancel')}>{t('reservationOps.detail.cancel')}</Button> : null}
        </>
    );

    return <ReservationDetailShell reservation={data} actions={actions} onBack={() => navigate('/theater/reservations')}>
        <Modal open={dialog !== null} onClose={() => setDialog(null)} title={dialog === 'approve' ? t('reservationOps.detail.approve') : dialog === 'reject' ? t('reservationOps.detail.reject') : t('reservationOps.detail.cancel')} footer={<><Button variant="secondary" onClick={() => setDialog(null)} disabled={busy}>{t('common.cancel')}</Button><Button variant={dialog === 'reject' ? 'danger' : 'primary'} onClick={() => void action()} loading={busy}>{t('common.confirm')}</Button></>}>
            <div className="space-y-4">
                {dialog === 'approve' ? <p className="text-sm text-ink-600">{t('reservationOps.detail.approveConfirm')}</p> : null}
                {dialog === 'reject' ? <Field label={t('reservationOps.detail.rejectReason')}><Textarea value={reason} onChange={(event) => setReason(event.target.value)} /></Field> : null}
                {dialog === 'cancel' ? <Field label={t('reservationOps.detail.cancelReason')}><Textarea value={reason} onChange={(event) => setReason(event.target.value)} /></Field> : null}
            </div>
        </Modal>
    </ReservationDetailShell>;
}

function ReservationDetailShell({ reservation, actions, onBack, children }: { reservation: ReservationRecord; actions?: React.ReactNode; onBack: () => void; children?: React.ReactNode }) {
    const { t } = useTranslation();
    const notice = reservation.status === 'approved' ? t('reservationOps.detail.approvedNotice') : reservation.status === 'rejected' ? t('reservationOps.detail.rejectedNotice') : reservation.status === 'cancelled' ? t('reservationOps.detail.cancelledNotice') : reservation.status === 'completed' ? t('reservationOps.detail.completedNotice') : reservation.status === 'expired' ? t('reservationOps.detail.expiredNotice') : t('reservationOps.detail.pendingNotice');
    const tone = reservation.status === 'approved' ? 'success' : ['rejected', 'cancelled', 'expired'].includes(reservation.status) ? 'danger' : 'info';

    return (
        <TenantPage title={t('reservationOps.detail.title')} subtitle={reservation.reference} actions={<><Button variant="secondary" onClick={onBack}><ArrowLeft size={15} /> {t('reservationOps.detail.back')}</Button>{actions}</>}>
            <Alert tone={tone}>{notice}</Alert>
            <div className="mt-5 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <section className="card card-body">
                    <h2 className="font-semibold text-ink-950">{t('reservationOps.detail.requestInfo')}</h2>
                    <dl className="mt-3 space-y-3 text-sm">
                        <InfoRow label={t('reservationOps.detail.reference')} value={reservation.reference} />
                        <InfoRow label={t('reservationOps.detail.price')} value={`${reservation.price} ${reservation.currency}`} />
                        <InfoRow label={t('reservationOps.detail.holdExpiry')} value={formatDateTime(reservation.hold_expires_at)} />
                        <InfoRow label={t('reservationOps.detail.requestedBy')} value={reservation.requested_by?.name ?? '—'} />
                    </dl>
                    {reservation.rejection_reason ? <div className="mt-4"><Alert tone="danger" title={t('reservationOps.detail.rejectReason')}>{reservation.rejection_reason}</Alert></div> : null}
                    {reservation.cancellation_reason ? <div className="mt-4"><Alert tone="warning">{reservation.cancellation_reason}</Alert></div> : null}
                </section>
                <section className="card card-body">
                    <h2 className="font-semibold text-ink-950">{t('reservationOps.detail.slotInfo')}</h2>
                    <dl className="mt-3 space-y-3 text-sm">
                        <InfoRow label={t('reservationOps.detail.theaterInfo')} value={reservation.theater.name ?? '—'} />
                        <InfoRow label={t('reservationOps.detail.groupInfo')} value={reservation.group.name ?? '—'} />
                        <InfoRow label={t('reservationOps.detail.contact')} value={[reservation.group.contact_person_name, reservation.group.email, reservation.group.phone].filter(Boolean).join(' · ') || '—'} />
                        <InfoRow label={t('reservationOps.discovery.hall')} value={reservation.hall?.name ?? '—'} />
                        <InfoRow label={t('reservationOps.groupReservations.slot')} value={reservation.slot ? `${reservation.slot.starts_at_local} – ${reservation.slot.ends_at_local}` : '—'} />
                    </dl>
                    <div className="mt-4 flex flex-wrap gap-2"><Badge tone={statusTone(reservation.status)}>{t(`reservationOps.status.${reservation.status}`)}</Badge></div>
                </section>
            </div>
            <section className="card card-body mt-4">
                <h2 className="font-semibold text-ink-950">{t('reservationOps.detail.history')}</h2>
                {reservation.history.length === 0 ? <p className="mt-2 text-sm text-ink-500">{t('reservationOps.detail.noHistory')}</p> : (
                    <ol className="mt-4 space-y-4 border-s border-ink-200 ps-5">{reservation.history.map((event) => (
                        <li key={event.id} className="relative"><span className="absolute -start-[1.65rem] top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-brand-500" /><p className="text-sm font-semibold text-ink-900">{humanize(event.action)}</p>{event.note ? <p className="mt-1 text-xs text-ink-500">{event.note}</p> : null}<p className="mt-1 text-[11px] text-ink-400">{formatDateTime(event.created_at)}</p></li>
                    ))}</ol>
                )}
            </section>
            {children}
        </TenantPage>
    );
}

function InfoRow({ label, value }: { label: string; value: string }) {
    return <div className="flex items-start justify-between gap-4 border-b border-ink-100 pb-2 last:border-0 last:pb-0"><dt className="text-ink-500">{label}</dt><dd className="text-end font-medium text-ink-900">{value}</dd></div>;
}

function CancelModal({ open, reason, setReason, busy, onClose, onConfirm }: { open: boolean; reason: string; setReason: (value: string) => void; busy: boolean; onClose: () => void; onConfirm: () => void }) {
    const { t } = useTranslation();
    return <Modal open={open} onClose={onClose} title={t('reservationOps.detail.cancel')} footer={<><Button variant="secondary" onClick={onClose} disabled={busy}>{t('common.cancel')}</Button><Button variant="danger" onClick={onConfirm} loading={busy}>{t('common.confirm')}</Button></>}><Field label={t('reservationOps.detail.cancelReason')}><Textarea value={reason} onChange={(event) => setReason(event.target.value)} /></Field></Modal>;
}
