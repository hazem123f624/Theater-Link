# Phase 5 Test Cases

Test specification for group management, theater discovery, hall reservations, and the reservation lifecycle.

**Status legend:** `Ready` implemented, `Gap` incorrect, `Planned` not implemented.

## 1. Group Profile and Discovery

| ID | Test case | Expected result | Status |
|---|---|---|---|
| GRP-01 | Approved group views own profile | `200`; group profile fields | Planned |
| GRP-02 | Group updates own profile | `200`; changes persisted | Planned |
| GRP-03 | Group views another group's private data | `404` | Planned |
| GRP-04 | Group browses active theaters | Only active theaters | Planned |
| GRP-05 | Group views public theater profile | No private theater data | Planned |
| GRP-06 | Group views hall availability | Only requestable slots | Planned |
| GRP-07 | Pending group browses theaters | `403`; workspace not active | Planned |

## 2. Reservation Request

| ID | Test case | Expected result | Status |
|---|---|---|---|
| RES-01 | Request an available slot | `201`/`200`; pending reservation; slot held; expiry set | Planned |
| RES-02 | Request a blocked slot | `422`/`409`; no reservation | Planned |
| RES-03 | Request a held slot | `422`/`409`; no second hold | Planned |
| RES-04 | Request a reserved slot | `422`/`409` | Planned |
| RES-05 | Request a past slot | `422` | Planned |
| RES-06 | Request a slot occupied by an event show | `422`/`409` | Planned |
| RES-07 | Two groups request the same slot concurrently | Exactly one succeeds | Planned |
| RES-08 | Request a slot from a suspended theater | `422`/`403` | Planned |
| RES-09 | Request a hall that belongs to a different theater | `404`/`422` | Planned |
| RES-10 | Price snapshot | Request price remains after theater price change | Planned |
| RES-11 | Duplicate submission | Idempotent or rejected; no duplicate hold | Planned |

## 3. Theater Decisions

| ID | Test case | Expected result | Status |
|---|---|---|---|
| DEC-01 | Theater lists incoming requests | Only requests for own halls | Planned |
| DEC-02 | Theater views request details | Group public profile and request data only | Planned |
| DEC-03 | Theater approves a valid pending hold | Approved; slot reserved | Planned |
| DEC-04 | Theater approves after expiry | Fails; slot released; group notified | Planned |
| DEC-05 | Theater approves twice | Second approval is idempotent or rejected | Planned |
| DEC-06 | Theater rejects with a reason | Rejected; slot available; group notified | Planned |
| DEC-07 | Theater rejects without a reason | `422` | Planned |
| DEC-08 | Theater approves another theater's request | `404` | Planned |
| DEC-09 | Group approves its own request | `403` | Planned |

## 4. Lifecycle

| ID | Test case | Expected result | Status |
|---|---|---|---|
| LIFE-01 | Pending hold expires | Status expired; slot available; notifications sent | Planned |
| LIFE-02 | Group cancels pending request | Cancelled; slot available | Planned |
| LIFE-03 | Group cancels approved reservation before start | Cancelled; slot released per policy | Planned |
| LIFE-04 | Cancellation after start | Rejected or no inventory release per policy | Planned |
| LIFE-05 | Approved reservation end passes | Status completed | Planned |
| LIFE-06 | Expiry job runs twice | No double release or duplicate history | Planned |
| LIFE-07 | Event show is created over a held slot | Hold is invalidated and group notified | Planned |

## 5. Security and Integrity

| ID | Test case | Expected result | Status |
|---|---|---|---|
| RSEC-01 | Group reads another group's reservation | `404` | Planned |
| RSEC-02 | Theater reads a non-owned reservation | `404` | Planned |
| RSEC-03 | Client sends a fake slot status or price | Server ignores; uses database values | Planned |
| RSEC-04 | Concurrent approve and expire | One wins; state remains consistent | Planned |
| RSEC-05 | Activity log for every transition | Actor, tenant, status, reason recorded | Planned |
