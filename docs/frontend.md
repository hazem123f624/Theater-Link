# TheaterLink Frontend

The TheaterLink web frontend is a React single-page application served by the Laravel application.

## Stack

- React 19 + TypeScript
- Vite 6
- React Router 6
- TanStack Query
- Zustand
- Tailwind CSS 4
- i18next with English and Arabic resources
- Vitest + Testing Library
- Playwright Core smoke test using the system Chrome installation

## Routes

| Area | Routes |
|---|---|
| Public | `/`, `/register/theater`, `/register/group`, `/register/user` |
| Authentication | `/theater/login`, `/group/login`, `/user/login`, `/admin/login` |
| Password reset | `/forgot-password/theater`, `/forgot-password/group`, `/forgot-password/user`, `/forgot-password/admin` |
| Invitations | `/invitations/accept?token=...` |
| Theater | `/theater`, `/theater/halls`, `/theater/halls/:id`, `/theater/events`, `/theater/events/:id`, `/theater/shows/:id/tiers`, `/theater/reservations`, `/theater/reservations/:id`, `/theater/bookings`, `/theater/bookings/:id`, `/theater/tickets/validate`, `/theater/team`, `/theater/team/trash`, `/theater/roles`, `/theater/invitations`, `/theater/account` |
| Group | `/group`, `/group/application`, `/group/theaters`, `/group/theaters/:id`, `/group/reservations`, `/group/reservations/:id`, `/group/team`, `/group/team/trash`, `/group/roles`, `/group/invitations`, `/group/account` |
| Individual | `/user`, `/user/bookings`, `/user/bookings/:id`, `/user/profile`, `/user/security` |
| Admin | `/admin`, `/admin/applications`, `/admin/applications/:id`, `/admin/profile` |
| Public catalog | `/theaters`, `/theaters/:id`, `/events`, `/events/:slug` |

Pending group owners are redirected to `/group/application` until the workspace is approved.

## Local development

```bash
npm install
npm run dev
php artisan serve
```

Vite proxies `/api` and `/docs` to `http://127.0.0.1:8000`. The built SPA is served by Laravel through the application shell in `resources/views/app.blade.php`.

## Commands

```bash
npm run typecheck   # TypeScript
npm run test        # Vitest
npm run build       # production assets
node scripts/frontend-smoke.mjs  # browser smoke test against a running server
```

The smoke test uses `/usr/bin/google-chrome` by default. Override with `CHROME_PATH` and `FRONTEND_BASE_URL` when needed.

## Authentication and API behavior

- Bearer tokens are stored in `sessionStorage`.
- Tenant requests send `X-Subdomain` and `X-Tenant` when a workspace session exists.
- The API client normalizes the standard `{ success, message, data }` envelope and top-level pagination.
- `401` responses clear the local session and redirect to the correct actor login.
- Tenant permissions from `/tenant/me` control navigation and actions. Backend authorization remains authoritative.
- The admin dashboard sends `step: 1` to `/api/v1/login`, matching the current backend flow.

## Backend prerequisites included in this frontend work

- `GET /api/v1/tenant/roles/permissions` returns the active permission catalog for the current tenant type.
- Tenant role responses include `permission_ids` so the role editor can preselect permissions.
- CORS allowed headers include `X-Subdomain`, `X-Tenant`, `X-NONCE`, and `Accept-Language`.
- Invitation emails link to `/invitations/accept?token=...` using `THEATERLINK_FRONTEND_URL`.
- Phase 3 review endpoints use admin permissions `group_applications-fetch/read/update`.
- Notifications are available to admins and tenant members through the shared notification endpoints.

## Current scope

The frontend covers the Phase 2, Phase 3, Phase 4, and Phase 5 surfaces: registration/OTP, workspace authentication, pending group applications, team management, invitations, custom roles, password reset, individual profile/security, admin group-application review, document decisions, resubmission, notifications, workspace suspension, theater profile management, halls, schedules, availability, events, shows, ticket tiers, group profile and theater discovery, hall reservation requests, theater reservation review, public event booking, individual booking/QR pages, theater booking management, ticket validation, and the public theater/event catalog.

The six planned product phases are implemented. Admin management screens, reporting, and production hardening remain future work.
