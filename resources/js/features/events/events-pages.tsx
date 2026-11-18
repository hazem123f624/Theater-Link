import { useEffect, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { toast } from 'sonner';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    ExternalLink,
    Eye,
    EyeOff,
    Image as ImageIcon,
    Pencil,
    Plus,
    Ticket,
    Trash2,
    XCircle,
} from 'lucide-react';
import {
    cancelEvent,
    cancelShow,
    createEvent,
    createShow,
    createTier,
    deactivateTier,
    getEvent,
    getEvents,
    getShow,
    getShows,
    getTiers,
    publishEvent,
    unpublishEvent,
    updateEvent,
    updateShow,
    updateTier,
} from '@/api/events';
import { getHalls } from '@/api/halls';
import { getPublicEventCategories } from '@/api/public';
import type { EventCategory, EventRecord, Hall, ShowRecord, TicketTierRecord } from '@/api/types';
import {
    Badge,
    Button,
    ConfirmDialog,
    EmptyState,
    Field,
    FormError,
    Input,
    Modal,
    PageHeader,
    Select,
    Skeleton,
    TableShell,
    Td,
    Th,
    Textarea,
} from '@/components/ui';
import { FileDropzone } from '@/components/forms';
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';
import { formatDateTime } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';

function toLocalInput(value: string | null | undefined): string {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (number: number) => String(number).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function toIso(value: string): string {
    return value ? new Date(value).toISOString() : '';
}

function eventStatusTone(status: string): 'success' | 'warning' | 'danger' | 'neutral' {
    return status === 'published' ? 'success' : status === 'cancelled' ? 'danger' : 'neutral';
}

export function EventsPage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [search, setSearch] = useState('');
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<EventRecord | null>(null);
    const canCreate = session?.permissions.includes('events.create') ?? false;
    const events = useQuery({ queryKey: ['theater', 'events', search], queryFn: () => getEvents({ search }) });

    const refresh = () => void queryClient.invalidateQueries({ queryKey: ['theater', 'events'] });

    return (
        <TenantPage
            title={t('theaterOps.events.title')}
            subtitle={t('theaterOps.events.subtitle')}
            actions={canCreate ? <Button onClick={() => { setEditing(null); setFormOpen(true); }}><Plus size={16} /> {t('theaterOps.events.add')}</Button> : undefined}
        >
            <div className="mb-4 max-w-md">
                <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t('theaterOps.public.searchPlaceholder')} />
            </div>

            {events.isLoading ? (
                <div className="space-y-3">{[1, 2, 3].map((item) => <Skeleton key={item} className="h-24" />)}</div>
            ) : events.data && events.data.data.length > 0 ? (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {events.data.data.map((event) => (
                        <article key={event.id} className="card overflow-hidden">
                            {event.poster_url ? <img src={event.poster_url} alt={event.title_en ?? ''} className="h-40 w-full object-cover" /> : <div className="flex h-40 items-center justify-center bg-brand-50 text-brand-300"><ImageIcon size={36} /></div>}
                            <div className="p-5">
                                <div className="flex items-center justify-between gap-2">
                                    <Badge tone={eventStatusTone(event.status)}>{t(`theaterOps.events.status${event.status[0].toUpperCase() + event.status.slice(1)}`)}</Badge>
                                    {event.category ? <span className="text-xs text-ink-500">{event.category.name}</span> : null}
                                </div>
                                <h2 className="mt-3 text-lg font-semibold text-ink-950">{event.title_en ?? t('theaterOps.events.title')}</h2>
                                {event.title_ar ? <p className="text-sm text-ink-500" dir="rtl">{event.title_ar}</p> : null}
                                <p className="mt-2 line-clamp-2 text-sm leading-6 text-ink-500">{event.description_en ?? '—'}</p>
                                <div className="mt-4 flex items-center justify-between text-xs text-ink-400">
                                    <span className="flex items-center gap-1"><CalendarDays size={13} /> {event.shows_count ?? 0} {t('theaterOps.events.showsTitle')}</span>
                                    <span>{formatDateTime(event.published_at)}</span>
                                </div>
                                <div className="mt-4 flex items-center justify-between gap-2">
                                    <Link to={`/theater/events/${event.id}`} className="btn-secondary btn-sm">{t('theaterOps.events.open')}</Link>
                                    {session?.permissions.includes('events.update') ? (
                                        <button type="button" onClick={() => { setEditing(event); setFormOpen(true); }} className="rounded-lg p-2 text-ink-500 hover:bg-ink-100" aria-label={t('common.edit')}><Pencil size={15} /></button>
                                    ) : null}
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <EmptyState title={t('theaterOps.events.empty')} icon={<CalendarDays size={22} />} action={canCreate ? <Button onClick={() => setFormOpen(true)}><Plus size={16} /> {t('theaterOps.events.add')}</Button> : undefined} />
            )}

            <EventModal open={formOpen} event={editing} onClose={() => setFormOpen(false)} onSaved={() => { setFormOpen(false); refresh(); }} />
        </TenantPage>
    );
}

function EventModal({ open, event, onClose, onSaved }: { open: boolean; event: EventRecord | null; onClose: () => void; onSaved: () => void }) {
    const { t } = useTranslation();
    const categories = useQuery({ queryKey: ['public', 'event-categories'], queryFn: getPublicEventCategories });
    const [form, setForm] = useState({ title_en: '', title_ar: '', description_en: '', description_ar: '', slug: '', category_id: '' });
    const [poster, setPoster] = useState<File | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) return;
        setForm({
            title_en: event?.title_en ?? '',
            title_ar: event?.title_ar ?? '',
            description_en: event?.description_en ?? '',
            description_ar: event?.description_ar ?? '',
            slug: event?.slug ?? '',
            category_id: event?.category ? String(event.category.id) : '',
        });
        setPoster(null);
        setError(null);
    }, [open, event]);

    const save = async () => {
        setSaving(true); setError(null);
        try {
            const payload = {
                title_en: form.title_en,
                title_ar: form.title_ar || null,
                description_en: form.description_en || null,
                description_ar: form.description_ar || null,
                slug: form.slug || null,
                category_id: form.category_id ? Number(form.category_id) : null,
            };
            if (event) { await updateEvent(event.id, payload, poster); toast.success(t('theaterOps.events.updated')); }
            else { await createEvent(payload, poster); toast.success(t('theaterOps.events.created')); }
            onSaved();
        } catch (caught) { setError(errorMessage(caught)); } finally { setSaving(false); }
    };

    return (
        <Modal open={open} onClose={onClose} title={event ? t('theaterOps.events.edit') : t('theaterOps.events.add')} size="lg" footer={<><Button variant="secondary" onClick={onClose} disabled={saving}>{t('common.cancel')}</Button><Button onClick={() => void save()} loading={saving}>{t('common.save')}</Button></>}>
            <div className="space-y-4">
                {error ? <FormError>{error}</FormError> : null}
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label={t('theaterOps.events.titleEn')}><Input value={form.title_en} onChange={(event) => setForm((current) => ({ ...current, title_en: event.target.value }))} /></Field>
                    <Field label={t('theaterOps.events.titleAr')}><Input value={form.title_ar} onChange={(event) => setForm((current) => ({ ...current, title_ar: event.target.value }))} dir="rtl" /></Field>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label={t('theaterOps.events.descriptionEn')}><Textarea value={form.description_en} onChange={(event) => setForm((current) => ({ ...current, description_en: event.target.value }))} /></Field>
                    <Field label={t('theaterOps.events.descriptionAr')}><Textarea value={form.description_ar} onChange={(event) => setForm((current) => ({ ...current, description_ar: event.target.value }))} dir="rtl" /></Field>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label={t('theaterOps.events.slug')} hint={t('theaterOps.events.slugHint')}><Input value={form.slug} onChange={(event) => setForm((current) => ({ ...current, slug: event.target.value }))} /></Field>
                    <Field label={t('theaterOps.events.category')}>
                        <Select value={form.category_id} onChange={(event) => setForm((current) => ({ ...current, category_id: event.target.value }))}>
                            <option value="">{t('theaterOps.events.noCategory')}</option>
                            {categories.data?.map((category) => <option key={category.id} value={category.id}>{category.name ?? category.slug}</option>)}
                        </Select>
                    </Field>
                </div>
                {event?.poster_url ? <div><p className="label">{t('theaterOps.events.currentPoster')}</p><img src={event.poster_url} alt="" className="h-28 w-44 rounded-xl border border-ink-200 object-cover" /></div> : null}
                <FileDropzone file={poster} onChange={setPoster} label={t('theaterOps.events.poster')} accept="image/jpeg,image/png,image/webp" maxMb={8} hint={t('theaterOps.events.imageHint')} />
            </div>
        </Modal>
    );
}

