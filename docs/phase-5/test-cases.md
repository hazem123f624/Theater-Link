# Phase 5 Test Cases

Test specification for group management, theater discovery, hall reservations, and the reservation lifecycle.

**Status legend:** `Ready` implemented, `Gap` incorrect, `Planned` not implemented.

Automated coverage lives in `tests/Feature/Phase5ReservationTest.php` (20 tests, 90 assertions). Cases marked `Ready` are implemented; cases whose automation is indirect are noted below the tables.

## 1. Group Profile and Discovery

| ID | Test case | Expected result | Status |
|---|---|---|---|
| GRP-01 | Approved group views own profile | `200`; group profile fields | Ready |
| GRP-02 | Group updates own profile | `200`; changes persisted | Ready |
| GRP-03 | Group views another group's private data | `404` | Ready |
| GRP-04 | Group browses active theaters | Only active theaters | Ready |
| GRP-05 | Group views public theater profile | No private theater data | Ready |
| GRP-06 | Group views hall availability | Only requestable slots | Ready |
| GRP-07 | Pending group browses theaters | `403`; workspace not active | Ready |

## 2. Reservation Request

| ID | Test case | Expected result | Status |
|---|---|---|---|
| RES-01 | Request an available slot | `201`; pending reservation; slot held; expiry set | Ready |
| RES-02 | Request a blocked slot | `422`; no reservation | Ready |
| RES-03 | Request a held slot | `422`; no second hold | Ready |
| RES-04 | Request a reserved slot | `422` | Ready |
| RES-05 | Request a past slot | `422` | Ready |
| RES-06 | Request a slot occupied by an event show | `422` | Ready |
| RES-07 | Two groups request the same slot concurrently | Exactly one succeeds | Ready |
| RES-08 | Request a slot from a suspended theater | `422`; no reservation | Ready |
| RES-09 | Request a hall that belongs to a different theater | `404`/`422` | Ready |
| RES-10 | Price snapshot | Request price remains after theater price change | Ready |
| RES-11 | Duplicate submission | Returns the existing reservation; no duplicate hold | Ready |
| RES-12 | Request a slot in an inactive hall | `422`; no reservation | Ready |

## 3. Theater Decisions

| ID | Test case | Expected result | Status |
|---|---|---|---|
| DEC-01 | Theater lists incoming requests | Only requests for own halls | Ready |
| DEC-02 | Theater views request details | Group public profile and request data only | Ready |
| DEC-03 | Theater approves a valid pending hold | Approved; slot reserved | Ready |
| DEC-04 | Theater approves after expiry | `422`; slot released; reservation expired | Ready |
| DEC-05 | Theater approves twice | Second approval is idempotent | Ready |
| DEC-06 | Theater rejects with a reason | Rejected; slot available; group notified | Ready |
| DEC-07 | Theater rejects without a reason | `422` | Ready |
| DEC-08 | Theater approves another theater's request | `404` | Ready |
| DEC-09 | Group approves its own request | `403` | Ready |
| DEC-10 | Theater approves after the slot start | `422`; reservation remains unapproved | Ready |

## 4. Lifecycle

| ID | Test case | Expected result | Status |
|---|---|---|---|
| LIFE-01 | Pending hold expires | Status expired; slot available; notifications sent | Ready |
| LIFE-02 | Group cancels pending request | Cancelled; slot available | Ready |
| LIFE-03 | Group cancels approved reservation before start | Cancelled; slot released | Ready |
| LIFE-04 | Group cancellation inside the 24-hour cutoff | `422`; reservation unchanged | Ready |
| LIFE-05 | Approved reservation end passes | Status completed; slot completed | Ready |
| LIFE-06 | Expiry job runs twice | No double release or duplicate history | Ready |
| LIFE-07 | Event show is created over a held slot | Hold expired; slot released to show scheduling; group notified | Ready |
| LIFE-08 | Theater cancels an approved reservation before start | Cancelled; slot available | Ready |
| LIFE-09 | Theater cancels after the reservation starts | `422`; reservation unchanged | Ready |
| LIFE-10 | Show creation fails after hold invalidation | Transaction rolls back; hold remains pending | Ready |

## 5. Security and Integrity

| ID | Test case | Expected result | Status |
|---|---|---|---|
| RSEC-01 | Group reads another group's reservation | `404` | Ready |
| RSEC-02 | Group cancels another group's reservation | `404` | Ready |
| RSEC-03 | Theater reads a non-owned reservation | `404` | Ready |
| RSEC-04 | Client sends a fake slot status or price | Server ignores; uses database values | Ready |
| RSEC-05 | Concurrent approve and expiry | Row locks keep a single consistent transition | Ready |
| RSEC-06 | Activity log for every transition | Actor, tenant, status, reason recorded | Ready |

## Automation Notes

- `RSEC-04` and `RES-10` are covered by server-side snapshots: the request endpoint accepts only `hall_time_slot_id` and `idempotency_key`, and the price is copied from the locked slot row.
- `RES-07` and `RSEC-05` are protected by `lockForUpdate()` transactions and covered by a sequential held-slot exclusivity test. The suite does not spawn parallel PHP processes.
- `LIFE-06` calls the expiry service twice and asserts the second run changes nothing.
- Notification delivery is covered indirectly by notification-service tests; Phase 5 asserts the business transitions that enqueue them.
