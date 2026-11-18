import { useEffect, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { Building2, KeyRound, Mail, Phone, ShieldCheck, Trash2, UserRound } from 'lucide-react';
import { changeAdminPassword, getAdminProfile, updateAdminProfile } from '@/api/admin';
import { verifyUserPhone } from '@/api/user';
import { changeUserPassword, deleteUserAccount, getUserDashboard, updateUserProfile } from '@/api/user';
import type { AdminProfile, UserProfile } from '@/api/types';
import {
    Alert,
    Badge,
    Button,
    ConfirmDialog,
    Field,
    FormError,
    Input,
    Skeleton,
} from '@/components/ui';
import { OtpInput, PasswordField, PhoneNumberField } from '@/components/forms';
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';
import { formatDateTime } from '@/lib/format';
import { useAuthStore } from '@/stores/auth-store';

function splitPhone(phone: string | null | undefined, countryCode: string | null | undefined): { code: string; local: string } {
    const code = countryCode || '+966';
    if (!phone) {
        return { code, local: '' };
    }

    return {
        code,
        local: phone.startsWith(code) ? phone.slice(code.length) : phone.replace(/\D/g, ''),
    };
}

export function TenantAccountPage() {
    const { t } = useTranslation();
    const session = useAuthStore((state) => state.session);
    const tenant = session?.tenant;
    const member = session?.member;

    return (
        <TenantPage title={t('profile.title')} subtitle={t('profile.subtitle')}>
            <div className="grid gap-4 lg:grid-cols-2">
                <section className="card card-body">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><UserRound size={18} /></span>
                        <div>
                            <h2 className="font-semibold text-ink-950">{t('profile.accountInfo')}</h2>
                            <p className="text-xs text-ink-500">{t('common.readOnly')}</p>
                        </div>
                    </div>
                    <dl className="space-y-3 text-sm">
                        <InfoRow label={t('common.name')} value={member?.name ?? '—'} />
                        <InfoRow label={t('common.email')} value={member?.email ?? '—'} />
                        <InfoRow label={t('common.phone')} value={member?.phone ?? '—'} />
                        <InfoRow label={t('profile.lastLogin')} value={formatDateTime(member?.last_login_at)} />
                    </dl>
                    <div className="mt-5 flex flex-wrap gap-2">
                        {member?.roles.map((role) => <Badge key={role.id} tone="neutral">{role.name}</Badge>)}
                        {member?.is_owner ? <Badge tone="info">{t('team.ownerBadge')}</Badge> : null}
                    </div>
                </section>

                <section className="card card-body">
                    <div className="mb-4 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><Building2 size={18} /></span>
                        <div>
                            <h2 className="font-semibold text-ink-950">{t('profile.workspaceInfo')}</h2>
                            <p className="text-xs text-ink-500">{t('profile.readOnlyNote')}</p>
                        </div>
                    </div>
                    <dl className="space-y-3 text-sm">
                        <InfoRow label={t('profile.tenantName')} value={tenant?.name ?? '—'} />
                        <InfoRow label={t('profile.subdomain')} value={tenant ? `${tenant.subdomain}.theaterlink` : '—'} />
                        <InfoRow label={t('profile.type')} value={tenant?.type === 'group' ? t('roles.group') : t('roles.theater')} />
                        <InfoRow label={t('common.status')} value={tenant?.status ?? '—'} />
                    </dl>
                    <div className="mt-5">
                        <Alert tone="info">{t('profile.readOnlyNote')}</Alert>
                    </div>
                </section>
            </div>

            <section className="card card-body mt-4">
                <div className="flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><ShieldCheck size={18} /></span>
                    <div>
                        <h2 className="font-semibold text-ink-950">{t('roleManagement.permissions')}</h2>
                        <p className="text-xs text-ink-500">{t('profile.securitySubtitle')}</p>
                    </div>
                </div>
                <div className="mt-4 flex flex-wrap gap-1.5">
                    {(session?.permissions ?? []).map((permission) => (
                        <span key={permission} className="rounded-lg bg-ink-100 px-2 py-1 font-mono text-[11px] text-ink-600">{permission}</span>
                    ))}
                </div>
            </section>
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

export function UserProfilePage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const setSession = useAuthStore((state) => state.setSession);
    const profile = useQuery({ queryKey: ['user', 'profile'], queryFn: getUserDashboard });
    const user = profile.data?.user ?? session?.user;
    const initial = splitPhone(user?.phone, user?.country_code);
    const [form, setForm] = useState({ name: '', email: '', postal_code: '', main_address: '' });
    const [countryCode, setCountryCode] = useState(initial.code);
    const [localPhone, setLocalPhone] = useState(initial.local);
    const [phoneNeedsVerification, setPhoneNeedsVerification] = useState(false);
    const [pendingPhone, setPendingPhone] = useState('');
    const [otp, setOtp] = useState('');
    const [devOtp, setDevOtp] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);
    const [verifying, setVerifying] = useState(false);

    useEffect(() => {
        if (!user) {
            return;
        }

        const phone = splitPhone(user.phone, user.country_code);
        setForm({
            name: user.name ?? '',
            email: user.email ?? '',
            postal_code: user.postal_code ?? '',
            main_address: user.main_address ?? '',
        });
        setCountryCode(phone.code);
        setLocalPhone(phone.local);
    }, [user]);

    if (profile.isLoading) {
        return <TenantPage><Skeleton className="h-64" /></TenantPage>;
    }

    const save = async () => {
        setError(null);
        setSaving(true);
        try {
            const response = await updateUserProfile({
                name: form.name,
                email: form.email || undefined,
                country_code: countryCode,
                phone: localPhone,
                postal_code: form.postal_code || undefined,
                main_address: form.main_address || undefined,
            }) as UserProfile & { needs_phone_verification?: boolean; pending_phone?: string; otp_code?: string | null };

            if (response.needs_phone_verification) {
                setPhoneNeedsVerification(true);
                setPendingPhone(response.pending_phone ?? localPhone);
                setDevOtp(response.otp_code ?? null);
                toast.success(t('profile.phonePending'));
                return;
            }

            if (session) {
                setSession({ ...session, user: response });
            }
            toast.success(t('profile.profileUpdated'));
            void queryClient.invalidateQueries({ queryKey: ['user'] });
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSaving(false);
        }
    };

    const verifyPhone = async () => {
        setError(null);
        setVerifying(true);
        try {
            const updated = await verifyUserPhone({ country_code: countryCode, phone: pendingPhone, otp });
            if (session) {
                setSession({ ...session, user: updated });
            }
            setPhoneNeedsVerification(false);
            setOtp('');
            toast.success(t('profile.phoneUpdated'));
            void queryClient.invalidateQueries({ queryKey: ['user'] });
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setVerifying(false);
        }
    };

    return (
        <TenantPage title={t('profile.userTitle')} subtitle={t('profile.userSubtitle')}>
            <div className="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                <section className="card card-body">
                    <div className="mb-5 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><UserRound size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('profile.accountInfo')}</h2>
                    </div>
                    <div className="space-y-4">
                        {error ? <FormError>{error}</FormError> : null}
                        <Field label={t('common.name')}><Input value={form.name} onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))} /></Field>
                        <Field label={t('auth.email')}><Input type="email" value={form.email} onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))} /></Field>
                        <PhoneNumberField
                            countryCode={countryCode}
                            phone={localPhone}
                            onCountryCodeChange={setCountryCode}
                            onPhoneChange={setLocalPhone}
                            hint={t('profile.phoneChangeHint')}
                        />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label={t('profile.postalCode')}><Input value={form.postal_code} onChange={(event) => setForm((current) => ({ ...current, postal_code: event.target.value }))} /></Field>
                            <Field label={t('profile.address')}><Input value={form.main_address} onChange={(event) => setForm((current) => ({ ...current, main_address: event.target.value }))} /></Field>
                        </div>
                        <Button onClick={() => void save()} loading={saving}>{t('profile.updateProfile')}</Button>
                    </div>
                </section>

                <div className="space-y-4">
                    <section className="card card-body">
                        <div className="flex items-center gap-3">
                            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-ink-100 text-ink-600"><Mail size={18} /></span>
                            <div>
                                <h2 className="font-semibold text-ink-950">{t('common.account')}</h2>
                                <p className="text-xs text-ink-500">{user?.phone}</p>
                            </div>
                        </div>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <Badge tone={user?.is_active ? 'success' : 'neutral'}>{user?.is_active ? t('common.active') : t('common.inactive')}</Badge>
                            {user?.country_code ? <Badge tone="neutral">{user.country_code}</Badge> : null}
                        </div>
                    </section>

                    {phoneNeedsVerification ? (
                        <section className="card card-body">
                            <h2 className="font-semibold text-ink-950">{t('profile.verifyPhone')}</h2>
                            <p className="mt-1 text-sm text-ink-500">{t('profile.phonePending')}</p>
                            <div className="mt-4"><OtpInput value={otp} onChange={setOtp} /></div>
                            {devOtp && import.meta.env.DEV ? <p className="mt-2 text-center text-xs text-ink-500">{t('common.devOtp')}: <span className="font-mono font-bold">{devOtp}</span></p> : null}
                            <Button className="mt-4 w-full" onClick={() => void verifyPhone()} loading={verifying} disabled={otp.length !== 4}>
                                <Phone size={16} /> {t('profile.verifyPhone')}
                            </Button>
                        </section>
                    ) : null}
                </div>
            </div>
        </TenantPage>
    );
}

