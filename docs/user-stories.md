# TheaterLink User Stories

Product-level user stories for every TheaterLink phase described in [`business.md`](business.md).

**Actors:**

- **Theater owner/member** — a team member of a theater workspace.
- **Group owner/member** — a team member of a theater-group workspace.
- **Individual** — an audience member who books tickets.
- **Super Admin** — the platform administrator who reviews group applications and manages oversight.

**Story format:** each story states the user goal and the acceptance criteria required for the story to be complete.

---

## Phase 2: Core Architecture, Registration & Tenant Foundation

### US-P2-01 — Register a theater

**As a** theater owner, **I want to** register my theater and get a workspace **so that** my team can manage halls, events, and reservations.

**Acceptance criteria:**

- I provide the theater name, address, location, contact information, and credentials.
- I choose a unique workspace address.
- Theater registration does not require Super Admin approval.
- My workspace is active immediately after successful registration.
- I become the workspace owner.
- The workspace has isolated data from every other tenant.

### US-P2-02 — Register a theater group

**As a** theater group owner, **I want to** register my group and upload our official permit **so that** we can be reviewed and start requesting halls.

**Acceptance criteria:**

- I explicitly choose the Theater Group registration type.
- I provide the group name, group information, contact information, credentials, and workspace address.
- I upload the required official permit.
- My application starts in Pending Review.
- I can sign in after registering.
- I can see only my application status page while pending.
- I cannot access the group dashboard until approved.

### US-P2-03 — Register as an individual

**As an** individual, **I want to** create an account and verify my phone **so that** I can browse events and book tickets.

**Acceptance criteria:**

- I provide the required personal information and a password.
- I verify my phone with an OTP.
- I can access the application immediately after verification.
- I do not require admin approval.

### US-P2-04 — Sign in to the correct workspace

**As a** theater or group team member, **I want to** sign in to my workspace **so that** I can work only with my tenant's data.

**Acceptance criteria:**

- I sign in with my workspace address, email, and password.
- A theater account cannot sign in through the group login endpoint, and vice versa.
- A pending group owner can sign in but is restricted to the application status area.
- An inactive member or inactive workspace cannot access workspace routes.
- My token is bound to my tenant membership.
- I cannot change a client-supplied tenant identifier to reach another workspace.

### US-P2-05 — Manage my team

**As a** workspace owner or manager, **I want to** invite and manage team members **so that** the right people can work in the workspace.

**Acceptance criteria:**

- I can invite a member with name, email, phone, and role.
- I can activate, deactivate, soft-delete, and restore a member.
- I can assign one or more roles to a member.
- I cannot remove the last owner.
- I cannot manage a member of another tenant.
- Team changes are audited.

### US-P2-06 — Manage roles and permissions

**As a** workspace owner, **I want to** define roles and permissions **so that** team members have least-privilege access.

**Acceptance criteria:**

- I can create, update, and deactivate tenant roles.
- I can select only permissions allowed for my tenant type.
- The owner role is immutable.
- A theater cannot assign theater-only permissions to a group, and vice versa.
- The backend rejects unauthorized actions even if the frontend hides them.

---

## Phase 3: Admin Review & Group Provisioning

### US-P3-01 — Review group applications

**As a** Super Admin, **I want to** see all group applications and their documents **so that** I can decide whether a group should be approved.

**Acceptance criteria:**

- I can list and search applications.
- I can filter by application status.
- I can open an application and see group information, contact details, and documents.
- I can preview and download a document through an authorized action.
- The list is paginated.

### US-P3-02 — Review documents

**As a** Super Admin, **I want to** accept, reject, or return a document **so that** the group knows whether its permit is acceptable.

**Acceptance criteria:**

- I can accept a document.
- I can reject a document with a reason.
- I can request a replacement document with feedback.
- A replacement returns the document to Pending and the application to Pending Review.
- The group sees the document status and feedback.

### US-P3-03 — Approve a group

**As a** Super Admin, **I want to** approve a ready application **so that** the group gets a live workspace.

**Acceptance criteria:**

- I can approve only when the application is ready and every required document is accepted.
- The owner's phone must be verified.
- Approval activates the tenant in one transaction.
- The group is notified.
- The group owner can access the dashboard and team management.
- Duplicate approval does not provision twice.

### US-P3-04 — Reject or reopen an application

**As a** Super Admin, **I want to** reject an application with a reason or reopen a rejected one **so that** the review can be corrected.

**Acceptance criteria:**

- I can reject an application that is not approved.
- A rejection requires a reason.
- The group sees the reason and remains blocked from its dashboard.
- I can reopen only a rejected application.
- Reopening restores the review state from before rejection.
- An approved application can never be reopened.

### US-P3-05 — Resubmit a document

**As a** group owner, **I want to** replace a rejected document **so that** my application can be reviewed again.

**Acceptance criteria:**

- I can resubmit only a document that was rejected or returned.
- The replacement is uploaded to my own application only.
- The document returns to Pending.
- The application returns to Pending Review.
- The Super Admin can review the new version.
- Previous versions remain in the history.

---

## Phase 4: Theater Profile, Halls, Schedules & Events

### US-P4-01 — Manage the theater profile

**As a** theater owner, **I want to** update my theater profile **so that** groups and individuals see accurate public information.

**Acceptance criteria:**

- I can update the theater name, description, contact information, address, location, logo, and cover.
- Only authorized theater members can update it.
- Public pages show only public fields.
- Team data and private documents remain private.

### US-P4-02 — Manage halls

**As a** theater manager, **I want to** create and manage halls **so that** I can schedule events and accept reservations.

