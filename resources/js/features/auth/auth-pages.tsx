import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from 'react';
import { Link, Navigate, useNavigate, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    FileCheck2,
    KeyRound,
    LockKeyhole,
    Mail,
    ShieldCheck,
    Sparkles,
    Ticket,
    Users,
} from 'lucide-react';
import {
    acceptInvitation,
    loginAdmin,
    loginGroup,
    loginTheater,
    loginUser,
    registerGroup,
    registerTheater,
    registerUser,
    requestAdminPasswordReset,
    requestTenantPasswordReset,
    requestUserPasswordReset,
    resendGroupOtp,
    resendTheaterOtp,
    resendUserOtp,
    resetAdminPassword,
    resetTenantPassword,
    resetUserPassword,
    verifyAdminPasswordReset,
    verifyGroup,
    verifyTenantPasswordReset,
    verifyTheater,
    verifyUser,
    verifyUserPasswordReset,
    type AdminLoginPayload,
} from '@/api/auth';
import { ApiError } from '@/api/client';
import type { Actor, TenantMember, UserProfile } from '@/api/types';
import { Alert, Badge, Button, Field, FormError, Input, Select, Textarea } from '@/components/ui';
import { FileDropzone, fullPhone, LocationFields, OtpInput, PasswordField, PhoneNumberField } from '@/components/forms';
import { AuthLayout, LanguageSwitch, PublicHeader } from '@/components/layout';
import { errorMessage, fieldError } from '@/lib/errors';
import { homePathForSession, adminSession, tenantSession, useAuthStore, userSession } from '@/stores/auth-store';

function useCountdown(initial = 0): [number, (value: number) => void] {
    const [seconds, setSeconds] = useState(initial);

    useEffect(() => {
        if (seconds <= 0) {
            return;
        }

        const timer = window.setTimeout(() => setSeconds((value) => Math.max(0, value - 1)), 1000);
        return () => window.clearTimeout(timer);
    }, [seconds]);

    return [seconds, setSeconds];
}

function DevOtpHint({ otp }: { otp: string | number | null | undefined }) {
    const { t } = useTranslation();
    if (!otp || !import.meta.env.DEV) {
        return null;
    }

    return (
        <p className="mt-3 text-center text-xs text-ink-500">
            {t('common.devOtp')}: <span className="font-mono font-bold text-ink-800">{otp}</span>
        </p>
    );
}

