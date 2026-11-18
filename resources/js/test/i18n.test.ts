import { describe, expect, it } from 'vitest';
import i18n, { setAppLanguage } from '@/lib/i18n';

describe('TheaterLink translations', () => {
    it('has the English experience copy', async () => {
        await i18n.changeLanguage('en');
        expect(i18n.t('common.appName')).toBe('TheaterLink');
        expect(i18n.t('auth.theaterLogin')).toBe('Theater sign in');
        expect(i18n.t('nav.team')).toBe('Team');
    });

    it('switches to Arabic and updates the document direction', async () => {
        setAppLanguage('ar');
        expect(i18n.t('nav.dashboard')).toBe('لوحة التحكم');
        expect(document.documentElement.dir).toBe('rtl');
        setAppLanguage('en');
        expect(document.documentElement.dir).toBe('ltr');
    });
});
