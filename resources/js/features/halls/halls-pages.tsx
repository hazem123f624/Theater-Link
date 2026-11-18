import { useEffect, useMemo, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { toast } from 'sonner';
import {
    ArrowLeft,
    Building2,
    CalendarClock,
    CalendarDays,
    Clock3,
    Pencil,
    Plus,
    RefreshCw,
    Trash2,
} from 'lucide-react';
import {
    createBlock,
    createHall,
    createSchedule,
    deleteBlock,
    deleteHall,
    deleteSchedule,
    generateSlots,
    getBlocks,
    getHall,
    getHalls,
    getSchedules,
    getSlots,
    updateHall,
    updateSchedule,
} from '@/api/halls';
import type { Hall, HallBlock, HallSchedule, HallSlot } from '@/api/types';
import {
    Badge,
    Button,
    ConfirmDialog,
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
} from '@/components/ui';
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';
import { formatDateTime } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';

const dayKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as const;

function dayLabel(day: number, t: (key: string) => string): string {
    return t(`theaterOps.weekdays.${dayKeys[day - 1] ?? 'mon'}`);
}

function slotTone(status: string): 'neutral' | 'success' | 'warning' | 'danger' | 'info' {
    return status === 'available' ? 'success' : status === 'blocked' ? 'danger' : status === 'held' || status === 'reserved' ? 'warning' : 'neutral';
}

export function HallsPage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<Hall | null>(null);
    const [deleting, setDeleting] = useState<Hall | null>(null);
    const [busy, setBusy] = useState(false);
    const canCreate = session?.permissions.includes('halls.create') ?? false;
    const canUpdate = session?.permissions.includes('halls.update') ?? false;
    const canDelete = session?.permissions.includes('halls.delete') ?? false;
    const halls = useQuery({ queryKey: ['theater', 'halls', search, page], queryFn: () => getHalls({ search, page, per_page: 10 }) });

    const refresh = () => void queryClient.invalidateQueries({ queryKey: ['theater', 'halls'] });

    const remove = async () => {
        if (!deleting) return;
        setBusy(true);
        try {
            await deleteHall(deleting.id);
            toast.success(t('theaterOps.halls.deleted'));
            setDeleting(null);
            refresh();
        } catch (error) {
            toast.error(errorMessage(error));
        } finally {
            setBusy(false);
        }
    };

    return (
        <TenantPage
            title={t('theaterOps.halls.title')}
            subtitle={t('theaterOps.halls.subtitle')}
            actions={canCreate ? <Button onClick={() => { setEditing(null); setFormOpen(true); }}><Plus size={16} /> {t('theaterOps.halls.add')}</Button> : undefined}
        >
            <div className="mb-4 max-w-md">
                <SearchInput value={search} onChange={(value) => { setSearch(value); setPage(1); }} placeholder={t('team.searchPlaceholder')} />
            </div>

            {halls.isLoading ? (
                <div className="space-y-3">{[1, 2, 3].map((item) => <Skeleton key={item} className="h-20" />)}</div>
            ) : halls.data && halls.data.data.length > 0 ? (
                <>
                    <TableShell>
                        <table className="min-w-full">
                            <thead className="bg-ink-50">
                                <tr>
                                    <Th>{t('theaterOps.halls.name')}</Th>
                                    <Th>{t('theaterOps.halls.capacity')}</Th>
                                    <Th>{t('common.status')}</Th>
                                    <Th>{t('theaterOps.halls.schedulesTitle')}</Th>
                                    <Th className="text-end">{t('common.actions')}</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-ink-100">
                                {halls.data.data.map((hall) => (
                                    <tr key={hall.id} className="hover:bg-ink-50/60">
                                        <Td>
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><Building2 size={16} /></span>
                                                <div>
                                                    <Link to={`/theater/halls/${hall.id}`} className="font-semibold text-ink-900 hover:text-brand-700">{hall.name}</Link>
                                                    {hall.description ? <p className="line-clamp-1 text-xs text-ink-500">{hall.description}</p> : null}
                                                </div>
                                            </div>
                                        </Td>
                                        <Td>{hall.capacity}</Td>
                                        <Td><Badge tone={hall.is_active ? 'success' : 'neutral'}>{hall.is_active ? t('common.active') : t('common.inactive')}</Badge></Td>
                                        <Td>{hall.schedules_count ?? 0}</Td>
                                        <Td>
                                            <div className="flex justify-end gap-1">
                                                <Link to={`/theater/halls/${hall.id}`} className="btn-secondary btn-sm">{t('theaterOps.halls.open')}</Link>
                                                {canUpdate ? <button type="button" onClick={() => { setEditing(hall); setFormOpen(true); }} className="rounded-lg p-2 text-ink-500 hover:bg-ink-100" aria-label={t('common.edit')}><Pencil size={15} /></button> : null}
                                                {canDelete ? <button type="button" onClick={() => setDeleting(hall)} className="rounded-lg p-2 text-red-500 hover:bg-red-50" aria-label={t('common.delete')}><Trash2 size={15} /></button> : null}
                                            </div>
                                        </Td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </TableShell>
                    <div className="mt-4">
                        <Pagination page={halls.data.pagination.current_page} lastPage={halls.data.pagination.last_page} total={halls.data.pagination.total} onChange={setPage} labels={{ previous: t('common.previous'), next: t('common.next'), showing: t('common.showing'), results: t('common.results'), page: t('common.page'), of: t('common.of') }} />
                    </div>
                </>
            ) : (
                <EmptyState title={t('theaterOps.halls.empty')} icon={<Building2 size={22} />} action={canCreate ? <Button onClick={() => setFormOpen(true)}><Plus size={16} /> {t('theaterOps.halls.add')}</Button> : undefined} />
            )}

            <HallModal open={formOpen} hall={editing} onClose={() => setFormOpen(false)} onSaved={() => { setFormOpen(false); refresh(); }} />
            <ConfirmDialog open={deleting !== null} title={t('theaterOps.halls.deleteHall')} description={t('theaterOps.halls.deleteConfirm')} confirmLabel={t('common.delete')} cancelLabel={t('common.cancel')} danger loading={busy} onClose={() => setDeleting(null)} onConfirm={() => void remove()} />
        </TenantPage>
    );
}

function HallModal({ open, hall, onClose, onSaved }: { open: boolean; hall: Hall | null; onClose: () => void; onSaved: () => void }) {
    const { t } = useTranslation();
    const [form, setForm] = useState({ name: '', description: '', capacity: '100', is_active: true });
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) return;
        setForm({ name: hall?.name ?? '', description: hall?.description ?? '', capacity: String(hall?.capacity ?? 100), is_active: hall?.is_active ?? true });
        setError(null);
    }, [open, hall]);

    const save = async () => {
        setSaving(true); setError(null);
        try {
            const payload = { name: form.name, description: form.description || null, capacity: Number(form.capacity), is_active: form.is_active };
            if (hall) { await updateHall(hall.id, payload); toast.success(t('theaterOps.halls.updated')); }
            else { await createHall(payload); toast.success(t('theaterOps.halls.created')); }
            onSaved();
        } catch (caught) { setError(errorMessage(caught)); } finally { setSaving(false); }
    };

    return (
        <Modal open={open} onClose={onClose} title={hall ? t('theaterOps.halls.edit') : t('theaterOps.halls.add')} footer={<><Button variant="secondary" onClick={onClose} disabled={saving}>{t('common.cancel')}</Button><Button onClick={() => void save()} loading={saving}>{t('common.save')}</Button></>}>
            <div className="space-y-4">
                {error ? <FormError>{error}</FormError> : null}
                <Field label={t('theaterOps.halls.name')}><Input value={form.name} onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))} /></Field>
                <Field label={t('theaterOps.halls.description')}><Textarea value={form.description} onChange={(event) => setForm((current) => ({ ...current, description: event.target.value }))} /></Field>
                <Field label={t('theaterOps.halls.capacity')}><Input type="number" min={1} value={form.capacity} onChange={(event) => setForm((current) => ({ ...current, capacity: event.target.value }))} /></Field>
                <label className="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" className="h-4 w-4 rounded border-ink-300 text-brand-700" checked={form.is_active} onChange={(event) => setForm((current) => ({ ...current, is_active: event.target.checked }))} /> {t('common.active')}</label>
            </div>
        </Modal>
    );
}