export function LandingPage() {
    const { t } = useTranslation();
    const session = useAuthStore((state) => state.session);

    const roleCards = [
        { icon: <Building2 size={22} />, label: t('roles.theater'), desc: t('roles.theaterDesc'), to: '/theater/login', register: '/register/theater' },
        { icon: <Users size={22} />, label: t('roles.group'), desc: t('roles.groupDesc'), to: '/group/login', register: '/register/group' },
        { icon: <Ticket size={22} />, label: t('roles.user'), desc: t('roles.userDesc'), to: '/user/login', register: '/register/user' },
    ];

    return (
        <div className="min-h-screen bg-brand-950 text-white">
            <PublicHeader />
            <section className="relative overflow-hidden">
                <div className="surface-grid absolute inset-0 opacity-60" aria-hidden="true" />
                <div className="absolute -end-40 top-24 h-96 w-96 rounded-full bg-brand-600/25 blur-3xl" aria-hidden="true" />
                <div className="absolute -start-32 bottom-0 h-80 w-80 rounded-full bg-gold-500/10 blur-3xl" aria-hidden="true" />

                <div className="relative mx-auto flex min-h-[78vh] max-w-7xl flex-col justify-center px-5 pb-16 pt-32 sm:px-8 lg:pt-36">
                    <div className="max-w-3xl">
                        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-gold-200">{t('landing.eyebrow')}</p>
                        <h1 className="mt-5 text-4xl font-bold leading-[1.08] sm:text-6xl">{t('landing.title')}</h1>
                        <p className="mt-6 max-w-2xl text-base leading-8 text-white/70 sm:text-lg">{t('landing.subtitle')}</p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link to="/register/theater" className="btn bg-gold-300 text-brand-950 hover:bg-gold-200">
                                {t('landing.registerTheater')} <ArrowRight size={16} />
                            </Link>
                            <Link to="/register/group" className="btn border border-white/20 bg-white/5 text-white hover:bg-white/10">
                                {t('landing.registerGroup')}
                            </Link>
                            <Link to="/events" className="btn border border-white/20 bg-white/5 text-white hover:bg-white/10">
                                {t('theaterOps.public.eventsTitle')}
                            </Link>
                        </div>
                    </div>

                    <div className="mt-14 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {roleCards.map((card) => (
                            <div key={card.label} className="rounded-2xl border border-white/10 bg-white/[0.06] p-5 backdrop-blur">
                                <div className="mb-4 inline-flex rounded-xl bg-white/10 p-2.5 text-gold-200">{card.icon}</div>
                                <h2 className="text-lg font-semibold">{card.label}</h2>
                                <p className="mt-1.5 min-h-10 text-sm leading-6 text-white/60">{card.desc}</p>
                                <div className="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-semibold">
                                    <Link to={card.to} className="text-gold-200 hover:text-white">{t('auth.signIn')}</Link>
                                    <Link to={card.register} className="text-white/60 hover:text-white">{t('auth.register')}</Link>
                                </div>
                            </div>
                        ))}
                        <div className="rounded-2xl border border-white/10 bg-brand-900/70 p-5">
                            <div className="mb-4 inline-flex rounded-xl bg-white/10 p-2.5 text-gold-200"><ShieldCheck size={22} /></div>
                            <h2 className="text-lg font-semibold">{t('roles.admin')}</h2>
                            <p className="mt-1.5 min-h-10 text-sm leading-6 text-white/60">{t('roles.adminDesc')}</p>
                            <div className="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-semibold">
                                <Link to="/admin/login" className="text-gold-200 hover:text-white">{t('auth.signIn')}</Link>
                                <a href="/docs" className="text-white/60 hover:text-white">{t('landing.docs')}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="border-t border-white/10 bg-brand-950">
                <div className="mx-auto grid max-w-7xl gap-8 px-5 py-12 sm:grid-cols-3 sm:px-8">
                    {[
                        [<LockKeyhole size={20} />, t('landing.feature1'), t('landing.feature1Desc')],
                        [<Users size={20} />, t('landing.feature2'), t('landing.feature2Desc')],
                        [<Sparkles size={20} />, t('landing.feature3'), t('landing.feature3Desc')],
                    ].map(([icon, title, description]) => (
                        <div key={String(title)} className="flex gap-3">
                            <span className="mt-0.5 text-gold-300">{icon as ReactNode}</span>
                            <div>
                                <h3 className="text-sm font-semibold text-white">{title as string}</h3>
                                <p className="mt-1 text-sm leading-6 text-white/55">{description as string}</p>
                            </div>
                        </div>
                    ))}
                </div>
                <div className="mx-auto flex max-w-7xl flex-col gap-3 border-t border-white/10 px-5 py-6 text-xs text-white/45 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <span>© {new Date().getFullYear()} TheaterLink</span>
                    <div className="flex items-center gap-5">
                        <a href="/docs" className="hover:text-white">{t('landing.docs')}</a>
                        <LanguageSwitch dark />
                    </div>
                </div>
            </section>

            {session ? (
                <div className="fixed bottom-4 end-4 z-30">
                    <Link to={homePathForSession(session)} className="btn-secondary shadow-xl">
                        {t('common.dashboard')} <ArrowRight size={15} />
                    </Link>
                </div>
            ) : null}
        </div>
    );
}

