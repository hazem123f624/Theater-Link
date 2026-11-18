export type Language = 'en' | 'ar';

export const LANGUAGE_KEY = 'theaterlink.language';

export function getStoredLanguage(): Language {
    if (typeof window === 'undefined') {
        return 'en';
    }

    return window.localStorage.getItem(LANGUAGE_KEY) === 'ar' ? 'ar' : 'en';
}

export function applyDocumentLanguage(language: Language): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.lang = language;
    document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
}

export function storeLanguage(language: Language): void {
    if (typeof window !== 'undefined') {
        window.localStorage.setItem(LANGUAGE_KEY, language);
    }

    applyDocumentLanguage(language);
}
