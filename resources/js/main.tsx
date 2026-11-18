import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router-dom';
import { AppProviders } from '@/app/providers';
import { router } from '@/app/router';
import '@/lib/i18n';

const root = document.getElementById('root');

if (!root) {
    throw new Error('The TheaterLink root element is missing.');
}

createRoot(root).render(
    <StrictMode>
        <AppProviders>
            <RouterProvider router={router} />
        </AppProviders>
    </StrictMode>,
);
