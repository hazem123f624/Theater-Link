# TheaterLink Business Design

TheaterLink connects theaters, theater groups, and individual audience members. Theaters publish events and make halls available. Theater groups request hall time for their productions. Individuals discover published events and book tickets. The platform never processes online payments; all payments happen outside the application.

This document contains pure business rules and workflows. It contains no code, database schemas, or implementation details.

---

## Platform Overview

**Actors:**

1. **Super Admin** — reviews theater-group registration applications, manages platform oversight, and can suspend or reactivate workspaces.
2. **Theater** — a tenant that manages its profile, halls, schedules, events, ticket tiers, team, reservations, and bookings.
3. **Theater Group** — a tenant that manages its profile, team, registration documents, and hall reservation requests. It can interact with many theaters without accessing their private data.
4. **Individual** — a user who browses published events and books tickets.

**Core model:** theaters and groups are separate tenant types with shared identity, team, role, and permission infrastructure. A group reservation always links one group tenant to one theater tenant and one hall time slot.

## Web Dashboards

TheaterLink is a web-only platform. There is no mobile client.

1. **Admin Dashboard** — platform administration and group-application review.
2. **Theater Dashboard** — theater profile, halls, schedules, events, shows, ticket tiers, reservations, and bookings.
3. **Group Dashboard** — group profile, team, documents, theater discovery, and reservation requests.
4. **User Dashboard** — individual profile, event discovery, ticket booking, and booking history.

---

## Phase 2: Core Architecture, Registration & Tenant Foundation

**Goal:** every actor can register and sign in to the correct workspace, and both theaters and groups have isolated team workspaces.

### Theater registration

- A theater registers with its name, address, location, contact information, required documents if applicable, credentials, and a workspace address.
- Theater registration does not require Super Admin approval.
- After successful registration, the theater owner gets an active workspace.
- The theater owner can access the theater dashboard and manage the theater.
- The workspace address is unique across the platform.
- A theater that loses access to its workspace can recover it through the normal account-recovery flow.

### Theater group registration

- During registration the user explicitly chooses **Individual** or **Theater Group**.
- A theater group must provide its group name, group information, contact information, official permit/license document, credentials, and a workspace address.
- The group application starts in **Pending Review**.
- The group owner can sign in, but cannot access the group dashboard until the application is approved.
- While pending, the group sees only its registration status page, uploaded document statuses, admin feedback, re-upload requirements, and support contact information.
- The group application can become **Approved**, **Rejected**, or **Requires Resubmission**.
- An approved group gets an active workspace and can manage its team, profile, and reservation requests.

### Individual registration

- An individual registers with the required personal information and a password.
- The individual verifies their phone with an OTP.
- After verification, the individual can access the application immediately.
- Individuals do not require admin approval.

### Workspace and team rules

- A theater and a group each have one isolated workspace.
- Multiple team members can sign in to the same workspace.
- Each team member has their own account.
- Team members can be invited, activated, deactivated, soft-deleted, restored, and assigned one or more roles.
- The workspace owner is a team member with the owner role.
- At least one active owner must remain in every workspace.
- A team member may belong to multiple workspaces, but each membership is separate and requires the correct workspace address at login.
- An account signed in to one workspace cannot access another workspace by changing a client-supplied tenant identifier.
- Roles and permissions are enforced by the backend, not only by the frontend.
- The owner role cannot be removed from the last owner.
- Workspace membership changes are audited.

### Tenant types

- A tenant is exactly one of **Theater** or **Theater Group**.
- A tenant type never changes in place.
- A theater can never access a group's private data, and a group can never access a theater's private data.
- A group can read a theater's public profile, active halls, and public availability, but not its team, internal notes, private documents, or other groups' reservations.

---

## Phase 3: Admin Review, Documents & Provisioning

**Goal:** the Super Admin can review group applications and decide whether a group workspace becomes active.

### Review list

- The Super Admin can view all submitted group applications.
- The list can be searched by group name and filtered by application status.
- The list is paginated.
- An invalid status or page size is reported as invalid input, not as an empty list.
- The Super Admin can open an application and see the group information, contact details, submitted documents, application history, and internal notes.

### Document review

Each uploaded document has one of these states:

- **Pending** — submitted, not yet reviewed.
- **Accepted** — reviewed and accepted.
- **Rejected** — refused with a reason.
- **Requires Resubmission** — the group must upload a replacement.

Rules:

- The Super Admin can preview and download submitted permits through an authorized action.
- Downloading or previewing a document never exposes a public storage path.
- A document can be accepted, rejected, or returned for resubmission.
- A rejection or resubmission request must include feedback for the group.
- The group sees the document status, feedback, and whether a replacement is required.
- A replacement document returns the document to **Pending** and the application to **Pending Review**.
- Previous document versions remain in the history for audit.

### Application decisions

**Approval**

