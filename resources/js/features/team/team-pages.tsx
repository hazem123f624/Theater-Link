import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link, useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { ArchiveRestore, Pencil, Plus, RotateCcw, ShieldCheck, Trash2, UserRound, Users, X } from 'lucide-react';
import {
    createTeamMember,
    deleteTeamMember,
    forceDeleteTeamMember,
    getRoles,
    getTeam,
    getTeamTrash,
    restoreTeamMember,
    toggleTeamMember,
    updateTeamMember,
    type TeamMemberPayload,
} from '@/api/tenant';
import type { TeamMember, TenantRole } from '@/api/types';
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
    Skeleton,
    TableShell,
    Td,
    Th,
} from '@/components/ui';
import { TenantPage } from '@/components/layout';
import { PasswordField } from '@/components/forms';
import { errorMessage } from '@/lib/errors';
import { formatDateTime, initials } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';

type ConfirmAction = { type: 'delete' | 'restore' | 'force-delete'; member: TeamMember } | null;

interface MemberFormState {
    name: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
    roles: number[];
    is_active: boolean;
}

const emptyForm: MemberFormState = {
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    roles: [],
    is_active: true,
};

export function TeamPage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<TeamMember | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [confirmAction, setConfirmAction] = useState<ConfirmAction>(null);
    const [busy, setBusy] = useState(false);

    const canCreate = session?.permissions.includes('team.create') ?? false;
    const canUpdate = session?.permissions.includes('team.update') ?? false;
    const canDelete = session?.permissions.includes('team.delete') ?? false;

    const team = useQuery({
        queryKey: ['tenant', 'team', search, page],
        queryFn: () => getTeam({ search, page, per_page: 10 }),
    });
    const roles = useQuery({ queryKey: ['tenant', 'roles'], queryFn: getRoles });

    const invalidate = () => {
        void queryClient.invalidateQueries({ queryKey: ['tenant', 'team'] });
        void queryClient.invalidateQueries({ queryKey: ['tenant', 'team-trash'] });
    };

    const openCreate = () => {
        setEditing(null);
        setFormOpen(true);
    };

    const openEdit = (member: TeamMember) => {
        setEditing(member);
        setFormOpen(true);
    };

    const onToggle = async (member: TeamMember) => {
        setBusy(true);
        try {
            await toggleTeamMember(member.id);
            toast.success(t('team.memberUpdated'));
            invalidate();
        } catch (error) {
            toast.error(errorMessage(error));
        } finally {
            setBusy(false);
        }
    };

    const runConfirm = async () => {
        if (!confirmAction) {
            return;
        }

        setBusy(true);
        try {
            if (confirmAction.type === 'delete') {
                await deleteTeamMember(confirmAction.member.id);
                toast.success(t('team.memberDeleted'));
            } else if (confirmAction.type === 'restore') {
                await restoreTeamMember(confirmAction.member.id);
                toast.success(t('team.memberRestored'));
            } else {
                await forceDeleteTeamMember(confirmAction.member.id);
                toast.success(t('team.memberForceDeleted'));
            }
            invalidate();
            setConfirmAction(null);
        } catch (error) {
            toast.error(errorMessage(error));
        } finally {
            setBusy(false);
        }
    };

    return (
        <TenantPage
            title={t('team.title')}
            subtitle={t('team.subtitle')}
            actions={
                <>
                    <Link to={`/${session?.actor}/team/trash`} className="btn-secondary">
                        <ArchiveRestore size={16} /> {t('team.showTrash')}
                    </Link>
                    {canCreate ? <Button onClick={openCreate}><Plus size={16} /> {t('team.addMember')}</Button> : null}
                </>
            }
        >
            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="w-full sm:max-w-sm">
                    <SearchInput value={search} onChange={(value) => { setSearch(value); setPage(1); }} placeholder={t('team.searchPlaceholder')} />
                </div>
                <p className="text-xs text-ink-500">{t('team.roles')}: {roles.data?.length ?? 0}</p>
            </div>

            {team.isLoading ? (
                <div className="space-y-3">{[1, 2, 3, 4].map((item) => <Skeleton key={item} className="h-16" />)}</div>
            ) : team.data && team.data.data.length > 0 ? (
                <>
                    <TableShell>
                        <table className="min-w-full">
                            <thead className="bg-ink-50">
                                <tr>
                                    <Th>{t('team.member')}</Th>
                                    <Th>{t('team.roles')}</Th>
                                    <Th>{t('team.status')}</Th>
                                    <Th>{t('team.lastLogin')}</Th>
                                    <Th className="text-end">{t('common.actions')}</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-ink-100">
                                {team.data.data.map((member) => (
                                    <tr key={member.id} className="hover:bg-ink-50/60">
                                        <Td>
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">
                                                    {initials(member.name)}
                                                </span>
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <p className="truncate font-semibold text-ink-900">{member.name}</p>
                                                        {member.is_owner ? <Badge tone="info">{t('team.ownerBadge')}</Badge> : null}
                                                    </div>
                                                    <p className="truncate text-xs text-ink-500">{member.email}</p>
                                                </div>
                                            </div>
                                        </Td>
                                        <Td>
                                            <div className="flex flex-wrap gap-1">
                                                {member.roles.length > 0 ? member.roles.map((role) => (
                                                    <span key={role.id} className="rounded-lg bg-ink-100 px-2 py-1 text-xs text-ink-600">{role.name}</span>
                                                )) : <span className="text-xs text-ink-400">—</span>}
                                            </div>
                                        </Td>
                                        <Td>
                                            <Badge tone={member.is_active ? 'success' : 'neutral'}>
                                                {member.is_active ? t('team.activeBadge') : t('team.inactiveBadge')}
                                            </Badge>
                                        </Td>
                                        <Td className="whitespace-nowrap text-xs text-ink-500">{formatDateTime(member.last_login_at)}</Td>
                                        <Td>
                                            <div className="flex items-center justify-end gap-1">
                                                {canUpdate ? (
                                                    <>
                                                        <button type="button" onClick={() => openEdit(member)} className="rounded-lg p-2 text-ink-500 hover:bg-ink-100 hover:text-ink-900" aria-label={t('common.edit')}>
                                                            <Pencil size={15} />
                                                        </button>
                                                        <button type="button" onClick={() => void onToggle(member)} disabled={busy} className="rounded-lg p-2 text-ink-500 hover:bg-ink-100 hover:text-ink-900" aria-label={t('team.status')}>
                                                            <UserRound size={15} />
                                                        </button>
                                                    </>
                                                ) : null}
                                                {canDelete && !member.is_owner ? (
                                                    <button type="button" onClick={() => setConfirmAction({ type: 'delete', member })} className="rounded-lg p-2 text-red-500 hover:bg-red-50 hover:text-red-700" aria-label={t('common.delete')}>
                                                        <Trash2 size={15} />
                                                    </button>
                                                ) : null}
                                            </div>
                                        </Td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </TableShell>
                    <div className="mt-4">
                        <Pagination
                            page={team.data.pagination.current_page}
                            lastPage={team.data.pagination.last_page}
                            total={team.data.pagination.total}
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
                    title={t('team.empty')}
                    icon={<Users size={22} />}
                    action={canCreate ? <Button onClick={openCreate}><Plus size={16} /> {t('team.addMember')}</Button> : undefined}
                />
            )}

            <MemberModal
                open={formOpen}
                member={editing}
                roles={roles.data ?? []}
                onClose={() => setFormOpen(false)}
                onSaved={() => {
                    setFormOpen(false);
                    invalidate();
                }}
            />

            <ConfirmDialog
                open={confirmAction !== null}
                title={confirmAction?.type === 'delete' ? t('common.delete') : confirmAction?.type === 'restore' ? t('team.restore') : t('team.forceDelete')}
                description={
                    confirmAction?.type === 'delete'
                        ? t('team.deleteConfirm', { name: confirmAction.member.name })
                        : confirmAction?.type === 'restore'
                            ? t('team.restoreConfirm', { name: confirmAction.member.name })
                            : t('team.forceDeleteConfirm', { name: confirmAction?.member.name ?? '' })
                }
                confirmLabel={confirmAction?.type === 'restore' ? t('team.restore') : t('common.delete')}
                cancelLabel={t('common.cancel')}
                danger={confirmAction?.type !== 'restore'}
                loading={busy}
                onClose={() => setConfirmAction(null)}
                onConfirm={() => void runConfirm()}
            />
        </TenantPage>
    );
}

function MemberModal({
    open,
    member,
    roles,
    onClose,
    onSaved,
}: {
    open: boolean;
    member: TeamMember | null;
    roles: TenantRole[];
    onClose: () => void;
    onSaved: () => void;
}) {
    const { t } = useTranslation();
    const [form, setForm] = useState<MemberFormState>(emptyForm);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        setError(null);
        setForm(member ? {
            name: member.name,
            email: member.email,
            phone: member.phone ?? '',
            password: '',
            password_confirmation: '',
            roles: member.roles.map((role) => role.id),
            is_active: member.is_active,
        } : emptyForm);
    }, [open, member]);

    const update = <K extends keyof MemberFormState>(key: K, value: MemberFormState[K]) => setForm((current) => ({ ...current, [key]: value }));

    const toggleRole = (roleId: number) => {
        setForm((current) => ({
            ...current,
            roles: current.roles.includes(roleId)
                ? current.roles.filter((id) => id !== roleId)
                : [...current.roles, roleId],
        }));
    };

    const save = async () => {
        setError(null);

        if (form.roles.length === 0) {
            setError(t('team.roleRequired'));
            return;
        }

        setSaving(true);
        const payload: TeamMemberPayload = {
            name: form.name,
            email: form.email,
            phone: form.phone || null,
            roles: form.roles,
            is_active: form.is_active,
        };

        if (form.password) {
            payload.password = form.password;
            payload.password_confirmation = form.password_confirmation;
        }

        try {
            if (member) {
                await updateTeamMember(member.id, payload);
                toast.success(t('team.memberUpdated'));
            } else {
                await createTeamMember(payload);
                toast.success(t('team.memberCreated'));
            }
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
            title={member ? t('team.editMember') : t('team.addMember')}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose} disabled={saving}>{t('common.cancel')}</Button>
                    <Button onClick={() => void save()} loading={saving}>{t('common.save')}</Button>
                </>
            }
        >
            <div className="space-y-4">
                {error ? <FormError>{error}</FormError> : null}
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label={t('common.name')}><Input value={form.name} onChange={(event) => update('name', event.target.value)} required /></Field>
                    <Field label={t('auth.email')}><Input type="email" value={form.email} onChange={(event) => update('email', event.target.value)} required /></Field>
                </div>
                <Field label={t('common.phone')}><Input value={form.phone} onChange={(event) => update('phone', event.target.value)} /></Field>
                <div className="grid gap-4 sm:grid-cols-2">
                    <PasswordField label={t('team.password')} value={form.password} onChange={(value) => update('password', value)} autoComplete="new-password" hint={member ? t('team.passwordHint') : undefined} />
                    <PasswordField label={t('team.passwordConfirmation')} value={form.password_confirmation} onChange={(value) => update('password_confirmation', value)} autoComplete="new-password" />
                </div>
                <div>
                    <p className="label">{t('team.roles')}</p>
                    <div className="grid max-h-52 gap-2 overflow-y-auto rounded-xl border border-ink-200 p-2 sm:grid-cols-2">
                        {roles.map((role) => (
                            <label key={role.id} className="flex cursor-pointer items-start gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-ink-50">
                                <input type="checkbox" className="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-700" checked={form.roles.includes(role.id)} onChange={() => toggleRole(role.id)} />
                                <span>
                                    <span className="block font-medium text-ink-800">{role.name}</span>
                                    <span className="block text-xs text-ink-400">{role.is_system ? t('roleManagement.system') : t('roleManagement.custom')}</span>
                                </span>
                            </label>
                        ))}
                    </div>
                    <p className="hint">{t('team.roleHint')}</p>
                </div>
                <label className="flex items-center gap-2 text-sm text-ink-700">
                    <input type="checkbox" className="h-4 w-4 rounded border-ink-300 text-brand-700" checked={form.is_active} onChange={(event) => update('is_active', event.target.checked)} />
                    {t('common.active')}
                </label>
            </div>
        </Modal>
    );
}

