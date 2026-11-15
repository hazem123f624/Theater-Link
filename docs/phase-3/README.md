# Phase 3 — Admin Review, Documents & Group Provisioning

This folder tracks the implementation and verification work for Phase 3 of TheaterLink.

**Goal:** the Super Admin can review group applications, inspect permits, approve or reject applications, request document resubmissions, and activate the group workspace after approval.

**Source of truth:** [`../business.md`](../business.md) — Phase 3.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist with current status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification for Phase 3. |

## Current Status

| Area | Planned | Notes |
|---|---|---|
| Group application schema | Planned | Application, documents, review metadata, and status history. |
| Admin application list/filter | Planned | Search, status filter, pagination. |
| Document preview/download | Planned | Authorized, private storage only. |
| Document review | Planned | Accept, reject, request resubmission. |
| Application approval | Planned | Transactional activation and idempotent provisioning. |
| Application rejection/reopen | Planned | Rejection reason and previous-status restoration. |
| Notifications | Planned | In-app plus queued email/SMS. |
| Admin permission enforcement | Planned | Policies and review permissions. |

## Planned Endpoints

Base path: `/api/v1`

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/admin/group-applications` | List and filter applications. |
| `GET` | `/admin/group-applications/statuses` | Application status lookup. |
| `GET` | `/admin/group-applications/{id}` | Application details and documents. |
| `GET` | `/admin/group-applications/{id}/documents` | Document list. |
| `GET` | `/admin/group-applications/{id}/documents/{docId}/download` | Authorized private download. |
| `POST` | `/admin/group-applications/{id}/documents/{docId}/accept` | Accept a document. |
| `POST` | `/admin/group-applications/{id}/documents/{docId}/reject` | Reject with reason. |
| `POST` | `/admin/group-applications/{id}/documents/{docId}/request-resubmission` | Request replacement. |
| `POST` | `/admin/group-applications/{id}/approve` | Approve and activate the workspace. |
| `POST` | `/admin/group-applications/{id}/reject` | Reject with reason. |
| `POST` | `/admin/group-applications/{id}/reopen` | Reopen a rejected application. |
| `POST` | `/admin/group-applications/{id}/notes` | Add internal notes. |
| `PATCH` | `/admin/group-applications/{id}/suspend` | Suspend an active group workspace. |

## Group Status Endpoints

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/group/application` | Status, reference, feedback, required actions. |
| `GET` | `/group/application/documents` | Document statuses and feedback. |
| `POST` | `/group/application/documents/{id}/resubmit` | Upload a replacement document. |
| `GET` | `/group/application/workspace` | Workspace status and address. |
| `GET` | `/group/application/support` | Support contact information. |

## Known Gaps

1. No review endpoints exist yet; the Phase 2 group tenant foundation is ready for the application schema.
2. No general notification service exists in the reference; TheaterLink must add in-app notifications plus queued email/SMS.
3. No signed private-document download exists yet.
4. Admin permissions exist in the central admin module, but no group-application policy is registered yet.