1. The application must be ready for approval.
2. Every required document must be accepted.
3. The owner's phone must be verified.
4. The Super Admin approves the application.
5. The tenant becomes active.
6. The group receives a notification.
7. The group can access its dashboard.
8. The group can manage its team and request theater reservations.

**Rejection**

1. An application that is already approved cannot be rejected.
2. The Super Admin rejects the application with a reason.
3. The group remains unable to access its dashboard.
4. The group sees the rejection reason and feedback on its status page.
5. The rejection and its reason stay in the application history.

**Reopening**

- Only a rejected application can be reopened.
- Reopening restores the review state that existed immediately before rejection.
- An approved application can never be reopened.
- Reopening keeps the previous rejection reason and history.

### Resubmission

- The Super Admin requests a replacement document.
- The group uploads the replacement.
- The application returns to **Pending Review**.
- The Super Admin can review it again.
- A group can resubmit only documents that were rejected or returned.
- A group cannot resubmit a document for another group or application.

### Notifications

- The group owner is notified when the application is submitted, a document is reviewed, resubmission is requested, and a final decision is made.
- The Super Admin is notified when a new application is submitted or a group resubmits a document.
- Notifications are delivered in the application and, where configured, through email/SMS.
- A notification is never sent for an operation that failed or rolled back.

---

## Phase 4: Theater Profile, Halls, Schedules & Events

**Goal:** an active theater can define its profile, halls, schedule, events, shows, and ticket tiers.

### Theater profile

- A theater manages its name, description, contact information, address, location, logo, and cover image.
- Only active theater members with the correct permission can update the profile.
- Public theater pages show only public profile fields.
- Team members, internal notes, private documents, and unpublished events are never public.

### Halls

- A theater can create, update, deactivate, and soft-delete halls.
- A hall has a name, description, and capacity.
- A hall belongs to exactly one theater.
- A hall cannot be used by another theater.
- A theater cannot create an event or schedule for another theater's hall.

### Hall schedules and availability

- Each hall has its own weekly schedule.
- A schedule defines the day of week, start time, end time, slot duration, price, and currency.
- The system generates concrete time slots from the schedule over a rolling horizon.
- A theater can block slots for maintenance, private use, internal activities, or other reasons.
- Event shows also occupy hall time and block overlapping slots.
- A slot can be **Available**, **Held** by a pending group request, **Reserved** by an approved reservation, or **Blocked** by the theater/event.
- The system prevents duplicate slot definitions for the same hall and start time.
- Past slots cannot be requested or approved.
- A theater can change a future schedule; existing held or reserved slots are never silently overwritten.
- Schedule changes and blocked-slot reasons are audited.

### Events and shows

- A theater can create, update, publish, unpublish, and cancel events.
- An event has translated titles/descriptions, a poster, a hall association, and one or more shows.
- A show has a date, start time, end time, hall, and status.
- An event is publicly discoverable only while published and not cancelled.
- A show can be cancelled without deleting its history.
- An event cannot be unpublished while it has active confirmed bookings unless the theater cancels those bookings according to policy.
- Event shows cannot overlap confirmed group reservations in the same hall.

### Ticket tiers

- Each show can have multiple ticket tiers.
- A tier has a name, description, price, currency, total quantity, sale window, and optional per-booking limit.
- Inventory is independent per show and tier.
- The sum of active tier quantities for a show cannot exceed the hall capacity.
- A tier with bookings cannot be deleted; it is deactivated instead.
- A tier cannot be sold outside its sale window.

---

## Phase 5: Theater Group Management & Hall Reservations

**Goal:** an approved group can discover theaters, request available hall slots, and track the reservation lifecycle.

### Group profile and team

- An approved group manages its group name, description, contact information, logo, and team.
- Group team members have group-scoped roles and permissions.
- Group team members cannot access theater private data.

### Theater discovery

- A group can browse active theaters.
- A group can view a theater's public profile and active halls.
- A group can view public hall availability for a date range.
- Availability shows only slots that are genuinely requestable.
- Discovery never exposes theater team members, internal notes, private documents, other groups' reservations, or draft events.

### Reservation request

1. The group selects a theater.
2. The group selects an active hall.
3. The group views available slots and prices.
4. The group selects an available slot.
5. The system verifies availability inside a database transaction.
6. The system places a temporary hold on the slot.
7. The group submits the request.
8. The theater receives the request.
9. The group sees the request as **Pending** with an expiry time.

### Pending hold rule

- A pending request temporarily holds the requested slot.
- Other groups cannot request a slot that is held.
- The hold expires after the theater's configured response window.
- On expiry, the request becomes **Expired** and the slot becomes available again.
- The theater may approve before expiry.
- Approval after expiry fails and releases the slot; the group is notified.
- The group may cancel a pending request; the slot is released.
- A theater rejection releases the slot.
- The system never relies on a frontend availability check.

### Reservation decisions