export function EventDetailPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const { id } = useParams();
    const eventId = Number(id);
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [editOpen, setEditOpen] = useState(false);
    const [showOpen, setShowOpen] = useState(false);
    const [editingShow, setEditingShow] = useState<ShowRecord | null>(null);
    const [confirm, setConfirm] = useState<'publish' | 'unpublish' | 'cancel-event' | 'cancel-show' | null>(null);
    const [busy, setBusy] = useState(false);
    const event = useQuery({ queryKey: ['theater', 'event', eventId], queryFn: () => getEvent(eventId), enabled: Number.isFinite(eventId) });
    const shows = useQuery({ queryKey: ['theater', 'event', eventId, 'shows'], queryFn: () => getShows(eventId), enabled: Number.isFinite(eventId) });

    const refresh = () => {
        void queryClient.invalidateQueries({ queryKey: ['theater', 'event', eventId] });
        void queryClient.invalidateQueries({ queryKey: ['theater', 'event', eventId, 'shows'] });
        void queryClient.invalidateQueries({ queryKey: ['theater', 'events'] });
    };

    const confirmAction = async () => {
        if (!confirm) return;
        setBusy(true);
        try {
            if (confirm === 'publish') { await publishEvent(eventId); toast.success(t('theaterOps.events.published')); }
            else if (confirm === 'unpublish') { await unpublishEvent(eventId); toast.success(t('theaterOps.events.unpublished')); }
            else if (confirm === 'cancel-event') { await cancelEvent(eventId); toast.success(t('theaterOps.events.cancelled')); }
            else if (confirm === 'cancel-show' && editingShow) { await cancelShow(editingShow.id); toast.success(t('theaterOps.events.showCancelled')); }
            setConfirm(null);
            setEditingShow(null);
            refresh();
        } catch (error) { toast.error(errorMessage(error)); } finally { setBusy(false); }
    };

    if (event.isLoading) return <TenantPage><Skeleton className="h-96" /></TenantPage>;
    if (!event.data) return <TenantPage><EmptyState title={t('errors.notFound')} /></TenantPage>;

    const data = event.data;
    const canUpdate = session?.permissions.includes('events.update') ?? false;
    const canPublish = session?.permissions.includes('events.publish') ?? false;

    return (
        <TenantPage
            title={data.title_en ?? t('theaterOps.events.title')}
            subtitle={data.title_ar ?? undefined}
            actions={
                <>
                    <Button variant="secondary" onClick={() => navigate('/theater/events')}><ArrowLeft size={15} /> {t('theaterOps.events.title')}</Button>
                    {data.status === 'published' ? <Link to={`/events/${data.slug}`} target="_blank" className="btn-secondary"><ExternalLink size={15} /> {t('theaterOps.events.viewPublic')}</Link> : null}
                    {canUpdate ? <Button variant="secondary" onClick={() => setEditOpen(true)}><Pencil size={15} /> {t('common.edit')}</Button> : null}
                </>
            }
        >
            <div className="flex flex-wrap items-center gap-2">
                <Badge tone={eventStatusTone(data.status)}>{t(`theaterOps.events.status${data.status[0].toUpperCase() + data.status.slice(1)}`)}</Badge>
                {data.category ? <Badge tone="neutral">{data.category.name}</Badge> : null}
                {canPublish && data.status !== 'cancelled' ? (
                    data.status === 'published'
                        ? <Button size="sm" variant="secondary" onClick={() => setConfirm('unpublish')}><EyeOff size={14} /> {t('theaterOps.events.unpublish')}</Button>
                        : <Button size="sm" onClick={() => setConfirm('publish')}><Eye size={14} /> {t('theaterOps.events.publish')}</Button>
                ) : null}
                {canUpdate && data.status !== 'cancelled' ? <Button size="sm" variant="danger" onClick={() => setConfirm('cancel-event')}><XCircle size={14} /> {t('theaterOps.events.cancel')}</Button> : null}
            </div>

            <div className="mt-5 grid gap-4 lg:grid-cols-[0.75fr_1.25fr]">
                <section className="card card-body">
                    {data.poster_url ? <img src={data.poster_url} alt="" className="mb-4 w-full rounded-xl border border-ink-200 object-cover" /> : <div className="mb-4 flex aspect-[4/3] items-center justify-center rounded-xl bg-brand-50 text-brand-300"><ImageIcon size={36} /></div>}
                    <h2 className="font-semibold text-ink-950">{t('theaterOps.events.title')}</h2>
                    <p className="mt-2 text-sm leading-6 text-ink-500">{data.description_en ?? '—'}</p>
                    {data.description_ar ? <p className="mt-2 text-sm leading-6 text-ink-500" dir="rtl">{data.description_ar}</p> : null}
                    <p className="mt-4 font-mono text-xs text-ink-400">/events/{data.slug}</p>
                </section>

                <section className="card overflow-hidden">
                    <div className="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                        <h2 className="font-semibold text-ink-950">{t('theaterOps.events.showsTitle')}</h2>
                        {canUpdate ? <Button size="sm" onClick={() => { setEditingShow(null); setShowOpen(true); }}><Plus size={14} /> {t('theaterOps.events.addShow')}</Button> : null}
                    </div>
                    {shows.isLoading ? <div className="p-5"><Skeleton className="h-24" /></div> : (shows.data?.length ?? 0) === 0 ? <div className="p-5"><EmptyState title={t('theaterOps.events.noShows')} icon={<CalendarDays size={20} />} /></div> : (
                        <TableShell className="rounded-none border-0">
                            <table className="min-w-full"><thead className="bg-ink-50"><tr><Th>{t('theaterOps.events.startsAt')}</Th><Th>{t('theaterOps.events.hall')}</Th><Th>{t('common.status')}</Th><Th className="text-end">{t('common.actions')}</Th></tr></thead><tbody className="divide-y divide-ink-100">
                                {shows.data?.map((show) => (
                                    <tr key={show.id}>
                                        <Td className="whitespace-nowrap text-xs">{formatDateTime(show.starts_at)}</Td>
                                        <Td className="text-xs">{show.hall?.name ?? '—'}</Td>
                                        <Td><Badge tone={show.status === 'scheduled' ? 'success' : show.status === 'cancelled' ? 'danger' : 'neutral'}>{show.status}</Badge></Td>
                                        <Td><div className="flex flex-wrap justify-end gap-1"><Link to={`/theater/shows/${show.id}/tiers`} className="btn-ghost btn-sm"><Ticket size={14} /> {t('theaterOps.events.tiersTitle')}</Link>{canUpdate && show.status === 'scheduled' ? <Button size="sm" variant="ghost" onClick={() => { setEditingShow(show); setConfirm('cancel-show'); }}><XCircle size={14} /> {t('theaterOps.events.cancelShow')}</Button> : null}</div></Td>
                                    </tr>
                                ))}
                            </tbody></table>
                        </TableShell>
                    )}
                </section>
            </div>

            <EventModal open={editOpen} event={data} onClose={() => setEditOpen(false)} onSaved={() => { setEditOpen(false); refresh(); }} />
            <ShowModal open={showOpen} eventId={eventId} show={editingShow} onClose={() => setShowOpen(false)} onSaved={() => { setShowOpen(false); refresh(); }} />
            <ConfirmDialog
                open={confirm !== null}
                title={confirm === 'publish' ? t('theaterOps.events.publish') : confirm === 'unpublish' ? t('theaterOps.events.unpublish') : confirm === 'cancel-event' ? t('theaterOps.events.cancel') : t('theaterOps.events.cancelShow')}
                description={confirm === 'publish' ? t('theaterOps.events.publishConfirm') : confirm === 'unpublish' ? t('theaterOps.events.unpublishConfirm') : confirm === 'cancel-event' ? t('theaterOps.events.cancelConfirm') : t('theaterOps.events.cancelShowConfirm')}
                confirmLabel={t('common.confirm')}
                cancelLabel={t('common.cancel')}
                danger={confirm === 'cancel-event' || confirm === 'cancel-show'}
                loading={busy}
                onClose={() => { setConfirm(null); setEditingShow(null); }}
                onConfirm={() => void confirmAction()}
            />
        </TenantPage>
    );
}

