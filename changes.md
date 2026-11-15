# Changes Log

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
- Added a 45-request Phase 2 Postman collection.
- Final verification: 29 tests / 144 assertions passing, `/docs` serving 112 endpoint methods, composer validation and security audit clean.

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
- Seeded the custom documentation UI with 112 endpoints across theater, group, user, tenant, admin, public, and internal API surfaces.
- Made public location lookups unauthenticated and kept central settings admin-only.
- Added seeded Saudi/Egypt location data for registration forms.
- Ensured suspended workspaces return a workspace-status error at login.
- Allowed a soft-deleted team member email to be reused safely.
- Fixed individual registration to store and authenticate the normalized E.164 phone number.
- Fixed the OTP production-mode check so production codes are random rather than always `1234`.
- Converted the individual surface from mobile API naming to the user dashboard API.
- Added explicit Theater, Group, and User dashboard summary endpoints.
- Updated vulnerable dependencies and re-ran the security audit; no known advisories remain.
