# Phase 3 Tasks

Status legend: `[x]` implemented, `[~]` partial, `[ ]` not started.

## 1. Group Application Schema

- [ ] Create the group application record linked one-to-one with a group tenant.
- [ ] Store the application reference, group information, contact details, and requested subdomain.
- [ ] Store the current status and previous review status.
- [ ] Store rejection reason, visible review feedback, and internal notes.
- [ ] Store reviewer and review/approval timestamps.
- [ ] Create application document records with type, file, status, feedback, and reviewer.
- [ ] Store document history instead of overwriting previous versions.
- [ ] Add indexes for status, reference, tenant, and created date.

## 2. Admin Review

- [ ] Add an admin application list with search, status filter, and pagination.
- [ ] Reject invalid status/page-size filters with a validation error.
- [ ] Add an application detail endpoint with documents and history.
- [ ] Add an authorized private document download endpoint.
- [ ] Add an accept-document action.
- [ ] Add a reject-document action with a reason.
- [ ] Add a request-resubmission action with feedback.
- [ ] Recompute the application status after every document action.
- [ ] Add internal notes that are never exposed to the group.
- [ ] Add an activity-log entry for every review action.
- [ ] Enforce a group-application review permission on every endpoint.

## 3. Approval and Provisioning

- [ ] Approve only when the application is ready for approval.
- [ ] Require every required document to be accepted.
- [ ] Require the owner's phone to be verified.
- [ ] Lock the application row before deciding.
- [ ] Activate the tenant and application in one transaction.
- [ ] Assign the owner role and ensure system roles exist.
- [ ] Make approval idempotent; duplicate approval must not provision twice.
- [ ] Send notifications only after commit.
- [ ] Add approval transaction and duplicate-approval tests.

## 4. Rejection, Reopening and Resubmission

- [ ] Reject only an application that is not approved.
- [ ] Require a rejection reason.
- [ ] Keep the rejection reason and history after reopening.
- [ ] Store the review status the rejection came from.
- [ ] Reopen only a rejected application and restore the previous review state.
- [ ] Prevent reopening an approved application.
- [ ] Allow a group to resubmit only a rejected/returned document.
- [ ] Return the document to Pending and the application to Pending Review after resubmission.
- [ ] Add rejection, reopen, and resubmission tests.

## 5. Group Status Page

- [ ] Add the application status endpoint.
- [ ] Show status, reference, submitted date, rejection reason, and visible feedback.
- [ ] Show document statuses and per-document feedback.
- [ ] Show required actions: missing documents and documents needing replacement.
- [ ] Show workspace status and address.
- [ ] Show support contact information.
- [ ] Block every group workspace endpoint while the tenant is pending.
- [ ] Add pending-access and cross-group isolation tests.

## 6. Notifications and Media

- [ ] Add an in-app notification table and service.
- [ ] Notify group owners about document review, resubmission, approval, and rejection.
- [ ] Notify central admins about new applications and resubmissions.
- [ ] Queue email/SMS/push channels through the existing message factory.
- [ ] Send notifications only after the transaction commits.
- [ ] Store permits on a private disk.
- [ ] Require authorization for every document download.
- [ ] Log document access.

## 7. Documentation and Verification

- [ ] Document all Phase 3 endpoints.
- [ ] Add Phase 3 cases to the Postman collection.
- [ ] Add feature tests for review, approval, rejection, reopening, resubmission, and authorization.
- [ ] Run the Phase 3 test suite and static analysis.

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