function ShowModal({ open, eventId, show, onClose, onSaved }: { open: boolean; eventId: number; show: ShowRecord | null; onClose: () => void; onSaved: () => void }) {
    const { t } = useTranslation();
    const halls = useQuery({ queryKey: ['theater', 'halls', 'options'], queryFn: () => getHalls({ per_page: 100 }), enabled: open });
    const [hallId, setHallId] = useState('');
    const [start, setStart] = useState('');
    const [end, setEnd] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) return;
        setHallId(show ? String(show.hall_id) : '');
        setStart(toLocalInput(show?.starts_at));
        setEnd(toLocalInput(show?.ends_at));
        setError(null);
    }, [open, show]);

    const save = async () => {
        setSaving(true); setError(null);
        try {
            if (show) {
                await updateShow(show.id, { hall_id: Number(hallId), starts_at: toIso(start), ends_at: toIso(end) });
                toast.success(t('theaterOps.events.showUpdated'));
            } else {
                await createShow(eventId, { hall_id: Number(hallId), starts_at: toIso(start), ends_at: toIso(end) });
                toast.success(t('theaterOps.events.showCreated'));
            }
            onSaved();
        } catch (caught) { setError(errorMessage(caught)); } finally { setSaving(false); }
    };

    return (
        <Modal open={open} onClose={onClose} title={show ? t('theaterOps.events.editShow') : t('theaterOps.events.addShow')} footer={<><Button variant="secondary" onClick={onClose} disabled={saving}>{t('common.cancel')}</Button><Button onClick={() => void save()} loading={saving}>{t('common.save')}</Button></>}>
            <div className="space-y-4">
                {error ? <FormError>{error}</FormError> : null}
                <Field label={t('theaterOps.events.hall')}><Select value={hallId} onChange={(event) => setHallId(event.target.value)}><option value="">—</option>{halls.data?.data.map((hall: Hall) => <option key={hall.id} value={hall.id}>{hall.name} ({hall.capacity})</option>)}</Select></Field>
                <div className="grid gap-4 sm:grid-cols-2"><Field label={t('theaterOps.events.startsAt')}><Input type="datetime-local" value={start} onChange={(event) => setStart(event.target.value)} /></Field><Field label={t('theaterOps.events.endsAt')}><Input type="datetime-local" value={end} onChange={(event) => setEnd(event.target.value)} /></Field></div>
            </div>
        </Modal>
    );
}

