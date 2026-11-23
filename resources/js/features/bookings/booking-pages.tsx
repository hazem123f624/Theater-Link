import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import type { TFunction } from 'i18next';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { toast } from 'sonner';
import { ArrowLeft, CalendarDays, CheckCircle2, QrCode, ScanLine, Ticket, XCircle } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import {
    cancelTheaterBooking,
    cancelUserBooking,
    getTheaterBooking,
    getTheaterBookings,
    getUserBooking,
    getUserBookings,
    validateTicket,
} from '@/api/bookings';
import type { BookingRecord, BookingTicketRecord } from '@/api/types';
import {
    Alert,
    Badge,
    Button,
    EmptyState,
    Field,
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
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';
import { formatDateTime, humanize } from '@/lib/format';

const bookingStatuses = ['confirmed', 'cancelled'];

function bookingStatusLabel(status: string, t: TFunction): string {
    return t(`bookingOps.status.${status}`, { defaultValue: humanize(status) });
}

function ticketStatusLabel(status: string, t: TFunction): string {
    return t(`bookingOps.ticketStatus.${status}`, { defaultValue: humanize(status) });
}

export function UserBookingsPage() {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [scope, setScope] = useState('');
    const [page, setPage] = useState(1);
    const bookings = useQuery({
        queryKey: ['user', 'bookings', search, status, scope, page],
        queryFn: () => getUserBookings({
            search: search || undefined,
            status: status || undefined,
            scope: (scope || undefined) as 'upcoming' | 'past' | undefined,
            page,
        }),
    });

    return (
        <TenantPage
            title={t('bookingOps.user.title')}
            subtitle={t('bookingOps.user.subtitle')}
            actions={<Link to="/events" className="btn-secondary"><CalendarDays size={15} /> {t('theaterOps.public.eventsTitle')}</Link>}
        >
            <div className="mb-4 grid gap-2 md:grid-cols-[1fr_auto_auto]">
                <SearchInput value={search} onChange={(value) => { setSearch(value); setPage(1); }} placeholder={t('bookingOps.user.search')} />
                <Select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}>
                    <option value="">{t('bookingOps.user.allStatuses')}</option>
                    {bookingStatuses.map((value) => <option key={value} value={value}>{bookingStatusLabel(value, t)}</option>)}
                </Select>
                <Select value={scope} onChange={(event) => { setScope(event.target.value); setPage(1); }}>
                    <option value="">{t('common.all')}</option>
                    <option value="upcoming">{t('bookingOps.user.upcoming')}</option>
                    <option value="past">{t('bookingOps.user.past')}</option>
                </Select>
            </div>

            {bookings.isLoading ? <Skeleton className="h-64" /> : bookings.data && bookings.data.data.length > 0 ? (
                <>
                    <BookingTable rows={bookings.data.data} user />
                    <div className="mt-4">
                        <Pagination
                            page={bookings.data.pagination.current_page}
                            lastPage={bookings.data.pagination.last_page}
                            total={bookings.data.pagination.total}
                            onChange={setPage}
                            labels={{ previous: t('common.previous'), next: t('common.next'), showing: t('common.showing'), results: t('common.results'), page: t('common.page'), of: t('common.of') }}
                        />
                    </div>
                </>
            ) : (
                <EmptyState
                    title={t('bookingOps.user.empty')}
                    icon={<Ticket size={22} />}
                    action={<Link to="/events" className="btn-secondary">{t('theaterOps.public.eventsTitle')}</Link>}
                />
            )}
        </TenantPage>
    );
}