export function UserSecurityPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const clearSession = useAuthStore((state) => state.clearSession);
    const [oldPassword, setOldPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const change = async () => {
        setError(null);
        setSaving(true);
        try {
            await changeUserPassword({
                old_password: oldPassword,
                new_password: newPassword,
                new_password_confirmation: confirmation,
            });
            setOldPassword('');
            setNewPassword('');
            setConfirmation('');
            toast.success(t('profile.passwordChanged'));
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSaving(false);
        }
    };

    const remove = async () => {
        setDeleting(true);
        try {
            await deleteUserAccount();
            clearSession();
            toast.success(t('profile.deleteWarning'));
            navigate('/user/login', { replace: true });
        } catch (caught) {
            toast.error(errorMessage(caught));
        } finally {
            setDeleting(false);
        }
    };

    return (
        <TenantPage title={t('profile.securityTitle')} subtitle={t('profile.securitySubtitle')}>
            <div className="grid gap-4 lg:grid-cols-2">
                <section className="card card-body">
                    <div className="mb-5 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><KeyRound size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('profile.changePassword')}</h2>
                    </div>
                    <div className="space-y-4">
                        {error ? <FormError>{error}</FormError> : null}
                        <PasswordField label={t('profile.currentPassword')} value={oldPassword} onChange={setOldPassword} />
                        <PasswordField label={t('profile.newPassword')} value={newPassword} onChange={setNewPassword} autoComplete="new-password" />
                        <PasswordField label={t('profile.confirmPassword')} value={confirmation} onChange={setConfirmation} autoComplete="new-password" />
                        <Button onClick={() => void change()} loading={saving}>{t('profile.changePassword')}</Button>
                    </div>
                </section>

                <section className="card card-body border-red-200">
                    <div className="mb-5 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600"><Trash2 size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('profile.deleteAccount')}</h2>
                    </div>
                    <p className="text-sm text-ink-500">{t('profile.deleteWarning')}</p>
                    <Button variant="danger" className="mt-5" onClick={() => setDeleteOpen(true)}>{t('profile.deleteAccount')}</Button>
                </section>
            </div>

            <ConfirmDialog
                open={deleteOpen}
                title={t('profile.deleteAccount')}
                description={t('profile.deleteConfirm')}
                confirmLabel={t('common.delete')}
                cancelLabel={t('common.cancel')}
                danger
                loading={deleting}
                onClose={() => setDeleteOpen(false)}
                onConfirm={() => void remove()}
            />
        </TenantPage>
    );
}