export function LoginPage({ actor }: { actor: Actor }) {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const session = useAuthStore((state) => state.session);
    const setSession = useAuthStore((state) => state.setSession);
    const [subdomain, setSubdomain] = useState('');
    const [email, setEmail] = useState('');
    const [countryCode, setCountryCode] = useState('+966');
    const [phone, setPhone] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const isTenant = actor === 'theater' || actor === 'group';
    const isUser = actor === 'user';
    const title = t(`auth.${actor}Login`);

    if (session?.actor === actor) {
        return <Navigate to={homePathForSession(session)} replace />;
    }

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);

        try {
            if (isTenant) {
                const member = actor === 'theater' ? await loginTheater({ subdomain, email, password }) : await loginGroup({ subdomain, email, password });
                setSession(tenantSession(actor, member));
                toast.success(t('auth.welcomeBack'));
                navigate(member.tenant?.status === 'active' ? `/${actor}` : '/group/application', { replace: true });
            } else if (isUser) {
                const user = await loginUser({ country_code: countryCode, phone, password });
                setSession(userSession(user));
                toast.success(t('auth.welcomeBack'));
                navigate('/user', { replace: true });
            } else {
                const payload: AdminLoginPayload = { email, password };
                const admin = await loginAdmin(payload);
                setSession(adminSession(admin));
                toast.success(t('auth.welcomeBack'));
                navigate('/admin', { replace: true });
            }
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AuthLayout
            title={title}
            subtitle={t('auth.loginSubtitle')}
            footer={
                actor !== 'admin' ? (
                    <span>
                        {t('auth.noAccount')}{' '}
                        <Link to={actor === 'theater' ? '/register/theater' : actor === 'group' ? '/register/group' : '/register/user'} className="font-semibold text-brand-700 hover:text-brand-800">
                            {t('auth.createAccount')}
                        </Link>
                    </span>
                ) : (
                    <a href="/docs" className="font-semibold text-brand-700 hover:text-brand-800">{t('landing.docs')}</a>
                )
            }
        >
            <form className="space-y-4" onSubmit={submit}>
                {error ? <FormError>{error}</FormError> : null}

                {isTenant ? (
                    <>
                        <Field label={t('auth.subdomain')} hint={t('auth.subdomainHint')}>
                            <Input value={subdomain} onChange={(event) => setSubdomain(event.target.value)} placeholder="al-noor" autoComplete="organization" required />
                        </Field>
                        <Field label={t('auth.email')}>
                            <Input type="email" value={email} onChange={(event) => setEmail(event.target.value)} placeholder="owner@example.com" autoComplete="email" required />
                        </Field>
                    </>
                ) : isUser ? (
                    <PhoneNumberField
                        countryCode={countryCode}
                        phone={phone}
                        onCountryCodeChange={setCountryCode}
                        onPhoneChange={setPhone}
                    />
                ) : (
                    <Field label={t('auth.email')}>
                        <Input type="email" value={email} onChange={(event) => setEmail(event.target.value)} placeholder="admin@theaterlink.test" autoComplete="email" required />
                    </Field>
                )}

                <PasswordField
                    label={t('auth.password')}
                    value={password}
                    onChange={setPassword}
                    autoComplete="current-password"
                    placeholder="••••••••"
                />

                <Button type="submit" className="w-full" loading={submitting}>
                    {submitting ? t('auth.signingIn') : t('auth.signIn')}
                </Button>

                {actor !== 'admin' ? (
                    <div className="text-center">
                        <Link to={`/forgot-password/${actor}`} className="text-sm font-semibold text-brand-700 hover:text-brand-800">
                            {t('auth.forgotPassword')}
                        </Link>
                    </div>
                ) : null}
            </form>
        </AuthLayout>
    );
}

interface TheaterFormState {
    name: string;
    subdomain: string;
    owner_name: string;
    email: string;
    countryCode: string;
    localPhone: string;
    password: string;
    password_confirmation: string;
    address: string;
    country_id: string;
    city_id: string;
}