function BookingTable({ rows, user = false }: { rows: BookingRecord[]; user?: boolean }) {
    const { t } = useTranslation();

    return (
        <TableShell>
            <table className="min-w-full">
                <thead className="bg-ink-50">
                    <tr>
                        <Th>{t('bookingOps.user.reference')}</Th>
                        <Th>{t('bookingOps.user.event')}</Th>
                        <Th>{t('bookingOps.user.show')}</Th>
                        <Th>{t('bookingOps.theater.customer')}</Th>
                        <Th>{t('bookingOps.user.tickets')}</Th>
                        <Th>{t('bookingOps.user.total')}</Th>
                        <Th>{t('bookingOps.user.status')}</Th>
                        <Th className="text-end">{t('common.actions')}</Th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-ink-100">
                    {rows.map((booking) => (
                        <tr key={booking.id} className="hover:bg-ink-50/60">
                            <Td className="font-mono text-xs">{booking.reference}</Td>
                            <Td>
                                <p className="font-semibold text-ink-900">{booking.event?.title ?? '—'}</p>
                                <p className="text-xs text-ink-500">{booking.theater.name ?? '—'}</p>
                            </Td>
                            <Td className="whitespace-nowrap text-xs">{booking.show?.starts_at_local ?? '—'}</Td>
                            <Td>{user ? (booking.hall?.name ?? '—') : (booking.user?.name ?? '—')}</Td>
                            <Td>{booking.ticket_count ?? 0}</Td>
                            <Td className="whitespace-nowrap text-xs">{booking.total} {booking.currency}</Td>
                            <Td><Badge tone={statusTone(booking.status)}>{bookingStatusLabel(booking.status, t)}</Badge></Td>
                            <Td>
                                <div className="flex justify-end">
                                    <Link to={user ? `/user/bookings/${booking.id}` : `/theater/bookings/${booking.id}`} className="btn-secondary btn-sm">
                                        {t('bookingOps.user.view')}
                                    </Link>
                                </div>
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </TableShell>
    );
}

export function UserBookingDetailPage() {
    const { t } = useTranslation();
    const { id } = useParams();
    const bookingId = Number(id);
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [cancelOpen, setCancelOpen] = useState(false);
    const [reason, setReason] = useState('');
    const booking = useQuery({ queryKey: ['user', 'booking', bookingId], queryFn: () => getUserBooking(bookingId), enabled: Number.isFinite(bookingId) });
    const cancel = useMutation({
        mutationFn: () => cancelUserBooking(bookingId, reason || null),
        onSuccess: () => {
            toast.success(t('bookingOps.user.cancelled'));
            setCancelOpen(false);
            void queryClient.invalidateQueries({ queryKey: ['user', 'booking', bookingId] });
            void queryClient.invalidateQueries({ queryKey: ['user', 'bookings'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard', 'user'] });
        },
        onError: (error) => toast.error(errorMessage(error)),
    });

    if (booking.isLoading) return <TenantPage><Skeleton className="h-80" /></TenantPage>;
    if (!booking.data) return <TenantPage><EmptyState title={t('errors.notFound')} /></TenantPage>;

    const record = booking.data;
    const canCancel = record.status === 'confirmed' && Boolean(record.show?.cancellation_cutoff_at) && new Date(record.show!.cancellation_cutoff_at) > new Date();

    return (
        <TenantPage
            title={t('bookingOps.user.detailTitle')}
            subtitle={record.reference}
            actions={<Button variant="secondary" onClick={() => navigate('/user/bookings')}><ArrowLeft size={15} /> {t('bookingOps.user.back')}</Button>}
        >
            <Alert tone={record.status === 'confirmed' ? 'success' : 'danger'}>
                {record.status === 'confirmed' ? t('bookingOps.user.confirmedNotice') : t('bookingOps.user.cancelledNotice')}
            </Alert>

            <div className="mt-5 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <section className="card card-body">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <h2 className="font-semibold text-ink-950">{record.event?.title ?? '—'}</h2>
                            <p className="mt-1 text-sm text-ink-500">{record.theater.name} · {record.hall?.name}</p>
                        </div>
                        <Badge tone={statusTone(record.status)}>{bookingStatusLabel(record.status, t)}</Badge>
                    </div>
                    <dl className="mt-4 space-y-3 text-sm">
                        <InfoRow label={t('bookingOps.user.reference')} value={record.reference} />
                        <InfoRow label={t('bookingOps.user.show')} value={record.show ? `${record.show.starts_at_local} – ${record.show.ends_at_local}` : '—'} />
                        <InfoRow label={t('bookingOps.user.ticketCount')} value={String(record.ticket_count)} />
                        <InfoRow label={t('bookingOps.user.total')} value={`${record.total} ${record.currency}`} />
                    </dl>
                    {record.cancellation_reason ? <div className="mt-4"><Alert tone="warning">{record.cancellation_reason}</Alert></div> : null}
                    {record.status === 'confirmed' && !canCancel ? <p className="mt-4 text-xs text-ink-500">{t('bookingOps.user.cutoff')}</p> : null}
                    {canCancel ? <Button variant="danger" className="mt-4" onClick={() => setCancelOpen(true)}><XCircle size={15} /> {t('bookingOps.user.cancel')}</Button> : null}
                </section>

                <section className="card card-body">
                    <h2 className="font-semibold text-ink-950">{t('bookingOps.user.items')}</h2>
                    <div className="mt-3 space-y-2">
                        {record.items.map((item) => (
                            <div key={item.id} className="flex items-center justify-between gap-3 rounded-xl bg-ink-50 px-3 py-2 text-sm">
                                <span>{item.name ?? '—'} × {item.quantity}</span>
                                <span className="font-semibold">{item.line_total} {item.currency}</span>
                            </div>
                        ))}
                    </div>
                </section>
            </div>

            <section className="card card-body mt-4">
                <div className="flex items-center gap-2">
                    <QrCode size={18} className="text-brand-700" />
                    <h2 className="font-semibold text-ink-950">{t('bookingOps.user.ticketsTitle')}</h2>
                </div>
                {record.tickets.length === 0 ? <p className="mt-2 text-sm text-ink-500">{t('bookingOps.user.noTickets')}</p> : (
                    <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {record.tickets.map((ticket) => (
                            <div key={ticket.id} className="rounded-2xl border border-ink-200 bg-white p-4 text-center">
                                <div className="mx-auto w-fit rounded-xl border border-ink-100 bg-white p-2">
                                    <QRCodeSVG value={ticket.qr_token} size={120} level="M" />
                                </div>
                                <p className="mt-3 font-mono text-xs font-semibold text-ink-800">{ticket.ticket_number}</p>
                                <p className="mt-1 text-xs text-ink-500">{ticket.tier_name ?? '—'}</p>
                                <div className="mt-2"><Badge tone={statusTone(ticket.status)}>{ticketStatusLabel(ticket.status, t)}</Badge></div>
                                <p className="mt-2 text-[11px] text-ink-400">{t('bookingOps.user.scanHint')}</p>
                            </div>
                        ))}
                    </div>
                )}
            </section>

            {record.history.length > 0 ? (
                <section className="card card-body mt-4">
                    <h2 className="font-semibold text-ink-950">{t('bookingOps.user.history')}</h2>
                    <ol className="mt-4 space-y-4 border-s border-ink-200 ps-5">
                        {record.history.map((event) => (
                            <li key={event.id} className="relative">
                                <span className="absolute -start-[1.65rem] top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-brand-500" />
                                <p className="text-sm font-semibold text-ink-900">{humanize(event.action)}</p>
                                {event.note ? <p className="mt-1 text-xs text-ink-500">{event.note}</p> : null}
                                <p className="mt-1 text-[11px] text-ink-400">{formatDateTime(event.created_at)}</p>
                            </li>
                        ))}
                    </ol>
                </section>
            ) : null}

            <Modal
                open={cancelOpen}
                onClose={() => setCancelOpen(false)}
                title={t('bookingOps.user.cancelTitle')}
                footer={<><Button variant="secondary" onClick={() => setCancelOpen(false)} disabled={cancel.isPending}>{t('common.cancel')}</Button><Button variant="danger" onClick={() => cancel.mutate()} loading={cancel.isPending}>{t('common.confirm')}</Button></>}
            >
                <div className="space-y-3">
                    <p className="text-sm text-ink-600">{t('bookingOps.user.cancelHelp')}</p>
                    <Field label={t('bookingOps.user.cancelReason')}><Textarea value={reason} onChange={(event) => setReason(event.target.value)} /></Field>
                </div>
            </Modal>
        </TenantPage>
    );
}

export function TheaterBookingsPage() {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [scope, setScope] = useState('');
    const [page, setPage] = useState(1);
    const bookings = useQuery({
        queryKey: ['theater', 'bookings', search, status, scope, page],
        queryFn: () => getTheaterBookings({
            search: search || undefined,
            status: status || undefined,
            scope: (scope || undefined) as 'upcoming' | 'past' | undefined,
            page,
        }),
    });

    return (
        <TenantPage
            title={t('bookingOps.theater.title')}
            subtitle={t('bookingOps.theater.subtitle')}
            actions={<Link to="/theater/tickets/validate" className="btn-secondary"><ScanLine size={15} /> {t('bookingOps.theater.validate')}</Link>}
        >
            <div className="mb-4 grid gap-2 md:grid-cols-[1fr_auto_auto]">
                <SearchInput value={search} onChange={(value) => { setSearch(value); setPage(1); }} placeholder={t('bookingOps.theater.search')} />
                <Select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}>
                    <option value="">{t('bookingOps.user.allStatuses')}</option>
                    {bookingStatuses.map((value) => <option key={value} value={value}>{bookingStatusLabel(value, t)}</option>)}
                </Select>
                <Select value={scope} onChange={(event) => { setScope(event.target.value); setPage(1); }}>
                    <option value="">{t('common.all')}</option>
                    <option value="upcoming">{t('bookingOps.user.upcoming')}</option>
                    <option value="past">{t('bookingOps.user.past')}</option>
                </Select>
            </div>

            {bookings.isLoading ? <Skeleton className="h-64" /> : bookings.data && bookings.data.data.length > 0 ? (
                <>
                    <BookingTable rows={bookings.data.data} />
                    <div className="mt-4">
                        <Pagination
                            page={bookings.data.pagination.current_page}
                            lastPage={bookings.data.pagination.last_page}
                            total={bookings.data.pagination.total}
                            onChange={setPage}
                            labels={{ previous: t('common.previous'), next: t('common.next'), showing: t('common.showing'), results: t('common.results'), page: t('common.page'), of: t('common.of') }}
                        />
                    </div>
                </>
            ) : <EmptyState title={t('bookingOps.theater.empty')} icon={<Ticket size={22} />} />}
        </TenantPage>
    );
}

export function TheaterBookingDetailPage() {
    const { t } = useTranslation();
    const { id } = useParams();
    const bookingId = Number(id);
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [cancelOpen, setCancelOpen] = useState(false);
    const [reason, setReason] = useState('');
    const booking = useQuery({ queryKey: ['theater', 'booking', bookingId], queryFn: () => getTheaterBooking(bookingId), enabled: Number.isFinite(bookingId) });
    const cancel = useMutation({
        mutationFn: () => cancelTheaterBooking(bookingId, reason.trim()),
        onSuccess: () => {
            toast.success(t('bookingOps.theater.cancelledMessage'));
            setCancelOpen(false);
            void queryClient.invalidateQueries({ queryKey: ['theater', 'booking', bookingId] });
            void queryClient.invalidateQueries({ queryKey: ['theater', 'bookings'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard', 'theater'] });
        },
        onError: (error) => toast.error(errorMessage(error)),
    });

    if (booking.isLoading) return <TenantPage><Skeleton className="h-80" /></TenantPage>;
    if (!booking.data) return <TenantPage><EmptyState title={t('errors.notFound')} /></TenantPage>;

    const record = booking.data;
    const canCancel = record.status === 'confirmed' && Boolean(record.show) && new Date(record.show!.starts_at) > new Date();

    return (
        <TenantPage
            title={t('bookingOps.user.detailTitle')}
            subtitle={record.reference}
            actions={<Button variant="secondary" onClick={() => navigate('/theater/bookings')}><ArrowLeft size={15} /> {t('bookingOps.user.back')}</Button>}
        >
            <Alert tone={record.status === 'confirmed' ? 'success' : 'danger'}>
                {record.status === 'confirmed' ? t('bookingOps.user.confirmedNotice') : t('bookingOps.user.cancelledNotice')}
            </Alert>

            <div className="mt-5 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <section className="card card-body">
                    <h2 className="font-semibold text-ink-950">{record.event?.title ?? '—'}</h2>
                    <dl className="mt-4 space-y-3 text-sm">
                        <InfoRow label={t('bookingOps.user.reference')} value={record.reference} />
                        <InfoRow label={t('bookingOps.user.show')} value={record.show ? `${record.show.starts_at_local} – ${record.show.ends_at_local}` : '—'} />
                        <InfoRow label={t('bookingOps.theater.customer')} value={record.user?.name ?? '—'} />
                        <InfoRow label={t('bookingOps.theater.contact')} value={[record.user?.email, record.user?.phone].filter(Boolean).join(' · ') || '—'} />
                        <InfoRow label={t('bookingOps.user.ticketCount')} value={String(record.ticket_count)} />
                        <InfoRow label={t('bookingOps.user.total')} value={`${record.total} ${record.currency}`} />
                    </dl>
                    {canCancel ? <Button variant="danger" className="mt-4" onClick={() => setCancelOpen(true)}><XCircle size={15} /> {t('bookingOps.theater.cancel')}</Button> : null}
                </section>

                <section className="card card-body">
                    <h2 className="font-semibold text-ink-950">{t('bookingOps.theater.ticketStatus')}</h2>
                    <div className="mt-3 space-y-2">
                        {record.tickets.map((ticket) => (
                            <div key={ticket.id} className="flex items-center justify-between gap-3 rounded-xl bg-ink-50 px-3 py-2 text-sm">
                                <div>
                                    <p className="font-mono text-xs font-semibold text-ink-800">{ticket.ticket_number}</p>
                                    <p className="text-xs text-ink-500">{ticket.tier_name ?? '—'}</p>
                                </div>
                                <Badge tone={statusTone(ticket.status)}>{ticketStatusLabel(ticket.status, t)}</Badge>
                            </div>
                        ))}
                    </div>
                </section>
            </div>

            <Modal
                open={cancelOpen}
                onClose={() => setCancelOpen(false)}
                title={t('bookingOps.theater.cancel')}
                footer={<><Button variant="secondary" onClick={() => setCancelOpen(false)} disabled={cancel.isPending}>{t('common.cancel')}</Button><Button variant="danger" onClick={() => cancel.mutate()} loading={cancel.isPending} disabled={!reason.trim()}>{t('common.confirm')}</Button></>}
            >
                <div className="space-y-3">
                    <p className="text-sm text-ink-600">{t('bookingOps.theater.cancelConfirm')}</p>
                    <Field label={t('bookingOps.theater.cancelReason')}><Textarea value={reason} onChange={(event) => setReason(event.target.value)} /></Field>
                </div>
            </Modal>
        </TenantPage>
    );
}

export function TicketValidationPage() {
    const { t } = useTranslation();
    const [token, setToken] = useState('');
    const [result, setResult] = useState<BookingTicketRecord | null>(null);
    const [error, setError] = useState<string | null>(null);
    const validate = useMutation({
        mutationFn: () => validateTicket(token.trim()),
        onSuccess: (ticket) => {
            setResult(ticket as BookingTicketRecord);
            setError(null);
            setToken('');
            toast.success(t('bookingOps.theater.valid'));
        },
        onError: (caught) => {
            setResult(null);
            setError(errorMessage(caught));
        },
    });

    return (
        <TenantPage title={t('bookingOps.theater.validateTitle')} subtitle={t('bookingOps.theater.validateSubtitle')}>
            <div className="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <section className="card card-body">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><ScanLine size={19} /></span>
                        <h2 className="font-semibold text-ink-950">{t('bookingOps.theater.validate')}</h2>
                    </div>
                    <form onSubmit={(event) => { event.preventDefault(); if (token.trim()) validate.mutate(); }}>
                        <Field label={t('bookingOps.theater.manual')}>
                            <Input value={token} onChange={(event) => setToken(event.target.value)} placeholder={t('bookingOps.theater.manualPlaceholder')} autoComplete="off" />
                        </Field>
                        <Button type="submit" className="mt-4" loading={validate.isPending} disabled={!token.trim()}><CheckCircle2 size={15} /> {t('bookingOps.theater.validateButton')}</Button>
                    </form>
                </section>

                <section className="card card-body">
                    <h2 className="font-semibold text-ink-950">{t('bookingOps.theater.recent')}</h2>
                    {result ? (
                        <div className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <div className="flex items-center gap-2 text-emerald-800"><CheckCircle2 size={18} /><p className="font-semibold">{t('bookingOps.theater.valid')}</p></div>
                            <p className="mt-3 font-mono text-sm font-semibold text-ink-900">{result.ticket_number}</p>
                            <p className="text-sm text-ink-600">{result.tier_name ?? '—'}</p>
                            <Badge tone={statusTone(result.status)}>{ticketStatusLabel(result.status, t)}</Badge>
                        </div>
                    ) : error ? (
                        <div className="mt-4"><Alert tone="danger">{error}</Alert></div>
                    ) : (
                        <div className="mt-6 text-center text-ink-400">
                            <QrCode size={42} className="mx-auto" />
                            <p className="mt-3 text-sm text-ink-500">{t('bookingOps.theater.manualPlaceholder')}</p>
                        </div>
                    )}
                </section>
            </div>
        </TenantPage>
    );
}

function InfoRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-ink-100 pb-2 last:border-0 last:pb-0">
            <dt className="text-ink-500">{label}</dt>
            <dd className="text-end font-medium text-ink-900">{value}</dd>
        </div>
    );
}
