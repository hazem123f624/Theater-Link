# Phase 4 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Theater Profile

- [ ] Create the theater profile record linked one-to-one with the theater tenant.
- [ ] Add name, description, contact information, address, location, logo, and cover fields.
- [ ] Add read/update endpoints for the theater workspace.
- [ ] Enforce `theater.profile.read` and `theater.profile.update`.
- [ ] Expose only public fields on public endpoints.
- [ ] Add profile validation and media handling tests.

## 2. Halls

- [ ] Create the hall table with tenant, name, description, capacity, and active state.
- [ ] Add hall CRUD endpoints.
- [ ] Enforce tenant ownership on every hall route.
- [ ] Enforce `halls.*` permissions.
- [ ] Validate capacity and unique hall names per tenant.
- [ ] Soft-delete halls with future reservations safely.
- [ ] Add cross-tenant IDOR tests.

## 3. Schedules and Slots

- [ ] Create weekly hall schedules with day, start/end, slot duration, price, and currency.
- [ ] Validate start before end, positive duration, and non-negative price.
- [ ] Create explicit hall slot blocks for maintenance/private use.
- [ ] Create materialized `hall_time_slots` with unique `(hall_id, starts_at)`.
- [ ] Add a `hall:generate-slots` command for a rolling horizon.
- [ ] Generate slots idempotently and never overwrite held/reserved/blocked slots.
- [ ] Add slot status: available, held, reserved, blocked, completed.
- [ ] Add indexes for tenant, hall, status, and start time.
- [ ] Add tests for duplicate slots, blocked periods, and schedule changes.

## 4. Events and Shows

- [ ] Create events with translated title/description and poster.
- [ ] Add event status: draft, published, cancelled.
- [ ] Add create/update/publish/unpublish/cancel endpoints.
- [ ] Create shows with event, hall, start/end, and status.
- [ ] Prevent overlapping shows in the same hall.
- [ ] Block overlapping hall slots when a show is scheduled.
- [ ] Prevent a show from overlapping a confirmed group reservation.
- [ ] Add public published-only event endpoints.
- [ ] Add translation and visibility tests.

## 5. Ticket Tiers

- [ ] Create show-scoped ticket tiers with name, description, price, currency, quantity, sale window, and per-booking limit.
- [ ] Validate total tier quantity does not exceed hall capacity.
- [ ] Add create/update/deactivate endpoints.
- [ ] Prevent deletion of tiers with bookings.
- [ ] Enforce `ticket_tiers.*` permissions.
- [ ] Add capacity and sale-window tests.

## 6. Theater Dashboard

- [ ] Add tenant-scoped dashboard counts.
- [ ] Never expose global platform counts.
- [ ] Add recent reservations/bookings summaries with tenant filtering.
- [ ] Add activity-log entries for hall/event/tier changes.
- [ ] Add tests for scoped counts.

## 7. Documentation and Verification

- [ ] Document all Phase 4 endpoints.
- [ ] Add Phase 4 cases to the Postman collection.
- [ ] Add feature tests for profile, halls, schedules, slots, events, shows, and tiers.
- [ ] Run the Phase 4 test suite and static analysis.

## Definition of Done

Phase 4 is complete when:

1. An active theater can manage its profile and halls.
2. Weekly schedules generate conflict-free materialized slots.
3. Theater blocks and event shows remove slots from availability.
4. Events, shows, and ticket tiers are managed with translations and media.
5. Public catalog endpoints expose only published public data.
6. Tenant isolation and permissions are enforced on every endpoint.
7. Phase 4 test cases pass.
