import {
    createContext,
    forwardRef,
    useContext,
    useEffect,
    useId,
    type ButtonHTMLAttributes,
    type HTMLAttributes,
    type InputHTMLAttributes,
    type ReactNode,
    type SelectHTMLAttributes,
    type TextareaHTMLAttributes,
} from 'react';
import { createPortal } from 'react-dom';
import { useTranslation } from 'react-i18next';
import { AlertCircle, Check, ChevronLeft, ChevronRight, Loader2, Search, X } from 'lucide-react';
import { cn } from '@/lib/cn';

type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'danger';
type ButtonSize = 'default' | 'sm';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    size?: ButtonSize;
    loading?: boolean;
}

const buttonVariants: Record<ButtonVariant, string> = {
    primary: 'btn-primary',
    secondary: 'btn-secondary',
    ghost: 'btn-ghost',
    danger: 'btn-danger',
};

export function Button({
    variant = 'primary',
    size = 'default',
    loading = false,
    className,
    children,
    disabled,
    ...props
}: ButtonProps) {
    return (
        <button
            className={cn(buttonVariants[variant], size === 'sm' && 'btn-sm', className)}
            disabled={disabled || loading}
            {...props}
        >
            {loading ? <Loader2 size={16} className="animate-spin" aria-hidden="true" /> : null}
            {children}
        </button>
    );
}

interface FieldContextValue {
    labelId: string;
    errorId: string;
    hasError: boolean;
}

const FieldContext = createContext<FieldContextValue | null>(null);

export function useFieldIds(): FieldContextValue | null {
    return useContext(FieldContext);
}

export const Input = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(
    function Input({ className, id, ...props }, ref) {
        const field = useContext(FieldContext);
        return (
            <input
                ref={ref}
                id={id ?? field?.labelId}
                aria-labelledby={props['aria-label'] ? undefined : field ? `${field.labelId}-label` : undefined}
                aria-describedby={field?.hasError ? field.errorId : undefined}
                className={cn('input', className)}
                {...props}
            />
        );
    },
);

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaHTMLAttributes<HTMLTextAreaElement>>(
    function Textarea({ className, id, ...props }, ref) {
        const field = useContext(FieldContext);
        return (
            <textarea
                ref={ref}
                id={id ?? field?.labelId}
                aria-labelledby={props['aria-label'] ? undefined : field ? `${field.labelId}-label` : undefined}
                aria-describedby={field?.hasError ? field.errorId : undefined}
                className={cn('textarea', className)}
                {...props}
            />
        );
    },
);

export const Select = forwardRef<HTMLSelectElement, SelectHTMLAttributes<HTMLSelectElement>>(
    function Select({ className, children, id, ...props }, ref) {
        const field = useContext(FieldContext);
        return (
            <select
                ref={ref}
                id={id ?? field?.labelId}
                aria-labelledby={props['aria-label'] ? undefined : field ? `${field.labelId}-label` : undefined}
                aria-describedby={field?.hasError ? field.errorId : undefined}
                className={cn('select', className)}
                {...props}
            >
                {children}
            </select>
        );
    },
);

interface FieldProps {
    label?: ReactNode;
    htmlFor?: string;
    error?: string;
    hint?: ReactNode;
    children: ReactNode;
    className?: string;
    required?: boolean;
}

export function Field({ label, htmlFor, error, hint, children, className, required }: FieldProps) {
    const generatedId = useId();
    const labelId = htmlFor ?? generatedId;
    const errorId = `${labelId}-error`;

    return (
        <div className={className}>
            <FieldContext.Provider value={{ labelId, errorId, hasError: Boolean(error) }}>
                {label ? (
                    <label className="label" id={`${labelId}-label`} htmlFor={labelId}>
                        {label}
                        {required ? <span className="text-brand-600"> *</span> : null}
                    </label>
                ) : null}
                {children}
                {error ? <p className="error-text" id={errorId}>{error}</p> : hint ? <p className="hint">{hint}</p> : null}
            </FieldContext.Provider>
        </div>
    );
}

export function FormError({ children }: { children: ReactNode }) {
    if (!children) {
        return null;
    }

    return (
        <div className="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-700" role="alert">
            <AlertCircle size={17} className="mt-0.5 shrink-0" aria-hidden="true" />
            <span>{children}</span>
        </div>
    );
}

