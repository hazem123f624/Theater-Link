import { useEffect, useRef, useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { CalendarDays, Minus, Plus, Ticket } from 'lucide-react';
import { createUserBooking, getPublicShowTiers } from '@/api/bookings';
import type { PublicEvent, PublicShowTierRecord } from '@/api/types';
import { Badge, Button, EmptyState, Field, Select, Skeleton } from '@/components/ui';
import { errorMessage } from '@/lib/errors';
import { formatDateTime } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';

function idempotencyKey(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

export function PublicBookingPanel({ event }: { event: PublicEvent }) {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const location = useLocation();
    const session = useAuthStore((state) => state.session);
    const [selectedShowId, setSelectedShowId] = useState<number | null>(event.shows[0]?.id ?? null);
    const [quantities, setQuantities] = useState<Record<number, number>>({});
    const keyRef = useRef(idempotencyKey());

    useEffect(() => {
        keyRef.current = idempotencyKey();
        setQuantities({});
    }, [selectedShowId]);

    const showTiers = useQuery({
        queryKey: ['public', 'show-tiers', selectedShowId],
        queryFn: () => getPublicShowTiers(selectedShowId ?? 0),
        enabled: selectedShowId !== null,
    });

    const booking = useMutation({
        mutationFn: createUserBooking,
        onSuccess: (record) => {
            toast.success(t('bookingOps.public.success'));
            navigate(`/user/bookings/${record.id}`);
        },
        onError: (error) => toast.error(errorMessage(error)),
    });

    if (event.shows.length === 0) {
        return <EmptyState title={t('theaterOps.public.noShows')} icon={<CalendarDays size={22} />} />;
    }

    const selectedShow = event.shows.find((show) => show.id === selectedShowId) ?? event.shows[0];
    const tiers = showTiers.data?.tiers ?? [];
    const selectedItems = Object.entries(quantities)
        .map(([id, quantity]) => ({ tier_id: Number(id), quantity }))
        .filter((item) => item.quantity > 0);
    const total = selectedItems.reduce((sum, item) => {
        const tier = tiers.find((candidate) => candidate.id === item.tier_id);
        return sum + (tier ? Number(tier.price) * item.quantity : 0);
    }, 0);

    const maxFor = (tier: PublicShowTierRecord): number => Math.min(tier.available_quantity, tier.per_booking_limit ?? 20, 20);

    const setQuantity = (tier: PublicShowTierRecord, value: number) => {
        const max = maxFor(tier);
        setQuantities((current) => ({
            ...current,
            [tier.id]: Math.max(0, Math.min(max, value)),
        }));
    };

    const unavailableLabel = (tier: PublicShowTierRecord): string => {
        if (tier.sale_state === 'not_started') return t('bookingOps.public.saleNotStarted');
        if (tier.sale_state === 'sale_ended') return t('bookingOps.public.saleEnded');
        if (tier.sale_state === 'booking_closed') return t('bookingOps.public.bookingClosed');
        if (tier.sale_state === 'inactive' || tier.sale_state === 'sold_out') return t('bookingOps.public.soldOut');

        return '';
    };

    const submit = () => {
        if (!selectedShowId || selectedItems.length === 0 || !session || session.actor !== 'user') {
            return;
        }

        booking.mutate({
            show_id: selectedShowId,
            idempotency_key: keyRef.current,
            items: selectedItems.map((item) => ({ ticket_tier_id: item.tier_id, quantity: item.quantity })),
        });
    };

    return (
        <section className="mt-6 rounded-2xl border border-brand-100 bg-brand-50/50 p-4 sm:p-5">
            <div className="flex items-center gap-2">
                <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-700 text-white"><Ticket size={17} /></span>
                <div>
                    <h2 className="font-semibold text-ink-950">{t('bookingOps.public.selectTickets')}</h2>
                    <p className="text-xs text-ink-500">{selectedShow ? formatDateTime(selectedShow.starts_at) : ''}</p>
                </div>
            </div>

            <div className="mt-4">
                <Field label={t('bookingOps.public.chooseShow')}>
                    <Select value={selectedShowId ?? ''} onChange={(input) => setSelectedShowId(Number(input.target.value))}>
                        {event.shows.map((show) => (
                            <option key={show.id} value={show.id}>
                                {formatDateTime(show.starts_at)} — {show.hall?.name ?? ''}
                            </option>
                        ))}
                    </Select>
                </Field>
            </div>

            {showTiers.isLoading ? <Skeleton className="mt-4 h-40" /> : tiers.length === 0 ? (
                <div className="mt-4"><EmptyState title={t('bookingOps.public.noTiers')} icon={<Ticket size={20} />} /></div>
            ) : (
                <div className="mt-4 space-y-2">
                    {tiers.map((tier) => {
                        const quantity = quantities[tier.id] ?? 0;
                        const max = maxFor(tier);

                        return (
                            <div key={tier.id} className="rounded-xl border border-ink-200 bg-white p-3">
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p className="font-semibold text-ink-900">{tier.name ?? 'Ticket'}</p>
                                        <p className="mt-0.5 text-xs text-ink-500">
                                            {tier.price} {tier.currency} · {t('bookingOps.public.remaining', { count: tier.available_quantity })}
                                            {tier.per_booking_limit ? ` · ${t('bookingOps.public.max', { count: tier.per_booking_limit })}` : ''}
                                        </p>
                                    </div>
                                    <Badge tone={tier.can_book ? 'success' : 'neutral'}>{tier.can_book ? t('bookingOps.ticketStatus.valid') : unavailableLabel(tier)}</Badge>
                                </div>
                                {tier.can_book ? (
                                    <div className="mt-3 flex items-center justify-between gap-3">
                                        <span className="text-xs text-ink-500">{t('bookingOps.public.quantity')}</span>
                                        <div className="flex items-center gap-1.5">
                                            <button type="button" className="rounded-lg border border-ink-200 p-1.5 text-ink-600 disabled:opacity-40" disabled={quantity <= 0} onClick={() => setQuantity(tier, quantity - 1)} aria-label="Decrease quantity"><Minus size={14} /></button>
                                            <span className="w-7 text-center text-sm font-semibold">{quantity}</span>
                                            <button type="button" className="rounded-lg border border-ink-200 p-1.5 text-ink-600 disabled:opacity-40" disabled={quantity >= max} onClick={() => setQuantity(tier, quantity + 1)} aria-label="Increase quantity"><Plus size={14} /></button>
                                        </div>
                                    </div>
                                ) : null}
                            </div>
                        );
                    })}
                </div>
            )}

            <div className="mt-4 rounded-xl border border-brand-100 bg-white p-4">
                <div className="flex items-center justify-between text-sm font-semibold text-ink-900">
                    <span>{t('bookingOps.public.total')}</span>
                    <span>{total.toFixed(2)} {tiers[0]?.currency ?? ''}</span>
                </div>
                <div className="mt-3">
                    {!session || session.actor !== 'user' ? (
                        <>
                            {session ? <p className="mb-2 text-xs text-ink-500">{t('bookingOps.public.individualOnly')}</p> : null}
                            <Link to={`/user/login?redirect=${encodeURIComponent(location.pathname)}`} className="btn-primary w-full justify-center">
                                {t('bookingOps.public.signIn')}
                            </Link>
                        </>
                    ) : (
                        <Button className="w-full justify-center" onClick={submit} disabled={selectedItems.length === 0} loading={booking.isPending}>
                            {t('bookingOps.public.confirm')}
                        </Button>
                    )}
                </div>
            </div>
        </section>
    );
}
