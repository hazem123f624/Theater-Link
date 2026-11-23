import { Navigate, createBrowserRouter, useParams } from 'react-router-dom';
import { CatalogLayout, DashboardLayout } from '@/components/layout';
import {
    AcceptInvitationPage,
    ForbiddenPage,
    ForgotPasswordPage,
    LandingPage,
    LoginPage,
    NotFoundPage,
    RegisterGroupPage,
    RegisterTheaterPage,
    RegisterUserPage,
} from '@/features/auth/auth-pages';
import {
    AdminDashboardPage,
    GroupApplicationPage,
    GroupDashboardPage,
    TheaterDashboardPage,
    UserDashboardPage,
} from '@/features/dashboards/dashboard-pages';
import { AdminApplicationsPage } from '@/features/admin-applications/admin-applications-page';
import { TheaterBookingDetailPage, TheaterBookingsPage, TicketValidationPage, UserBookingDetailPage, UserBookingsPage } from '@/features/bookings/booking-pages';
import { AdminApplicationDetailPage } from '@/features/admin-applications/admin-application-detail-page';
import { TeamPage, TeamTrashPage } from '@/features/team/team-pages';
import { RolesPage } from '@/features/roles/roles-page';
import { InvitationsPage } from '@/features/invitations/invitations-page';
import { HallDetailPage, HallsPage } from '@/features/halls/halls-pages';
import { EventDetailPage, EventsPage, ShowTiersPage } from '@/features/events/events-pages';
import { TheaterProfilePage } from '@/features/theater-profile/theater-profile-page';
import { PublicEventPage, PublicEventsPage, PublicTheaterPage, PublicTheatersPage } from '@/features/public/public-pages';
import {
    GroupProfilePage,
    GroupReservationDetailPage,
    GroupReservationsPage,
    GroupTheaterPage,
    GroupTheatersPage,
    TheaterReservationDetailPage,
    TheaterReservationsPage,
} from '@/features/reservations/reservation-pages';
import {
    AdminProfilePage,
    TenantAccountPage,
    UserProfilePage,
    UserSecurityPage,
} from '@/features/profile/profile-pages';
import type { Actor } from '@/api/types';
import { homePathForSession, useAuthStore } from '@/stores/auth-store';
import type { ReactNode } from 'react';

function RequireActor({ actor, children }: { actor: Actor; children: ReactNode }) {
    const session = useAuthStore((state) => state.session);

    if (!session) {
        return <Navigate to={`/${actor}/login`} replace />;
    }

    if (session.actor !== actor) {
        return <Navigate to={homePathForSession(session)} replace />;
    }

    return <>{children}</>;
}

function RequirePermission({ permission, children }: { permission: string; children: ReactNode }) {
    const session = useAuthStore((state) => state.session);

    if (!session?.permissions.includes(permission)) {
        return <ForbiddenPage />;
    }

    return <>{children}</>;
}

function RequireActiveTenant({ children }: { children: ReactNode }) {
    const session = useAuthStore((state) => state.session);

    if (session?.tenant?.status !== 'active') {
        if (session?.actor === 'group') {
            return <Navigate to="/group/application" replace />;
        }

        return <ForbiddenPage />;
    }

    return <>{children}</>;
}

function GroupDashboardGate() {
    const session = useAuthStore((state) => state.session);

    if (session?.tenant?.status !== 'active') {
        return <Navigate to="/group/application" replace />;
    }

    return <GroupDashboardPage />;
}

function GroupAccountGate() {
    const session = useAuthStore((state) => state.session);

    if (session?.tenant?.status !== 'active') {
        return <TenantAccountPage />;
    }

    return <GroupProfilePage />;
}

function ForgotPasswordRoute() {
    const { actor } = useParams();
    const validActors: Actor[] = ['theater', 'group', 'user', 'admin'];

    if (!actor || !validActors.includes(actor as Actor)) {
        return <NotFoundPage />;
    }

    return <ForgotPasswordPage actor={actor as Actor} />;
}