- The theater can view incoming requests for its own halls only.
- The theater can see the group's public profile and contact details needed to evaluate the request.
- The theater can approve or reject the request.
- Rejection requires a reason.
- Approval revalidates that the slot is still held by this request, has not expired, and is not blocked by an event or theater block.
- A confirmed reservation blocks the slot.
- Overlapping confirmed reservations are never allowed.
- A slot cannot have more than one active pending or confirmed reservation.
- The group can track **Pending**, **Approved**, **Rejected**, **Cancelled**, **Expired**, and **Completed** reservations.
- Notifications are sent for request submission, decision, expiry, and cancellation.

### Reservation pricing

- The theater defines the reservation price.
- The price is snapshotted when the request is submitted.
- A later price change does not change an existing request.
- No online payment is processed. Payment is handled externally between the theater and the group.

---

## Phase 6: Individual Event Discovery & Ticket Booking

**Goal:** an individual can discover published events, choose a show and ticket tier, and book tickets without overselling.

### Event discovery

- Individuals can browse active theaters and published events.
- Events can be searched and filtered by theater, date, and category.
- Event details show the description, poster, theater, hall, upcoming shows, and ticket tiers.
- Only published, non-cancelled events and scheduled shows are visible.
- Draft events, cancelled shows, and private theater data are never public.
- Pagination is required for lists; an empty result is a valid response.

### Ticket booking

1. The individual selects an event.
2. The individual selects an available show.
3. The individual selects a ticket tier.
4. The individual specifies the quantity.
5. The backend validates the tier, sale window, per-booking limit, and availability.
6. The backend reserves the requested quantity atomically.
7. The booking is created as **Confirmed**.
8. The individual receives booking confirmation and ticket information.

### Inventory rules

- Total quantity, sold quantity, held quantity, available quantity, cancelled quantity, and expired quantity are distinct concepts.
- In the standard flow there is no temporary hold: a successful booking immediately becomes confirmed and consumes inventory.
- A temporary hold may be introduced later if the business adds an external payment-confirmation step; the inventory design supports it.
- Inventory can never be oversold, even when multiple individuals book the final ticket simultaneously.
- If the requested quantity exceeds availability, the booking fails without changing inventory.
- A booking across multiple tiers is all-or-nothing.
- Each tier has an independent inventory.
- The sum of tier totals for a show cannot exceed hall capacity.
- Prices are snapshotted on the booking; later price changes do not alter confirmed bookings.

### Duplicate requests

- A repeated booking request with the same idempotency key returns the existing booking.
- A retry must never create a second booking or a second set of tickets.
- Duplicate submissions are rejected or collapsed safely.

### Tickets and QR codes

- If per-ticket entry control is required, each ticket has a unique ticket number and opaque QR token.
- A ticket has a status: valid, cancelled, used, or expired.
- Ticket generation is idempotent; the same booking cannot generate duplicate tickets.
- QR payloads contain no personal or tenant secrets.
- Ticket validation checks the ticket status and records usage once.

### Cancellation

- The business must define who can cancel and up to when.
- Recommended: an individual may cancel before a configured cutoff; theater/admin cancellation follows theater policy.
- A cancelled booking may return inventory according to policy.
- Cancellation after the show starts does not restore inventory automatically.
- Payments/refunds are handled outside the platform; the application records booking status only.

---

## Cross-Phase Business Rules

1. Theater registration and theater-group registration are separate workflows.
2. Group workspaces require admin approval; theaters and individuals do not.
3. Both theaters and approved groups support multiple team members with roles and permissions.
4. Each theater manages its own halls, schedules, events, ticket tiers, reservations, and bookings.
5. Each group can request reservations from multiple theaters.
6. A group can never access another group's private data or a theater's private data.
7. A theater can see only reservation requests addressed to it.
8. Reservation requests respect hall availability and event occupancy.
9. Theaters define reservation prices.
10. Events can have multiple shows and multiple ticket tiers.
11. Ticket inventory is protected against overselling.
12. The platform does not process payments.
13. Tenant isolation is enforced by the backend on every request.
14. All list endpoints are paginated and deterministic.
15. All user-facing messages and statuses support Arabic and English.

---

## Glossary

- **Tenant** — an isolated theater or theater-group workspace.
- **Tenant member** — a person with an account in one tenant workspace.
- **Owner** — the tenant member with the immutable owner role; at least one owner must remain.
- **Theater** — a tenant that owns halls, schedules, events, and ticket inventory.
- **Theater group** — a tenant that requests hall time and may have its own team.
- **Hall** — a physical performance space owned by a theater.
- **Slot** — a concrete, fixed-duration period generated from a hall schedule.
- **Hold** — a temporary lock on a slot created by a pending group request.
- **Reservation** — a group's request for a hall slot and the theater's decision on it.
- **Event** — a theater production with translated content, a poster, and one or more shows.
- **Show** — a dated performance of an event in a hall.
- **Ticket tier** — a price/category bucket with independent inventory for a show.
- **Booking** — an individual's confirmed purchase of one or more tickets.
- **Idempotency key** — a client-supplied key that makes retried booking requests safe.
