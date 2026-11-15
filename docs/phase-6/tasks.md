# Phase 6 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Public Event Discovery

- [ ] Add published-only event list endpoints.
- [ ] Add search by event/theater name.
- [ ] Add filters for theater, date, and category.
- [ ] Paginate all list endpoints.
- [ ] Add event detail with translated content and poster.
- [ ] Add upcoming shows and active ticket tiers.
- [ ] Return only published, non-cancelled events/shows.
- [ ] Add public visibility and empty-result tests.

## 2. Ticket Inventory

- [ ] Create ticket tiers with total quantity, sold quantity, held quantity, and sale window.
- [ ] Add database checks for non-negative counters and the total invariant.
- [ ] Add indexes for show/tier availability.
- [ ] Ensure tier totals do not exceed show/hall capacity.
- [ ] Expose available quantity as `total - sold - held`.
- [ ] Add availability tests.

## 3. Booking Creation

- [ ] Add booking and booking-item tables with price snapshots.
- [ ] Add a unique booking reference.
- [ ] Add an idempotency key with a unique user/key constraint.
- [ ] Validate user, event, show, tier, quantity, sale window, and per-booking limit.
- [ ] Lock tier rows in deterministic order inside a transaction.
- [ ] Recheck inventory after locking.
- [ ] Increment sold quantity atomically.
- [ ] Create booking and items, or roll back all tiers on failure.
- [ ] Add concurrent last-ticket, insufficient-inventory, and multi-tier rollback tests.

## 4. Tickets and Confirmation

- [ ] Generate a booking confirmation with reference and ticket information.
- [ ] Add per-ticket records if entry control is required.
- [ ] Add unique ticket numbers and opaque QR tokens.
- [ ] Make ticket generation idempotent.
- [ ] Add QR validation and used-status rules.
- [ ] Ensure no personal or tenant secrets are encoded in QR payloads.
- [ ] Add duplicate ticket-generation tests.

## 5. Cancellation

- [ ] Define the cancellation policy and cutoff.
- [ ] Allow an individual to cancel own bookings only.
- [ ] Lock booking and tiers during cancellation.
- [ ] Restore inventory only when policy allows.
- [ ] Mark booking and tickets cancelled.
- [ ] Prevent counters from going negative.
- [ ] Add cancellation and audit tests.

## 6. Notifications and Documentation

- [ ] Notify the individual after booking confirmation.
- [ ] Notify the individual after cancellation.
- [ ] Queue notifications after commit.
- [ ] Document all Phase 6 endpoints.
- [ ] Add Phase 6 cases to the Postman collection.
- [ ] Add feature tests for discovery, booking, concurrency, cancellation, and IDOR.
- [ ] Run the Phase 6 test suite and static analysis.

## Definition of Done

Phase 6 is complete when:

1. Individuals can discover published events and available shows/tiers.
2. Booking validates availability and reserves inventory atomically.
3. Concurrent bookings cannot oversell inventory.
4. Retries with the same idempotency key never create duplicate bookings.
5. Booking confirmation and ticket data are returned safely.
6. Cancellation follows policy and keeps counters consistent.
7. Phase 6 test cases pass.