export function RegisterTheaterPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const setSession = useAuthStore((state) => state.setSession);
    const [form, setForm] = useState<TheaterFormState>({
        name: '', subdomain: '', owner_name: '', email: '', countryCode: '+966', localPhone: '',
        password: '', password_confirmation: '', address: '', country_id: '', city_id: '',
    });
    const [step, setStep] = useState<'form' | 'otp'>('form');
    const [otp, setOtp] = useState('');
    const [devOtp, setDevOtp] = useState<string | number | null>(null);
    const [phone, setPhone] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [resendSeconds, setResendSeconds] = useCountdown();
    const update = <K extends keyof TheaterFormState>(key: K, value: TheaterFormState[K]) => setForm((current) => ({ ...current, [key]: value }));

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);
        const internationalPhone = fullPhone(form.countryCode, form.localPhone);

        try {
            const response = await registerTheater({
                name: form.name,
                subdomain: form.subdomain,
                owner_name: form.owner_name,
                email: form.email,
                phone: internationalPhone,
                password: form.password,
                password_confirmation: form.password_confirmation,
                address: form.address || undefined,
                country_id: form.country_id ? Number(form.country_id) : undefined,
                city_id: form.city_id ? Number(form.city_id) : undefined,
            });
            setPhone(internationalPhone);
            setDevOtp(response.otp ?? null);
            setResendSeconds(60);
            setStep('otp');
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    const verify = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            const response = await verifyTheater(phone, otp);
            setSession(tenantSession('theater', response.member));
            toast.success(t('register.success'));
            navigate('/theater', { replace: true });
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    const resend = async () => {
        setError(null);
        try {
            const response = await resendTheaterOtp(phone);
            setDevOtp(response.otp ?? null);
            setResendSeconds(60);
            toast.success(t('passwordReset.codeSent'));
        } catch (caught) {
            setError(errorMessage(caught));
        }
    };

    if (step === 'otp') {
        return (
            <AuthLayout title={t('register.otpTitle')} subtitle={t('register.otpSubtitle', { phone })}>
                <form className="space-y-6" onSubmit={verify}>
                    {error ? <FormError>{error}</FormError> : null}
                    <OtpInput value={otp} onChange={setOtp} />
                    <DevOtpHint otp={devOtp} />
                    <Button type="submit" className="w-full" loading={submitting} disabled={otp.length !== 4}>
                        {submitting ? t('register.verifying') : t('register.verify')}
                    </Button>
                    <div className="flex items-center justify-between text-sm">
                        <button type="button" className="font-semibold text-ink-500 hover:text-ink-800" onClick={() => setStep('form')}>
                            {t('register.back')}
                        </button>
                        <button
                            type="button"
                            className="font-semibold text-brand-700 disabled:text-ink-400"
                            onClick={() => void resend()}
                            disabled={resendSeconds > 0}
                        >
                            {resendSeconds > 0 ? t('register.resendIn', { seconds: resendSeconds }) : t('register.resend')}
                        </button>
                    </div>
                </form>
            </AuthLayout>
        );
    }

    return (
        <AuthLayout
            title={t('register.theaterTitle')}
            subtitle={t('register.theaterSubtitle')}
            footer={<span>{t('register.alreadyAccount')} <Link to="/theater/login" className="font-semibold text-brand-700">{t('register.signIn')}</Link></span>}
        >
            <form className="space-y-4" onSubmit={submit}>
                {error ? <FormError>{error}</FormError> : null}
                <Field label={t('register.theaterName')}>
                    <Input value={form.name} onChange={(event) => update('name', event.target.value)} required />
                </Field>
                <Field label={t('auth.subdomain')} hint={t('register.subdomainHint')}>
                    <Input value={form.subdomain} onChange={(event) => update('subdomain', event.target.value.toLowerCase())} placeholder="al-noor" required />
                </Field>
                <Field label={t('register.ownerName')}>
                    <Input value={form.owner_name} onChange={(event) => update('owner_name', event.target.value)} required />
                </Field>
                <Field label={t('auth.email')}>
                    <Input type="email" value={form.email} onChange={(event) => update('email', event.target.value)} required />
                </Field>
                <PhoneNumberField
                    countryCode={form.countryCode}
                    phone={form.localPhone}
                    onCountryCodeChange={(value) => update('countryCode', value)}
                    onPhoneChange={(value) => update('localPhone', value)}
                />
                <Field label={t('register.address')}>
                    <Input value={form.address} onChange={(event) => update('address', event.target.value)} />
                </Field>
                <LocationFields
                    countryId={form.country_id}
                    cityId={form.city_id}
                    onCountryChange={(value) => update('country_id', value)}
                    onCityChange={(value) => update('city_id', value)}
                />
                <PasswordField label={t('register.password')} value={form.password} onChange={(value) => update('password', value)} autoComplete="new-password" hint={t('register.passwordHint')} />
                <PasswordField label={t('register.passwordConfirmation')} value={form.password_confirmation} onChange={(value) => update('password_confirmation', value)} autoComplete="new-password" />
                <Button type="submit" className="w-full" loading={submitting}>
                    {submitting ? t('register.sendingCode') : t('register.createWorkspace')}
                </Button>
            </form>
        </AuthLayout>
    );
}

