# Phase 2 Tasks

Status legend: `[x]` implemented and verified, `[~]` partial/deferred with an explicit note, `[ ]` not started.

## 1. Project Foundation

- [x] Create the TheaterLink Laravel 12 application from the reference foundation.
- [x] Keep the modular architecture with `nwidart/laravel-modules`.
- [x] Include only the `Admins`, `Users`, `Settings`, and `Tenancy` modules.
- [x] Reuse the shared OTP, media, messaging, authentication, security, activity-log, repository, response, and localization services.
- [x] Replace the reference valuer-specific auth maps with admins, individuals, and tenant members.
- [x] Add the `tenant_members` guard and provider.
- [x] Configure the local environment for the TheaterLink database.
- [x] Seed the central admin, settings, and tenant permission catalog.
- [x] Add a project README and phase documentation structure.

## 2. Tenancy Foundation

- [x] Create the `tenants` table with ULID, UUID, type, subdomain, status, and approval timestamp.
- [x] Create the `tenant_members` table with per-tenant email/phone uniqueness, owner flag, status, and login-lock fields.
- [x] Create the global tenant permission catalog.
- [x] Restrict permissions by tenant type.
- [x] Create per-tenant roles and role translations.
- [x] Create role-permission and member-role pivots.
- [x] Add tenant role/permission models.
- [x] Add the tenant provisioning service.
- [x] Provision system roles automatically for a new tenant.
- [x] Assign the owner role to the tenant owner.
- [x] Add the generic `BelongsToTenant` scope.
- [x] Add the request-scoped `TenantContext`.
- [x] Add theater/group profile and group application/document models and migrations.
- [x] Add team invitation model, token expiry, and acceptance flow.

## 3. Tenant Authentication

- [x] Add separate theater and group login endpoints.
- [x] Validate the workspace subdomain, member email, and password.
- [x] Bind the issued token to the tenant member.
- [x] Reject a theater account on the group endpoint and vice versa.
- [x] Allow a pending group owner to sign in.
- [x] Reject inactive members and inactive workspaces.
- [x] Add temporary login lock after repeated failures.
- [x] Add `GET /tenant/me`, logout, and refresh token.
- [x] Add tenant-context middleware.
- [x] Validate optional `X-Subdomain` and plain or encrypted `X-Tenant` headers against the token.
- [x] Add tenant-active, tenant-type, and tenant-permission middleware.
- [x] Add focused feature tests for login, type isolation, and header mismatch.
- [x] Add encrypted `X-Tenant` support compatible with the reference.
- [x] Add password reset for tenant members.
- [ ] Add dedicated login and OTP rate-limit tests; shared security middleware already applies the limits. Deferred to security hardening.

## 4. Theater Registration

- [x] Provisioning service supports an active theater tenant.
- [x] Add `POST /theater/register`.
- [x] Validate theater name, address, contact information, credentials, and subdomain.
- [ ] Add optional theater documents; deferred to the Phase 4 theater profile/media scope.
- [x] Send and verify the owner phone OTP.
- [x] Create the theater profile record.
- [x] Create the owner membership and system roles.
- [x] Return the owner token and workspace details.
- [x] Add registration, duplicate-subdomain, validation, and OTP-verification tests.

## 5. Theater Group Registration

- [x] Provisioning service supports a pending group tenant.
- [x] Add `POST /group/register`.
- [x] Validate group name, group information, contact information, credentials, and subdomain.
- [x] Accept and privately store the official permit.
- [x] Send and verify the owner phone OTP.
- [x] Create the group profile and application record.
- [x] Return an application reference and a pending-owner token.
- [x] Add `GET /group/application` and document/status endpoints.
- [x] Block the group dashboard while pending.
- [x] Add registration, permit-upload, and pending-access tests.

## 6. Individual Registration

