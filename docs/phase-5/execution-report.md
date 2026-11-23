# Phase 5 Execution Report

**Date:** 2026-11-19

**Result:** Phase 5 backend and frontend complete and verified.

## Delivered

### Group profile and discovery
- Group public profile with name, description, contact person, email, phone, active state, and logo upload.
- Profile read/update permissions scoped to the group tenant.
- Active-theater discovery with public profile fields and active hall counts.
- Theater detail with active halls and requestable slot availability.
- Pending or suspended groups cannot use discovery or reservation endpoints.

### Reservation schema and holds
- `reservations` table with theater tenant, group tenant, hall, slot, requester, status, price/currency snapshot, lifecycle timestamps, rejection/cancellation reasons, and idempotency key.
- `reservation_status_histories` table for every transition.
- `hall_time_slots.reservation_id` link and `held` / `reserved` / `completed` slot states.
- Indexes for theater/status, group/status, slot/status, expiry scans, and group idempotency.
- Transactional request flow with a row lock on the slot.
- Pending hold window of 48 hours by default.
- Duplicate requests with the same group idempotency key return the existing reservation.
- Server-authoritative price and currency snapshot; client-supplied prices are ignored.

### Theater decisions
- Tenant-scoped inbox and detail views with group public contact information.
- Approve under a row lock after revalidating hold expiry, slot ownership, slot state, shows, and blocks.
- Reject with a required reason and release the slot.
- Cross-theater access returns `404`; a group cannot act as the theater.
- Approval is idempotent; approving an expired hold expires the reservation and releases the slot.

### Lifecycle
- Group cancellation for pending requests and approved reservations outside the 24-hour cutoff.
- Theater cancellation before the reservation starts.
- Automatic expiry of pending holds with idempotent slot release.
- Automatic completion after the slot ends.
- Show scheduling over a pending hold expires the hold and releases the slot before show occupancy is validated.
- Approved reservations block overlapping show creation through the existing show conflict checks.
- Activity log and status history for every transition.
- In-app notifications for theaters and groups; queued email/SMS dispatch is registered after commit.

### Scheduling
- `reservations:expire-holds` every five minutes with `withoutOverlapping()`.
- `reservations:complete` every ten minutes with `withoutOverlapping()`.

### Frontend
- Group profile editor with logo upload.
- Theater discovery list, theater/hall detail, date-range availability, and slot request modal.
- Group reservation list with search/status filters, detail page, history timeline, and cancellation.
- Theater reservation inbox with search/status filters, detail page, approve/reject/cancel dialogs.
- Group "Discover"/"Reservations" and Theater "Reservations" navigation.
- Real reservation counters and dashboard quick actions.
- English/Arabic labels and RTL support.

### Documentation and tooling
- 15 Phase 5 endpoint methods added to the custom `/docs` seeder.
- Phase 5 Postman folder with 15 requests, reservation/idempotency variables, and reservation-ID capture.
- Phase 5 README, tasks, test cases, and this report.
- Root README, docs index, frontend guide, and `.env.example` updated.

## Verification

```text
php artisan test
Tests: 71 passed (371 assertions)
```

```text
vendor/bin/phpstan analyse Modules/Reservations Modules/Theaters Modules/Tenancy app/Services/Notifications --memory-limit=1G
[OK] No errors
```

```text
npm run typecheck
TypeScript: clean

npm run test
Test Files: 4 passed
Tests: 7 passed

npm run build
Production build: successful
```

```text
npm run smoke
✓ theater registration and dashboard
✓ theater team page
✓ tenant role create with permission catalog
✓ invitation create and acceptance
✓ theater hall and weekly schedule
✓ event, show, ticket tier, publish, and public catalog
✓ admin login and dashboard
✓ tenant password reset and re-login
✓ group registration, pending application, dashboard guard
✓ admin document review, group resubmission, approval, and notification
✓ group discovers theater, hall, slot, and requests a reservation
✓ theater approves reservation and reserves the slot
✓ group sees the approved reservation
✓ individual registration and dashboard
✓ Arabic RTL switch
```

```text
GET /docs
200 OK; 185 documented endpoint methods
Postman collection: 118 requests
```

## Test Coverage

`Phase5ReservationTest` includes 20 tests / 90 assertions covering:

- Group profile read/update.
- Theater discovery, halls, and availability.
- Pending slot request and held-slot state.
- Idempotent duplicate requests.
- Blocked, held, expired, inactive-hall, suspended-theater, and past-slot rejection.
- Price snapshot preservation.
- Theater approval and group visibility.
- Approve-after-expiry and approve-after-start handling.
- Rejection reason validation and slot release.
- Group cancellation and 24-hour approved-reservation cutoff.
- Theater cancellation of an approved reservation and rejection after start.
- Hold expiry and completion idempotency.
- Cross-group and cross-theater isolation.
- Show scheduling over a held slot and rollback of hold invalidation when show creation fails.
- Pending group request rejection.

## Known Limitations

- The suite validates held-slot exclusivity sequentially; it does not spawn parallel PHP processes for a true race. Row locking provides the production guarantee.
- Payment and refund handling remain out of scope.
- Ticket booking, inventory consumption, QR tickets, and public booking flows belong to Phase 6.
