# Phase 4 Test Cases

Test specification for theater profile, halls, schedules, slots, events, shows, and ticket tiers.

**Status legend:** `Ready` implemented, `Gap` incorrect, `Planned` not implemented.

## 1. Theater Profile

| ID | Test case | Expected result | Status |
|---|---|---|---|
| THP-01 | View own theater profile | `200`; private workspace fields | Ready |
| THP-02 | Update own profile | `200`; changes persisted | Ready |
| THP-03 | Update another theater's profile | `404` | Ready |
| THP-04 | Member without profile-update permission | `403` | Ready |
| THP-05 | Public profile excludes private data | No team, notes, or documents | Ready |

## 2. Halls

| ID | Test case | Expected result | Status |
|---|---|---|---|
| HALL-01 | Create a valid hall | `200`; hall belongs to own tenant | Ready |
| HALL-02 | Create with negative capacity | `422` | Ready |
| HALL-03 | Create duplicate name in same tenant | `422` | Ready |
| HALL-04 | View another tenant's hall | `404` | Ready |
| HALL-05 | Update another tenant's hall | `404` | Ready |
| HALL-06 | Deactivate a hall with future reservations | Conflict or controlled cancellation | Ready |
| HALL-07 | Member without hall permission | `403` | Ready |

## 3. Schedules and Slots

| ID | Test case | Expected result | Status |
|---|---|---|---|
| SCH-01 | Create a valid weekly schedule | Slots can be generated | Ready |
| SCH-02 | End time before start time | `422` | Ready |
| SCH-03 | Generate slots twice | No duplicate slots | Ready |
| SCH-04 | Block a maintenance period | Overlapping slots become blocked/unavailable | Ready |
| SCH-05 | Generate over a held slot | Held slot is preserved | Ready |
| SCH-06 | Request a past slot | Rejected | Ready |
| SCH-07 | Unique hall/start slot constraint | Duplicate insert fails | Ready |

## 4. Events and Shows

| ID | Test case | Expected result | Status |
|---|---|---|---|
| EVT-01 | Create a draft event | Visible only in theater workspace | Ready |
| EVT-02 | Publish an event | Publicly discoverable | Ready |
| EVT-03 | Unpublish an event | Removed from public catalog | Ready |
| EVT-04 | Create a show in own hall | Show saved | Ready |
| EVT-05 | Create a show in another tenant's hall | `404`/`422` | Ready |
| EVT-06 | Overlapping shows in one hall | `422`; second show rejected | Ready |
| EVT-07 | Show overlaps a confirmed reservation | `422`; no double occupancy | Ready |
| EVT-08 | Cancel an event | Removed from public catalog; history retained | Ready |
| EVT-09 | Arabic and English translation | Both locales return correct content | Ready |

## 5. Ticket Tiers

| ID | Test case | Expected result | Status |
|---|---|---|---|
| TIR-01 | Create a valid tier | Tier belongs to own show | Ready |
| TIR-02 | Tier totals exceed hall capacity | `422` | Ready |
| TIR-03 | Negative price or quantity | `422` | Ready |
| TIR-04 | Delete a tier with bookings | `422`/conflict; deactivate instead | Ready |
| TIR-05 | View another tenant's tier | `404` | Ready |
| TIR-06 | Sale window boundaries | Outside window rejected | Ready |

## 6. Public Catalog

| ID | Test case | Expected result | Status |
|---|---|---|---|
| PUB-01 | List published events | Only published, non-cancelled events | Ready |
| PUB-02 | Request a draft event | `404` | Ready |
| PUB-03 | Public availability | Only requestable slots | Ready |
| PUB-04 | Public theater page | No private data | Ready |
| PUB-05 | Empty catalog | `200` with empty paginated result | Ready |
