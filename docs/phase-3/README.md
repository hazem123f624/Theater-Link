# Phase 3 — Admin Review, Documents & Group Provisioning

This folder tracks the implementation and verification work for Phase 3 of TheaterLink.

**Goal:** the Super Admin can review group applications, inspect permits, approve or reject applications, request document resubmissions, and activate the group workspace after approval.

**Status:** Phase 3 backend and frontend are complete and verified.

**Source of truth:** [`../business.md`](../business.md) — Phase 3.

## Files

| File | Purpose |
|---|---|
| [`tasks.md`](tasks.md) | Implementation checklist and final status. |
| [`test-cases.md`](test-cases.md) | QA and automation test-case specification. |
| [`execution-report.md`](execution-report.md) | Final test, static-analysis, and implementation report. |

## Current Status

| Area | Status | Notes |
|---|---|---|
| Review schema and history | Complete | Application status history, document versions, reviewer metadata, and phone verification. |
| Admin application list/filter | Complete | Search, status filter, validation, and pagination. |
| Document access | Complete | Authorized private preview/download for admins and owning groups. |
| Document review | Complete | Accept, reject with reason, request replacement, and status recalculation. |
| Approval and provisioning | Complete | Transactional, row-locked, idempotent activation with blockers. |
| Rejection/reopen | Complete | Reason/feedback preserved with history. |
| Group resubmission | Complete | Replacement upload, version history, and return to pending review. |
| Notifications | Complete | In-app records; queued email/SMS channels after commit. |
| Activity logs | Complete | Every review action records actor and state details. |
| Admin permissions | Complete | `group_applications-fetch/read/update` enforced by middleware. |
| Admin review frontend | Complete | Application list, detail, documents, decisions, notes, history, suspension controls. |
| Group resubmission frontend | Complete | Document download, replacement upload, and review history. |
| Notification bell | Complete | Unread count, dropdown, mark read, and application deep links. |
| Group status page | Complete | Status, reference, feedback, required actions, documents, and workspace information. |

## Endpoints

Base path: `/api/v1`

### Admin review

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/dashboard/group-applications` | List and filter applications. |
| `GET` | `/dashboard/group-applications/statuses` | Application status lookup. |
| `GET` | `/dashboard/group-applications/{application}` | Application details and documents. |
| `GET` | `/dashboard/group-applications/{application}/documents/{document}/download` | Authorized private download. |
| `POST` | `/dashboard/group-applications/{application}/documents/{document}/accept` | Accept a document. |
| `POST` | `/dashboard/group-applications/{application}/documents/{document}/reject` | Reject with reason. |
| `POST` | `/dashboard/group-applications/{application}/documents/{document}/request-resubmission` | Request replacement. |
| `POST` | `/dashboard/group-applications/{application}/approve` | Approve and activate the workspace. |
| `POST` | `/dashboard/group-applications/{application}/reject` | Reject with reason. |
| `POST` | `/dashboard/group-applications/{application}/reopen` | Reopen a rejected application. |
| `POST` | `/dashboard/group-applications/{application}/notes` | Add internal notes. |
| `PATCH` | `/dashboard/group-applications/{application}/suspend` | Suspend an active group workspace. |
| `PATCH` | `/dashboard/group-applications/{application}/reactivate` | Reactivate a suspended group workspace. |

### Group status and resubmission

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/group/application` | Status, reference, feedback, required actions, history. |
| `GET` | `/group/application/documents` | Document statuses and feedback. |
| `GET` | `/group/application/documents/{document}/download` | Authorized own-document download. |
| `POST` | `/group/application/documents/{document}/resubmit` | Upload a replacement document. |
| `GET` | `/group/application/workspace` | Workspace status and address. |
| `GET` | `/group/application/support` | Support contact information. |

### Notifications

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/notifications` | List in-app notifications and unread count. |
| `PATCH` | `/notifications/{notification}/read` | Mark one notification as read. |
| `POST` | `/notifications/read-all` | Mark all notifications as read. |

## Approval Rules

- The owner phone must be verified.
- Every required document must exist and be accepted.
- The application must be ready for review.
- Approval locks the application and tenant, activates the workspace, and records history exactly once.
- Duplicate approval returns the already-approved application without provisioning again.

## Notifications

- In-app notifications are created for reviewers and group owners.
- Email/SMS channels are dispatched through a queued job after the transaction commits.
- A queue worker and configured mail/SMS providers are required for external delivery.

## Deferred

- External email/SMS provider configuration and production queue operation.
- Operational theater/group screens from later phases.