export function RegisterGroupPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const setSession = useAuthStore((state) => state.setSession);
    const [form, setForm] = useState({
        name: '', subdomain: '', contact_person_name: '', email: '', countryCode: '+966', localPhone: '',
        password: '', password_confirmation: '', description: '',
    });
    const [permit, setPermit] = useState<File | null>(null);
    const [step, setStep] = useState<'form' | 'otp'>('form');
    const [otp, setOtp] = useState('');
    const [devOtp, setDevOtp] = useState<string | number | null>(null);
    const [phone, setPhone] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [resendSeconds, setResendSeconds] = useCountdown();
    const update = (key: keyof typeof form, value: string) => setForm((current) => ({ ...current, [key]: value }));

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);

        if (!permit) {
            setError(t('register.permitHint'));
            return;
        }

        if (permit.size > 4 * 1024 * 1024) {
            setError(t('errors.fileSize'));
            return;
        }

        setSubmitting(true);
        const internationalPhone = fullPhone(form.countryCode, form.localPhone);
        const data = new FormData();
        data.append('name', form.name);
        data.append('subdomain', form.subdomain);
        data.append('contact_person_name', form.contact_person_name);
        data.append('email', form.email);
        data.append('phone', internationalPhone);
        data.append('password', form.password);
        data.append('password_confirmation', form.password_confirmation);
        data.append('description', form.description);
        data.append('permit', permit);

        try {
            const response = await registerGroup(data);
            setPhone(internationalPhone);
            setDevOtp(response.otp ?? null);
            setResendSeconds(60);
            setStep('otp');
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    const verify = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            const response = await verifyGroup(phone, otp);
            setSession(tenantSession('group', response.member));
            toast.success(t('register.success'));
            navigate('/group/application', { replace: true });
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    const resend = async () => {
        setError(null);
        try {
            const response = await resendGroupOtp(phone);
            setDevOtp(response.otp ?? null);
            setResendSeconds(60);
            toast.success(t('passwordReset.codeSent'));
        } catch (caught) {
            setError(errorMessage(caught));
        }
    };

    if (step === 'otp') {
        return (
            <AuthLayout title={t('register.otpTitle')} subtitle={t('register.otpSubtitle', { phone })}>
                <form className="space-y-6" onSubmit={verify}>
                    {error ? <FormError>{error}</FormError> : null}
                    <OtpInput value={otp} onChange={setOtp} />
                    <DevOtpHint otp={devOtp} />
                    <Button type="submit" className="w-full" loading={submitting} disabled={otp.length !== 4}>
                        {submitting ? t('register.verifying') : t('register.verify')}
                    </Button>
                    <div className="flex items-center justify-between text-sm">
                        <button type="button" className="font-semibold text-ink-500 hover:text-ink-800" onClick={() => setStep('form')}>{t('register.back')}</button>
                        <button type="button" className="font-semibold text-brand-700 disabled:text-ink-400" onClick={() => void resend()} disabled={resendSeconds > 0}>
                            {resendSeconds > 0 ? t('register.resendIn', { seconds: resendSeconds }) : t('register.resend')}
                        </button>
                    </div>
                </form>
            </AuthLayout>
        );
    }

    return (
        <AuthLayout
            title={t('register.groupTitle')}
            subtitle={t('register.groupSubtitle')}
            footer={<span>{t('register.alreadyAccount')} <Link to="/group/login" className="font-semibold text-brand-700">{t('register.signIn')}</Link></span>}
        >
            <form className="space-y-4" onSubmit={submit}>
                {error ? <FormError>{error}</FormError> : null}
                <Field label={t('register.groupName')}>
                    <Input value={form.name} onChange={(event) => update('name', event.target.value)} required />
                </Field>
                <Field label={t('auth.subdomain')} hint={t('register.subdomainHint')}>
                    <Input value={form.subdomain} onChange={(event) => update('subdomain', event.target.value.toLowerCase())} placeholder="mask-group" required />
                </Field>
                <Field label={t('register.contactPerson')}>
                    <Input value={form.contact_person_name} onChange={(event) => update('contact_person_name', event.target.value)} required />
                </Field>
                <Field label={t('auth.email')}>
                    <Input type="email" value={form.email} onChange={(event) => update('email', event.target.value)} required />
                </Field>
                <PhoneNumberField
                    countryCode={form.countryCode}
                    phone={form.localPhone}
                    onCountryCodeChange={(value) => update('countryCode', value)}
                    onPhoneChange={(value) => update('localPhone', value)}
                />
                <Field label={t('register.description')}>
                    <Textarea value={form.description} onChange={(event) => update('description', event.target.value)} />
                </Field>
                <FileDropzone file={permit} onChange={setPermit} label={t('register.permit')} hint={`${t('register.permitHint')}`} />
                <PasswordField label={t('register.password')} value={form.password} onChange={(value) => update('password', value)} autoComplete="new-password" hint={t('register.passwordHint')} />
                <PasswordField label={t('register.passwordConfirmation')} value={form.password_confirmation} onChange={(value) => update('password_confirmation', value)} autoComplete="new-password" />
                <Button type="submit" className="w-full" loading={submitting}>
                    {submitting ? t('register.sendingCode') : t('register.createWorkspace')}
                </Button>
            </form>
        </AuthLayout>
    );
}

