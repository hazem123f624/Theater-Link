# Phase 2 — Core Architecture, Authentication, Registration & Tenancy

This folder tracks the implementation and verification work for Phase 2 of TheaterLink.

**Goal:** every actor can register, sign in, and operate inside the correct isolated workspace, with the shared tenant, membership, role, and permission foundation in place.

**Status:** Phase 2 backend is complete and verified. Admin review/approval, theater operations, reservations, and booking are later phases.

**Source of truth:** [`../business.md`](../business.md) — Phase 2 and the architecture decisions in [`../phase-1-technical-analysis.md`](../phase-1-technical-analysis.md).

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with final status and deferred items. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 2. |
| [`execution-report.md`](execution-report.md) | Final local test, static-analysis, and implementation report. |
| [`../frontend.md`](../frontend.md) | React frontend stack, routes, development commands, and API integration notes. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Laravel 12 / modular foundation | Complete | Lean scaffold with `Admins`, `Users`, `Settings`, and `Tenancy` modules. |
| Shared infrastructure | Complete | OTP, media, messaging, authentication factory, security, activity log, country/city services, repository, response, and localization foundations. |
| Tenancy schema | Complete | `tenants`, `tenant_members`, global tenant permissions, per-tenant roles, translations, and pivots. |
| Tenant provisioning | Complete | Creates a tenant, owner member, system roles, and owner role assignment in one transaction. |
| Tenant authentication | Complete | Separate theater/group login, tenant profile, logout, refresh token, encrypted/plain `X-Tenant`, and `/tenant/resolve`. |
| Tenant context middleware | Complete | Token-driven tenant resolution, member status, header consistency, active workspace, tenant type, and permission checks. |
| Theater registration | Complete | Public registration, OTP verification/resend, theater profile, active workspace, and owner token. |
| Group registration | Complete | Permit upload, OTP verification/resend, group profile, pending application, and application reference. |
| Pending group access | Complete | Pending owners can view application status/documents/workspace/support; dashboard remains blocked. |
| Individual registration | Complete | Registration, OTP verification, login, and user dashboard with normalized E.164 phone numbers. |
| Team management | Complete | List/create/update, activate/deactivate, soft-delete, trash, restore, force-delete, and last-owner protection. |
| Team invitations | Complete | Invite by email, assign a role, accept, and issue a workspace token. |
| Tenant roles | Complete | List/create/show/update/delete custom roles, permission validation by tenant type, system-role protection. |
| Tenant password reset | Complete | Forgot-password OTP, token verification, and password reset; existing tokens are revoked. |
| Localization | Complete | English and Arabic messages for tenancy, registration, teams, roles, invitations, and password reset. |
| API documentation | Complete | QEEI-style `/docs` with all 170 API endpoint methods, including every Phase 2 endpoint. |
| Postman collection | Complete | [`../postman/TheaterLink-Phase-2.postman_collection.json`](../postman/TheaterLink-Phase-2.postman_collection.json) with 103 Phase 2–4 requests. |
| Web frontend | Complete | React 19 + TypeScript SPA with registration, four sign-in surfaces, tenant dashboards, team, roles, invitations, password reset, and admin shell. |
| Frontend verification | Complete | 7 Vitest tests, TypeScript clean, production build successful, browser smoke test covering all Phase 2 flows including invitations and password reset. |

## Implemented Endpoints

Base path: `/api/v1`

### Theater Onboarding and Dashboard

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/theater/register` | Start theater registration and send the owner OTP. |
| `POST` | `/theater/verify` | Verify the OTP, provision the theater, and return the owner token. |
| `POST` | `/theater/resend-otp` | Resend a pending theater registration OTP. |
| `POST` | `/theater/login` | Sign in to an active theater workspace. |
| `GET` | `/theater/dashboard` | Theater dashboard summary. |

### Group Onboarding, Application, and Dashboard

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/group/register` | Start group registration with the official permit upload. |
| `POST` | `/group/verify` | Verify the OTP, provision the pending group, and create the application. |
| `POST` | `/group/resend-otp` | Resend a pending group registration OTP. |
| `POST` | `/group/login` | Sign in as a pending or active group owner/member. |
| `GET` | `/group/dashboard` | Group dashboard summary; active groups only. |
| `GET` | `/group/application` | Pending application status, feedback, and required actions. |
| `GET` | `/group/application/documents` | Application documents and review status. |
| `GET` | `/group/application/workspace` | Workspace status, subdomain, and workspace URL. |
| `GET` | `/group/application/support` | Support contact information. |

