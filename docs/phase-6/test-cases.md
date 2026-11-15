# Phase 6 Test Cases

Test specification for individual event discovery, ticket inventory, booking, tickets, and cancellation.

**Status legend:** `Ready` implemented, `Gap` incorrect, `Planned` not implemented.

## 1. Event Discovery

| ID | Test case | Expected result | Status |
|---|---|---|---|
| EVT-01 | List published events | Only published, non-cancelled events | Planned |
| EVT-02 | Search by event name | Matching events only | Planned |
| EVT-03 | Filter by theater/date/category | Correct subset | Planned |
| EVT-04 | Request a draft/cancelled event | `404` | Planned |
| EVT-05 | Event details | Shows, tiers, theater, and hall public data | Planned |
| EVT-06 | Empty catalog | `200` with empty pagination | Planned |
| EVT-07 | Pagination bounds | Invalid page size rejected | Planned |

## 2. Availability

| ID | Test case | Expected result | Status |
|---|---|---|---|
| AVL-01 | Available tier | Shows correct total/available/price | Planned |
| AVL-02 | Sold-out tier | No availability; booking fails | Planned |
| AVL-03 | Outside sale window | Not bookable | Planned |
| AVL-04 | Inactive tier | Not bookable | Planned |
| AVL-05 | Tier belongs to another show | `404`/`422` | Planned |
| AVL-06 | Sum of tier totals exceeds capacity | Rejected at configuration time | Planned |

## 3. Booking

| ID | Test case | Expected result | Status |
|---|---|---|---|
| BOOK-01 | Book available tickets | Confirmed booking; inventory decremented | Planned |
| BOOK-02 | Request more than available | `422`; no inventory change | Planned |
| BOOK-03 | Two users book the last ticket concurrently | Exactly one succeeds | Planned |
| BOOK-04 | Multiple concurrent bookings | Total sold never exceeds total | Planned |
| BOOK-05 | Multi-tier booking with one unavailable tier | Entire booking rolls back | Planned |
| BOOK-06 | Quantity above per-booking limit | `422` | Planned |
| BOOK-07 | Booking outside sale window | `422` | Planned |
| BOOK-08 | Price snapshot | Booking total uses server price at booking time | Planned |
| BOOK-09 | Client supplies a fake price | Server ignores it | Planned |

## 4. Idempotency and Tickets

| ID | Test case | Expected result | Status |
|---|---|---|---|
| IDM-01 | Repeat booking with same idempotency key | Existing booking returned; no duplicate | Planned |
| IDM-02 | Simultaneous duplicate requests | Unique constraint resolves safely | Planned |
| IDM-03 | Generate tickets for a booking | One ticket per unit | Planned |
| IDM-04 | Retry ticket generation | No duplicate tickets | Planned |
| IDM-05 | QR token uniqueness | Duplicate QR insert fails | Planned |
| IDM-06 | QR validation | Valid ticket accepted once; used/cancelled rejected | Planned |

## 5. Cancellation

| ID | Test case | Expected result | Status |
|---|---|---|---|
| CAN-01 | Cancel own confirmed booking before cutoff | Cancelled; inventory restored per policy | Planned |
| CAN-02 | Cancel another user's booking | `404`/`403` | Planned |
| CAN-03 | Cancel after cutoff/start | `422`; inventory unchanged | Planned |
| CAN-04 | Cancel twice | Idempotent or second rejection; no double restore | Planned |
| CAN-05 | Counters never negative | Check constraint/service guard passes | Planned |
| CAN-06 | Cancellation audit | Actor, booking, reason, old/new status recorded | Planned |

## 6. Security and Isolation

| ID | Test case | Expected result | Status |
|---|---|---|---|
| SEC-01 | Individual reads another user's booking | `404` | Planned |
| SEC-02 | Tenant token books as an individual | `401`/`403` | Planned |
| SEC-03 | Individual token accesses tenant booking management | `401`/`403` | Planned |
| SEC-04 | Oversized/negative quantity | `422` | Planned |
| SEC-05 | Rate-limited repeated booking attempts | `429` | Planned |