interface AlertProps {
    tone?: 'info' | 'success' | 'warning' | 'danger';
    title?: string;
    children: ReactNode;
    action?: ReactNode;
}

const alertTones = {
    info: 'border-sky-200 bg-sky-50 text-sky-900',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    warning: 'border-amber-200 bg-amber-50 text-amber-900',
    danger: 'border-red-200 bg-red-50 text-red-900',
};

export function Alert({ tone = 'info', title, children, action }: AlertProps) {
    return (
        <div className={cn('rounded-xl border px-4 py-3 text-sm', alertTones[tone])} role="status">
            <div className="flex items-start justify-between gap-3">
                <div>
                    {title ? <p className="font-semibold">{title}</p> : null}
                    <div className={cn(title && 'mt-1')}>{children}</div>
                </div>
                {action}
            </div>
        </div>
    );
}

type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info';

export function Badge({ tone = 'neutral', children, className }: {
    tone?: BadgeTone;
    children: ReactNode;
    className?: string;
}) {
    return <span className={cn(`badge-${tone}`, className)}>{children}</span>;
}

export function statusTone(status: string | null | undefined): BadgeTone {
    switch (status) {
        case 'active':
        case 'approved':
        case 'accepted':
            return 'success';
        case 'pending':
        case 'pending_review':
        case 'under_review':
        case 'requires_resubmission':
        case 'ready_for_review':
            return 'warning';
        case 'rejected':
        case 'suspended':
        case 'expired':
        case 'cancelled':
            return 'danger';
        default:
            return 'neutral';
    }
}

export function Spinner({ className }: { className?: string }) {
    const { t } = useTranslation();

    return (
        <span className={cn('inline-flex items-center gap-2 text-sm text-ink-500', className)} role="status">
            <Loader2 size={17} className="animate-spin" aria-hidden="true" />
            <span>{t('common.loading')}</span>
        </span>
    );
}