export function ShowTiersPage() {
    const { t } = useTranslation();
    const { id } = useParams();
    const showId = Number(id);
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<TicketTierRecord | null>(null);
    const [deactivating, setDeactivating] = useState<TicketTierRecord | null>(null);
    const [busy, setBusy] = useState(false);
    const show = useQuery({ queryKey: ['theater', 'show', showId], queryFn: () => getShow(showId), enabled: Number.isFinite(showId) });
    const tiers = useQuery({ queryKey: ['theater', 'show', showId, 'tiers'], queryFn: () => getTiers(showId), enabled: Number.isFinite(showId) });
    const canCreate = session?.permissions.includes('ticket_tiers.create') ?? false;
    const canUpdate = session?.permissions.includes('ticket_tiers.update') ?? false;
    const canDelete = session?.permissions.includes('ticket_tiers.delete') ?? false;

    const refresh = () => {
        void queryClient.invalidateQueries({ queryKey: ['theater', 'show', showId, 'tiers'] });
        void queryClient.invalidateQueries({ queryKey: ['theater', 'show', showId] });
    };

    const deactivate = async () => {
        if (!deactivating) return;
        setBusy(true);
        try { await deactivateTier(deactivating.id); toast.success(t('theaterOps.events.tierDeactivated')); setDeactivating(null); refresh(); }
        catch (error) { toast.error(errorMessage(error)); } finally { setBusy(false); }
    };

    if (show.isLoading) return <TenantPage><Skeleton className="h-96" /></TenantPage>;
    if (!show.data) return <TenantPage><EmptyState title={t('errors.notFound')} /></TenantPage>;

    return (
        <TenantPage
            title={t('theaterOps.events.tiersTitle')}
            subtitle={`${show.data.event?.title_en ?? ''} · ${formatDateTime(show.data.starts_at)}`}
            actions={<><Link to={`/theater/events/${show.data.event_id}`} className="btn-secondary"><ArrowLeft size={15} /> {t('theaterOps.events.title')}</Link>{canCreate ? <Button onClick={() => { setEditing(null); setFormOpen(true); }}><Plus size={15} /> {t('theaterOps.events.addTier')}</Button> : null}</>}
        >
            <div className="mb-4 grid gap-3 sm:grid-cols-3">
                <div className="card card-body"><p className="text-xs font-semibold uppercase text-ink-500">{t('theaterOps.events.hall')}</p><p className="mt-1 font-semibold text-ink-900">{show.data.hall?.name ?? '—'}</p></div>
                <div className="card card-body"><p className="text-xs font-semibold uppercase text-ink-500">{t('theaterOps.events.available')}</p><p className="mt-1 font-semibold text-ink-900">{tiers.data?.reduce((sum, tier) => sum + tier.available_quantity, 0) ?? 0}</p></div>
                <div className="card card-body"><p className="text-xs font-semibold uppercase text-ink-500">{t('theaterOps.events.sold')}</p><p className="mt-1 font-semibold text-ink-900">{tiers.data?.reduce((sum, tier) => sum + tier.sold_quantity, 0) ?? 0}</p></div>
            </div>

            {tiers.isLoading ? <Skeleton className="h-40" /> : (tiers.data?.length ?? 0) === 0 ? <EmptyState title={t('theaterOps.events.noTiers')} icon={<Ticket size={22} />} action={canCreate ? <Button onClick={() => setFormOpen(true)}><Plus size={16} /> {t('theaterOps.events.addTier')}</Button> : undefined} /> : (
                <TableShell><table className="min-w-full"><thead className="bg-ink-50"><tr><Th>{t('common.name')}</Th><Th>{t('theaterOps.events.price')}</Th><Th>{t('theaterOps.events.totalQuantity')}</Th><Th>{t('theaterOps.events.available')}</Th><Th>{t('common.status')}</Th><Th className="text-end">{t('common.actions')}</Th></tr></thead><tbody className="divide-y divide-ink-100">
                    {tiers.data?.map((tier) => (
                        <tr key={tier.id}>
                            <Td><p className="font-semibold text-ink-900">{tier.name_en ?? tier.name}</p>{tier.name_ar ? <p className="text-xs text-ink-500" dir="rtl">{tier.name_ar}</p> : null}</Td>
                            <Td>{tier.price} {tier.currency}</Td>
                            <Td>{tier.total_quantity}</Td>
                            <Td>{tier.available_quantity}</Td>
                            <Td><Badge tone={tier.is_active ? 'success' : 'neutral'}>{tier.is_active ? t('common.active') : t('common.inactive')}</Badge></Td>
                            <Td><div className="flex justify-end gap-1">{canUpdate ? <button type="button" onClick={() => { setEditing(tier); setFormOpen(true); }} className="rounded-lg p-2 text-ink-500 hover:bg-ink-100" aria-label={t('common.edit')}><Pencil size={15} /></button> : null}{canDelete && tier.is_active ? <button type="button" onClick={() => setDeactivating(tier)} className="rounded-lg p-2 text-red-500 hover:bg-red-50" aria-label={t('theaterOps.events.deactivate')}><Trash2 size={15} /></button> : null}</div></Td>
                        </tr>
                    ))}
                </tbody></table></TableShell>
            )}

            <TierModal open={formOpen} showId={showId} tier={editing} onClose={() => setFormOpen(false)} onSaved={() => { setFormOpen(false); refresh(); }} />
            <ConfirmDialog open={deactivating !== null} title={t('theaterOps.events.deactivate')} description={t('theaterOps.events.deactivateConfirm')} confirmLabel={t('common.confirm')} cancelLabel={t('common.cancel')} danger loading={busy} onClose={() => setDeactivating(null)} onConfirm={() => void deactivate()} />
        </TenantPage>
    );
}