export function TeamTrashPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [page, setPage] = useState(1);
    const [confirmAction, setConfirmAction] = useState<ConfirmAction>(null);
    const [busy, setBusy] = useState(false);
    const trash = useQuery({ queryKey: ['tenant', 'team-trash', page], queryFn: () => getTeamTrash({ page, per_page: 10 }) });

    const canRestore = session?.permissions.includes('team.update') ?? false;
    const canDelete = session?.permissions.includes('team.delete') ?? false;

    const runConfirm = async () => {
        if (!confirmAction) {
            return;
        }
        setBusy(true);
        try {
            if (confirmAction.type === 'restore') {
                await restoreTeamMember(confirmAction.member.id);
                toast.success(t('team.memberRestored'));
            } else {
                await forceDeleteTeamMember(confirmAction.member.id);
                toast.success(t('team.memberForceDeleted'));
            }
            void queryClient.invalidateQueries({ queryKey: ['tenant', 'team-trash'] });
            void queryClient.invalidateQueries({ queryKey: ['tenant', 'team'] });
            setConfirmAction(null);
        } catch (error) {
            toast.error(errorMessage(error));
        } finally {
            setBusy(false);
        }
    };

    return (
        <TenantPage
            title={t('team.trashTitle')}
            subtitle={t('team.trashSubtitle')}
            actions={<Button variant="secondary" onClick={() => navigate(`/${session?.actor}/team`)}><X size={16} /> {t('team.backToTeam')}</Button>}
        >
            {trash.isLoading ? (
                <div className="space-y-3">{[1, 2, 3].map((item) => <Skeleton key={item} className="h-16" />)}</div>
            ) : trash.data && trash.data.data.length > 0 ? (
                <>
                    <TableShell>
                        <table className="min-w-full">
                            <thead className="bg-ink-50">
                                <tr>
                                    <Th>{t('team.member')}</Th>
                                    <Th>{t('team.roles')}</Th>
                                    <Th className="text-end">{t('common.actions')}</Th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-ink-100">
                                {trash.data.data.map((member) => (
                                    <tr key={member.id}>
                                        <Td>
                                            <p className="font-semibold text-ink-900">{member.name}</p>
                                            <p className="text-xs text-ink-500">{member.email}</p>
                                        </Td>
                                        <Td>{member.roles.map((role) => role.name).join(', ') || '—'}</Td>
                                        <Td>
                                            <div className="flex justify-end gap-2">
                                                {canRestore ? (
                                                    <Button size="sm" variant="secondary" onClick={() => setConfirmAction({ type: 'restore', member })}>
                                                        <RotateCcw size={14} /> {t('team.restore')}
                                                    </Button>
                                                ) : null}
                                                {canDelete ? (
                                                    <Button size="sm" variant="danger" onClick={() => setConfirmAction({ type: 'force-delete', member })}>
                                                        <Trash2 size={14} /> {t('team.forceDelete')}
                                                    </Button>
                                                ) : null}
                                            </div>
                                        </Td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </TableShell>
                    <div className="mt-4">
                        <Pagination
                            page={trash.data.pagination.current_page}
                            lastPage={trash.data.pagination.last_page}
                            total={trash.data.pagination.total}
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
                <EmptyState title={t('team.noTrash')} icon={<ArchiveRestore size={22} />} />
            )}

            <ConfirmDialog
                open={confirmAction !== null}
                title={confirmAction?.type === 'restore' ? t('team.restore') : t('team.forceDelete')}
                description={confirmAction?.type === 'restore' ? t('team.restoreConfirm', { name: confirmAction.member.name }) : t('team.forceDeleteConfirm', { name: confirmAction?.member.name ?? '' })}
                confirmLabel={confirmAction?.type === 'restore' ? t('team.restore') : t('common.delete')}
                cancelLabel={t('common.cancel')}
                danger={confirmAction?.type === 'force-delete'}
                loading={busy}
                onClose={() => setConfirmAction(null)}
                onConfirm={() => void runConfirm()}
            />
        </TenantPage>
    );
}