export const router = createBrowserRouter([
    { path: '/', element: <LandingPage /> },
    { path: '/theater/login', element: <LoginPage actor="theater" /> },
    { path: '/group/login', element: <LoginPage actor="group" /> },
    { path: '/user/login', element: <LoginPage actor="user" /> },
    { path: '/admin/login', element: <LoginPage actor="admin" /> },
    { path: '/register/theater', element: <RegisterTheaterPage /> },
    { path: '/register/group', element: <RegisterGroupPage /> },
    { path: '/register/user', element: <RegisterUserPage /> },
    { path: '/forgot-password/:actor', element: <ForgotPasswordRoute /> },
    { path: '/invitations/accept', element: <AcceptInvitationPage /> },
    {
        path: '/theater',
        element: <RequireActor actor="theater"><DashboardLayout /></RequireActor>,
        children: [
            { index: true, element: <TheaterDashboardPage /> },
            { path: 'team', element: <RequirePermission permission="team.fetch"><TeamPage /></RequirePermission> },
            { path: 'team/trash', element: <RequirePermission permission="team.fetch"><TeamTrashPage /></RequirePermission> },
            { path: 'halls', element: <RequirePermission permission="halls.fetch"><HallsPage /></RequirePermission> },
            { path: 'halls/:id', element: <RequirePermission permission="halls.fetch"><HallDetailPage /></RequirePermission> },
            { path: 'events', element: <RequirePermission permission="events.fetch"><EventsPage /></RequirePermission> },
            { path: 'events/:id', element: <RequirePermission permission="events.fetch"><EventDetailPage /></RequirePermission> },
            { path: 'shows/:id/tiers', element: <RequirePermission permission="ticket_tiers.fetch"><ShowTiersPage /></RequirePermission> },
            { path: 'reservations', element: <RequirePermission permission="reservations.fetch"><TheaterReservationsPage /></RequirePermission> },
            { path: 'reservations/:id', element: <RequirePermission permission="reservations.read"><TheaterReservationDetailPage /></RequirePermission> },
            { path: 'bookings', element: <RequirePermission permission="bookings.fetch"><TheaterBookingsPage /></RequirePermission> },
            { path: 'bookings/:id', element: <RequirePermission permission="bookings.read"><TheaterBookingDetailPage /></RequirePermission> },
            { path: 'tickets/validate', element: <RequirePermission permission="tickets.validate"><TicketValidationPage /></RequirePermission> },
            { path: 'roles', element: <RequirePermission permission="roles.fetch"><RolesPage /></RequirePermission> },
            { path: 'invitations', element: <RequirePermission permission="team.fetch"><InvitationsPage /></RequirePermission> },
            { path: 'account', element: <TheaterProfilePage /> },
            { path: 'profile', element: <TheaterProfilePage /> },
        ],
    },
    {
        path: '/group',
        element: <RequireActor actor="group"><DashboardLayout /></RequireActor>,
        children: [
            { index: true, element: <GroupDashboardGate /> },
            { path: 'application', element: <GroupApplicationPage /> },
            { path: 'theaters', element: <RequireActiveTenant><RequirePermission permission="reservations.fetch"><GroupTheatersPage /></RequirePermission></RequireActiveTenant> },
            { path: 'theaters/:id', element: <RequireActiveTenant><RequirePermission permission="reservations.fetch"><GroupTheaterPage /></RequirePermission></RequireActiveTenant> },
            { path: 'reservations', element: <RequireActiveTenant><RequirePermission permission="reservations.fetch"><GroupReservationsPage /></RequirePermission></RequireActiveTenant> },
            { path: 'reservations/:id', element: <RequireActiveTenant><RequirePermission permission="reservations.read"><GroupReservationDetailPage /></RequirePermission></RequireActiveTenant> },
            { path: 'team', element: <RequireActiveTenant><RequirePermission permission="team.fetch"><TeamPage /></RequirePermission></RequireActiveTenant> },
            { path: 'team/trash', element: <RequireActiveTenant><RequirePermission permission="team.fetch"><TeamTrashPage /></RequirePermission></RequireActiveTenant> },
            { path: 'roles', element: <RequireActiveTenant><RequirePermission permission="roles.fetch"><RolesPage /></RequirePermission></RequireActiveTenant> },
            { path: 'invitations', element: <RequireActiveTenant><RequirePermission permission="team.fetch"><InvitationsPage /></RequirePermission></RequireActiveTenant> },
            { path: 'account', element: <GroupAccountGate /> },
        ],
    },
    {
        path: '/theaters',
        element: <CatalogLayout />,
        children: [
            { index: true, element: <PublicTheatersPage /> },
            { path: ':id', element: <PublicTheaterPage /> },
        ],
    },
    {
        path: '/events',
        element: <CatalogLayout />,
        children: [
            { index: true, element: <PublicEventsPage /> },
            { path: ':slug', element: <PublicEventPage /> },
        ],
    },
    {
        path: '/user',
        element: <RequireActor actor="user"><DashboardLayout /></RequireActor>,
        children: [
            { index: true, element: <UserDashboardPage /> },
            { path: 'bookings', element: <UserBookingsPage /> },
            { path: 'bookings/:id', element: <UserBookingDetailPage /> },
            { path: 'profile', element: <UserProfilePage /> },
            { path: 'security', element: <UserSecurityPage /> },
        ],
    },
    {
        path: '/admin',
        element: <RequireActor actor="admin"><DashboardLayout /></RequireActor>,
        children: [
            { index: true, element: <AdminDashboardPage /> },
            { path: 'applications', element: <RequirePermission permission="group_applications-fetch"><AdminApplicationsPage /></RequirePermission> },
            { path: 'applications/:id', element: <RequirePermission permission="group_applications-read"><AdminApplicationDetailPage /></RequirePermission> },
            { path: 'profile', element: <AdminProfilePage /> },
        ],
    },
    { path: '*', element: <NotFoundPage /> },
]);
