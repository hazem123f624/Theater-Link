# Phase 6 Test Cases

Test specification for individual event discovery, ticket inventory, booking, tickets, QR validation, and cancellation.

**Status legend:** `Ready` implemented, `Gap` incorrect, `Planned` not implemented.

Automated coverage lives in `tests/Feature/Phase6BookingTest.php` (17 tests, 78 assertions). Cases marked `Ready` are implemented; indirect concurrency coverage is noted below.

## 1. Event and Availability

| ID | Test case | Expected result | Status |
|---|---|---|---|
| EVT-01 | List published events | Only published, non-cancelled events | Ready |
| EVT-02 | Search by event name | Matching events only | Ready |
| EVT-03 | Filter by theater/date/category | Correct subset | Ready |
| EVT-04 | Request a draft/cancelled event or show | `404` | Ready |
| EVT-05 | Event details | Shows, tiers, theater, and hall public data | Ready |
| EVT-06 | Empty catalog | `200` with empty pagination | Ready |
| AVL-01 | Available tier | Correct total, available quantity, price, and sale state | Ready |
| AVL-02 | Sold-out tier | `can_book=false`; booking fails | Ready |
| AVL-03 | Outside sale window | `can_book=false`; booking fails | Ready |
| AVL-04 | Inactive tier | Not bookable | Ready |
| AVL-05 | Tier belongs to another show | `422` | Ready |
| AVL-06 | Tier totals exceed capacity | Rejected at configuration time | Ready |

## 2. Booking

| ID | Test case | Expected result | Status |
|---|---|---|---|
| BOOK-01 | Book available tickets | Confirmed booking; inventory decremented; tickets issued | Ready |
| BOOK-02 | Request more than available | `422`; no inventory change | Ready |
| BOOK-03 | Book the last tickets | Inventory never goes below zero | Ready |
| BOOK-04 | Multi-tier booking with one unavailable tier | Entire booking rolls back | Ready |
| BOOK-05 | Quantity above per-booking limit | `422` | Ready |
| BOOK-06 | Booking outside sale window | `422` | Ready |
| BOOK-07 | Price snapshot | Booking total uses server price at booking time | Ready |
| BOOK-08 | Client supplies a fake price | Server ignores it | Ready |
| BOOK-09 | Mixed tier currencies | `422` | Ready |
| BOOK-10 | Draft event show | `404`; no booking | Ready |

## 3. Idempotency and Tickets

| ID | Test case | Expected result | Status |
|---|---|---|---|
| IDM-01 | Repeat booking with same key and payload | Existing booking returned; no duplicate | Ready |
| IDM-02 | Same key with different payload | `422`; no second booking | Ready |
| IDM-03 | Generate tickets for a booking | One ticket per unit | Ready |
| IDM-04 | Ticket number and QR uniqueness | Unique constraints pass | Ready |
| IDM-05 | Ticket validation | Valid ticket accepted once and marked used | Ready |
| IDM-06 | Second validation | `422`; already used | Ready |
| IDM-07 | Validate another theater's ticket | `404` | Ready |
| IDM-08 | Expire unused tickets after show end | Tickets become `expired` | Ready |

## 4. Cancellation

| ID | Test case | Expected result | Status |
|---|---|---|---|
| CAN-01 | Cancel own confirmed booking before cutoff | Cancelled; inventory restored; tickets cancelled | Ready |
| CAN-02 | Cancel another user's booking | `404` | Ready |
| CAN-03 | Cancel inside cutoff | `422`; inventory unchanged | Ready |
| CAN-04 | Cancel twice | Idempotent cancellation response | Ready |
| CAN-05 | Theater cancels before show start | Cancelled; inventory restored; user notified | Ready |
| CAN-06 | Theater cancels another theater's booking | `404` | Ready |
| CAN-07 | Used ticket blocks cancellation | `422`; inventory unchanged | Ready |
| CAN-08 | Cancellation audit | Actor, status, reason, and booking recorded | Ready |

## 5. Security and Isolation

| ID | Test case | Expected result | Status |
|---|---|---|---|
| SEC-01 | Individual reads another user's booking | `404` | Ready |
| SEC-02 | Individual cancels another user's booking | `404` | Ready |
| SEC-03 | Theater token accesses individual booking APIs | `401`/`403` | Ready |
| SEC-04 | Individual token accesses theater booking management | `401`/`403` | Ready |
| SEC-05 | Negative or oversized quantity | `422` | Ready |
| SEC-06 | Repeated booking attempts | Rate-limited by `throttle:10,1` | Ready |

## 6. Integration

| ID | Test case | Expected result | Status |
|---|---|---|---|
| INT-01 | Show cancellation cancels bookings and tickets | Statuses updated; users notified | Ready |
| INT-02 | Unpublish event with active bookings | `422`; event remains published | Ready |
| INT-03 | User dashboard booking counters | Real total and upcoming bookings | Ready |
| INT-04 | Theater dashboard counters | Real total and upcoming bookings | Ready |
| INT-05 | Admin dashboard booking counters | Real total bookings and reservations | Ready |
| INT-06 | Public tier refresh endpoint | Sale state and availability match database | Ready |

## Automation Notes

- `BOOK-03` and concurrent inventory safety are protected by deterministic tier-ID locks, transaction rollback, unique constraints, and database check constraints. The suite tests inventory invariants sequentially rather than launching parallel PHP processes.
- `CAN-04` returns the existing cancelled booking for an idempotent second cancellation.
- Notifications are created in the database and queued channel delivery is covered by the shared notification infrastructure; Phase 6 asserts booking transitions and real dashboard counters.