export function HallDetailPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const { id } = useParams();
    const hallId = Number(id);
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [scheduleOpen, setScheduleOpen] = useState(false);
    const [editingSchedule, setEditingSchedule] = useState<HallSchedule | null>(null);
    const [deletingSchedule, setDeletingSchedule] = useState<HallSchedule | null>(null);
    const [blockOpen, setBlockOpen] = useState(false);
    const [deletingBlock, setDeletingBlock] = useState<HallBlock | null>(null);
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');
    const [status, setStatus] = useState('');
    const [busy, setBusy] = useState(false);
    const canSchedule = session?.permissions.includes('hall_schedules.create') ?? false;
    const canUpdateSchedule = session?.permissions.includes('hall_schedules.update') ?? false;
    const canDeleteSchedule = session?.permissions.includes('hall_schedules.delete') ?? false;
    const hall = useQuery({ queryKey: ['theater', 'hall', hallId], queryFn: () => getHall(hallId), enabled: Number.isFinite(hallId) });
    const schedules = useQuery({ queryKey: ['theater', 'hall', hallId, 'schedules'], queryFn: () => getSchedules(hallId), enabled: Number.isFinite(hallId) });
    const slots = useQuery({ queryKey: ['theater', 'hall', hallId, 'slots', from, to, status], queryFn: () => getSlots(hallId, { from: from || undefined, to: to || undefined, status: status || undefined }), enabled: Number.isFinite(hallId) });
    const blocks = useQuery({ queryKey: ['theater', 'hall', hallId, 'blocks'], queryFn: () => getBlocks(hallId), enabled: Number.isFinite(hallId) });

    const refresh = () => {
        void queryClient.invalidateQueries({ queryKey: ['theater', 'hall', hallId] });
        void queryClient.invalidateQueries({ queryKey: ['theater', 'hall', hallId, 'schedules'] });
        void queryClient.invalidateQueries({ queryKey: ['theater', 'hall', hallId, 'slots'] });
        void queryClient.invalidateQueries({ queryKey: ['theater', 'hall', hallId, 'blocks'] });
    };

    const generate = async () => {
        setBusy(true);
        try { const result = await generateSlots(hallId); toast.success(`${t('theaterOps.halls.generated')} (${result.generated})`); refresh(); }
        catch (error) { toast.error(errorMessage(error)); } finally { setBusy(false); }
    };

    const removeSchedule = async () => {
        if (!deletingSchedule) return;
        setBusy(true);
        try { await deleteSchedule(deletingSchedule.id); toast.success(t('theaterOps.halls.scheduleDeleted')); setDeletingSchedule(null); refresh(); }
        catch (error) { toast.error(errorMessage(error)); } finally { setBusy(false); }
    };

    const removeBlock = async () => {
        if (!deletingBlock) return;
        setBusy(true);
        try { await deleteBlock(deletingBlock.id); toast.success(t('theaterOps.halls.blockDeleted')); setDeletingBlock(null); refresh(); }
        catch (error) { toast.error(errorMessage(error)); } finally { setBusy(false); }
    };

    if (hall.isLoading) return <TenantPage><Skeleton className="h-96" /></TenantPage>;
    if (!hall.data) return <TenantPage><EmptyState title={t('errors.notFound')} /></TenantPage>;

    return (
        <TenantPage
            title={hall.data.name}
            subtitle={`${t('theaterOps.halls.capacity')}: ${hall.data.capacity}`}
            actions={<><Button variant="secondary" onClick={() => navigate('/theater/halls')}><ArrowLeft size={15} /> {t('theaterOps.halls.title')}</Button>{canSchedule ? <Button onClick={() => { setEditingSchedule(null); setScheduleOpen(true); }}><Plus size={15} /> {t('theaterOps.halls.addSchedule')}</Button> : null}<Button variant="secondary" onClick={() => setBlockOpen(true)} disabled={!canSchedule}><CalendarClock size={15} /> {t('theaterOps.halls.blockPeriod')}</Button></>}
        >
            <div className="grid gap-4 xl:grid-cols-[1fr_1.25fr]">
                <section className="card overflow-hidden">
                    <div className="flex items-center justify-between border-b border-ink-100 px-5 py-4"><h2 className="font-semibold text-ink-950">{t('theaterOps.halls.schedulesTitle')}</h2><Badge tone="neutral">{schedules.data?.length ?? 0}</Badge></div>
                    {schedules.isLoading ? <div className="p-5"><Skeleton className="h-24" /></div> : (schedules.data?.length ?? 0) === 0 ? <div className="p-5"><EmptyState title={t('theaterOps.halls.noSchedules')} icon={<CalendarDays size={20} />} /></div> : (
                        <div className="divide-y divide-ink-100">
                            {schedules.data?.map((schedule) => (
                                <div key={schedule.id} className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="font-semibold text-ink-900">{dayLabel(schedule.day_of_week, t)}</p>
                                        <p className="text-sm text-ink-500">{schedule.starts_at.slice(0, 5)} – {schedule.ends_at.slice(0, 5)} · {schedule.slot_duration_minutes} {t('theaterOps.halls.minutes')}</p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge tone={schedule.is_active ? 'success' : 'neutral'}>{schedule.price} {schedule.currency}</Badge>
                                        {canUpdateSchedule ? <button type="button" onClick={() => { setEditingSchedule(schedule); setScheduleOpen(true); }} className="rounded-lg p-2 text-ink-500 hover:bg-ink-100" aria-label={t('common.edit')}><Pencil size={15} /></button> : null}
                                        {canDeleteSchedule ? <button type="button" onClick={() => setDeletingSchedule(schedule)} className="rounded-lg p-2 text-red-500 hover:bg-red-50" aria-label={t('common.delete')}><Trash2 size={15} /></button> : null}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section className="card overflow-hidden">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-5 py-4">
                        <h2 className="font-semibold text-ink-950">{t('theaterOps.halls.slotsTitle')}</h2>
                        <Button size="sm" variant="secondary" onClick={() => void generate()} loading={busy}><RefreshCw size={14} /> {t('theaterOps.halls.generate')}</Button>
                    </div>
                    <div className="flex flex-wrap gap-2 border-b border-ink-100 px-5 py-3">
                        <Input type="date" value={from} onChange={(event) => setFrom(event.target.value)} className="w-auto" />
                        <Input type="date" value={to} onChange={(event) => setTo(event.target.value)} className="w-auto" />
                        <Select value={status} onChange={(event) => setStatus(event.target.value)} className="w-auto"><option value="">{t('common.all')}</option>{['available', 'held', 'reserved', 'blocked', 'completed'].map((value) => <option key={value} value={value}>{t(`theaterOps.halls.slotStatus${value[0].toUpperCase() + value.slice(1)}`)}</option>)}</Select>
                    </div>
                    {slots.isLoading ? <div className="p-5"><Skeleton className="h-32" /></div> : (slots.data?.length ?? 0) === 0 ? <div className="p-5"><EmptyState title={t('theaterOps.halls.noSlots')} icon={<Clock3 size={20} />} /></div> : (
                        <div className="max-h-80 overflow-y-auto">
                            <TableShell className="rounded-none border-0">
                                <table className="min-w-full"><thead className="bg-ink-50"><tr><Th>{t('theaterOps.halls.startTime')}</Th><Th>{t('theaterOps.halls.endTime')}</Th><Th>{t('common.status')}</Th><Th>{t('theaterOps.halls.price')}</Th></tr></thead><tbody className="divide-y divide-ink-100">{slots.data?.map((slot) => <tr key={slot.id}><Td className="whitespace-nowrap text-xs">{slot.starts_at_local}</Td><Td className="whitespace-nowrap text-xs">{slot.ends_at_local}</Td><Td><Badge tone={slotTone(slot.status)}>{t(`theaterOps.halls.slotStatus${slot.status[0].toUpperCase() + slot.status.slice(1)}`)}</Badge></Td><Td className="text-xs">{slot.price} {slot.currency}</Td></tr>)}</tbody></table>
                            </TableShell>
                        </div>
                    )}
                </section>
            </div>

            <section className="card mt-4 overflow-hidden">
                <div className="flex items-center justify-between border-b border-ink-100 px-5 py-4"><h2 className="font-semibold text-ink-950">{t('theaterOps.halls.blocksTitle')}</h2><Badge tone="neutral">{blocks.data?.length ?? 0}</Badge></div>
                {blocks.isLoading ? <div className="p-5"><Skeleton className="h-20" /></div> : (blocks.data?.length ?? 0) === 0 ? <div className="p-5"><EmptyState title={t('theaterOps.halls.noBlocks')} icon={<CalendarClock size={20} />} /></div> : (
                    <TableShell className="rounded-none border-0"><table className="min-w-full"><thead className="bg-ink-50"><tr><Th>{t('common.details')}</Th><Th>{t('theaterOps.halls.blockReason')}</Th><Th className="text-end">{t('common.actions')}</Th></tr></thead><tbody className="divide-y divide-ink-100">{blocks.data?.map((block) => <tr key={block.id}><Td className="text-xs">{formatDateTime(block.starts_at)} – {formatDateTime(block.ends_at)}</Td><Td className="text-xs">{block.reason ?? '—'}</Td><Td><div className="flex justify-end">{canDeleteSchedule ? <button type="button" onClick={() => setDeletingBlock(block)} className="rounded-lg p-2 text-red-500 hover:bg-red-50" aria-label={t('common.delete')}><Trash2 size={15} /></button> : null}</div></Td></tr>)}</tbody></table></TableShell>
                )}
            </section>

            <ScheduleModal open={scheduleOpen} schedule={editingSchedule} hallId={hallId} onClose={() => setScheduleOpen(false)} onSaved={() => { setScheduleOpen(false); refresh(); }} />
            <BlockModal open={blockOpen} hallId={hallId} onClose={() => setBlockOpen(false)} onSaved={() => { setBlockOpen(false); refresh(); }} />
            <ConfirmDialog open={deletingSchedule !== null} title={t('common.delete')} description={t('theaterOps.halls.scheduleDeleteConfirm')} confirmLabel={t('common.delete')} cancelLabel={t('common.cancel')} danger loading={busy} onClose={() => setDeletingSchedule(null)} onConfirm={() => void removeSchedule()} />
            <ConfirmDialog open={deletingBlock !== null} title={t('common.delete')} description={t('theaterOps.halls.blockDeleteConfirm')} confirmLabel={t('common.delete')} cancelLabel={t('common.cancel')} danger loading={busy} onClose={() => setDeletingBlock(null)} onConfirm={() => void removeBlock()} />
        </TenantPage>
    );
}

function ScheduleModal({ open, schedule, hallId, onClose, onSaved }: { open: boolean; schedule: HallSchedule | null; hallId: number; onClose: () => void; onSaved: () => void }) {
    const { t } = useTranslation();
    const [form, setForm] = useState({ day_of_week: '1', starts_at: '10:00', ends_at: '12:00', slot_duration_minutes: '60', price: '0', currency: 'SAR', is_active: true });
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) return;
        setForm({
            day_of_week: String(schedule?.day_of_week ?? 1),
            starts_at: schedule?.starts_at?.slice(0, 5) ?? '10:00',
            ends_at: schedule?.ends_at?.slice(0, 5) ?? '12:00',
            slot_duration_minutes: String(schedule?.slot_duration_minutes ?? 60),
            price: String(schedule?.price ?? 0),
            currency: schedule?.currency ?? 'SAR',
            is_active: schedule?.is_active ?? true,
        });
        setError(null);
    }, [open, schedule]);

    const save = async () => {
        setSaving(true); setError(null);
        try {
            const payload = { day_of_week: Number(form.day_of_week), starts_at: form.starts_at, ends_at: form.ends_at, slot_duration_minutes: Number(form.slot_duration_minutes), price: Number(form.price), currency: form.currency, is_active: form.is_active };
            if (schedule) { await updateSchedule(schedule.id, payload); toast.success(t('theaterOps.halls.scheduleUpdated')); }
            else { await createSchedule(hallId, payload); toast.success(t('theaterOps.halls.scheduleCreated')); }
            onSaved();
        } catch (caught) { setError(errorMessage(caught)); } finally { setSaving(false); }
    };

    return (
        <Modal open={open} onClose={onClose} title={schedule ? t('theaterOps.halls.editSchedule') : t('theaterOps.halls.addSchedule')} footer={<><Button variant="secondary" onClick={onClose} disabled={saving}>{t('common.cancel')}</Button><Button onClick={() => void save()} loading={saving}>{t('common.save')}</Button></>}>
            <div className="space-y-4">
                {error ? <FormError>{error}</FormError> : null}
                <Field label={t('theaterOps.halls.day')}><Select value={form.day_of_week} onChange={(event) => setForm((current) => ({ ...current, day_of_week: event.target.value }))}>{[1, 2, 3, 4, 5, 6, 7].map((day) => <option key={day} value={day}>{dayLabel(day, t)}</option>)}</Select></Field>
                <div className="grid gap-4 sm:grid-cols-2"><Field label={t('theaterOps.halls.startTime')}><Input type="time" value={form.starts_at} onChange={(event) => setForm((current) => ({ ...current, starts_at: event.target.value }))} /></Field><Field label={t('theaterOps.halls.endTime')}><Input type="time" value={form.ends_at} onChange={(event) => setForm((current) => ({ ...current, ends_at: event.target.value }))} /></Field></div>
                <div className="grid gap-4 sm:grid-cols-3"><Field label={t('theaterOps.halls.slotMinutes')}><Input type="number" min={15} step={15} value={form.slot_duration_minutes} onChange={(event) => setForm((current) => ({ ...current, slot_duration_minutes: event.target.value }))} /></Field><Field label={t('theaterOps.halls.price')}><Input type="number" min={0} step="0.01" value={form.price} onChange={(event) => setForm((current) => ({ ...current, price: event.target.value }))} /></Field><Field label={t('theaterOps.halls.currency')}><Input value={form.currency} maxLength={3} onChange={(event) => setForm((current) => ({ ...current, currency: event.target.value.toUpperCase() }))} /></Field></div>
                <label className="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" className="h-4 w-4 rounded border-ink-300 text-brand-700" checked={form.is_active} onChange={(event) => setForm((current) => ({ ...current, is_active: event.target.checked }))} /> {t('common.active')}</label>
            </div>
        </Modal>
    );
}

function BlockModal({ open, hallId, onClose, onSaved }: { open: boolean; hallId: number; onClose: () => void; onSaved: () => void }) {
    const { t } = useTranslation();
    const [start, setStart] = useState('');
    const [end, setEnd] = useState('');
    const [reason, setReason] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => { if (open) { setStart(''); setEnd(''); setReason(''); setError(null); } }, [open]);

    const save = async () => {
        if (!start || !end) { setError(t('errors.required')); return; }
        setSaving(true); setError(null);
        try {
            await createBlock(hallId, { starts_at: new Date(start).toISOString(), ends_at: new Date(end).toISOString(), reason: reason || null });
            toast.success(t('theaterOps.halls.blockCreated'));
            onSaved();
        } catch (caught) { setError(errorMessage(caught)); } finally { setSaving(false); }
    };

    return (
        <Modal open={open} onClose={onClose} title={t('theaterOps.halls.blockPeriod')} footer={<><Button variant="secondary" onClick={onClose} disabled={saving}>{t('common.cancel')}</Button><Button onClick={() => void save()} loading={saving}>{t('common.save')}</Button></>}>
            <div className="space-y-4">
                {error ? <FormError>{error}</FormError> : null}
                <div className="grid gap-4 sm:grid-cols-2"><Field label={t('theaterOps.halls.startTime')}><Input type="datetime-local" value={start} onChange={(event) => setStart(event.target.value)} /></Field><Field label={t('theaterOps.halls.endTime')}><Input type="datetime-local" value={end} onChange={(event) => setEnd(event.target.value)} /></Field></div>
                <Field label={t('theaterOps.halls.blockReason')}><Input value={reason} onChange={(event) => setReason(event.target.value)} /></Field>
            </div>
        </Modal>
    );
}
