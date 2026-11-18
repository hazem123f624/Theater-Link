import { useRef, useState, type ChangeEvent, type DragEvent, type ReactNode } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { Eye, EyeOff, FileText, UploadCloud } from 'lucide-react';
import { getCitiesByCountry, getCountries } from '@/api/locations';
import { Field, Input, Select } from '@/components/ui';
import { cn } from '@/lib/cn';
import { localizedName } from '@/lib/format';

export function PasswordField({
    label,
    value,
    onChange,
    error,
    hint,
    autoComplete = 'current-password',
    placeholder,
    name,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: ReactNode;
    autoComplete?: string;
    placeholder?: string;
    name?: string;
}) {
    const { t } = useTranslation();
    const [visible, setVisible] = useState(false);

    return (
        <Field label={label} error={error} hint={hint}>
            <div className="relative">
                <Input
                    name={name}
                    type={visible ? 'text' : 'password'}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    autoComplete={autoComplete}
                    placeholder={placeholder}
                    className="pe-11"
                />
                <button
                    type="button"
                    className="absolute end-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-ink-400 hover:bg-ink-100 hover:text-ink-700"
                    onClick={() => setVisible((current) => !current)}
                    aria-label={visible ? t('common.hidePassword') : t('common.showPassword')}
                >
                    {visible ? <EyeOff size={17} /> : <Eye size={17} />}
                </button>
            </div>
        </Field>
    );
}

export function CountryCodeSelect({
    value,
    onChange,
    label,
    error,
}: {
    value: string;
    onChange: (value: string) => void;
    label: string;
    error?: string;
}) {
    const { t } = useTranslation();
    const countries = useQuery({ queryKey: ['locations', 'countries'], queryFn: getCountries, staleTime: 60 * 60 * 1000 });
    const options = (countries.data ?? []).filter((country) => country.phone_code);

    return (
        <Field label={label} error={error}>
            <Select value={value} onChange={(event) => onChange(event.target.value)} aria-label={t('auth.countryCode')}>
                <option value="">—</option>
                {options.map((country) => (
                    <option key={country.id} value={country.phone_code ?? ''}>
                        {localizedName(country)} ({country.phone_code})
                    </option>
                ))}
            </Select>
        </Field>
    );
}

export function PhoneNumberField({
    countryCode,
    phone,
    onCountryCodeChange,
    onPhoneChange,
    countryError,
    phoneError,
    hint,
}: {
    countryCode: string;
    phone: string;
    onCountryCodeChange: (value: string) => void;
    onPhoneChange: (value: string) => void;
    countryError?: string;
    phoneError?: string;
    hint?: ReactNode;
}) {
    const { t } = useTranslation();

    return (
        <div className="grid gap-3 sm:grid-cols-[9.5rem_1fr]">
            <CountryCodeSelect
                value={countryCode}
                onChange={onCountryCodeChange}
                label={t('auth.countryCode')}
                error={countryError}
            />
            <Field label={t('auth.phone')} error={phoneError} hint={hint}>
                <Input
                    value={phone}
                    onChange={(event) => onPhoneChange(event.target.value.replace(/[^\d]/g, ''))}
                    inputMode="tel"
                    autoComplete="tel-national"
                    placeholder="500000001"
                />
            </Field>
        </div>
    );
}

export function fullPhone(countryCode: string, phone: string): string {
    const normalizedCode = countryCode.startsWith('+') ? countryCode : `+${countryCode}`;
    return `${normalizedCode}${phone.replace(/\D/g, '')}`;
}

export function LocationFields({
    countryId,
    cityId,
    onCountryChange,
    onCityChange,
    countryError,
    cityError,
}: {
    countryId: string;
    cityId: string;
    onCountryChange: (value: string) => void;
    onCityChange: (value: string) => void;
    countryError?: string;
    cityError?: string;
}) {
    const countries = useQuery({ queryKey: ['locations', 'countries'], queryFn: getCountries, staleTime: 60 * 60 * 1000 });
    const numericCountryId = countryId ? Number(countryId) : 0;
    const cities = useQuery({
        queryKey: ['locations', 'cities', numericCountryId],
        queryFn: () => getCitiesByCountry(numericCountryId),
        enabled: numericCountryId > 0,
        staleTime: 60 * 60 * 1000,
    });

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Country" error={countryError}>
                <Select
                    value={countryId}
                    onChange={(event) => {
                        onCountryChange(event.target.value);
                        onCityChange('');
                    }}
                >
                    <option value="">—</option>
                    {(countries.data ?? []).map((country) => (
                        <option key={country.id} value={country.id}>{localizedName(country)}</option>
                    ))}
                </Select>
            </Field>
            <Field label="City" error={cityError}>
                <Select
                    value={cityId}
                    onChange={(event) => onCityChange(event.target.value)}
                    disabled={!countryId || cities.isLoading}
                >
                    <option value="">—</option>
                    {(cities.data ?? []).map((city) => (
                        <option key={city.id} value={city.id}>{localizedName(city)}</option>
                    ))}
                </Select>
            </Field>
        </div>
    );
}

