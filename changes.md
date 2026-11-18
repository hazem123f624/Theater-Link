# Changes Log

## Phase 4 backend and frontend — 2026-11-18

- Added the `Theaters` domain module and theater operations schema.
- Added editable theater profiles with timezone, logo, and cover media.
- Added halls, weekly schedules, materialized time slots, maintenance blocks, and public availability.
- Added events with English/Arabic content, categories, posters, publishing, unpublishing, and cancellation.
- Added shows with hall overlap validation, event occupancy, cancellation, and slot release.
- Added ticket tiers with capacity, sale windows, quantities, and per-booking limits.
- Added public theater, event, category, and hall-availability APIs.
- Added real theater dashboard counts.
- Added theater profile, hall, schedule, availability, event, show, and ticket-tier management screens.
- Added public theater and event catalog pages.
- Added English/Arabic Phase 4 labels and RTL support.
- Extended the browser smoke test through the full theater operations and public catalog flow.
- Updated `/docs` to 170 endpoint methods and the Postman collection to 103 requests.
- Added Phase 4 feature tests covering profile, halls, schedules, slots, blocks, events, shows, tiers, public visibility, and tenant isolation.

## Phase 3 backend and frontend — 2026-11-15

- Added group-application status history, document version history, reviewer metadata, and owner phone-verification timestamps.
- Added transactional review workflow with status recalculation, idempotent approval, rejection/reopening, resubmission, notes, suspension, and reactivation.
- Added admin group-application list, search, status filters, detail, private document download/preview, decision actions, and internal notes.
- Added group document download and replacement upload with retained versions.
- Added in-app notifications with queued email/SMS delivery after commit, plus notification list/read endpoints and a bell UI.
- Enforced admin review permissions with `group_applications-fetch/read/update`.
- Made admin dashboard counts reflect theaters, groups, and pending applications.
- Added English/Arabic Phase 3 labels and RTL-compatible admin/group review interfaces.
- Extended the browser smoke test through document rejection, group resubmission, approval, dashboard access, and notifications.
- Updated `/docs` to 170 endpoint methods and the Postman collection to 103 requests.
- Added Phase 3 backend feature tests covering review, documents, approval, rejection/reopen, resubmission, permissions, notifications, suspension, and counts.

## Phase 2 frontend — 2026-11-15

- Added a React 19 + TypeScript + Vite single-page application served by Laravel.
- Added public landing, theater/group/individual registration with OTP, and invitation acceptance.
- Added theater, group, individual, and admin login surfaces with actor-specific guards.
- Added the pending group application experience and dashboard-access guard.
- Added theater/group dashboards, team CRUD/trash/restore, invitations, custom roles with permission selection, and account pages.
- Added individual profile, phone verification, password change, and account deletion.
- Added admin dashboard counts, profile editing, and password change.
- Added English/Arabic localization with RTL layout and language persistence.
- Added API client normalization, session refresh, permission-aware navigation, accessible form fields, and responsive layouts.
- Added the tenant permission catalog endpoint and role `permission_ids`, CORS headers, and invitation email links.
- Added frontend unit/component tests and a browser smoke test covering registration, invitations, role creation, password reset, and RTL.
- Verified: 30 backend tests / 153 assertions, 7 frontend tests, TypeScript clean, production build successful, browser smoke test covering all Phase 2 flows.

## Phase 2 completion — 2026-11-12

- Completed theater registration with OTP verification/resend, theater profile creation, workspace provisioning, and owner token issuance.
- Completed group registration with private permit upload, OTP verification/resend, group profile, pending application reference, and pending-owner access.
- Added pending group application status, document, workspace, and support endpoints while keeping the group dashboard blocked.
- Added tenant password reset with OTP, reset token, password update, and token revocation.
- Added team invitations with expiring tokens, role assignment, acceptance, and token issuance.
- Added team trash, restore, and force-delete flows with last-owner protection and member email conflict checks.
- Added custom tenant role create/show/update/delete endpoints with tenant-type permission validation and system-role protection.
- Added encrypted `X-Tenant` support and the `/tenant/resolve` workspace discovery endpoint.
- Added English and Arabic messages for registration, applications, invitations, roles, teams, and password reset.
- Added Phase 2 registration, role, invitation, restore/force-delete, and password-reset feature tests.
- Added model and relation annotations; targeted PHPStan analysis is clean.
- Added a 46-request Phase 2 Postman collection.
- Final verification: 51 tests / 281 assertions passing, `/docs` serving 170 endpoint methods, composer validation and security audit clean.

## Session 2026-11-12 — TheaterLink foundation

- Created the TheaterLink backend scaffold from the existing modular Laravel reference.
- Kept the shared OTP, media, messaging, authentication, security, activity-log, response, repository, and localization foundations.
- Added the shared tenant, membership, role, permission, and tenant-type foundation.
- Added theater and group workspace login, tenant profile, logout, and token refresh.
- Added tenant-aware request context and backend tenant/type/permission checks.
- Added tenant team listing, creation, update, activation, removal, and role assignment.
- Added role listing for tenant workspaces.
- Added English and Arabic messages for the new tenancy module.
- Added architecture, business, user-story, and phase documentation for all phases.
- Added tenant authentication, pending-group access, team isolation, permission, and localization tests.
- Replaced the Scribe documentation page with the QEEI-style custom documentation UI, including the sidebar, endpoint cards, parameter tables, code boxes, search, theme toggle, and responsive layout.
- Seeded the custom documentation UI with 170 endpoints across theater, group, user, tenant, admin, public, and internal API surfaces.
- Made public location lookups unauthenticated and kept central settings admin-only.
- Added seeded Saudi/Egypt location data for registration forms.
- Ensured suspended workspaces return a workspace-status error at login.
- Allowed a soft-deleted team member email to be reused safely.
- Fixed individual registration to store and authenticate the normalized E.164 phone number.
- Fixed the OTP production-mode check so production codes are random rather than always `1234`.
- Converted the individual surface from mobile API naming to the user dashboard API.
- Added explicit Theater, Group, and User dashboard summary endpoints.
- Updated vulnerable dependencies and re-ran the security audit; no known advisories remain.