export function AdminProfilePage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const session = useAuthStore((state) => state.session);
    const setSession = useAuthStore((state) => state.setSession);
    const profile = useQuery({ queryKey: ['admin', 'profile'], queryFn: getAdminProfile });
    const admin = profile.data ?? session?.admin;
    const [name, setName] = useState(admin?.name ?? '');
    const [phone, setPhone] = useState(admin?.phone ?? '');
    const [oldPassword, setOldPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmation, setConfirmation] = useState('');
    const [profileError, setProfileError] = useState<string | null>(null);
    const [passwordError, setPasswordError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);
    const [changing, setChanging] = useState(false);

    useEffect(() => {
        if (admin) {
            setName(admin.name ?? '');
            setPhone(admin.phone ?? '');
        }
    }, [admin]);

    if (profile.isLoading) {
        return <TenantPage><Skeleton className="h-64" /></TenantPage>;
    }

    const saveProfile = async () => {
        setProfileError(null);
        setSaving(true);
        try {
            const updated = await updateAdminProfile({ name, phone: phone || null });
            if (session) {
                setSession({ ...session, admin: updated });
            }
            toast.success(t('profile.profileUpdated'));
            void queryClient.invalidateQueries({ queryKey: ['admin'] });
        } catch (caught) {
            setProfileError(errorMessage(caught));
        } finally {
            setSaving(false);
        }
    };

    const changePassword = async () => {
        setPasswordError(null);
        setChanging(true);
        try {
            await changeAdminPassword({ old_password: oldPassword, new_password: newPassword, password_confirmation: confirmation });
            setOldPassword('');
            setNewPassword('');
            setConfirmation('');
            toast.success(t('profile.passwordChanged'));
        } catch (caught) {
            setPasswordError(errorMessage(caught));
        } finally {
            setChanging(false);
        }
    };

    return (
        <TenantPage title={t('admin.profileTitle')} subtitle={t('admin.profileSubtitle')}>
            <div className="grid gap-4 lg:grid-cols-2">
                <section className="card card-body">
                    <div className="mb-5 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><ShieldCheck size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('profile.accountInfo')}</h2>
                    </div>
                    <div className="space-y-4">
                        {profileError ? <FormError>{profileError}</FormError> : null}
                        <Field label={t('common.name')}><Input value={name} onChange={(event) => setName(event.target.value)} /></Field>
                        <Field label={t('auth.email')}><Input value={admin?.email ?? ''} disabled /></Field>
                        <Field label={t('common.phone')}><Input value={phone} onChange={(event) => setPhone(event.target.value)} /></Field>
                        <Button onClick={() => void saveProfile()} loading={saving}>{t('common.save')}</Button>
                    </div>
                </section>

                <section className="card card-body">
                    <div className="mb-5 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><KeyRound size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('profile.changePassword')}</h2>
                    </div>
                    <div className="space-y-4">
                        {passwordError ? <FormError>{passwordError}</FormError> : null}
                        <PasswordField label={t('profile.currentPassword')} value={oldPassword} onChange={setOldPassword} />
                        <PasswordField label={t('profile.newPassword')} value={newPassword} onChange={setNewPassword} autoComplete="new-password" />
                        <PasswordField label={t('profile.confirmPassword')} value={confirmation} onChange={setConfirmation} autoComplete="new-password" />
                        <Button onClick={() => void changePassword()} loading={changing}>{t('profile.changePassword')}</Button>
                    </div>
                </section>
            </div>
            <div className="mt-4"><Alert tone="info">{t('admin.phaseNote')}</Alert></div>
        </TenantPage>
    );
}
