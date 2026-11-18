import { useEffect, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { CalendarClock, Mail, Plus, Send, UserPlus } from 'lucide-react';
import { createInvitation, getInvitations, getRoles } from '@/api/tenant';
import type { Invitation } from '@/api/types';
import {
    Badge,
    Button,
    EmptyState,
    Field,
    FormError,
    Input,
    Modal,
    Pagination,
    Select,
    Skeleton,
    TableShell,
    Td,
    Th,
} from '@/components/ui';
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';
import { formatDate } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';

type InvitationStatus = 'pending' | 'accepted' | 'expired';

function invitationStatus(invitation: Invitation): InvitationStatus {
    if (invitation.accepted_at) {
        return 'accepted';
    }

    return new Date(invitation.expires_at).getTime() < Date.now() ? 'expired' : 'pending';
}

export function InvitationsPage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [page, setPage] = useState(1);
    const [open, setOpen] = useState(false);
    const canInvite = session?.permissions.includes('team.create') ?? false;

    const invitations = useQuery({
        queryKey: ['tenant', 'invitations', page],
        queryFn: () => getInvitations({ page, per_page: 10 }),
    });
    const roles = useQuery({ queryKey: ['tenant', 'roles'], queryFn: getRoles });

    const refresh = () => {
        void queryClient.invalidateQueries({ queryKey: ['tenant', 'invitations'] });
    };

    return (
        <TenantPage
            title={t('invitations.title')}
            subtitle={t('invitations.subtitle')}
            actions={canInvite ? <Button onClick={() => setOpen(true)}><Plus size={16} /> {t('invitations.invite')}</Button> : undefined}
        >
            {invitations.isLoading ? (
                <div className="space-y-3">{[1, 2, 3].map((item) => <Skeleton key={item} className="h-16" />)}</div>
            ) : invitations.data && invitations.data.data.length > 0 ? (
                <>
                    <TableShell>
                        <table className="min-w-full">
                            <thead className="bg-ink-50">
                                <tr>
                                    <Th>{t('invitations.email')}</Th>
                                    <Th>{t('invitations.role')}</Th>
                                    <Th>{t('invitations.status')}</Th>
                                    <Th>{t('invitations.sent')}</Th>
                                    <Th>{t('invitations.expires')}</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-ink-100">
                                {invitations.data.data.map((invitation) => {
                                    const status = invitationStatus(invitation);
                                    return (
                                        <tr key={invitation.id} className="hover:bg-ink-50/60">
                                            <Td>
                                                <div className="flex items-center gap-3">
                                                    <span className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                                                        <Mail size={15} />
                                                    </span>
                                                    <div>
                                                        <p className="font-semibold text-ink-900">{invitation.name ?? invitation.email}</p>
                                                        <p className="text-xs text-ink-500">{invitation.email}</p>
                                                    </div>
                                                </div>
                                            </Td>
                                            <Td>
                                                {invitation.role_id ? (
                                                    <span className="text-sm text-ink-700">{roles.data?.find((role) => role.id === invitation.role_id)?.name ?? `#${invitation.role_id}`}</span>
                                                ) : (
                                                    <span className="text-xs text-ink-400">{t('invitations.noRole')}</span>
                                                )}
                                            </Td>
                                            <Td>
                                                <Badge tone={status === 'accepted' ? 'success' : status === 'expired' ? 'danger' : 'warning'}>
                                                    {t(`invitations.${status}`)}
                                                </Badge>
                                            </Td>
                                            <Td className="text-xs text-ink-500">{formatDate(invitation.accepted_at ?? invitation.expires_at)}</Td>
                                            <Td className="text-xs text-ink-500">{formatDate(invitation.expires_at)}</Td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </TableShell>
                    <div className="mt-4">
                        <Pagination
                            page={invitations.data.pagination.current_page}
                            lastPage={invitations.data.pagination.last_page}
                            total={invitations.data.pagination.total}
                            onChange={setPage}
                            labels={{
                                previous: t('common.previous'),
                                next: t('common.next'),
                                showing: t('common.showing'),
                                results: t('common.results'),
                                page: t('common.page'),
                                of: t('common.of'),
                            }}
                        />
                    </div>
                </>
            ) : (
                <EmptyState
                    title={t('invitations.empty')}
                    icon={<UserPlus size={22} />}
                    action={canInvite ? <Button onClick={() => setOpen(true)}><Plus size={16} /> {t('invitations.invite')}</Button> : undefined}
                />
            )}

            <InviteModal
                open={open}
                roles={roles.data ?? []}
                onClose={() => setOpen(false)}
                onSaved={() => { setOpen(false); refresh(); }}
            />
        </TenantPage>
    );
}

function InviteModal({
    open,
    roles,
    onClose,
    onSaved,
}: {
    open: boolean;
    roles: Array<{ id: number; name: string }>;
    onClose: () => void;
    onSaved: () => void;
}) {
    const { t } = useTranslation();
    const [email, setEmail] = useState('');
    const [name, setName] = useState('');
    const [roleId, setRoleId] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        setEmail('');
        setName('');
        setRoleId('');
        setError(null);
    }, [open]);

    const save = async () => {
        setError(null);
        setSaving(true);
        try {
            await createInvitation({
                email,
                name: name || undefined,
                role_id: roleId ? Number(roleId) : null,
            });
            toast.success(t('invitations.invitationSent'));
            onSaved();
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSaving(false);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title={t('invitations.invite')}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose} disabled={saving}>{t('common.cancel')}</Button>
                    <Button onClick={() => void save()} loading={saving}><Send size={15} /> {t('invitations.invite')}</Button>
                </>
            }
        >
            <div className="space-y-4">
                {error ? <FormError>{error}</FormError> : null}
                <Field label={t('invitations.email')}>
                    <Input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required />
                </Field>
                <Field label={t('invitations.name')}>
                    <Input value={name} onChange={(event) => setName(event.target.value)} />
                </Field>
                <Field label={t('invitations.role')}>
                    <Select value={roleId} onChange={(event) => setRoleId(event.target.value)}>
                        <option value="">{t('invitations.noRole')}</option>
                        {roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}
                    </Select>
                </Field>
                <div className="rounded-xl bg-ink-50 p-3 text-xs text-ink-500">
                    <p className="flex items-center gap-2"><CalendarClock size={14} /> {t('invitations.expires')}: 7 days</p>
                </div>
            </div>
        </Modal>
    );
}
