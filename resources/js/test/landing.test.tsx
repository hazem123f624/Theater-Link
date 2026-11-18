import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { LandingPage } from '@/features/auth/auth-pages';
import i18n from '@/lib/i18n';

describe('landing page', () => {
    it('renders the public entry points', async () => {
        await i18n.changeLanguage('en');
        render(
            <MemoryRouter>
                <LandingPage />
            </MemoryRouter>,
        );

        expect(screen.getByText('One stage for every theater, group, and audience.')).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Theater' })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /register a theater/i })).toBeInTheDocument();
    });
});
