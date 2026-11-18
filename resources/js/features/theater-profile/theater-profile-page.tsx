import { useEffect, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';
import { Building2, ExternalLink, Image as ImageIcon, Save } from 'lucide-react';
import { getTheaterProfile, updateTheaterProfile } from '@/api/theater-profile';
import type { TheaterProfileData } from '@/api/types';
import { Button, Field, FormError, Input, Skeleton, Textarea } from '@/components/ui';
import { FileDropzone, LocationFields } from '@/components/forms';
import { TenantPage } from '@/components/layout';
import { errorMessage } from '@/lib/errors';

interface ProfileForm {
    name: string;
    description: string;
    email: string;
    phone: string;
    address: string;
    country_id: string;
    city_id: string;
    timezone: string;
}

function formFromProfile(profile: TheaterProfileData): ProfileForm {
    return {
        name: profile.name ?? '',
        description: profile.description ?? '',
        email: profile.email ?? '',
        phone: profile.phone ?? '',
        address: profile.address ?? '',
        country_id: profile.country_id ? String(profile.country_id) : '',
        city_id: profile.city_id ? String(profile.city_id) : '',
        timezone: profile.timezone ?? 'Asia/Riyadh',
    };
}

export function TheaterProfilePage() {
    const { t } = useTranslation();
    const queryClient = useQueryClient();
    const profile = useQuery({ queryKey: ['theater', 'profile'], queryFn: getTheaterProfile });
    const [form, setForm] = useState<ProfileForm>({
        name: '', description: '', email: '', phone: '', address: '',
        country_id: '', city_id: '', timezone: 'Asia/Riyadh',
    });
    const [logo, setLogo] = useState<File | null>(null);
    const [cover, setCover] = useState<File | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (profile.data) {
            setForm(formFromProfile(profile.data));
        }
    }, [profile.data]);

    const update = (key: keyof ProfileForm, value: string) => setForm((current) => ({ ...current, [key]: value }));

    if (profile.isLoading) {
        return <TenantPage><Skeleton className="h-96" /></TenantPage>;
    }

    const save = async () => {
        setSaving(true);
        setError(null);
        try {
            await updateTheaterProfile({
                ...form,
                country_id: form.country_id ? Number(form.country_id) : null,
                city_id: form.city_id ? Number(form.city_id) : null,
            }, logo, cover);
            setLogo(null);
            setCover(null);
            toast.success(t('theaterOps.profile.saved'));
            await queryClient.invalidateQueries({ queryKey: ['theater', 'profile'] });
        } catch (caught) {
            setError(errorMessage(caught));
        } finally {
            setSaving(false);
        }
    };

    return (
        <TenantPage
            title={t('theaterOps.profile.title')}
            subtitle={t('theaterOps.profile.subtitle')}
            actions={
                profile.data?.tenant_id ? (
                    <Link to={`/theaters/${profile.data.tenant_id}`} className="btn-secondary" target="_blank">
                        <ExternalLink size={15} /> {t('theaterOps.profile.publicPreview')}
                    </Link>
                ) : undefined
            }
        >
            <div className="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <section className="card card-body">
                    <div className="mb-5 flex items-center gap-3">
                        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><Building2 size={18} /></span>
                        <h2 className="font-semibold text-ink-950">{t('theaterOps.profile.title')}</h2>
                    </div>
                    <div className="space-y-4">
                        {error ? <FormError>{error}</FormError> : null}
                        <Field label={t('theaterOps.profile.name')}>
                            <Input value={form.name} onChange={(event) => update('name', event.target.value)} />
                        </Field>
                        <Field label={t('theaterOps.profile.description')}>
                            <Textarea value={form.description} onChange={(event) => update('description', event.target.value)} />
                        </Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label={t('theaterOps.profile.email')}><Input type="email" value={form.email} onChange={(event) => update('email', event.target.value)} /></Field>
                            <Field label={t('theaterOps.profile.phone')}><Input value={form.phone} onChange={(event) => update('phone', event.target.value)} /></Field>
                        </div>
                        <Field label={t('theaterOps.profile.address')}><Input value={form.address} onChange={(event) => update('address', event.target.value)} /></Field>
                        <LocationFields
                            countryId={form.country_id}
                            cityId={form.city_id}
                            onCountryChange={(value) => update('country_id', value)}
                            onCityChange={(value) => update('city_id', value)}
                        />
                        <Field label={t('theaterOps.profile.timezone')} hint="Example: Asia/Riyadh">
                            <Input value={form.timezone} onChange={(event) => update('timezone', event.target.value)} />
                        </Field>
                        <Button onClick={() => void save()} loading={saving}><Save size={16} /> {t('theaterOps.profile.save')}</Button>
                    </div>
                </section>

                <section className="space-y-4">
                    <div className="card card-body">
                        <div className="mb-3 flex items-center gap-2 text-sm font-semibold text-ink-800"><ImageIcon size={16} /> {t('theaterOps.profile.logo')}</div>
                        {profile.data?.logo_url ? <img src={profile.data.logo_url} alt={t('theaterOps.profile.currentLogo')} className="mb-3 h-24 w-24 rounded-2xl border border-ink-200 object-cover" /> : null}
                        <FileDropzone file={logo} onChange={setLogo} label={t('theaterOps.profile.logo')} accept="image/jpeg,image/png,image/webp" hint={t('theaterOps.profile.imageHint')} />
                    </div>
                    <div className="card card-body">
                        <div className="mb-3 flex items-center gap-2 text-sm font-semibold text-ink-800"><ImageIcon size={16} /> {t('theaterOps.profile.cover')}</div>
                        {profile.data?.cover_url ? <img src={profile.data.cover_url} alt={t('theaterOps.profile.currentCover')} className="mb-3 aspect-[16/6] w-full rounded-2xl border border-ink-200 object-cover" /> : null}
                        <FileDropzone file={cover} onChange={setCover} label={t('theaterOps.profile.cover')} accept="image/jpeg,image/png,image/webp" hint={t('theaterOps.profile.imageHint')} />
                    </div>
                </section>
            </div>
        </TenantPage>
    );
}
