# Phase 6 — Individual Events & Ticket Booking

This folder tracks the implementation and verification work for Phase 6 of TheaterLink.

**Goal:** an individual can discover published events, choose a show and ticket tier, book tickets safely, and receive booking confirmation without overselling.

**Source of truth:** [`../business.md`](../business.md) — Phase 6.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 6. |

## Current Status

| Area | Planned | Notes |
|---|---|---|
| Public event discovery | Planned | Depends on Phase 4 events/shows. |
| Show and tier availability | Planned | Depends on Phase 4 ticket tiers. |
| Ticket inventory | Planned | Immediate confirmation with atomic decrement. |
| Booking creation | Planned | Idempotency key and row locking. |
| Booking confirmation | Planned | Booking reference and ticket information. |
| Ticket issuance/QR | Planned | Per-ticket records if entry control is required. |
| Booking cancellation | Planned | Policy-dependent inventory restoration. |
| Notifications | Planned | Queued after commit. |

## Planned Endpoints

Base path: `/api/v1`

### Public discovery

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/public/events` | Published events with search/filter/pagination. |
| `GET` | `/public/events/{slug}` | Event details and upcoming shows. |
| `GET` | `/public/shows/{show}/tiers` | Available ticket tiers and prices. |

### Individual booking

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/user/bookings` | List own bookings. |
| `POST` | `/user/bookings` | Create a booking with an idempotency key. |
| `GET` | `/user/bookings/{booking}` | Booking details and tickets. |
| `POST` | `/user/bookings/{booking}/cancel` | Cancel according to policy. |

## Known Gaps

1. Event/show/tier models from Phase 4 are not implemented yet.
2. Booking, ticket, and inventory tables are not implemented yet.
3. The event public catalog must expose only published, non-cancelled content.
4. No online payment exists; booking confirmation is immediate and external payment remains outside the platform.
5. Cancellation policy needs business confirmation.
