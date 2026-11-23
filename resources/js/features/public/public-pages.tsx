import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link, useParams } from 'react-router-dom';
import { CalendarDays, MapPin, Search, Ticket, Users } from 'lucide-react';
import {
    getPublicEvent,
    getPublicEventCategories,
    getPublicEvents,
    getPublicTheater,
    getPublicTheaters,
} from '@/api/public';
import type { PublicEvent, PublicTheater } from '@/api/types';
import { Badge, Button, EmptyState, Pagination, SearchInput, Select, Skeleton } from '@/components/ui';
import { formatDateTime, localizedName } from '@/lib/format';
import { PublicBookingPanel } from '@/features/bookings/public-booking-panel';

function PublicShell({ children }: { children: React.ReactNode }) {
    return <div className="page-shell">{children}</div>;
}

export function PublicTheatersPage() {
    const { t } = useTranslation();
    const [page, setPage] = useState(1);
    const theaters = useQuery({ queryKey: ['public', 'theaters', page], queryFn: () => getPublicTheaters({ page, per_page: 9 }) });

    return (
        <PublicShell>
            <div className="mb-7">
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">TheaterLink</p>
                <h1 className="mt-2 text-3xl font-bold text-ink-950">{t('theaterOps.public.theatersTitle')}</h1>
                <p className="mt-2 text-sm text-ink-500">{t('theaterOps.public.theatersSubtitle')}</p>
            </div>

            {theaters.isLoading ? (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">{[1, 2, 3, 4, 5, 6].map((item) => <Skeleton key={item} className="h-64" />)}</div>
            ) : theaters.data && theaters.data.data.length > 0 ? (
                <>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {theaters.data.data.map((theater) => <TheaterCard key={theater.id} theater={theater} />)}
                    </div>
                    <div className="mt-6">
                        <Pagination page={theaters.data.pagination.current_page} lastPage={theaters.data.pagination.last_page} total={theaters.data.pagination.total} onChange={setPage} labels={{ previous: t('common.previous'), next: t('common.next'), showing: t('common.showing'), results: t('common.results'), page: t('common.page'), of: t('common.of') }} />
                    </div>
                </>
            ) : (
                <EmptyState title={t('theaterOps.public.noTheaters')} icon={<MapPin size={22} />} />
            )}
        </PublicShell>
    );
}

function TheaterCard({ theater }: { theater: PublicTheater }) {
    const { t } = useTranslation();
    return (
        <Link to={`/theaters/${theater.id}`} className="group card overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
            {theater.cover_url ? <img src={theater.cover_url} alt="" className="h-36 w-full object-cover" /> : <div className="flex h-36 items-center justify-center bg-brand-900 text-brand-200"><MapPin size={34} /></div>}
            <div className="card-body">
                <div className="flex items-start gap-3">
                    {theater.logo_url ? <img src={theater.logo_url} alt="" className="h-11 w-11 rounded-xl border border-ink-200 object-cover" /> : <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><Users size={18} /></span>}
                    <div className="min-w-0">
                        <h2 className="truncate text-lg font-semibold text-ink-950 group-hover:text-brand-700">{theater.name}</h2>
                        <p className="flex items-center gap-1 text-xs text-ink-500"><MapPin size={12} /> {theater.address ?? t('common.details')}</p>
                    </div>
                </div>
                <p className="mt-3 line-clamp-2 min-h-10 text-sm leading-6 text-ink-500">{theater.description ?? '—'}</p>
                <div className="mt-4 flex items-center justify-between text-xs text-ink-400">
                    <span>{theater.halls_count ?? theater.halls?.length ?? 0} {t('theaterOps.public.theaterHalls')}</span>
                    <span className="font-semibold text-brand-700">{t('theaterOps.public.viewTheater')} →</span>
                </div>
            </div>
        </Link>
    );
}