export function RegisterUserPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const setSession = useAuthStore((state) => state.setSession);
    const [form, setForm] = useState({
        name: '', email: '', countryCode: '+966', localPhone: '', password: '', password_confirmation: '',
    });
    const [step, setStep] = useState<'form' | 'otp'>('form');
    const [otp, setOtp] = useState('');
    const [devOtp, setDevOtp] = useState<string | number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [resendSeconds, setResendSeconds] = useCountdown();
    const update = (key: keyof typeof form, value: string) => setForm((current) => ({ ...current, [key]: value }));

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            const response = await registerUser({
                name: form.name,
                country_code: form.countryCode,
                phone: form.localPhone,
                email: form.email,
                password: form.password,
                password_confirmation: form.password_confirmation,
            });
            setDevOtp(response.otp ?? null);
            setResendSeconds(60);
            setStep('otp');
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    const verify = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            const user = await verifyUser({
                country_code: form.countryCode,
                phone: form.localPhone,
                otp,
            });
            setSession(userSession(user));
            toast.success(t('register.success'));
            navigate('/user', { replace: true });
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    const resend = async () => {
        setError(null);
        try {
            const response = await resendUserOtp({ country_code: form.countryCode, phone: form.localPhone });
            setDevOtp(response.otp ?? null);
            setResendSeconds(60);
            toast.success(t('passwordReset.codeSent'));
        } catch (caught) {
            setError(errorMessage(caught));
        }
    };

    if (step === 'otp') {
        return (
            <AuthLayout title={t('register.otpTitle')} subtitle={t('register.otpSubtitle', { phone: fullPhone(form.countryCode, form.localPhone) })}>
                <form className="space-y-6" onSubmit={verify}>
                    {error ? <FormError>{error}</FormError> : null}
                    <OtpInput value={otp} onChange={setOtp} />
                    <DevOtpHint otp={devOtp} />
                    <Button type="submit" className="w-full" loading={submitting} disabled={otp.length !== 4}>
                        {submitting ? t('register.verifying') : t('register.verify')}
                    </Button>
                    <div className="flex items-center justify-between text-sm">
                        <button type="button" className="font-semibold text-ink-500 hover:text-ink-800" onClick={() => setStep('form')}>{t('register.back')}</button>
                        <button type="button" className="font-semibold text-brand-700 disabled:text-ink-400" onClick={() => void resend()} disabled={resendSeconds > 0}>
                            {resendSeconds > 0 ? t('register.resendIn', { seconds: resendSeconds }) : t('register.resend')}
                        </button>
                    </div>
                </form>
            </AuthLayout>
        );
    }

    return (
        <AuthLayout
            title={t('register.userTitle')}
            subtitle={t('register.userSubtitle')}
            footer={<span>{t('register.alreadyAccount')} <Link to="/user/login" className="font-semibold text-brand-700">{t('register.signIn')}</Link></span>}
        >
            <form className="space-y-4" onSubmit={submit}>
                {error ? <FormError>{error}</FormError> : null}
                <Field label={t('common.name')}>
                    <Input value={form.name} onChange={(event) => update('name', event.target.value)} required />
                </Field>
                <Field label={t('auth.email')}>
                    <Input type="email" value={form.email} onChange={(event) => update('email', event.target.value)} required />
                </Field>
                <PhoneNumberField
                    countryCode={form.countryCode}
                    phone={form.localPhone}
                    onCountryCodeChange={(value) => update('countryCode', value)}
                    onPhoneChange={(value) => update('localPhone', value)}
                />
                <PasswordField label={t('register.password')} value={form.password} onChange={(value) => update('password', value)} autoComplete="new-password" hint={t('register.passwordHint')} />
                <PasswordField label={t('register.passwordConfirmation')} value={form.password_confirmation} onChange={(value) => update('password_confirmation', value)} autoComplete="new-password" />
                <Button type="submit" className="w-full" loading={submitting}>
                    {submitting ? t('register.sendingCode') : t('register.createWorkspace')}
                </Button>
            </form>
        </AuthLayout>
    );
}