**Acceptance criteria:**

- I can create, update, deactivate, and soft-delete a hall.
- A hall has a name, description, and capacity.
- A hall belongs to only my theater.
- Another theater cannot access or use my hall.
- Capacity cannot be negative.

### US-P4-03 — Define hall schedules

**As a** theater manager, **I want to** set weekly availability and slot pricing **so that** groups know when and at what price they can request the hall.

**Acceptance criteria:**

- I can define day-of-week windows, slot duration, price, and currency.
- The system generates concrete slots over a rolling horizon.
- I can block slots for maintenance or private use.
- A slot has one of Available, Held, Reserved, or Blocked.
- Past slots cannot be requested.
- Schedule changes do not silently overwrite held or reserved slots.

### US-P4-04 — Manage events and shows

**As a** theater manager, **I want to** publish events and schedule shows **so that** individuals can discover and book them.

**Acceptance criteria:**

- I can create, update, publish, unpublish, and cancel an event.
- I can add translated titles and descriptions and a poster.
- I can create one or more shows with hall, date, and time.
- Published events are publicly discoverable; drafts are not.
- An event show blocks overlapping hall slots.
- A show cannot overlap a confirmed group reservation.

### US-P4-05 — Define ticket tiers

**As a** theater manager, **I want to** define ticket tiers for each show **so that** I can sell different ticket categories.

**Acceptance criteria:**

- I can add multiple tiers with name, description, price, currency, total quantity, and sale window.
- Inventory is independent per show and tier.
- The sum of tier totals does not exceed hall capacity.
- A tier with bookings cannot be deleted; it is deactivated.
- Sales outside the window are rejected.

---

## Phase 5: Group Management & Hall Reservations

### US-P5-01 — Manage the group profile and team

**As a** group owner, **I want to** manage our profile and team **so that** the right coordinators can request halls.

**Acceptance criteria:**

- An approved group can update its name, description, contact information, and logo.
- Group team members have group-scoped roles.
- Group members cannot access theater private data.
- The owner is protected.

### US-P5-02 — Discover theaters and availability

**As a** group coordinator, **I want to** browse theaters and available hall slots **so that** I can choose a venue for a production.

**Acceptance criteria:**

- I can list active theaters.
- I can view a theater's public profile and active halls.
- I can view availability for a date range.
- I see only genuinely requestable slots.
- I never see theater team data, private documents, internal notes, or other groups' reservations.

### US-P5-03 — Request a hall reservation

**As a** group coordinator, **I want to** request an available slot **so that** the theater can approve it.

**Acceptance criteria:**

- I select one available slot.
- The backend locks the slot and creates a temporary hold.
- I see the price snapshot and expiry time.
- Another group cannot hold the same slot at the same time.
- A held, reserved, or blocked slot cannot be requested.
- I can cancel my pending request and release the slot.

### US-P5-04 — Review reservation requests as a theater

**As a** theater manager, **I want to** accept or reject incoming requests **so that** I control my hall schedule.

**Acceptance criteria:**

- I see only requests addressed to my theater.
- I can view the group's public profile and contact details.
- I can approve a pending hold before expiry.
- Approval revalidates the hold and slot occupancy.
- I can reject with a reason.
- Rejection releases the slot.
- I cannot approve another theater's request.

### US-P5-05 — Track reservation lifecycle

**As a** group coordinator, **I want to** track my request status **so that** I know whether the hall is secured.

**Acceptance criteria:**

- Statuses are Pending, Approved, Rejected, Cancelled, Expired, and Completed.
- Expired holds release the slot automatically.
- Approved reservations block the slot.
- Overlapping confirmed reservations never occur.
- Notifications are sent for submission, decision, expiry, and cancellation.

---

## Phase 6: Individual Events & Ticket Booking

### US-P6-01 — Discover events

**As an** individual, **I want to** browse published events **so that** I can find a show to attend.

**Acceptance criteria:**

- I can browse active theaters and published events.
- I can search and filter by theater, date, and category.
- Event details show description, poster, theater, hall, shows, and tiers.
- Only published, non-cancelled events are visible.
- Lists are paginated.

### US-P6-02 — Select a show and ticket tier

**As an** individual, **I want to** choose a specific show and ticket category **so that** I can book the right tickets.

**Acceptance criteria:**

- I can see upcoming shows for an event.
- I can see available ticket tiers and prices.
- Sold-out tiers show no availability.
- Only active tiers within the sale window are bookable.

### US-P6-03 — Book tickets safely

**As an** individual, **I want to** book a quantity of tickets **so that** I can attend the show.

**Acceptance criteria:**

- The backend validates availability, sale window, and per-booking limit.
- Inventory is reserved atomically.
- The booking is confirmed immediately.
- I receive booking confirmation and ticket information.
- If availability is insufficient, the booking fails without changing inventory.
- Concurrent bookings cannot oversell the last ticket.

### US-P6-04 — Retry safely

**As an** individual, **I want to** retry a booking request without being charged twice **so that** network problems do not create duplicate bookings.

**Acceptance criteria:**

- A repeated request with the same idempotency key returns the existing booking.
- No duplicate booking or ticket rows are created.
- A unique constraint protects against concurrent duplicate inserts.

### US-P6-05 — Cancel a booking

**As an** individual, **I want to** cancel my booking according to policy **so that** my plans can change.

**Acceptance criteria:**

- Cancellation is allowed only within the configured policy.
- The booking and tickets are marked cancelled.
- Inventory is restored only when policy allows it.
- Counters never go below zero.
- Cancellation is audited.
- External refunds remain outside the platform.