export function PublicTheaterPage() {
    const { t, i18n } = useTranslation();
    const { id } = useParams();
    const theaterId = Number(id);
    const theater = useQuery({ queryKey: ['public', 'theater', theaterId], queryFn: () => getPublicTheater(theaterId), enabled: Number.isFinite(theaterId) });
    const events = useQuery({ queryKey: ['public', 'events', 'theater', theaterId], queryFn: () => getPublicEvents({ theater_id: theaterId, per_page: 6 }), enabled: Number.isFinite(theaterId) });

    if (theater.isLoading) return <PublicShell><Skeleton className="h-96" /></PublicShell>;
    if (!theater.data) return <PublicShell><EmptyState title={t('errors.notFound')} /></PublicShell>;

    const data = theater.data;
    return (
        <PublicShell>
            <div className="overflow-hidden rounded-3xl border border-ink-200 bg-white shadow-soft">
                {data.cover_url ? <img src={data.cover_url} alt="" className="h-56 w-full object-cover sm:h-72" /> : <div className="h-44 bg-gradient-to-br from-brand-900 to-brand-700" />}
                <div className="relative px-5 pb-6 sm:px-8">
                    <div className="-mt-10 flex items-end gap-4">
                        {data.logo_url ? <img src={data.logo_url} alt="" className="h-24 w-24 rounded-2xl border-4 border-white object-cover shadow-lg" /> : <span className="flex h-24 w-24 items-center justify-center rounded-2xl border-4 border-white bg-brand-50 text-brand-700 shadow-lg"><Users size={30} /></span>}
                        <div className="pb-1">
                            <h1 className="text-2xl font-bold text-ink-950 sm:text-3xl">{data.name}</h1>
                            <p className="mt-1 flex items-center gap-1 text-sm text-ink-500"><MapPin size={14} /> {[data.address, localizedName(data.city), localizedName(data.country)].filter(Boolean).join(', ') || '—'}</p>
                        </div>
                    </div>
                    <p className="mt-5 max-w-3xl text-sm leading-7 text-ink-600">{data.description ?? '—'}</p>
                </div>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-[0.7fr_1.3fr]">
                <section>
                    <h2 className="mb-3 text-xl font-bold text-ink-950">{t('theaterOps.public.theaterHalls')}</h2>
                    <div className="space-y-3">
                        {data.halls?.length ? data.halls.map((hall) => (
                            <div key={hall.id} className="card card-body">
                                <div className="flex items-center justify-between gap-2"><h3 className="font-semibold text-ink-900">{hall.name}</h3><Badge tone="neutral">{hall.capacity} {t('theaterOps.halls.capacity')}</Badge></div>
                                <p className="mt-1 text-sm text-ink-500">{hall.description ?? '—'}</p>
                            </div>
                        )) : <EmptyState title={t('theaterOps.public.availabilityEmpty')} />}
                    </div>
                </section>
                <section>
                    <h2 className="mb-3 text-xl font-bold text-ink-950">{t('theaterOps.public.eventsTitle')}</h2>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {events.data?.data.length ? events.data.data.map((event) => <PublicEventCard key={event.id} event={event} />) : <EmptyState title={t('theaterOps.public.noEvents')} />}
                    </div>
                </section>
            </div>
            <p className="mt-6 text-xs text-ink-400">{i18n.language === 'ar' ? 'ثيتر لينك' : 'TheaterLink'}</p>
        </PublicShell>
    );
}