### Tenant Session

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/tenant/resolve` | Resolve an active workspace by subdomain and return its encrypted identifier. |
| `GET` | `/tenant/me` | Return the authenticated member, tenant, roles, and permissions. |
| `POST` | `/tenant/logout` | Revoke the current token. |
| `POST` | `/tenant/refresh-token` | Revoke the old token and issue a new one. |

### Team and Invitations

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/tenant/team` | List workspace team members. |
| `POST` | `/tenant/team` | Create a member and assign roles. |
| `PUT` | `/tenant/team/{member}` | Update a member and synchronize roles. |
| `PATCH` | `/tenant/team/{member}/toggle-active` | Activate or deactivate a member. |
| `DELETE` | `/tenant/team/{member}` | Soft-delete a member. |
| `GET` | `/tenant/team/trash` | List soft-deleted members. |
| `PATCH` | `/tenant/team/{member}/restore` | Restore a soft-deleted member. |
| `DELETE` | `/tenant/team/{member}/force-delete` | Permanently remove a member. |
| `GET` | `/tenant/team/invitations` | List workspace invitations. |
| `POST` | `/tenant/team/invitations` | Invite a member by email and assign a role. |
| `POST` | `/tenant/team/invitations/accept` | Accept an invitation and return a member token. |

### Roles

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/tenant/roles` | List tenant roles and permissions. |
| `POST` | `/tenant/roles` | Create a custom role with tenant-type-valid permissions. |
| `GET` | `/tenant/roles/{role}` | View a role and its permissions. |
| `PUT` | `/tenant/roles/{role}` | Update a custom role and its permissions. |
| `DELETE` | `/tenant/roles/{role}` | Soft-delete a custom role; system roles are protected. |

### Password Reset

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/tenant/forgot-password` | Send a password reset OTP and return a reset token. |
| `POST` | `/tenant/verify-forgot-password` | Verify the password reset OTP. |
| `POST` | `/tenant/reset-password` | Set a new password and revoke existing tokens. |

### Individual User and Dashboards

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/user/register` | Register an individual user. |
| `POST` | `/user/verify` | Verify the registration OTP and return a user token. |
| `POST` | `/user/login` | Sign in as an individual user. |
| `GET` | `/user/dashboard` | Individual user dashboard summary. |
| `GET` | `/user/profile` | Individual user profile. |

## Deferred to Later Phases

| Item | Reason |
|---|---|
| Optional theater registration documents | Theater profile media belongs to Phase 4 theater management. |
| Real hall/event/reservation/booking dashboard counters | Requires the Phase 4–6 domain modules. |
| Dedicated login/OTP rate-limit tests | Shared security middleware already applies limits; dedicated coverage is part of security hardening. |
| Operational theater and reservation screens | Phase 4–6 domain scope. |

## Test Data

- Seeded administrator: `admin@base.com` / `P@ssw0rd`
- Tenant login: `subdomain`, `email`, `password`
- Local/testing OTP: `1234`
- Seed permissions: `php artisan db:seed --class="Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder"`
- Run tests: `php artisan test`
- Postman: import [`../postman/TheaterLink-Phase-2.postman_collection.json`](../postman/TheaterLink-Phase-2.postman_collection.json)

## Verification

```text
php artisan test
Tests: 51 passed (281 assertions)

vendor/bin/phpstan analyse app/Services/Tenancy Modules/Tenancy/app Modules/Tenancy/routes database/seeders/PlatformDocumentationSeeder.php config/documentation.php --memory-limit=1G
[OK] No errors
```

`GET /docs` returns `200` and renders all 170 documented endpoint methods.

```text
npm run typecheck
npm run test
npm run build
```

Frontend tests: 7 passed. Production assets build successfully. The browser smoke test covers theater/group/individual registration, OTP, pending group access, team, role creation, invitations, tenant password reset, admin login, and Arabic RTL.
