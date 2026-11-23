# Phase 6 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Public Event Discovery

- [x] Reuse the Phase 4 published-only event list and detail endpoints.
- [x] Add search by event/theater name and filters for theater, date, and category.
- [x] Paginate all list endpoints.
- [x] Return only published, non-cancelled events and scheduled shows.
- [x] Expose active tiers and upcoming shows publicly.
- [x] Add public visibility and empty-result tests.

## 2. Ticket Inventory

- [x] Use tier total, sold, and held quantities with sale windows.
- [x] Add database checks for non-negative counters and the total invariant.
- [x] Add indexes for show/tier availability.
- [x] Enforce tier totals against hall capacity.
- [x] Expose available quantity as `total - sold - held`.
- [x] Add availability and sold-out tests.

## 3. Booking Creation

- [x] Add booking, booking-item, ticket, and status-history tables with price snapshots.
- [x] Add a unique booking reference.
- [x] Add a unique user/idempotency key with request hashing.
- [x] Validate user, event, show, tier, quantity, sale window, and per-booking limit.
- [x] Lock tier rows in deterministic order inside a transaction.
- [x] Recheck inventory after locking.
- [x] Increment sold quantity atomically.
- [x] Create booking, items, and tickets or roll back all tiers on failure.
- [x] Add multi-tier rollback and insufficient-inventory tests.
- [~] Add a true parallel last-ticket test. Row locking and a sequential invariant test cover the behavior; the suite does not spawn parallel PHP processes.

## 4. Tickets and Confirmation

- [x] Generate a booking confirmation with reference and ticket information.
- [x] Add per-ticket records.
- [x] Add unique ticket numbers and opaque QR tokens.
- [x] Make ticket generation idempotent through booking idempotency and unique constraints.
- [x] Add QR validation and used-status rules.
- [x] Ensure no personal or tenant secrets are encoded in QR payloads.
- [x] Add ticket-generation and validation tests.

## 5. Cancellation

- [x] Define the individual cutoff as 24 hours before show start.
- [x] Allow individuals to cancel own bookings only.
- [x] Allow theaters to cancel own bookings before show start.
- [x] Lock booking and tiers during cancellation.
- [x] Restore inventory only when cancellation succeeds and no ticket is used.
- [x] Mark booking and tickets cancelled.
- [x] Prevent counters from going negative with database checks.
- [x] Add cancellation and audit tests.

## 6. Notifications and Documentation

- [x] Notify the individual after booking confirmation and cancellation.
- [x] Notify the theater after a new booking and cancellation.
- [x] Queue notifications after commit.
- [x] Enable notification delivery for individual users.
- [x] Document all Phase 6 endpoints.
- [x] Add Phase 6 cases to the Postman collection.
- [x] Add feature tests for discovery, booking, idempotency, cancellation, and isolation.
- [x] Run the Phase 6 test suite and static analysis.

## Definition of Done

Phase 6 is complete when:

1. Individuals can discover published events and available shows/tiers.
2. Booking validates availability and reserves inventory atomically.
3. Concurrent bookings cannot oversell inventory.
4. Retries with the same idempotency key never create duplicate bookings.
5. Booking confirmation and ticket data are returned safely.
6. Cancellation follows policy and keeps counters consistent.
7. Phase 6 test cases pass.
