# Phase 4 Test Cases

Test specification for theater profile, halls, schedules, slots, events, shows, and ticket tiers.

**Status legend:** `Ready` implemented, `Gap` incorrect, `Planned` not implemented.

## 1. Theater Profile

| ID | Test case | Expected result | Status |
|---|---|---|---|
| THP-01 | View own theater profile | `200`; private workspace fields | Planned |
| THP-02 | Update own profile | `200`; changes persisted | Planned |
| THP-03 | Update another theater's profile | `404` | Planned |
| THP-04 | Member without profile-update permission | `403` | Planned |
| THP-05 | Public profile excludes private data | No team, notes, or documents | Planned |

## 2. Halls

| ID | Test case | Expected result | Status |
|---|---|---|---|
| HALL-01 | Create a valid hall | `200`; hall belongs to own tenant | Planned |
| HALL-02 | Create with negative capacity | `422` | Planned |
| HALL-03 | Create duplicate name in same tenant | `422` | Planned |
| HALL-04 | View another tenant's hall | `404` | Planned |
| HALL-05 | Update another tenant's hall | `404` | Planned |
| HALL-06 | Deactivate a hall with future reservations | Conflict or controlled cancellation | Planned |
| HALL-07 | Member without hall permission | `403` | Planned |

## 3. Schedules and Slots

| ID | Test case | Expected result | Status |
|---|---|---|---|
| SCH-01 | Create a valid weekly schedule | Slots can be generated | Planned |
| SCH-02 | End time before start time | `422` | Planned |
| SCH-03 | Generate slots twice | No duplicate slots | Planned |
| SCH-04 | Block a maintenance period | Overlapping slots become blocked/unavailable | Planned |
| SCH-05 | Generate over a held slot | Held slot is preserved | Planned |
| SCH-06 | Request a past slot | Rejected | Planned |
| SCH-07 | Unique hall/start slot constraint | Duplicate insert fails | Planned |

## 4. Events and Shows

| ID | Test case | Expected result | Status |
|---|---|---|---|
| EVT-01 | Create a draft event | Visible only in theater workspace | Planned |
| EVT-02 | Publish an event | Publicly discoverable | Planned |
| EVT-03 | Unpublish an event | Removed from public catalog | Planned |
| EVT-04 | Create a show in own hall | Show saved | Planned |
| EVT-05 | Create a show in another tenant's hall | `404`/`422` | Planned |
| EVT-06 | Overlapping shows in one hall | `422`; second show rejected | Planned |
| EVT-07 | Show overlaps a confirmed reservation | `422`; no double occupancy | Planned |
| EVT-08 | Cancel an event | Removed from public catalog; history retained | Planned |
| EVT-09 | Arabic and English translation | Both locales return correct content | Planned |

## 5. Ticket Tiers

| ID | Test case | Expected result | Status |
|---|---|---|---|
| TIR-01 | Create a valid tier | Tier belongs to own show | Planned |
| TIR-02 | Tier totals exceed hall capacity | `422` | Planned |
| TIR-03 | Negative price or quantity | `422` | Planned |
| TIR-04 | Delete a tier with bookings | `422`/conflict; deactivate instead | Planned |
| TIR-05 | View another tenant's tier | `404` | Planned |
| TIR-06 | Sale window boundaries | Outside window rejected | Planned |

## 6. Public Catalog

| ID | Test case | Expected result | Status |
|---|---|---|---|
| PUB-01 | List published events | Only published, non-cancelled events | Planned |
| PUB-02 | Request a draft event | `404` | Planned |
| PUB-03 | Public availability | Only requestable slots | Planned |
| PUB-04 | Public theater page | No private data | Planned |
| PUB-05 | Empty catalog | `200` with empty paginated result | Planned |
