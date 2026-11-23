# Phase 5 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Group Profile and Team

- [x] Create the group profile record linked one-to-one with the group tenant.
- [x] Add group profile read/update endpoints.
- [x] Enforce `group.profile.read` and `group.profile.update`.
- [x] Reuse the shared tenant team and role endpoints.
- [x] Protect the last group owner.
- [x] Add group profile and team authorization tests.

## 2. Theater Discovery

- [x] List active theaters with public fields only.
- [x] Show public theater profile and active halls.
- [x] Show requestable slot availability for a date range.
- [x] Exclude blocked, held, reserved, past, and event-occupied slots.
- [x] Never expose theater team, private documents, notes, or other reservations.
- [x] Add pagination and public visibility tests.

## 3. Reservation Schema and State Machine

- [x] Create reservations with theater tenant, group tenant, hall, slot, requester, and price snapshot.
- [x] Create reservation status: pending, approved, rejected, cancelled, expired, completed.
- [x] Create status history records.
- [x] Add indexes for theater tenant/status, group tenant/status, and slot.
- [x] Enforce price and time invariants.
- [x] Add a policy for group create/read/cancel and theater approve/reject.
- [x] Add cross-tenant access tests.

## 4. Request and Slot Hold

- [x] Add group reservation request endpoint.
- [x] Verify the group tenant is active and approved.
- [x] Verify the theater tenant is active.
- [x] Verify the hall and slot belong to the selected theater.
- [x] Open a transaction and lock the slot row.
- [x] Reject unavailable, held, reserved, blocked, past, or event-occupied slots.
- [x] Set slot held and store `hold_expires_at`.
- [x] Create the pending reservation and price snapshot.
- [x] Add duplicate-request protection with a per-group idempotency key.
- [~] Add a true parallel concurrency test where only one request succeeds. Row locking and a held-slot exclusivity test cover the behavior, but the suite does not launch parallel processes.

## 5. Theater Decisions

- [x] List only requests addressed to the current theater.
- [x] Show the group's public profile and contact details.
- [x] Approve a pending hold before expiry.
- [x] Revalidate slot ownership, hold, expiry, and event occupancy on approval.
- [x] Set reservation approved and slot reserved.
- [x] Reject with a reason and release the slot.
- [x] Prevent double approval and cross-theater approval.
- [x] Add state-transition tests.

## 6. Expiry, Cancellation and Completion

- [x] Add a job to expire pending holds after `hold_expires_at`.
- [x] Expire idempotently; release the slot; write history; notify.
- [x] Cancel pending requests and release the slot.
- [x] Cancel approved requests according to policy and release future slots.
- [x] Mark approved reservations completed after their end time.
- [x] Add `reservations:expire-holds` and `reservations:complete` schedules with `withoutOverlapping()`.
- [x] Add expiry/cancellation/completion tests.

## 7. Notifications and Documentation

- [x] Notify theater approvers when a request arrives.
- [x] Notify groups when a request is approved, rejected, expired, or cancelled.
- [x] Send queued channel notifications after commit.
- [x] Document all Phase 5 endpoints.
- [x] Add Phase 5 cases to the Postman collection.
- [x] Add feature tests for discovery, request, approval, rejection, expiry, cancellation, and isolation.
- [x] Run the Phase 5 test suite and static analysis.

## Definition of Done

Phase 5 is complete when:

1. An approved group can discover theaters and request an available slot.
2. A pending request exclusively holds the slot until the configured expiry.
3. Only the owning theater can approve or reject a request.
4. Approval revalidates availability under a database lock.
5. Rejection, cancellation, and expiry release the slot safely.
6. Confirmed reservations never overlap.
7. Both sides receive notifications.
8. Phase 5 test cases pass.
