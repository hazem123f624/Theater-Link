# Phase 5 — Group Profile, Discovery & Hall Reservations

This folder tracks the implementation and verification work for Phase 5 of TheaterLink.

**Status:** Phase 5 backend and frontend are complete and verified.

**Goal:** an approved group can maintain its public profile, discover active theaters and halls, request an available slot with a temporary hold, and track the reservation lifecycle; a theater can review, approve, reject, or cancel requests for its own halls.

**Source of truth:** [`../business.md`](../business.md) — Phase 5.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 5. |
| [`execution-report.md`](execution-report.md) | Delivered scope and verification evidence. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Group profile | Complete | Public profile, logo upload, contact details, permissions. |
| Group team | Complete | Reuses the shared tenant team, role, and invitation endpoints. |
| Theater discovery | Complete | Active theaters and halls only; public projections. |
| Hall availability | Complete | Requestable materialized slots from Phase 4. |
| Reservation request | Complete | Transactional pending hold with idempotency key and server price snapshot. |
| Theater reservation inbox | Complete | Tenant-scoped list and detail with group contact information. |
| Approval/rejection | Complete | Row-locked transitions; approval revalidates hold, slot, show, and block conflicts. |
| Cancellation and completion | Complete | Group/theater cancellation, cutoff enforcement, automatic completion. |
| Expiry and completion jobs | Complete | `reservations:expire-holds` every five minutes, `reservations:complete` every ten minutes. |
| Notifications | Complete | In-app group/theater notifications with queued email/SMS after commit. |
| Show conflict handling | Complete | Scheduling a show over a pending hold expires the hold and releases the slot. |

## Reservation Policy

| Rule | Default |
|---|---|
| Pending request hold | 48 hours (`THEATERLINK_RESERVATION_HOLD_MINUTES=2880`) |
| Group cancellation cutoff for approved reservations | 24 hours before start (`THEATERLINK_GROUP_CANCEL_CUTOFF_HOURS=24`) |
| Theater cancellation | Allowed until the reservation starts |
| Pending request for the same idempotency key | Returns the existing reservation without creating a second hold |
| Price and currency | Snapshotted from the slot at request time |
| Slot conflict | Show scheduling invalidates a pending hold; approved reservations block show creation |
| Completed reservation | Automatic after the slot end time |

## Endpoints

Base path: `/api/v1`

### Group workspace

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/group/dashboard/profile` | View the group public profile. |
| `PUT` | `/group/dashboard/profile` | Update profile details and logo. |
| `GET` | `/group/dashboard/theaters` | Discover active theaters. |
| `GET` | `/group/dashboard/theaters/{theater}` | View a theater and its active halls. |
| `GET` | `/group/dashboard/theaters/{theater}/halls` | List a theater's active halls. |
| `GET` | `/group/dashboard/halls/{hall}/availability` | List requestable slots for a date range. |
| `GET` | `/group/dashboard/reservations` | List the group's reservations. |
| `POST` | `/group/dashboard/reservations` | Request a hall slot and create a pending hold. |
| `GET` | `/group/dashboard/reservations/{reservation}` | View an own reservation and its history. |
| `POST` | `/group/dashboard/reservations/{reservation}/cancel` | Cancel an own reservation. |

### Theater workspace

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/theater/dashboard/reservations` | List incoming reservation requests. |
| `GET` | `/theater/dashboard/reservations/{reservation}` | View a request and the group's public profile. |
| `POST` | `/theater/dashboard/reservations/{reservation}/approve` | Approve the hold and reserve the slot. |
| `POST` | `/theater/dashboard/reservations/{reservation}/reject` | Reject with a reason and release the slot. |
| `POST` | `/theater/dashboard/reservations/{reservation}/cancel` | Cancel a pending or approved reservation. |

All routes require a Sanctum tenant-member token, tenant context headers, an active workspace, the correct tenant type, and the `reservations.*` / `group.profile.*` permission.

## State Machine

```text
pending ──approve──> approved ──complete──> completed
   │                    │
   ├──reject──> rejected│
   ├──cancel──> cancelled
   ├──expiry──> expired
   └──show conflict──> expired
```

Every transition writes a `reservation_status_histories` row and an activity log entry.

## Frontend

- Group profile page with logo upload and bilingual labels.
- Theater discovery list and theater detail with hall selection and date-range availability.
- Slot request modal with hold explanation and idempotent submission.
- Group reservation list, filters, detail, lifecycle history, and cancellation.
- Theater reservation inbox, filters, detail, approve/reject/cancel actions with reasons.
- New Group "Discover" and "Reservations" navigation plus Theater "Reservations" navigation.
- Dashboard quick actions and real pending/approved/upcoming reservation counters.
- English/Arabic labels and RTL support.

## Deferred

- Ticket discovery, booking, inventory consumption, QR tickets, and public booking polish — Phase 6.
- External payments and refunds remain out of scope.
- Mobile clients are out of scope; TheaterLink is web-only.
