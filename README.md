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

Phase 1 analysis is complete. Phase 2 backend is complete and verified with:

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
- English and Arabic localization, tenant isolation tests, and permission tests.

API documentation is available at `/docs`. A Phase 2 Postman collection is in [`docs/postman/TheaterLink-Phase-2.postman_collection.json`](docs/postman/TheaterLink-Phase-2.postman_collection.json).

See [`docs/README.md`](docs/README.md) for the full phase plan and [`docs/phase-1-technical-analysis.md`](docs/phase-1-technical-analysis.md) for the architecture analysis.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
```

Configure `DB_*` in `.env` before migrating. The test suite uses the `theaterlink_testing` MySQL database; create it before running `php artisan test`.

## Local Test Data

- Seeded administrator: `admin@base.com` / `P@ssw0rd`
- Local/testing OTP: `1234`
- Tenant login: `POST /api/v1/theater/login` or `POST /api/v1/group/login` with `subdomain`, `email`, and `password`

## Health Check

`GET /up`
