import i18n from '@/lib/i18n';

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(i18n.language === 'ar' ? 'ar' : 'en', {
        dateStyle: 'medium',
    }).format(date);
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(i18n.language === 'ar' ? 'ar' : 'en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

export function initials(name: string | null | undefined): string {
    if (!name) {
        return 'TL';
    }

    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export function localizedName(item: { name_en?: string; name_ar?: string } | null | undefined): string {
    if (!item) {
        return '';
    }

    return i18n.language === 'ar' ? item.name_ar ?? item.name_en ?? '' : item.name_en ?? item.name_ar ?? '';
}

export function humanize(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}
