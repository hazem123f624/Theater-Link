# Phase 5 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Group Profile and Team

- [ ] Create the group profile record linked one-to-one with the group tenant.
- [ ] Add group profile read/update endpoints.
- [ ] Enforce `group.profile.read` and `group.profile.update`.
- [ ] Reuse the shared tenant team and role endpoints.
- [ ] Protect the last group owner.
- [ ] Add group profile and team authorization tests.

## 2. Theater Discovery

- [ ] List active theaters with public fields only.
- [ ] Show public theater profile and active halls.
- [ ] Show requestable slot availability for a date range.
- [ ] Exclude blocked, held, reserved, past, and event-occupied slots.
- [ ] Never expose theater team, private documents, notes, or other reservations.
- [ ] Add pagination and public visibility tests.

## 3. Reservation Schema and State Machine

- [ ] Create reservations with theater tenant, group tenant, hall, slot, requester, and price snapshot.
- [ ] Create reservation status: pending, approved, rejected, cancelled, expired, completed.
- [ ] Create status history records.
- [ ] Add indexes for theater tenant/status, group tenant/status, and slot.
- [ ] Enforce price and time invariants.
- [ ] Add a policy for group create/read/cancel and theater approve/reject.
- [ ] Add cross-tenant access tests.

## 4. Request and Slot Hold

- [ ] Add group reservation request endpoint.
- [ ] Verify the group tenant is active and approved.
- [ ] Verify the theater tenant is active.
- [ ] Verify the hall and slot belong to the selected theater.
- [ ] Open a transaction and lock the slot row.
- [ ] Reject unavailable, held, reserved, blocked, past, or event-occupied slots.
- [ ] Set slot held and store `hold_expires_at`.
- [ ] Create the pending reservation and price snapshot.
- [ ] Add duplicate-request protection.
- [ ] Add a concurrent request test where only one succeeds.

## 5. Theater Decisions

- [ ] List only requests addressed to the current theater.
- [ ] Show the group's public profile and contact details.
- [ ] Approve a pending hold before expiry.
- [ ] Revalidate slot ownership, hold, expiry, and event occupancy on approval.
- [ ] Set reservation approved and slot reserved.
- [ ] Reject with a reason and release the slot.
- [ ] Prevent double approval and cross-theater approval.
- [ ] Add state-transition tests.

## 6. Expiry, Cancellation and Completion

- [ ] Add a job to expire pending holds after `hold_expires_at`.
- [ ] Expire idempotently; release the slot; write history; notify.
- [ ] Cancel pending requests and release the slot.
- [ ] Cancel approved requests according to policy and release future slots.
- [ ] Mark approved reservations completed after their end time.
- [ ] Add `reservations:expire-holds` and `reservations:complete` schedules with `withoutOverlapping()`.
- [ ] Add expiry/cancellation/completion tests.

## 7. Notifications and Documentation

- [ ] Notify theater approvers when a request arrives.
- [ ] Notify groups when a request is approved, rejected, expired, or cancelled.
- [ ] Send notifications after commit.
- [ ] Document all Phase 5 endpoints.
- [ ] Add Phase 5 cases to the Postman collection.
- [ ] Add feature tests for discovery, request, approval, rejection, expiry, cancellation, and isolation.
- [ ] Run the Phase 5 test suite and static analysis.

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
