# TheaterLink Backend

TheaterLink is a multi-tenant theater management and booking platform. The backend connects theaters, theater groups, and individual audience members. It supports theater teams, hall schedules, group reservation requests, event publishing, ticket tiers, and ticket booking. Online payments are intentionally outside the platform.

## Stack

- PHP 8.2
- Laravel 12
- Laravel Sanctum
- `nwidart/laravel-modules`
- MySQL 8+
- PHPUnit 11
- Larastan

## Current Scope

Phase 1 analysis and Phases 2–4 are complete and verified with:

- Lean modular project scaffold.
- Shared OTP, media, messaging, security, activity-log, repository, response, and localization infrastructure.
- `Admins`, `Users`, `Settings`, and `Tenancy` modules.
- Tenant, tenant-member, role, permission, invitation, profile, and application schema.
- Tenant provisioning with system roles and owner assignment.
- Theater registration with OTP, theater profile, and owner token issuance.
- Group registration with permit upload, OTP, pending application, and application status endpoints.
- Individual registration, OTP verification, login, and user dashboard.
- Theater/group tenant login, tenant profile, logout, refresh token, and tenant context middleware.
- Web-only Admin, Theater, Group, and User dashboard entry points.
- Tenant team management, trash/restore/force-delete, invitations, and custom roles.
- Tenant password reset and encrypted/plain `X-Tenant` support.
- React + TypeScript web frontend for registration, all four dashboards, team management, invitations, custom roles, password reset, account security, admin group-application review, document decisions, notifications, and group resubmission.
- Admin group-application review, private document access, document decisions, transactional approval/rejection/reopening, group resubmission, notes, workspace suspension, and notifications.
- Theater profile management, halls, weekly schedules, materialized slots, maintenance blocks, events, shows, ticket tiers, and public catalog APIs.
- English and Arabic localization with RTL support, tenant isolation tests, permission tests, and Phase 3 review workflow tests.

The web frontend is a React + TypeScript SPA served by Laravel. It covers public registration, all four sign-in surfaces, tenant dashboards, team management, invitations, custom roles, password reset, admin group-application review, document decisions, notifications, group resubmission, theater profile/hall/schedule/event/show/tier management, and the public theater/event catalog. See [`docs/frontend.md`](docs/frontend.md).

API documentation is available at `/docs`. A Phase 2–4 Postman collection is in [`docs/postman/TheaterLink-Phase-2.postman_collection.json`](docs/postman/TheaterLink-Phase-2.postman_collection.json).

See [`docs/README.md`](docs/README.md) for the full phase plan and [`docs/phase-1-technical-analysis.md`](docs/phase-1-technical-analysis.md) for the architecture analysis.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
npm run typecheck
npm run test
npm run build
```

For local UI development, run `npm run dev` and `php artisan serve` in separate terminals.

Configure `DB_*` in `.env` before migrating. The test suite uses the `theaterlink_testing` MySQL database; create it before running `php artisan test`.

## Local Test Data

- Seeded administrator: `admin@base.com` / `P@ssw0rd`
- Local/testing OTP: `1234`
- Tenant login: `POST /api/v1/theater/login` or `POST /api/v1/group/login` with `subdomain`, `email`, and `password`

## Health Check

`GET /up`
