# Phase 4 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Theater Profile

- [x] Create the theater profile record linked one-to-one with the theater tenant.
- [x] Add name, description, contact information, address, location, logo, and cover fields.
- [x] Add read/update endpoints for the theater workspace.
- [x] Enforce `theater.profile.read` and `theater.profile.update`.
- [x] Expose only public fields on public endpoints.
- [x] Add profile validation and media handling tests.

## 2. Halls

- [x] Create the hall table with tenant, name, description, capacity, and active state.
- [x] Add hall CRUD endpoints.
- [x] Enforce tenant ownership on every hall route.
- [x] Enforce `halls.*` permissions.
- [x] Validate capacity and unique hall names per tenant.
- [x] Soft-delete halls with future reservations safely.
- [x] Add cross-tenant IDOR tests.

## 3. Schedules and Slots

- [x] Create weekly hall schedules with day, start/end, slot duration, price, and currency.
- [x] Validate start before end, positive duration, and non-negative price.
- [x] Create explicit hall slot blocks for maintenance/private use.
- [x] Create materialized `hall_time_slots` with unique `(hall_id, starts_at)`.
- [x] Add a `hall:generate-slots` command for a rolling horizon.
- [x] Generate slots idempotently and never overwrite held/reserved/blocked slots.
- [x] Add slot status: available, held, reserved, blocked, completed.
- [x] Add indexes for tenant, hall, status, and start time.
- [x] Add tests for duplicate slots, blocked periods, and schedule changes.

## 4. Events and Shows

- [x] Create events with translated title/description and poster.
- [x] Add event status: draft, published, cancelled.
- [x] Add create/update/publish/unpublish/cancel endpoints.
- [x] Create shows with event, hall, start/end, and status.
- [x] Prevent overlapping shows in the same hall.
- [x] Block overlapping hall slots when a show is scheduled.
- [x] Prevent a show from overlapping a confirmed group reservation.
- [x] Add public published-only event endpoints.
- [x] Add translation and visibility tests.

## 5. Ticket Tiers

- [x] Create show-scoped ticket tiers with name, description, price, currency, quantity, sale window, and per-booking limit.
- [x] Validate total tier quantity does not exceed hall capacity.
- [x] Add create/update/deactivate endpoints.
- [x] Prevent deletion of tiers with bookings.
- [x] Enforce `ticket_tiers.*` permissions.
- [x] Add capacity and sale-window tests.

## 6. Theater Dashboard

- [x] Add tenant-scoped dashboard counts.
- [x] Never expose global platform counts.
- [x] Add recent reservations/bookings summaries with tenant filtering.
- [x] Add activity-log entries for hall/event/tier changes.
- [x] Add tests for scoped counts.

## 7. Documentation and Verification

- [x] Document all Phase 4 endpoints.
- [x] Add Phase 4 cases to the Postman collection.
- [x] Add feature tests for profile, halls, schedules, slots, events, shows, and tiers.
- [x] Run the Phase 4 test suite and static analysis.

## 8. Frontend

- [x] Add the editable theater profile with logo/cover uploads.
- [x] Add hall list/detail, hall CRUD, and weekly schedule management.
- [x] Add generated slot browsing, date/status filters, manual generation, and maintenance blocks.
- [x] Add event list/detail with English/Arabic content and poster upload.
- [x] Add show scheduling, cancellation, and overlap error handling.
- [x] Add ticket tier management with capacity and sale-window fields.
- [x] Add public theater and event catalog pages with search/filtering.
- [x] Add Arabic/English labels and RTL support for Phase 4.
- [x] Extend the browser smoke test through hall, schedule, event, show, tier, publish, and public catalog flows.

## Definition of Done

Phase 4 is complete when:

1. An active theater can manage its profile and halls.
2. Weekly schedules generate conflict-free materialized slots.
3. Theater blocks and event shows remove slots from availability.
4. Events, shows, and ticket tiers are managed with translations and media.
5. Public catalog endpoints expose only published public data.
6. Tenant isolation and permissions are enforced on every endpoint.
7. Phase 4 test cases pass.