function TierModal({ open, showId, tier, onClose, onSaved }: { open: boolean; showId: number; tier: TicketTierRecord | null; onClose: () => void; onSaved: () => void }) {
    const { t } = useTranslation();
    const [form, setForm] = useState({ name_en: '', name_ar: '', description_en: '', description_ar: '', price: '0', currency: 'SAR', total_quantity: '1', sale_starts_at: '', sale_ends_at: '', per_booking_limit: '', is_active: true });
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) return;
        setForm({
            name_en: tier?.name_en ?? '',
            name_ar: tier?.name_ar ?? '',
            description_en: tier?.description_en ?? '',
            description_ar: tier?.description_ar ?? '',
            price: String(tier?.price ?? 0),
            currency: tier?.currency ?? 'SAR',
            total_quantity: String(tier?.total_quantity ?? 1),
            sale_starts_at: toLocalInput(tier?.sale_starts_at),
            sale_ends_at: toLocalInput(tier?.sale_ends_at),
            per_booking_limit: tier?.per_booking_limit ? String(tier.per_booking_limit) : '',
            is_active: tier?.is_active ?? true,
        });
        setError(null);
    }, [open, tier]);

    const save = async () => {
        setSaving(true); setError(null);
        try {
            const payload = { ...form, price: Number(form.price), total_quantity: Number(form.total_quantity), per_booking_limit: form.per_booking_limit ? Number(form.per_booking_limit) : null, sale_starts_at: form.sale_starts_at ? toIso(form.sale_starts_at) : null, sale_ends_at: form.sale_ends_at ? toIso(form.sale_ends_at) : null };
            if (tier) { await updateTier(tier.id, payload); toast.success(t('theaterOps.events.tierUpdated')); }
            else { await createTier(showId, payload); toast.success(t('theaterOps.events.tierCreated')); }
            onSaved();
        } catch (caught) { setError(errorMessage(caught)); } finally { setSaving(false); }
    };

    return (
        <Modal open={open} onClose={onClose} title={tier ? t('theaterOps.events.editTier') : t('theaterOps.events.addTier')} size="lg" footer={<><Button variant="secondary" onClick={onClose} disabled={saving}>{t('common.cancel')}</Button><Button onClick={() => void save()} loading={saving}>{t('common.save')}</Button></>}>
            <div className="space-y-4">
                {error ? <FormError>{error}</FormError> : null}
                <div className="grid gap-4 sm:grid-cols-2"><Field label={t('theaterOps.events.nameEn')}><Input value={form.name_en} onChange={(event) => setForm((current) => ({ ...current, name_en: event.target.value }))} /></Field><Field label={t('theaterOps.events.nameAr')}><Input value={form.name_ar} onChange={(event) => setForm((current) => ({ ...current, name_ar: event.target.value }))} dir="rtl" /></Field></div>
                <div className="grid gap-4 sm:grid-cols-2"><Field label={t('theaterOps.events.descriptionEn')}><Textarea value={form.description_en} onChange={(event) => setForm((current) => ({ ...current, description_en: event.target.value }))} /></Field><Field label={t('theaterOps.events.descriptionAr')}><Textarea value={form.description_ar} onChange={(event) => setForm((current) => ({ ...current, description_ar: event.target.value }))} dir="rtl" /></Field></div>
                <div className="grid gap-4 sm:grid-cols-3"><Field label={t('theaterOps.events.price')}><Input type="number" min={0} step="0.01" value={form.price} onChange={(event) => setForm((current) => ({ ...current, price: event.target.value }))} /></Field><Field label={t('theaterOps.halls.currency')}><Input value={form.currency} maxLength={3} onChange={(event) => setForm((current) => ({ ...current, currency: event.target.value.toUpperCase() }))} /></Field><Field label={t('theaterOps.events.totalQuantity')}><Input type="number" min={1} value={form.total_quantity} onChange={(event) => setForm((current) => ({ ...current, total_quantity: event.target.value }))} /></Field></div>
                <div className="grid gap-4 sm:grid-cols-2"><Field label={t('theaterOps.events.saleStartsAt')}><Input type="datetime-local" value={form.sale_starts_at} onChange={(event) => setForm((current) => ({ ...current, sale_starts_at: event.target.value }))} /></Field><Field label={t('theaterOps.events.saleEndsAt')}><Input type="datetime-local" value={form.sale_ends_at} onChange={(event) => setForm((current) => ({ ...current, sale_ends_at: event.target.value }))} /></Field></div>
                <Field label={t('theaterOps.events.perBookingLimit')} hint={t('theaterOps.events.capacityHint')}><Input type="number" min={1} value={form.per_booking_limit} onChange={(event) => setForm((current) => ({ ...current, per_booking_limit: event.target.value }))} /></Field>
                <label className="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" className="h-4 w-4 rounded border-ink-300 text-brand-700" checked={form.is_active} onChange={(event) => setForm((current) => ({ ...current, is_active: event.target.checked }))} /> {t('common.active')}</label>
            </div>
        </Modal>
    );
}
