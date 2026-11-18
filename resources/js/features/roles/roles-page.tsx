import { useEffect, useMemo, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import { LockKeyhole, Pencil, Plus, ShieldCheck, Trash2 } from 'lucide-react';
import { createRole, deleteRole, getPermissionCatalog, getRoles, updateRole } from '@/api/tenant';
import type { PermissionOption, TenantRole } from '@/api/types';
import {
    Alert,
    Badge,
    Button,
    ConfirmDialog,
    EmptyState,
    Field,
    FormError,
    Input,
    Modal,
    SearchInput,
    Skeleton,
    TableShell,
    Td,
    Th,
} from '@/components/ui';
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';
import { useAuthStore } from '@/stores/auth-store';

export function RolesPage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<TenantRole | null>(null);
    const [deleting, setDeleting] = useState<TenantRole | null>(null);
    const [busy, setBusy] = useState(false);

    const canCreate = session?.permissions.includes('roles.create') ?? false;
    const canUpdate = session?.permissions.includes('roles.update') ?? false;
    const canDelete = session?.permissions.includes('roles.delete') ?? false;

    const roles = useQuery({ queryKey: ['tenant', 'roles'], queryFn: getRoles });
    const permissions = useQuery({
        queryKey: ['tenant', 'permissions'],
        queryFn: getPermissionCatalog,
        enabled: canCreate || canUpdate,
    });

    const refresh = () => {
        void queryClient.invalidateQueries({ queryKey: ['tenant', 'roles'] });
    };

    const remove = async () => {
        if (!deleting) {
            return;
        }

        setBusy(true);
        try {
            await deleteRole(deleting.id);
            toast.success(t('roleManagement.roleDeleted'));
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
            title={t('roleManagement.title')}
            subtitle={t('roleManagement.subtitle')}
            actions={canCreate ? <Button onClick={() => { setEditing(null); setFormOpen(true); }}><Plus size={16} /> {t('roleManagement.addRole')}</Button> : undefined}
        >
            {permissions.isError ? (
                <div className="mb-4">
                    <Alert tone="warning" title={t('errors.server')}>{errorMessage(permissions.error)}</Alert>
                </div>
            ) : null}

            {roles.isLoading ? (
                <div className="space-y-3">{[1, 2, 3].map((item) => <Skeleton key={item} className="h-20" />)}</div>
            ) : roles.data && roles.data.length > 0 ? (
                <TableShell>
                    <table className="min-w-full">
                        <thead className="bg-ink-50">
                            <tr>
                                <Th>{t('roleManagement.name')}</Th>
                                <Th>{t('common.status')}</Th>
                                <Th>{t('roleManagement.permissions')}</Th>
                                <Th className="text-end">{t('common.actions')}</Th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-ink-100">
                            {roles.data.map((role) => (
                                <tr key={role.id} className="hover:bg-ink-50/60">
                                    <Td>
                                        <div className="flex items-center gap-3">
                                            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                                                {role.is_system ? <LockKeyhole size={16} /> : <ShieldCheck size={16} />}
                                            </span>
                                            <div>
                                                <p className="font-semibold text-ink-900">{role.name}</p>
                                                <p className="text-xs text-ink-500">{role.is_system ? t('roleManagement.system') : t('roleManagement.custom')}</p>
                                            </div>
                                        </div>
                                    </Td>
                                    <Td><Badge tone={role.is_active ? 'success' : 'neutral'}>{role.is_active ? t('common.active') : t('common.inactive')}</Badge></Td>
                                    <Td>
                                        <span className="text-sm text-ink-600">{t('roleManagement.permissionCount', { count: role.permissions.length })}</span>
                                    </Td>
                                    <Td>
                                        {!role.is_system ? (
                                            <div className="flex justify-end gap-1">
                                                {canUpdate ? (
                                                    <button type="button" onClick={() => { setEditing(role); setFormOpen(true); }} className="rounded-lg p-2 text-ink-500 hover:bg-ink-100 hover:text-ink-900" aria-label={t('common.edit')}>
                                                        <Pencil size={15} />
                                                    </button>
                                                ) : null}
                                                {canDelete ? (
                                                    <button type="button" onClick={() => setDeleting(role)} className="rounded-lg p-2 text-red-500 hover:bg-red-50 hover:text-red-700" aria-label={t('common.delete')}>
                                                        <Trash2 size={15} />
                                                    </button>
                                                ) : null}
                                            </div>
                                        ) : (
                                            <span className="block text-end text-xs text-ink-400">{t('roleManagement.protected')}</span>
                                        )}
                                    </Td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </TableShell>
            ) : (
                <EmptyState
                    title={t('roleManagement.empty')}
                    icon={<ShieldCheck size={22} />}
                    action={canCreate ? <Button onClick={() => setFormOpen(true)}><Plus size={16} /> {t('roleManagement.addRole')}</Button> : undefined}
                />
            )}

            <RoleModal
                open={formOpen}
                role={editing}
                permissions={permissions.data ?? []}
                onClose={() => setFormOpen(false)}
                onSaved={() => { setFormOpen(false); refresh(); }}
            />

            <ConfirmDialog
                open={deleting !== null}
                title={t('common.delete')}
                description={t('roleManagement.deleteConfirm', { name: deleting?.name ?? '' })}
                confirmLabel={t('common.delete')}
                cancelLabel={t('common.cancel')}
                danger
                loading={busy}
                onClose={() => setDeleting(null)}
                onConfirm={() => void remove()}
            />
        </TenantPage>
    );
}

function RoleModal({
    open,
    role,
    permissions,
    onClose,
    onSaved,
}: {
    open: boolean;
    role: TenantRole | null;
    permissions: PermissionOption[];
    onClose: () => void;
    onSaved: () => void;
}) {
    const { t } = useTranslation();
    const [name, setName] = useState('');
    const [selected, setSelected] = useState<number[]>([]);
    const [search, setSearch] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        setName(role?.name ?? '');
        setSelected(role?.permission_ids ?? []);
        setSearch('');
        setError(null);
    }, [open, role]);

    const filtered = useMemo(() => {
        const query = search.trim().toLowerCase();
        return permissions.filter((permission) => !query || permission.title.toLowerCase().includes(query) || permission.name.toLowerCase().includes(query));
    }, [permissions, search]);

    const grouped = useMemo(() => {
        return filtered.reduce<Record<string, PermissionOption[]>>((groups, permission) => {
            const key = permission.module ?? 'general';
            groups[key] = groups[key] ?? [];
            groups[key].push(permission);
            return groups;
        }, {});
    }, [filtered]);

    const toggle = (id: number) => {
        setSelected((current) => current.includes(id) ? current.filter((value) => value !== id) : [...current, id]);
    };

    const save = async () => {
        setError(null);
        setSaving(true);
        try {
            if (role) {
                await updateRole(role.id, { name, permission_ids: selected });
                toast.success(t('roleManagement.roleUpdated'));
            } else {
                await createRole({ name, permission_ids: selected });
                toast.success(t('roleManagement.roleCreated'));
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
            title={role ? t('roleManagement.editRole') : t('roleManagement.addRole')}
            size="lg"
            footer={
                <>
                    <Button variant="secondary" onClick={onClose} disabled={saving}>{t('common.cancel')}</Button>
                    <Button onClick={() => void save()} loading={saving}>{t('common.save')}</Button>
                </>
            }
        >
            <div className="space-y-4">
                {error ? <FormError>{error}</FormError> : null}
                <Field label={t('roleManagement.name')}>
                    <Input value={name} onChange={(event) => setName(event.target.value)} required />
                </Field>
                <div>
                    <div className="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p className="label mb-0">{t('roleManagement.permissions')}</p>
                        <div className="flex flex-wrap gap-2 text-xs">
                            <button type="button" className="font-semibold text-brand-700 hover:text-brand-800" onClick={() => setSelected(permissions.map((permission) => permission.id))}>{t('roleManagement.selectAll')}</button>
                            <span className="text-ink-300">·</span>
                            <button type="button" className="font-semibold text-ink-500 hover:text-ink-800" onClick={() => setSelected([])}>{t('roleManagement.clearAll')}</button>
                        </div>
                    </div>
                    <SearchInput value={search} onChange={setSearch} placeholder={t('roleManagement.permissionSearch')} />
                    <div className="mt-3 max-h-80 space-y-4 overflow-y-auto rounded-xl border border-ink-200 p-3">
                        {Object.keys(grouped).length === 0 ? (
                            <p className="py-6 text-center text-sm text-ink-500">{t('roleManagement.noPermissions')}</p>
                        ) : Object.entries(grouped).map(([module, items]) => (
                            <section key={module}>
                                <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">{module.replaceAll('_', ' ')}</h3>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {items.map((permission) => (
                                        <label key={permission.id} className="flex cursor-pointer items-start gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-ink-50">
                                            <input type="checkbox" className="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-700" checked={selected.includes(permission.id)} onChange={() => toggle(permission.id)} />
                                            <span>
                                                <span className="block font-medium text-ink-800">{permission.title}</span>
                                                <span className="block font-mono text-[11px] text-ink-400">{permission.name}</span>
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>
                </div>
            </div>
        </Modal>
    );
}
