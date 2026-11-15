# TheaterLink Documentation

This folder holds the product and engineering documentation for the TheaterLink backend.

> **Current status — 2026-11-12:** Phase 1 analysis is complete. **Phase 2 backend is complete and verified:** theater/group/individual registration, OTP verification, workspace-bound authentication, tenant isolation and permission middleware, team management, invitations, custom tenant roles, password reset, dashboards, English/Arabic localization, and the Phase 2 test suite. The custom QEEI-style API reference at `/docs` covers all 112 API endpoint methods, and a 45-request Postman collection is available at [`postman/TheaterLink-Phase-2.postman_collection.json`](postman/TheaterLink-Phase-2.postman_collection.json). Phase 3 admin review and approval is next.

| File | What it covers |
|---|---|
| [`business.md`](business.md) | TheaterLink business design, actors, phases, reservation lifecycle, ticketing rules, and glossary. |
| [`user-stories.md`](user-stories.md) | Product user stories and acceptance criteria for every phase. |
| [`commands.md`](commands.md) | Common setup, development, database, testing, module, and route commands. |
| [`phase-1-technical-analysis.md`](phase-1-technical-analysis.md) | Phase 1 source-code analysis, multi-tenancy decision, reservation strategy, ticket inventory strategy, and architecture proposal. |
| [`phase-1/`](phase-1/README.md) | Phase 1 analysis summary, task checklist, and architecture review checklist. |
| [`phase-2/`](phase-2/README.md) | Phase 2 core architecture, authentication, registration, tenancy, team, and roles tasks and test cases. |
| [`phase-3/`](phase-3/README.md) | Phase 3 admin review, document review, approval, rejection, resubmission, and provisioning tasks and test cases. |
| [`phase-4/`](phase-4/README.md) | Phase 4 theater profile, halls, schedules, availability, events, and ticket tiers tasks and test cases. |
| [`phase-5/`](phase-5/README.md) | Phase 5 group management, theater discovery, reservation requests, and reservation lifecycle tasks and test cases. |
| [`phase-6/`](phase-6/README.md) | Phase 6 individual event discovery, shows, ticket tiers, inventory, and booking tasks and test cases. |

## Architecture Decisions

| Topic | Decision |
|---|---|
| Tenancy | Hybrid: shared `tenants`, `tenant_members`, auth, roles, and permissions; separate theater and group domain modules. |
| Database | Shared database with strict `tenant_id` row isolation. No database-per-tenant in v1. |
| Tenant type | Derived from the authenticated `TenantMember`; never trusted from request input. |
| Hall scheduling | Materialized fixed-duration slots with temporary pending holds and automatic expiry. |
| Reservation conflicts | Database transactions, row locks, unique slot constraints, and approval-time revalidation. |
| Ticket inventory | Immediate confirmation with atomic inventory decrement; schema is ready for temporary holds if external payment confirmation is added later. |
| Duplicate bookings | Idempotency key plus a unique index. |

The full comparison and rationale are in [`phase-1-technical-analysis.md`](phase-1-technical-analysis.md), Sections 15–16.

## Quick Links

- API documentation page: `/docs`
- Phase 2 Postman collection: [`postman/TheaterLink-Phase-2.postman_collection.json`](postman/TheaterLink-Phase-2.postman_collection.json)
- API base path: `/api/v1`
- Tenant login: `POST /api/v1/theater/login`, `POST /api/v1/group/login`
- Tenant profile: `GET /api/v1/tenant/me`
- Admin dashboard: `/api/v1/dashboard/*`
- Theater dashboard: `GET /api/v1/theater/dashboard`
- Group dashboard: `GET /api/v1/group/dashboard`
- User dashboard: `GET /api/v1/user/dashboard`
- Individual user API: `/api/v1/user/*`
- Health check: `/up`

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
```

## Test Data

- Local/testing OTP: `1234` (non-production only)
- Seed administrator: `admin@base.com` / `P@ssw0rd`
- Tenant login requires `subdomain`, `email`, and `password`

## Status Legend

- `[x]` implemented and verified
- `[~]` partially implemented or reused but not fully verified
- `[ ]` not started
- `Ready` — test case can be executed now
- `Gap` — implementation exists but does not meet the expected result
- `Planned` — feature does not exist yet
