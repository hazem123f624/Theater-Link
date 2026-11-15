# Phase 3 Test Cases

Test specification for group application review, documents, approval, rejection, reopening, resubmission, and provisioning.

**Status legend:** `Ready` implemented, `Gap` incorrect, `Planned` not implemented.

## 1. Review List and Detail

| ID | Test case | Expected result | Status |
|---|---|---|---|
| REV-01 | List group applications as a permitted admin | `200`; paginated applications | Planned |
| REV-02 | Search by group name | Matching applications only | Planned |
| REV-03 | Filter by status | Matching status only | Planned |
| REV-04 | Filter by an invalid status | `422`; not an empty list | Planned |
| REV-05 | Open an application | Group info, documents, history, and feedback | Planned |
| REV-06 | Admin without review permission opens the list | `403` | Planned |
| REV-07 | Non-admin token opens the list | `401`/`403` | Planned |

## 2. Documents

| ID | Test case | Expected result | Status |
|---|---|---|---|
| DOC-01 | Preview a private permit | Authorized stream/temporary URL; no public path | Planned |
| DOC-02 | Download a private permit | Authorized download; access logged | Planned |
| DOC-03 | Download another application's document | `404`; no data | Planned |
| DOC-04 | Accept a document | Status accepted; application recalculated | Planned |
| DOC-05 | Reject a document without a reason | `422` | Planned |
| DOC-06 | Reject a document with a reason | Status rejected; reason visible to group | Planned |
| DOC-07 | Request resubmission | Document requires resubmission; application requires resubmission | Planned |
| DOC-08 | All documents accepted | Application becomes ready for approval | Planned |
| DOC-09 | Empty document set | Approval remains blocked; required documents are reported | Planned |
| DOC-10 | Resubmit a rejected document | Document pending; application pending review; history retained | Planned |

## 3. Approval

| ID | Test case | Expected result | Status |
|---|---|---|---|
| APR-01 | Approve a ready application | Tenant active; application approved; owner role present | Planned |
| APR-02 | Approve with a pending document | `422`; no activation | Planned |
| APR-03 | Approve with an unverified owner phone | `422`; no activation | Planned |
| APR-04 | Approve twice concurrently | Only one activation; no duplicate roles | Planned |
| APR-05 | Approved group owner accesses dashboard | `200` | Planned |
| APR-06 | Notification after approval | Notification exists after commit | Planned |
| APR-07 | Approval rollback | No partial tenant/role activation | Planned |

## 4. Rejection and Reopening

| ID | Test case | Expected result | Status |
|---|---|---|---|
| REJ-01 | Reject a pending application with a reason | Status rejected; reason visible; dashboard blocked | Planned |
| REJ-02 | Reject without a reason | `422` | Planned |
| REJ-03 | Reject an approved application | `422`/`409`; no change | Planned |
| REJ-04 | Reopen a rejected application | Previous review state restored | Planned |
| REJ-05 | Reopen an approved application | `422`; no change | Planned |
| REJ-06 | Rejection history remains after reopening | Previous reason and review events retained | Planned |
| REJ-07 | Group sees rejection feedback | Status page shows reason and feedback | Planned |

## 5. Pending Group Access

| ID | Test case | Expected result | Status |
|---|---|---|---|
| PEN-01 | Pending owner opens status page | `200`; status and required actions | Planned |
| PEN-02 | Pending owner opens dashboard | `403`; workspace not active | Planned |
| PEN-03 | Pending owner downloads own document | Authorized only | Planned |
| PEN-04 | Pending owner accesses another group's application | `404` | Planned |
| PEN-05 | Suspended group opens dashboard | `403` | Planned |

## 6. Security and Integrity

| ID | Test case | Expected result | Status |
|---|---|---|---|
| SEC-01 | Review action by non-admin | `401`/`403` | Planned |
| SEC-02 | Review action by admin without permission | `403` | Planned |
| SEC-03 | Permit path is never returned publicly | No public storage URL in responses | Planned |
| SEC-04 | Concurrent document review and approval | State cannot become inconsistent | Planned |
| SEC-05 | Activity log for every decision | Actor, application, action, old/new state recorded | Planned |
