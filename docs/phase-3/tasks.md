# Phase 3 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Group Application Schema

- [x] Create the group application record linked one-to-one with a group tenant.
- [x] Store the application reference, group information, contact details, and requested subdomain.
- [x] Store the current status and previous review status.
- [x] Store rejection reason, visible review feedback, and internal notes.
- [x] Store reviewer and review/approval timestamps.
- [x] Create application document records with type, file, status, feedback, and reviewer.
- [x] Store document history instead of overwriting previous versions.
- [x] Add indexes for status, reference, tenant, and created date.

## 2. Admin Review

- [x] Add an admin application list with search, status filter, and pagination.
- [x] Reject invalid status/page-size filters with a validation error.
- [x] Add an application detail endpoint with documents and history.
- [x] Add an authorized private document download endpoint.
- [x] Add an accept-document action.
- [x] Add a reject-document action with a reason.
- [x] Add a request-resubmission action with feedback.
- [x] Recompute the application status after every document action.
- [x] Add internal notes that are never exposed to the group.
- [x] Add an activity-log entry for every review action.
- [x] Enforce a group-application review permission on every endpoint.

## 3. Approval and Provisioning

- [x] Approve only when the application is ready for approval.
- [x] Require every required document to be accepted.
- [x] Require the owner's phone to be verified.
- [x] Lock the application row before deciding.
- [x] Activate the tenant and application in one transaction.
- [x] Assign the owner role and ensure system roles exist.
- [x] Make approval idempotent; duplicate approval must not provision twice.
- [x] Send notifications only after commit.
- [x] Add approval transaction and duplicate-approval tests.

## 4. Rejection, Reopening and Resubmission

- [x] Reject only an application that is not approved.
- [x] Require a rejection reason.
- [x] Keep the rejection reason and history after reopening.
- [x] Store the review status the rejection came from.
- [x] Reopen only a rejected application and restore the previous review state.
- [x] Prevent reopening an approved application.
- [x] Allow a group to resubmit only a rejected/returned document.
- [x] Return the document to Pending and the application to Pending Review after resubmission.
- [x] Add rejection, reopen, and resubmission tests.

## 5. Group Status Page

- [x] Add the application status endpoint.
- [x] Show status, reference, submitted date, rejection reason, and visible feedback.
- [x] Show document statuses and per-document feedback.
- [x] Show required actions: missing documents and documents needing replacement.
- [x] Show workspace status and address.
- [x] Show support contact information.
- [x] Block every group workspace endpoint while the tenant is pending.
- [x] Add pending-access and cross-group isolation tests.

## 6. Notifications and Media

- [x] Add an in-app notification table and service.
- [x] Notify group owners about document review, resubmission, approval, and rejection.
- [x] Notify central admins about new applications and resubmissions.
- [x] Queue email/SMS/push channels through the existing message factory.
- [x] Send notifications only after the transaction commits.
- [x] Store permits on a private disk.
- [x] Require authorization for every document download.
- [x] Log document access.

## 7. Documentation and Verification

- [x] Document all Phase 3 endpoints.
- [x] Add Phase 3 cases to the Postman collection.
- [x] Add feature tests for review, approval, rejection, reopening, resubmission, and authorization.
- [x] Run the Phase 3 test suite and static analysis.

## 8. Frontend

- [x] Add the admin group-applications list with search, status filter, pagination, and permission-aware navigation.
- [x] Add the admin application detail page with group/owner information, documents, review history, blockers, and internal notes.
- [x] Add document preview/download, accept, reject, and request-resubmission actions.
- [x] Add approve, reject, reopen, suspend, and reactivate workspace actions.
- [x] Add group document downloads, replacement upload, and application history.
- [x] Add the notification bell with unread count, mark-read actions, and application deep links.
- [x] Add English/Arabic labels for all Phase 3 UI.
- [x] Extend the browser smoke test through document rejection, group resubmission, approval, dashboard access, and notifications.

## Definition of Done

Phase 3 is complete when:

1. A group application can be reviewed from submission to final decision.
2. Documents are private and only accessible through authorized actions.
3. Approval activates the group workspace exactly once.
4. Rejection and reopening preserve history and feedback.
5. A group can resubmit a returned document and return to review.
6. Pending groups can access only the status area.
7. Notifications are delivered after commit.
8. Phase 3 test cases pass.