export function ForgotPasswordPage({ actor }: { actor: Actor }) {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const [step, setStep] = useState<1 | 2 | 3>(1);
    const [subdomain, setSubdomain] = useState('');
    const [email, setEmail] = useState('');
    const [countryCode, setCountryCode] = useState('+966');
    const [phone, setPhone] = useState('');
    const [otp, setOtp] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [token, setToken] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [complete, setComplete] = useState(false);

    const requestReset = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            if (actor === 'admin') {
                const response = await requestAdminPasswordReset(email);
                setToken(response.change_password_token ?? '');
            } else if (actor === 'user') {
                const response = await requestUserPasswordReset({ country_code: countryCode, phone });
                setToken(response.token ?? response.change_password_token ?? '');
            } else {
                const response = await requestTenantPasswordReset({ subdomain, email });
                setToken(response.change_password_token ?? '');
            }
            setStep(2);
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    const verify = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            if (actor === 'admin') {
                await verifyAdminPasswordReset({ email, otp, change_password_token: token });
            } else if (actor === 'user') {
                await verifyUserPasswordReset({ country_code: countryCode, phone, otp, change_password_token: token });
            } else {
                await verifyTenantPasswordReset({ subdomain, email, otp, change_password_token: token });
            }
            setStep(3);
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    const reset = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            if (actor === 'admin') {
                await resetAdminPassword({ email, password, new_password_confirmation: passwordConfirmation, change_password_token: token });
            } else if (actor === 'user') {
                await resetUserPassword({ country_code: countryCode, phone, password, password_confirmation: passwordConfirmation, change_password_token: token });
            } else {
                await resetTenantPassword({ subdomain, email, otp, change_password_token: token, password, password_confirmation: passwordConfirmation });
            }
            setComplete(true);
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    if (complete) {
        return (
            <AuthLayout title={t('passwordReset.title')}>
                <div className="space-y-5">
                    <Alert tone="success"><p>{t('passwordReset.success')}</p></Alert>
                    <Button className="w-full" onClick={() => navigate(`/${actor}/login`)}>{t('passwordReset.backToLogin')}</Button>
                </div>
            </AuthLayout>
        );
    }

    return (
        <AuthLayout title={t('passwordReset.title')} subtitle={t('passwordReset.subtitle')}>
            <div className="mb-5 flex gap-2">
                {[1, 2, 3].map((value) => (
                    <span key={value} className={`h-1.5 flex-1 rounded-full ${step >= value ? 'bg-brand-700' : 'bg-ink-200'}`} />
                ))}
            </div>
            <form className="space-y-4" onSubmit={step === 1 ? requestReset : step === 2 ? verify : reset}>
                {error ? <FormError>{error}</FormError> : null}

                {step === 1 ? (
                    actor === 'admin' ? (
                        <Field label={t('auth.email')}><Input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required /></Field>
                    ) : actor === 'user' ? (
                        <PhoneNumberField countryCode={countryCode} phone={phone} onCountryCodeChange={setCountryCode} onPhoneChange={setPhone} />
                    ) : (
                        <>
                            <Field label={t('auth.subdomain')}><Input value={subdomain} onChange={(event) => setSubdomain(event.target.value)} required /></Field>
                            <Field label={t('auth.email')}><Input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required /></Field>
                        </>
                    )
                ) : null}

                {step === 2 ? (
                    <>
                        <div className="text-center"><OtpInput value={otp} onChange={setOtp} /></div>
                        <p className="text-center text-xs text-ink-500">{t('passwordReset.codeSent')}</p>
                    </>
                ) : null}

                {step === 3 ? (
                    <>
                        <PasswordField label={t('passwordReset.newPassword')} value={password} onChange={setPassword} autoComplete="new-password" hint={t('register.passwordHint')} />
                        <PasswordField label={t('passwordReset.confirmPassword')} value={passwordConfirmation} onChange={setPasswordConfirmation} autoComplete="new-password" />
                    </>
                ) : null}

                <Button type="submit" className="w-full" loading={submitting} disabled={step === 2 && otp.length !== 4}>
                    {step === 1 ? t('passwordReset.request') : step === 2 ? t('passwordReset.verify') : t('passwordReset.reset')}
                </Button>
                <div className="text-center">
                    <Link to={`/${actor}/login`} className="text-sm font-semibold text-brand-700">{t('passwordReset.backToLogin')}</Link>
                </div>
            </form>
        </AuthLayout>
    );
}

