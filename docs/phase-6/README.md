# Phase 6 — Individual Events & Ticket Booking

This folder tracks the implementation and verification work for Phase 6 of TheaterLink.

**Status:** Phase 6 backend and frontend are complete and verified.

**Goal:** an individual can discover published events, choose a show and ticket tier, book tickets without overselling, receive QR tickets, cancel according to policy, and have tickets validated at entry.

**Source of truth:** [`../business.md`](../business.md) — Phase 6.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 6. |
| [`execution-report.md`](execution-report.md) | Delivered scope and verification evidence. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Public event discovery | Complete | Phase 4 catalog reused; public event content is published-only. |
| Show and tier availability | Complete | `GET /public/shows/{show}/tiers` returns sale state and remaining inventory. |
| Ticket inventory | Complete | Atomic `sold_quantity` updates with row locks and database checks. |
| Booking creation | Complete | Idempotent, multi-tier, all-or-nothing, server-authoritative totals. |
| Booking confirmation | Complete | Human-readable reference, items, totals, and ticket records. |
| Ticket issuance and QR | Complete | One ticket per unit, unique numbers, opaque QR tokens, QR rendering. |
| Ticket validation | Complete | Theater-only validation marks a valid ticket used exactly once. |
| Cancellation | Complete | Individual cutoff and theater cancellation, inventory restoration, notifications. |
| Notifications | Complete | Individual/theater in-app notifications plus queued email/SMS after commit. |
| Dashboard counters | Complete | User, theater, and admin booking counters are real. |

## Booking Policy

| Rule | Default |
|---|---|
| Booking confirmation | Immediate `confirmed`; no temporary hold |
| Booking cutoff | Show must start after `THEATERLINK_BOOKING_LEAD_MINUTES` (default 60) |
| Individual cancellation cutoff | 24 hours before show start (`THEATERLINK_BOOKING_CANCEL_CUTOFF_HOURS=24`) |
| Theater cancellation | Allowed before show start |
| Inventory restoration | Only on successful cancellation and only when no ticket was used |
| Multi-tier booking | Supported; one unavailable tier rolls back the full booking |
| Price and currency | Snapshotted on booking items and the booking total |
| Idempotency | Unique per user + key; same payload returns the existing booking |
| Tickets | One ticket per unit with unique ticket number and opaque QR token |
| QR validation | Valid ticket becomes `used` once; cancelled/expired/other-theater tickets are rejected |
| Payments | Out of scope; payment is external |

## Endpoints

Base path: `/api/v1`

### Public

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/public/shows/{show}/tiers` | Fresh bookable tiers, sale state, and remaining quantities. |

### Individual

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/user/bookings` | List own bookings with status, scope, search, and pagination. |
| `POST` | `/user/bookings` | Create a confirmed booking with an idempotency key. |
| `GET` | `/user/bookings/{booking}` | View booking items, tickets, QR tokens, and history. |
| `POST` | `/user/bookings/{booking}/cancel` | Cancel an own booking before the cutoff. |

### Theater

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/theater/dashboard/bookings` | List theater bookings and customer information. |
| `GET` | `/theater/dashboard/bookings/{booking}` | View a theater booking and ticket statuses. |
| `POST` | `/theater/dashboard/bookings/{booking}/cancel` | Cancel before show start with a reason. |
| `POST` | `/theater/dashboard/tickets/validate` | Validate a QR token and mark the ticket used. |

All booking routes use Sanctum abilities, tenant context where applicable, tenant-type checks, and the `bookings.*` / `tickets.validate` permissions.

## Frontend

- Public event page with show selection, tier quantity controls, availability, and live totals.
- Sign-in-to-book redirect for individual accounts.
- User booking list with status, scope, search, and pagination.
- User booking detail with items, totals, history, QR tickets, and cancellation.
- Theater booking list/detail with customer contact, ticket states, and cancellation.
- Theater ticket validation page with manual QR-token entry.
- User, theater, and admin dashboard booking counters.
- Notification bell enabled for all actors with booking/reservation routing.
- English/Arabic labels and RTL support.

## Deferred

- Online payments, refunds, and settlement reconciliation remain out of scope.
- Camera-based QR scanning can be added as a device-specific enhancement; manual token validation is implemented.
- Admin booking management, advanced reporting, promotions, and seat-level selection are future work.