- [x] Reference individual registration and OTP endpoints are present through `Modules/Users`.
- [x] Review individual fields for TheaterLink.
- [x] Add TheaterLink-specific registration and OTP tests.
- [x] Confirm an individual token cannot access tenant routes.
- [x] Confirm tenant tokens cannot access individual routes.
- [x] Normalize and store individual phone numbers in E.164 format.

## 6A. Dashboard Entry Points

- [x] Add `GET /user/dashboard` for individual users.
- [x] Add `GET /theater/dashboard` for active theater tenants.
- [x] Add `GET /group/dashboard` for active group tenants.
- [ ] Add real hall/event/reservation/booking counters after their modules are implemented.

## 7. Team Management

- [x] Tenant membership schema and owner flag.
- [x] Add team list endpoint with pagination and search.
- [x] Add member create endpoint with role assignment.
- [x] Add member update endpoint and role sync.
- [x] Add activate/deactivate endpoint.
- [x] Add soft-delete endpoint.
- [x] Add trash listing, restore, and force-delete endpoints.
- [x] Add role assignment through member create/update.
- [x] Prevent removal or deactivation of the last active owner.
- [x] Revoke tokens when a member is deactivated or removed.
- [x] Add cross-tenant membership isolation tests.
- [x] Add team invitation list/create/accept endpoints with expiring tokens.
- [x] Add restore/force-delete feature tests.

## 8. Roles and Permissions

- [x] Global permission catalog and tenant-type allowlist.
- [x] System role templates for theaters and groups.
- [x] Add role list endpoint.
- [x] Add custom role create/show/update/delete endpoints.
- [x] Allow permission selection through role create/update payloads.
- [x] Validate permissions against the tenant type.
- [x] Enforce role permissions on team, invitation, and role endpoints.
- [x] Protect system roles and the last active owner from destructive changes.
- [x] Add viewer permission-denial and tenant-type permission tests.

## 9. Documentation and Verification

- [x] Add phase documentation in the project format.
- [x] Add tenant authentication, registration, team, role, invitation, and password-reset tests.
- [x] Run the full test suite successfully.
- [x] Add API documentation cases for every Phase 2 endpoint; `/docs` now covers 170 API endpoint methods.
- [x] Add a Postman collection covering 103 Phase 2–4 requests.
- [x] Run targeted static analysis on the tenancy, registration, role, invitation, password-reset, and documentation code.
- [x] Add Arabic translations for all Phase 2 messages.
- [x] Validate `composer.json` and run the dependency security audit.

## 10. Web Frontend

- [x] Add a React 19 + TypeScript + Vite SPA served through the Laravel application shell.
- [x] Add a responsive TheaterLink design system with accessible form controls and RTL support.
- [x] Add public landing, theater/group/individual registration, OTP verification, and invitation acceptance.
- [x] Add theater, group, individual, and admin sign-in flows.
- [x] Add the pending group application status experience and dashboard guard.
- [x] Add theater/group dashboards, team management, trash/restore, invitations, and custom role editing.
- [x] Add individual profile, phone verification, password change, and account deletion.
- [x] Add admin dashboard counts, profile editing, and password change.
- [x] Add English/Arabic translations with RTL layout and language persistence.
- [x] Add session refresh, normalized API envelopes/pagination, permission-aware navigation, and actor guards.
- [x] Add the tenant permission catalog endpoint and role `permission_ids`.
- [x] Add frontend unit/component tests, TypeScript checking, production build, and a browser smoke test.

## Definition of Done

Phase 2 is complete when:

1. Theater, group, and individual registration work end to end.
2. Pending group owners can access only their application status area.
3. Tenant login is workspace-bound and type-bound.
4. The owner can manage team members and roles.
5. Every tenant endpoint enforces tenant isolation and permissions at the backend.
6. The Phase 2 test cases pass.
7. API documentation and the Postman collection include the final endpoints.

All Phase 2 definition-of-done items are satisfied. Items explicitly marked `[ ]` above are deferred to later phases or security hardening and are not blockers for Phase 2.
