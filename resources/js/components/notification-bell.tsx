import { useEffect, useRef, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { Bell, CheckCheck, Inbox, X } from 'lucide-react';
import { getNotifications, markAllNotificationsRead, markNotificationRead } from '@/api/notifications';
import type { AppNotification } from '@/api/types';
import { cn } from '@/lib/cn';
import { formatDateTime } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';

export function NotificationBell() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [open, setOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement | null>(null);
    const notifications = useQuery({
        queryKey: ['notifications', session?.actor, session?.user?.id ?? session?.member?.id ?? session?.admin?.id],
        queryFn: getNotifications,
        enabled: Boolean(session),
        refetchInterval: 60_000,
    });

    useEffect(() => {
        const onClick = (event: MouseEvent) => {
            if (!menuRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        if (open) {
            document.addEventListener('mousedown', onClick);
        }

        return () => document.removeEventListener('mousedown', onClick);
    }, [open]);

    if (!session) {
        return null;
    }

    const unread = notifications.data?.unread_count ?? 0;

    const markRead = async (notification: AppNotification) => {
        if (!notification.read_at) {
            await markNotificationRead(notification.id);
            void queryClient.invalidateQueries({ queryKey: ['notifications'] });
        }
    };

    const openNotification = async (notification: AppNotification) => {
        await markRead(notification);
        setOpen(false);

        if (notification.data?.application_id) {
            navigate(session.actor === 'admin'
                ? `/admin/applications/${notification.data.application_id}`
                : '/group/application');
        } else if (notification.data?.reservation_id) {
            navigate(session.actor === 'theater'
                ? `/theater/reservations/${notification.data.reservation_id}`
                : `/group/reservations/${notification.data.reservation_id}`);
        } else if (notification.data?.booking_id) {
            navigate(session.actor === 'theater'
                ? `/theater/bookings/${notification.data.booking_id}`
                : `/user/bookings/${notification.data.booking_id}`);
        }
    };

    const markAll = async () => {
        await markAllNotificationsRead();
        void queryClient.invalidateQueries({ queryKey: ['notifications'] });
    };

    return (
        <div className="relative" ref={menuRef}>
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                className="relative rounded-xl p-2 text-ink-500 transition hover:bg-ink-100 hover:text-ink-900"
                aria-label={t('notifications.title')}
                aria-expanded={open}
            >
                <Bell size={19} />
                {unread > 0 ? (
                    <span className="absolute -end-0.5 -top-0.5 flex min-h-4 min-w-4 items-center justify-center rounded-full bg-brand-700 px-1 text-[9px] font-bold text-white">
                        {unread > 9 ? '9+' : unread}
                    </span>
                ) : null}
            </button>

            {open ? (
                <div className="absolute end-0 z-30 mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-2xl">
                    <div className="flex items-center justify-between border-b border-ink-100 px-4 py-3">
                        <div>
                            <h2 className="text-sm font-semibold text-ink-950">{t('notifications.title')}</h2>
                            {unread > 0 ? <p className="text-xs text-ink-500">{t('notifications.unread', { count: unread })}</p> : null}
                        </div>
                        <div className="flex items-center gap-1">
                            {unread > 0 ? (
                                <button type="button" onClick={() => void markAll()} className="rounded-lg p-1.5 text-ink-400 hover:bg-ink-100 hover:text-ink-800" title={t('notifications.markAllRead')} aria-label={t('notifications.markAllRead')}>
                                    <CheckCheck size={16} />
                                </button>
                            ) : null}
                            <button type="button" onClick={() => setOpen(false)} className="rounded-lg p-1.5 text-ink-400 hover:bg-ink-100 hover:text-ink-800" aria-label={t('notifications.close')}>
                                <X size={16} />
                            </button>
                        </div>
                    </div>

                    <div className="max-h-96 overflow-y-auto">
                        {notifications.isLoading ? (
                            <p className="px-4 py-8 text-center text-sm text-ink-500">{t('common.loading')}</p>
                        ) : (notifications.data?.data.length ?? 0) === 0 ? (
                            <div className="flex flex-col items-center px-4 py-10 text-center">
                                <Inbox size={24} className="mb-2 text-ink-300" />
                                <p className="text-sm text-ink-500">{t('notifications.empty')}</p>
                            </div>
                        ) : (
                            notifications.data?.data.map((notification) => (
                                <button
                                    key={notification.id}
                                    type="button"
                                    onClick={() => void openNotification(notification)}
                                    className={cn(
                                        'block w-full border-b border-ink-50 px-4 py-3 text-start transition last:border-0 hover:bg-ink-50',
                                        !notification.read_at && 'bg-brand-50/40',
                                    )}
                                >
                                    <div className="flex items-start gap-3">
                                        <span className={cn('mt-1.5 h-2 w-2 shrink-0 rounded-full', notification.read_at ? 'bg-ink-200' : 'bg-brand-600')} />
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold text-ink-900">{notification.title}</p>
                                            <p className="mt-0.5 line-clamp-2 text-xs leading-5 text-ink-500">{notification.body}</p>
                                            <p className="mt-1 text-[11px] text-ink-400">{formatDateTime(notification.created_at)}</p>
                                        </div>
                                    </div>
                                </button>
                            ))
                        )}
                    </div>
                </div>
            ) : null}
        </div>
    );
}