export function PublicEventsPage() {
    const { t } = useTranslation();
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('');
    const [theaterId, setTheaterId] = useState('');
    const [page, setPage] = useState(1);
    const categories = useQuery({ queryKey: ['public', 'event-categories'], queryFn: getPublicEventCategories });
    const theaters = useQuery({ queryKey: ['public', 'theaters', 'options'], queryFn: () => getPublicTheaters({ per_page: 100 }) });
    const events = useQuery({
        queryKey: ['public', 'events', search, category, theaterId, page],
        queryFn: () => getPublicEvents({ search: search || undefined, category: category || undefined, theater_id: theaterId ? Number(theaterId) : undefined, page, per_page: 9 }),
    });

    return (
        <PublicShell>
            <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">TheaterLink</p>
                    <h1 className="mt-2 text-3xl font-bold text-ink-950">{t('theaterOps.public.eventsTitle')}</h1>
                    <p className="mt-2 text-sm text-ink-500">{t('theaterOps.public.eventsSubtitle')}</p>
                </div>
                <div className="flex items-center gap-2 text-sm text-ink-500"><Ticket size={16} /> {events.data?.pagination.total ?? 0}</div>
            </div>

            <div className="mb-6 grid gap-3 sm:grid-cols-[1fr_12rem_12rem]">
                <SearchInput value={search} onChange={(value) => { setSearch(value); setPage(1); }} placeholder={t('theaterOps.public.searchPlaceholder')} />
                <Select value={category} onChange={(event) => { setCategory(event.target.value); setPage(1); }}><option value="">{t('theaterOps.public.allCategories')}</option>{categories.data?.map((item) => <option key={item.id} value={item.slug}>{item.name ?? item.slug}</option>)}</Select>
                <Select value={theaterId} onChange={(event) => { setTheaterId(event.target.value); setPage(1); }}><option value="">{t('theaterOps.public.allTheaters')}</option>{theaters.data?.data.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</Select>
            </div>

            {events.isLoading ? <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">{[1, 2, 3, 4, 5, 6].map((item) => <Skeleton key={item} className="h-72" />)}</div> : events.data && events.data.data.length > 0 ? (
                <>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">{events.data.data.map((event) => <PublicEventCard key={event.id} event={event} />)}</div>
                    <div className="mt-6"><Pagination page={events.data.pagination.current_page} lastPage={events.data.pagination.last_page} total={events.data.pagination.total} onChange={setPage} labels={{ previous: t('common.previous'), next: t('common.next'), showing: t('common.showing'), results: t('common.results'), page: t('common.page'), of: t('common.of') }} /></div>
                </>
            ) : <EmptyState title={t('theaterOps.public.noEvents')} icon={<CalendarDays size={22} />} />}
        </PublicShell>
    );
}

function PublicEventCard({ event }: { event: PublicEvent }) {
    const { t } = useTranslation();
    const firstShow = event.shows?.[0];
    return (
        <Link to={`/events/${event.slug}`} className="group card overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
            {event.poster_url ? <img src={event.poster_url} alt="" className="h-48 w-full object-cover" /> : <div className="flex h-48 items-center justify-center bg-brand-900 text-brand-200"><Ticket size={36} /></div>}
            <div className="card-body">
                <div className="flex items-center justify-between gap-2"><Badge tone="info">{event.category?.name ?? t('theaterOps.public.eventsTitle')}</Badge><span className="text-xs text-ink-400">{event.theater.name}</span></div>
                <h2 className="mt-3 line-clamp-2 text-lg font-semibold text-ink-950 group-hover:text-brand-700">{event.title ?? '—'}</h2>
                <p className="mt-1 line-clamp-2 min-h-10 text-sm leading-6 text-ink-500">{event.description ?? '—'}</p>
                <div className="mt-3 flex items-center justify-between text-xs text-ink-500"><span className="flex items-center gap-1"><CalendarDays size={13} /> {firstShow ? formatDateTime(firstShow.starts_at) : t('theaterOps.public.noShows')}</span><span className="font-semibold text-brand-700">{t('theaterOps.public.viewEvent')} →</span></div>
            </div>
        </Link>
    );
}

export function PublicEventPage() {
    const { t } = useTranslation();
    const { slug } = useParams();
    const event = useQuery({ queryKey: ['public', 'event', slug], queryFn: () => getPublicEvent(slug ?? ''), enabled: Boolean(slug) });

    if (event.isLoading) return <PublicShell><Skeleton className="h-96" /></PublicShell>;
    if (!event.data) return <PublicShell><EmptyState title={t('errors.notFound')} /></PublicShell>;

    const data = event.data;
    return (
        <PublicShell>
            <div className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                <div>
                    {data.poster_url ? <img src={data.poster_url} alt="" className="w-full rounded-3xl border border-ink-200 object-cover shadow-soft" /> : <div className="flex aspect-[4/3] items-center justify-center rounded-3xl bg-brand-900 text-brand-200"><Ticket size={48} /></div>}
                </div>
                <div>
                    <div className="flex flex-wrap items-center gap-2"><Badge tone="info">{data.category?.name ?? t('theaterOps.public.eventsTitle')}</Badge><span className="text-sm text-ink-500">{data.theater.name}</span></div>
                    <h1 className="mt-4 text-3xl font-bold text-ink-950 sm:text-4xl">{data.title ?? '—'}</h1>
                    <p className="mt-4 text-base leading-8 text-ink-600">{data.description ?? '—'}</p>
                    <PublicBookingPanel event={data} />
                </div>
            </div>
        </PublicShell>
    );
}