export function OtpInput({
    value,
    onChange,
    error,
}: {
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    const { t } = useTranslation();
    const inputs = useRef<Array<HTMLInputElement | null>>([]);
    const digits = value.padEnd(4, ' ').slice(0, 4).split('');

    const setDigit = (index: number, next: string) => {
        const clean = next.replace(/\D/g, '').slice(-1);
        const nextDigits = [...digits];
        nextDigits[index] = clean || ' ';
        onChange(nextDigits.join('').replace(/\s/g, '').slice(0, 4));

        if (clean && index < 3) {
            inputs.current[index + 1]?.focus();
        }
    };

    const onPaste = (event: React.ClipboardEvent<HTMLDivElement>) => {
        const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 4);
        if (pasted) {
            event.preventDefault();
            onChange(pasted);
            inputs.current[Math.min(pasted.length, 4) - 1]?.focus();
        }
    };

    return (
        <div>
            <div className="flex justify-center gap-2 sm:gap-3" dir="ltr" onPaste={onPaste}>
                {digits.map((digit, index) => (
                    <input
                        key={index}
                        ref={(element) => { inputs.current[index] = element; }}
                        value={digit.trim()}
                        onChange={(event) => setDigit(index, event.target.value)}
                        onKeyDown={(event) => {
                            if (event.key === 'Backspace' && !digit.trim() && index > 0) {
                                inputs.current[index - 1]?.focus();
                            }
                            if (event.key === 'ArrowLeft' && index > 0) {
                                inputs.current[index - 1]?.focus();
                            }
                            if (event.key === 'ArrowRight' && index < 3) {
                                inputs.current[index + 1]?.focus();
                            }
                        }}
                        inputMode="numeric"
                        autoComplete={index === 0 ? 'one-time-code' : 'off'}
                        maxLength={1}
                        className={cn(
                            'h-14 w-12 rounded-xl border border-ink-200 bg-white text-center text-2xl font-bold text-ink-950 shadow-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100 sm:h-16 sm:w-14',
                            error && 'border-red-300',
                        )}
                        aria-label={`${t('common.digit')} ${index + 1}`}
                    />
                ))}
            </div>
            {error ? <p className="error-text text-center">{error}</p> : null}
        </div>
    );
}

export function FileDropzone({
    file,
    onChange,
    error,
    accept = 'application/pdf,image/jpeg,image/png',
    maxMb = 4,
    label,
    hint,
}: {
    file: File | null;
    onChange: (file: File | null) => void;
    error?: string;
    accept?: string;
    maxMb?: number;
    label: string;
    hint?: string;
}) {
    const { t } = useTranslation();
    const inputRef = useRef<HTMLInputElement | null>(null);
    const [dragging, setDragging] = useState(false);

    const acceptFile = (candidate: File | undefined) => {
        if (!candidate) {
            return;
        }

        onChange(candidate);
    };

    const onDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setDragging(false);
        acceptFile(event.dataTransfer.files?.[0]);
    };

    return (
        <Field label={label} error={error} hint={hint}>
            <div
                className={cn(
                    'flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-5 py-8 text-center transition',
                    dragging ? 'border-brand-400 bg-brand-50' : 'border-ink-200 bg-white hover:border-brand-300 hover:bg-brand-50/40',
                    error && 'border-red-300',
                )}
                onClick={() => inputRef.current?.click()}
                onDragOver={(event) => { event.preventDefault(); setDragging(true); }}
                onDragLeave={() => setDragging(false)}
                onDrop={onDrop}
                role="button"
                tabIndex={0}
                aria-label={label}
                onKeyDown={(event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        inputRef.current?.click();
                    }
                }}
            >
                {file ? (
                    <>
                        <FileText size={28} className="mb-2 text-brand-600" aria-hidden="true" />
                        <p className="text-sm font-semibold text-ink-900">{file.name}</p>
                        <p className="mt-1 text-xs text-ink-500">{(file.size / 1024 / 1024).toFixed(2)} MB</p>
                        <button
                            type="button"
                            className="mt-3 text-xs font-semibold text-brand-700 underline"
                            onClick={(event) => {
                                event.stopPropagation();
                                onChange(null);
                                if (inputRef.current) {
                                    inputRef.current.value = '';
                                }
                            }}
                        >
                            {t('common.removeFile')}
                        </button>
                    </>
                ) : (
                    <>
                        <UploadCloud size={30} className="mb-2 text-ink-400" aria-hidden="true" />
                        <p className="text-sm font-semibold text-ink-800">{t('register.permitChoose')} {t('common.dragFile')}</p>
                        <p className="mt-1 text-xs text-ink-500">{t('register.permitHint')}</p>
                    </>
                )}
                <input
                    ref={inputRef}
                    type="file"
                    accept={accept}
                    className="hidden"
                    onChange={(event: ChangeEvent<HTMLInputElement>) => acceptFile(event.target.files?.[0])}
                />
            </div>
        </Field>
    );
}