export function AcceptInvitationPage() {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const setSession = useAuthStore((state) => state.setSession);
    const [token, setToken] = useState(params.get('token') ?? '');
    const [name, setName] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setError(null);
        setSubmitting(true);
        try {
            const member = await acceptInvitation({ token, name, password, password_confirmation: passwordConfirmation });
            const actor = (member.tenant?.type ?? 'theater') as 'theater' | 'group';
            setSession(tenantSession(actor, member));
            toast.success(t('invitations.success'));
            navigate(member.tenant?.status === 'active' ? `/${actor}` : '/group/application', { replace: true });
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AuthLayout title={t('invitations.acceptTitle')} subtitle={t('invitations.acceptSubtitle')}>
            <form className="space-y-4" onSubmit={submit}>
                {error ? <FormError>{error}</FormError> : null}
                <Field label={t('passwordReset.token')} hint={t('invitations.invalidToken')}>
                    <Input value={token} onChange={(event) => setToken(event.target.value)} required />
                </Field>
                <Field label={t('common.name')}>
                    <Input value={name} onChange={(event) => setName(event.target.value)} required />
                </Field>
                <PasswordField label={t('register.password')} value={password} onChange={setPassword} autoComplete="new-password" hint={t('register.passwordHint')} />
                <PasswordField label={t('register.passwordConfirmation')} value={passwordConfirmation} onChange={setPasswordConfirmation} autoComplete="new-password" />
                <Button type="submit" className="w-full" loading={submitting}>{t('invitations.accept')}</Button>
            </form>
        </AuthLayout>
    );
}

export function NotFoundPage() {
    const { t } = useTranslation();
    const session = useAuthStore((state) => state.session);
    const home = session ? homePathForSession(session) : '/';

    return (
        <div className="flex min-h-screen items-center justify-center bg-ink-50 px-5">
            <div className="max-w-md text-center">
                <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-100 text-brand-700">
                    <FileCheck2 size={28} />
                </div>
                <h1 className="text-2xl font-bold text-ink-950">{t('errors.pageNotFound')}</h1>
                <p className="mt-2 text-sm text-ink-500">{t('errors.pageNotFoundText')}</p>
                <Link to={home} className="btn-primary mt-6">{t('errors.goHome')}</Link>
            </div>
        </div>
    );
}

export function ForbiddenPage() {
    const { t } = useTranslation();
    const session = useAuthStore((state) => state.session);
    const home = session ? homePathForSession(session) : '/';

    return (
        <div className="flex min-h-screen items-center justify-center bg-ink-50 px-5">
            <div className="max-w-md text-center">
                <div className="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <LockKeyhole size={28} />
                </div>
                <h1 className="text-2xl font-bold text-ink-950">{t('errors.forbidden')}</h1>
                <p className="mt-2 text-sm text-ink-500">{t('errors.unauthorized')}</p>
                <Link to={home} className="btn-primary mt-6">{t('errors.goHome')}</Link>
            </div>
        </div>
    );
}