export function PageHeader({
    eyebrow,
    title,
    subtitle,
    actions,
}: {
    eyebrow?: string;
    title: string;
    subtitle?: string;
    actions?: ReactNode;
}) {
    return (
        <header className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                {eyebrow ? <p className="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{eyebrow}</p> : null}
                <h1 className="text-2xl font-bold text-ink-950 sm:text-3xl">{title}</h1>
                {subtitle ? <p className="mt-1.5 max-w-2xl text-sm text-ink-500">{subtitle}</p> : null}
            </div>
            {actions ? <div className="flex flex-wrap items-center gap-2">{actions}</div> : null}
        </header>
    );
}

export function StatCard({
    label,
    value,
    icon,
    note,
}: {
    label: string;
    value: ReactNode;
    icon?: ReactNode;
    note?: string;
}) {
    return (
        <div className="card card-body">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-ink-500">{label}</p>
                    <p className="mt-2 text-3xl font-bold text-ink-950">{value}</p>
                    {note ? <p className="mt-1 text-xs text-ink-400">{note}</p> : null}
                </div>
                {icon ? (
                    <span className="rounded-xl bg-brand-50 p-2.5 text-brand-700" aria-hidden="true">
                        {icon}
                    </span>
                ) : null}
            </div>
        </div>
    );
}

export function EmptyState({
    title,
    description,
    action,
    icon,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
    icon?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-ink-200 bg-white/60 px-6 py-12 text-center">
            {icon ? <div className="mb-3 rounded-2xl bg-ink-100 p-3 text-ink-500">{icon}</div> : null}
            <h3 className="text-base font-semibold text-ink-900">{title}</h3>
            {description ? <p className="mt-1 max-w-md text-sm text-ink-500">{description}</p> : null}
            {action ? <div className="mt-4">{action}</div> : null}
        </div>
    );
}

export function SearchInput({
    value,
    onChange,
    placeholder,
}: {
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
}) {
    return (
        <div className="relative">
            <Search size={16} className="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-ink-400" aria-hidden="true" />
            <input
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                aria-label={placeholder}
                className="input ps-9"
                type="search"
            />
        </div>
    );
}

export function Pagination({
    page,
    lastPage,
    total,
    onChange,
    labels,
}: {
    page: number;
    lastPage: number;
    total: number;
    onChange: (page: number) => void;
    labels: { previous: string; next: string; showing: string; results: string; page: string; of: string };
}) {
    if (lastPage <= 1) {
        return <p className="text-xs text-ink-500">{`${labels.showing} ${total} ${labels.results}`}</p>;
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-xs text-ink-500">
                {`${labels.showing} ${total} ${labels.results} · ${labels.page} ${page} ${labels.of} ${lastPage}`}
            </p>
            <div className="flex items-center gap-2">
                <Button variant="secondary" size="sm" disabled={page <= 1} onClick={() => onChange(page - 1)}>
                    <ChevronLeft size={14} /> {labels.previous}
                </Button>
                <Button variant="secondary" size="sm" disabled={page >= lastPage} onClick={() => onChange(page + 1)}>
                    {labels.next} <ChevronRight size={14} />
                </Button>
            </div>
        </div>
    );
}

export function Modal({
    open,
    onClose,
    title,
    description,
    children,
    footer,
    size = 'md',
}: {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children?: ReactNode;
    footer?: ReactNode;
    size?: 'sm' | 'md' | 'lg';
}) {
    const { t } = useTranslation();
    const titleId = useId();

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('keydown', onKeyDown);
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = previousOverflow;
        };
    }, [open, onClose]);

    if (!open || typeof document === 'undefined') {
        return null;
    }

    const widths = { sm: 'max-w-md', md: 'max-w-xl', lg: 'max-w-3xl' };

    return createPortal(
        <div className="fixed inset-0 z-50 flex items-end justify-center bg-ink-950/45 p-4 sm:items-center" role="presentation" onMouseDown={onClose}>
            <div
                className={cn('w-full rounded-2xl bg-white shadow-2xl', widths[size])}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                onMouseDown={(event) => event.stopPropagation()}
            >
                <div className="flex items-start justify-between gap-4 border-b border-ink-100 px-5 py-4">
                    <div>
                        <h2 id={titleId} className="text-lg font-semibold text-ink-950">{title}</h2>
                        {description ? <p className="mt-1 text-sm text-ink-500">{description}</p> : null}
                    </div>
                    <button type="button" onClick={onClose} className="rounded-lg p-1.5 text-ink-400 hover:bg-ink-100 hover:text-ink-700" aria-label={t('common.close')}>
                        <X size={18} />
                    </button>
                </div>
                {children ? <div className="px-5 py-5">{children}</div> : null}
                {footer ? <div className="flex flex-wrap justify-end gap-2 border-t border-ink-100 px-5 py-4">{footer}</div> : null}
            </div>
        </div>,
        document.body,
    );
}

export function ConfirmDialog({
    open,
    title,
    description,
    confirmLabel,
    cancelLabel,
    danger,
    loading,
    onConfirm,
    onClose,
}: {
    open: boolean;
    title: string;
    description: string;
    confirmLabel: string;
    cancelLabel: string;
    danger?: boolean;
    loading?: boolean;
    onConfirm: () => void;
    onClose: () => void;
}) {
    return (
        <Modal
            open={open}
            onClose={onClose}
            title={title}
            size="sm"
            footer={
                <>
                    <Button variant="secondary" onClick={onClose} disabled={loading}>{cancelLabel}</Button>
                    <Button variant={danger ? 'danger' : 'primary'} onClick={onConfirm} loading={loading}>{confirmLabel}</Button>
                </>
            }
        >
            <p className="text-sm text-ink-600">{description}</p>
        </Modal>
    );
}

export function Skeleton({ className }: { className?: string }) {
    return <div className={cn('skeleton', className)} aria-hidden="true" />;
}

export function TableShell({ children, className }: { children: ReactNode; className?: string }) {
    return <div className={cn('overflow-x-auto rounded-2xl border border-ink-200 bg-white', className)}>{children}</div>;
}

export function Td({ children, className, ...props }: HTMLAttributes<HTMLTableCellElement>) {
    return <td className={cn('px-4 py-3 text-sm text-ink-700', className)} {...props}>{children}</td>;
}

export function Th({ children, className, ...props }: HTMLAttributes<HTMLTableCellElement>) {
    return <th className={cn('whitespace-nowrap px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-ink-500', className)} {...props}>{children}</th>;
}

export function CheckMark({ checked }: { checked: boolean }) {
    return checked ? <Check size={16} /> : null;
}
