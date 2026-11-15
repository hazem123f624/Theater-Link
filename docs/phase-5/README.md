# Phase 5 — Group Management & Hall Reservations

This folder tracks the implementation and verification work for Phase 5 of TheaterLink.

**Goal:** an approved group can discover theaters and halls, request an available slot, and track the reservation lifecycle; a theater can approve or reject requests for its own halls.

**Source of truth:** [`../business.md`](../business.md) — Phase 5.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 5. |

## Current Status

| Area | Planned | Notes |
|---|---|---|
| Group profile | Planned | Tenant, owner, and roles foundation exists. |
| Group team | Planned | Shared tenant member/role endpoints from Phase 2. |
| Theater discovery | Planned | Public projections over active theaters. |
| Hall availability | Planned | Uses Phase 4 materialized slots. |
| Reservation request | Planned | Temporary slot hold with expiry. |
| Theater reservation inbox | Planned | Tenant-scoped requests only. |
| Approval/rejection | Planned | Row-locked state transitions. |
| Expiry and completion jobs | Planned | `reservations:expire-holds`, `reservations:complete`. |
| Notifications | Planned | Group and theater notifications after commit. |

## Planned Endpoints

Base path: `/api/v1`

### Group workspace

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/group/dashboard/profile` | View group profile. |
| `PUT` | `/group/dashboard/profile` | Update group profile. |
| `GET` | `/group/dashboard/theaters` | Browse active theaters. |
| `GET` | `/group/dashboard/theaters/{id}` | Public theater profile. |
| `GET` | `/group/dashboard/theaters/{id}/halls` | Active halls. |
| `GET` | `/group/dashboard/halls/{hall}/availability` | Requestable slots. |
| `GET` | `/group/dashboard/reservations` | Own reservation requests. |
| `POST` | `/group/dashboard/reservations` | Request a hall slot. |
| `GET` | `/group/dashboard/reservations/{reservation}` | Own reservation details. |
| `POST` | `/group/dashboard/reservations/{reservation}/cancel` | Cancel an own request. |

### Theater workspace

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/theater/dashboard/reservations` | Incoming reservation requests. |
| `GET` | `/theater/dashboard/reservations/{reservation}` | Request details and group public profile. |
| `POST` | `/theater/dashboard/reservations/{reservation}/approve` | Approve and confirm the slot. |
| `POST` | `/theater/dashboard/reservations/{reservation}/reject` | Reject with reason. |
| `POST` | `/theater/dashboard/reservations/{reservation}/cancel` | Cancel an approved reservation per policy. |

## Known Gaps

1. Reservation schema, slot hold, and state machine are not implemented yet.
2. The slot generator from Phase 4 must exist before reservation requests can be tested end to end.
3. Event-show occupancy must block overlapping reservations.
4. Background jobs and notifications are not implemented yet.
5. Reservation cancellation policy needs business confirmation.
