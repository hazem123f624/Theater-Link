# Phase 1 Architecture Review Checklist

This is a review checklist for the architecture proposal, not a runtime test suite.

**Status legend:**

- `Pass` — reviewed and accepted.
- `Gap` — needs correction before implementation.
- `Planned` — implementation or verification is still pending.

| ID | Review case | Expected result | Status |
|---|---|---|---|
| ARC-01 | Reference tenancy is inspected from source | Analysis states that the real reference model is shared database with row-level `valuer_id` isolation, not database-per-tenant | Pass |
| ARC-02 | Legacy physical-tenant code is identified | The dead `Tenant` model/commands are documented and excluded from TheaterLink | Pass |
| ARC-03 | Tenant architecture is compared | Shared, separate, and hybrid options are compared across reuse, isolation, security, maintenance, and complexity | Pass |
| ARC-04 | Hybrid architecture is recommended | Shared tenant/member/auth/RBAC foundation with separate theater/group domain modules | Pass |
| ARC-05 | Database isolation is decided | Shared database with mandatory `tenant_id` and no physical DB layer in v1 | Pass |
| ARC-06 | Tenant type is not client-controlled | Type is derived from the authenticated tenant member and enforced by middleware/policies | Pass |
| ARC-07 | Cross-tenant reservations are modeled | Reservation links theater tenant, group tenant, hall, and slot; neither side sees private data from the other | Pass |
| ARC-08 | Hall conflict strategies are compared | Pending-block, non-block, and temporary-lock options are compared | Pass |
| ARC-09 | Reservation strategy is recommended | Temporary hold with expiry, materialized slots, row locks, and approval revalidation | Pass |
| ARC-10 | Ticket inventory strategies are compared | Immediate confirmation versus temporary hold is compared | Pass |
| ARC-11 | Ticket strategy is recommended | Immediate confirmation with atomic decrement; hold-ready schema | Pass |
| ARC-12 | Duplicate-booking protection is defined | Idempotency key plus unique constraint; retries return the existing booking | Pass |
| ARC-13 | Scheduling and background jobs are identified | Slot generation, hold expiry, completion, and notification jobs are documented | Pass |
| ARC-14 | Security and data-integrity requirements are documented | Transactions, locks, constraints, authorized media, and IDOR tests are specified | Pass |
| ARC-15 | Implementation starts only after approval | Phase 1 analysis was completed before Phase 2 code began | Pass |
