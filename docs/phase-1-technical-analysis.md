# TheaterLink — Phase 1 Technical Analysis

> **Status:** Phase 1 analysis complete. Phase 2 implementation has started using these recommendations. This document remains the architecture reference for the project. It includes the three required critical architecture decisions in **Section 15** and the final decision table in **Section 16**.
>
> **Reference project inspected:** `/home/masarat/Downloads/Qeei/qeei_backend_admin`
>
> **Target project:** `/home/masarat/Desktop/TheaterLink` (no application code yet; only this analysis document and IDE metadata)
>
> **Verification note:** all findings below come from static inspection of the reference source. No test suite, migration, or runtime flow was executed during this phase.

---

## 1. Executive Summary

The reference project is a Laravel 12 application built with `nwidart/laravel-modules`. It already contains the foundations TheaterLink needs:

- A mature **OTP service** with cache-based and model-based flows.
- A full **media/file management subsystem** with polymorphic folders, versions, private/public visibility, and image variants.
- A multi-driver **authentication factory** on top of Sanctum, with separate guards for admins, individual users, valuer companies, and valuer team members.
- A **central admin RBAC model** (modules, permissions, roles, admins).
- A **tenant-scoped RBAC model** for valuer companies (tenant roles, tenant permissions, tenant admins).
- A complete **onboarding application and document review workflow** for valuer companies (registration, OTP, document review, approval, rejection, resubmission, reopening).
- A working **row-level tenant isolation pattern** based on `valuer_id` plus a global Eloquent scope.
- Shared response, repository, DTO/resource, translation, validation, activity-log, and location services.

The most important architectural finding is this:

> **The reference project does not actually run one database per tenant.** Although `App\Services\Tenant` contains a dynamic database-isolation layer driven by a central tenant API, the current architecture has merged the old `tenants` table into `valuers`, runs with `TENANCY_ENABLED=false`, and enforces tenant separation through `valuer_id` columns and `BelongsToCurrentValuer`. The real, working tenancy is **shared database + row-level isolation**, not physical database-per-tenant.

That is good news for TheaterLink. TheaterLink needs cross-tenant discovery and transactions (individuals browse all theaters' published events; groups request halls from theaters; theaters review requests from groups). A shared database with strict row-level isolation is a better fit for v1 than separate tenant databases. The recommendation in this document is to keep the **shared-database, strict-tenant-scope** model and replace the valuer-specific plumbing with a generic **`tenant_id` + `TenantMember`** foundation that supports both theater and theater-group tenants.

The reference also has several gaps that must **not** be copied blindly:

- Tenant roles and permissions exist but are largely not enforced on routes; most routes only check `abilities:valuer_admins`, which proves the token type, not the member's permission.
- `ValuerCatalogService` copies the entire permission/model/role catalog into every tenant, which is expensive and causes drift.
- Approval provisioning is partly outside the approval transaction.
- The generic repository filter builder accepts client-provided column names.
- Registration stores the full registration payload (including the plain password) in cache until OTP verification.
- Private documents on local disks are not consistently protected by signed URLs.
- There is no general in-app notification subsystem; only email/SMS/WhatsApp/push message senders exist, plus one budget notification class.

The recommended TheaterLink architecture is therefore:

1. **Central shared database** with a `tenants` table (`type = theater | group`) and a generic `tenant_id` boundary.
2. **One tenant-member identity model** for both theater and group teams, with per-tenant roles and a global permission catalog.
3. **Separate domain profiles** (`theaters`, `theater_groups`) and separate tenant application tables.
4. **Central admin** (existing `Modules/Admins`) for group application review.
5. **Backend-enforced isolation** through tenant context, global scopes, scoped route bindings, policies, and IDOR tests.
6. **Materialized hall time slots** for conflict-free reservations and **row-locked ticket-tier inventory** for oversell protection.

The three critical decisions requested after the initial analysis are resolved as recommendations in **Section 15**:

- **Multi-tenancy:** Hybrid — shared tenant infrastructure, shared database, separate theater/group domain modules.
- **Hall scheduling:** Temporary reservation lock — a pending request holds the slot for a configurable period, then expires automatically.
- **Ticket inventory:** Immediate confirmation — atomic inventory decrement at booking time, with a schema ready for temporary holds if external payment confirmation is added later.

The consolidated decision table is in **Section 16**.

---

## 2. Reference Project Architecture

### 2.1 Stack and module loading

From `composer.json`:

- PHP `^8.2`
- Laravel `^12.0`
- Laravel Sanctum `^4.3`
- `nwidart/laravel-modules` `^12.0`
- PHPUnit `^11.5.3`
- Larastan, Pint, Telescope, Scribe, Reverb

Modules are registered in `modules_statuses.json` and each module has its own `module.json`, `composer.json`, service provider, route service provider, migrations, translations, and tests. `composer.json` uses `wikimedia/composer-merge-plugin` to include `Modules/*/composer.json`, so every module owns its PSR-4 namespace.

Active modules in the reference include:

`Admins`, `Valuers`, `ValuerAdmins`, `Users`, `Settings`, `Categories`, `ManagePlans`, `Service`, `Tags`, `Client`, `MoneyCircle`, `Budgets`, `AssetDocs`, `BusinessCards`, `FAQ`, `StaticPages`, `Documentations`.

The relevant modules for TheaterLink are `Admins`, `Valuers`, `ValuerAdmins`, `Users`, `Settings`, and the shared `app/Services` infrastructure.

### 2.2 Module anatomy

A typical reference module follows this layout:

```text
Modules/{Module}/
├── app/
│   ├── DTOs/
│   ├── Enums/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   ├── Interfaces/
│   ├── Models/
│   ├── Policies/
│   ├── Providers/
│   │   ├── {Module}ServiceProvider.php
│   │   └── RouteServiceProvider.php
│   ├── Repositories/
│   ├── Resources/
│   └── Rules/
├── config/config.php
├── database/migrations/
├── database/seeders/
├── lang/{en,ar}/messages.php
├── routes/api.php
├── module.json
└── composer.json
```

The module service provider binds interfaces to repositories, loads migrations/translations/config, and registers the route provider. Example: `Modules/Valuers/app/Providers/ValuersServiceProvider.php` binds `ValuerRequestInterface`, `ValuerInterface`, and `ValuerContactMessageInterface` to their repositories.

Controllers are intentionally thin. They validate through Form Requests, call repository/interface methods, and format responses through API Resources or DTOs.

### 2.3 Request lifecycle and middleware

`bootstrap/app.php` defines:

- Global `SetLocaleFromHeader` middleware.
- Aliases for `abilities`, `ability`, `security.threat`, `security.nonce`, `x-tenant`, and `role`.
- A `tenant-group` middleware group containing `InitializeTenant`.
- A `security` middleware group with threat detection, IP filtering, rate limiting, JSON forcing, payload-size limiting, security headers, CORS, API-key validation, timestamp anti-replay, signature validation, and nonce validation.

Most module route providers wrap API routes with `['api', 'tenant-group']`, e.g. `Modules/Admins/app/Providers/RouteServiceProvider.php`:

```php
Route::middleware(['api', 'tenant-group'])->prefix('api')->name('api.')->group(...);
```

However, `TENANCY_ENABLED=false` in `.env`. Under that setting `InitializeTenant` simply forces the default database connection and passes the request through. In practice the `tenant-group` middleware is a no-op for the current deployment, and the real tenant isolation is the `valuer_id` row scope.

The main API route groups are:

- Central admin: `/api/v1/...` with `auth:sanctum` + `abilities:admins`.
- Valuer portal: `/api/v1/web/...` and `/api/v1/valuer/...` with `auth:sanctum`.
- Valuer admin workspace: `/api/v1/valuer-admin/...` with `x-tenant` and `auth:sanctum` + `abilities:valuer_admins`.
- Reference individual-user API: `/api/v1/mobile/users/...` with `auth:sanctum` + `abilities:users`. TheaterLink replaces this surface with the web-only user dashboard at `/api/v1/user/...`.

### 2.4 Response contract

`app/Http/Traits/HttpResponsesTrait.php` provides:

- `success($message, $data = [], $status = 200)` → `{ data, message, success: true }`
- `failure($message, $status = 400)` → `{ success: false, message }`
- `error($message, $status, $data)` → `{ success: false, message, data }`
- `errors($message, $data, $status = 422)` → `{ success: false, message, errors }`

Global exception rendering in `bootstrap/app.php` normalizes authentication, validation, model-not-found, and HTTP errors into the same shape. For TheaterLink this contract should be kept to minimize frontend integration differences.

### 2.5 Shared services

The reusable shared services live in `app/Services` and `app/Classes`:

| Area | Main classes |
|---|---|
| OTP | `App\Services\OtpService`, `App\Models\Otp` |
| Authentication | `App\Classes\Authentication\AuthController`, `App\Services\Authentication\AuthFactory`, `AuthInterface`, `SanctumRepository` |
| Messaging | `App\Services\Messages\MessageFactory`, `SmsMessageService`, `EmailMessageService`, `WhatsAppMessageService`, `PushNotificationService` |
| Media | `App\Services\MediaSystem\MediaService`, `FileService`, `Models\MediaFile`, `Models\MediaFolder`, `Traits\HasMediaFiles` |
| Repository helpers | `App\Services\ForRepository\RepositoryService`, `DatabaseStatementsInitTrait`, `TranslateRepositoryService`, `RelatedModuleRepositoryService` |
| Responses | `App\Http\Traits\HttpResponsesTrait` |
| Translations | `App\Http\Traits\TranslateTrait`, module `lang/{en,ar}` files, translation tables |
| Activity log | `App\Services\ActivityLog\ActivityLogService` |
| Security | `App\Services\Security\*` (API keys, signatures, rate limiting) |
| Countries/phone | `App\Services\CountryService`, `App\Traits\HasPhoneValidation`, `App\Constants\RegexPatterns`, `App\Rules\StrongPassword` |
| Tenancy (legacy) | `App\Services\Tenant\*` |

---

## 3. Reusable Components Inventory

### 3.1 Reuse directly

These are domain-neutral and fit TheaterLink with little or no change.

| Component | Exact reference | Why it is reusable | TheaterLink usage |
|---|---|---|---|
| OTP generation/verification | `app/Services/OtpService.php` | Cache-based registration OTP, model-based login/forgot-password OTP, hashed code, expiry, cooldown, attempts limit | Theater, group, and individual registration; login; password reset; phone change |
| OTP persistence | `app/Models/Otp.php`, `database/migrations/2026_02_12_072843_create_otps_table.php`, `2026_09_14_000000_make_otpable_nullable_in_otps_table.php` | Polymorphic `otpable`, nullable for pre-account registration | Same |
| Media orchestration | `app/Services/MediaSystem/MediaService.php` | `upload`, `uploadMany`, `updateFile`, `uploadToStorage`, `url`, `temporaryUrl`, `delete`, fluent `withVisibility`, `withTags`, `withType`, `withResize`, `withQuality`, `withExtension`, `withCategory`, `useDisk` | Theater logos/covers, event posters, group permits, registration documents, ticket attachments |
| File storage | `app/Services/MediaSystem/FileService.php` | Disk selection, visibility, image resizing, unique names, URL/temporary URL generation | Same |
| Media models | `MediaFile`, `MediaFolder`, `HasMediaFiles` | Polymorphic folders and files, versions, soft deletes, size recalculation | Same |
| SMS/email/WhatsApp/push | `app/Services/Messages/MessageFactory.php` and `types/*` | Single factory for outbound channels | OTP, application status, reservation decisions, booking confirmations |
| Authentication factory | `app/Services/Authentication/AuthFactory.php`, `AuthInterface`, `SanctumRepository` | Login, logout, refresh token, forgot/reset password, token abilities, login lockout | New `tenant_members` auth type; reuse admin/user auth unchanged |
| Dynamic guards | `app/Providers/AppServiceProvider.php::registerDynamicGuards()` | Creates session guards from `config/auth_guards.php`, enabling `attempt()` while Sanctum issues API tokens | Add a `tenant_members` guard without rewriting auth |
| API responses | `app/Http/Traits/HttpResponsesTrait.php` | Consistent response envelope | All TheaterLink APIs |
| Activity log | `app/Services/ActivityLog/ActivityLogService.php` | Polymorphic audit trail with subject/causer/properties | Application review, reservation transitions, team changes, event publication, booking cancellation |
| Country/city lookups | `Modules/Settings/app/Models/Settings.php`, `SettingsController`, `App\Services\CountryService` | Country/city/nationality data, phone code/length/currency | Theater and group addresses; theater currency defaults |
| Validation primitives | `App\Constants\RegexPatterns`, `App\Rules\StrongPassword`, `HasPhoneValidation` | Reusable name/phone/password rules | All registration and team-member forms |
| Locale middleware | `app/Http/Middleware/SetLocaleFromHeader.php` | `Accept-Language` negotiation between `en` and `ar` | All APIs |
| Repository query helper | `app/Services/ForRepository/RepositoryService.php`, `DatabaseStatementsInitTrait` | Pagination, eager loading, generic filters, media handling | Admin lists and simple CRUD, with filter whitelisting |

### 3.2 Reuse with adaptation

| Component | Exact reference | What must change |
|---|---|---|
| Registration OTP + temporary document flow | `Modules/Valuers/app/Http/Controllers/Valuer/ValuerController.php` (`register`, `verify`, `resendOtp`) and `ValuerDocumentController` (`persistDocuments`, `rehydrateDocuments`, `tempDirectoryFor`, `registrationCacheKey`) | Replace valuer-specific field names/types with theater and group registration DTOs. Do not cache the plain password; hash it before caching or store a draft row. Use a dedicated application service instead of controller static methods. |
| Application persistence | `Modules/Valuers/app/Repositories/ValuerRequestRepository.php::saveValidated()` | Create `tenant` + `theater_group_application` + owner member + documents. Keep the same OTP-after-validate pattern. |
| Document review | `ValuerRequestRepository::acceptDocument`, `rejectDocument`, `requestReupload`, `reuploadDocument`, `updateRequestStatus` | Rename `need_reupload` to `requires_resubmission`; add `reviewed_by`, `reviewed_at`, visible `review_feedback`, and document history. Ensure required-document completeness is checked even when the document collection is empty. |
| Approval/rejection/reopen | `ValuerRequestRepository::approve`, `reject`, `reopen` | Keep the state-machine ideas (`rejected_from_status`, rejection reason, reopen only rejected). Fix transaction boundaries and add row locks/idempotency. |
| Admin review controller | `Modules/Valuers/app/Http/Controllers/Admin/AdminController.php` | Copy the endpoint shape (list, show, documents, accept/reject/reupload, approve/reject/reopen, notes) but enforce permissions and use a `GroupApplicationPolicy`. |
| Tenant identification | `App\Services\Tenant\Middleware\XTenantMiddleware.php`, `TenantIdentificationService.php`, `ApiKeyService::encrypt/decrypt` | Do not hardcode `Valuer`. Resolve `TenantMember` → `Tenant`; derive the tenant from the authenticated token, not from the client header. Keep the header only as a routing hint that must match the token's tenant. |
| Tenant provisioning | `App\Services\Tenant\Services\TenantProvisioningService.php` | Generalize to both tenant types. Do not create physical databases. Provision profile, owner member, system roles, storage folder, and default settings in one idempotent transaction. |
| Tenant RBAC | `Modules/ValuerAdmins/*`, `ValuerCatalogService` | Use one generic `tenant_members`/`tenant_roles` model for both theaters and groups. Prefer a **global permission catalog + per-tenant roles** instead of copying the whole catalog into every tenant. Keep per-tenant role names and translations. |
| Tenant global scope | `app/Traits/BelongsToCurrentValuer.php` | Create a new `BelongsToTenant` trait for TheaterLink. Do not modify the reference trait; it is tightly coupled to `ValuerAdmins` and `valuer_id`. |
| Generic filter builder | `DatabaseStatementsInitTrait` | Whitelist filter keys per endpoint. The reference maps arbitrary request keys to SQL columns, which is unsafe for public/tenant-facing APIs. Use it only behind a Form Request with an explicit allowlist. |
| Translation helper | `TranslateRepositoryService` | The reference auto-discovers `{table}_translate` and calls `dd()` on exceptions. Use explicit translation relations in new repositories, or wrap the helper and remove `dd()` in the new project. |
| Media download | `ValuerDocumentController::download`, `MediaFile::getUrlAttribute` | Serve private documents through an authorized controller or signed temporary URL. Do not return a raw storage path or a public URL for permits/licenses. |
| Notifications | `MessageFactory` only | Add an in-app notification table/service; `MessageFactory` handles outbound email/SMS/push. There is no general notification service in the reference. |

### 3.3 Do not reuse

| Component | Reason |
|---|---|
| `Modules/Valuers` domain rules (valuation categories, Taqeem license types, subscription plans, valuer catalogue) | Different domain. Use only as a workflow blueprint. |
| `Modules/ManagePlans`, `Modules/Paymob`, subscription/payment models | TheaterLink explicitly has no online payments. |
| `Modules/MoneyCircle`, `Modules/Budgets`, `Modules/AssetDocs`, `Modules/BusinessCards`, `Modules/Tags`, `Modules/Service`, `Modules/FAQ`, `Modules/StaticPages`, `Modules/Client` | Unrelated to TheaterLink. |
| `App\Services\Tenant\Services\TenantApiClient`, `TenantResolver`, `TenantDTO`, `InitializeTenant` | Based on an external central tenant API and physical DB switching. Current project disables it and has moved away from the `tenants` table. Do not make TheaterLink depend on it. |
| `ValuerAdmins` tables/models directly | Domain name and columns are valuer-specific. Create generic tenant models; copying the table names would create a parallel valuer-shaped system. |

---

## 4. How Valuer Multi-Tenancy Actually Works

### 4.1 Historic design versus current code

The documentation in `docs/tenant-service.md` describes a physical database-per-tenant design:

- `TENANCY_ENABLED=true`
- `InitializeTenant` resolves a subdomain from the request host.
- `TenantResolver` calls `TenantApiClient::findBySubdomain()` on a central management API.
- `TenantDTO::configureDatabaseConnection()` rewrites `database.connections.tenant`.
- `InitializeTenant::isolateTenant()` changes database, session, cache, storage, logging, queue, and Redis prefixes.

The current code has moved away from that:

- `.env` has `TENANCY_ENABLED=false`.
- `Modules/Valuers/database/migrations/2026_10_21_000001_merge_tenants_into_valuers_table.php` adds `ulid`, `uuid`, and `subdomain` to `valuers`, backfills from `tenants`, and drops the `tenants` table.
- `TenantProvisioningService::provision()` writes workspace identity directly onto the `Valuer` row and explicitly states: “The Valuer record IS the tenant — ... No separate tenants table exists.”
- No code creates a separate database, database user, or tenant-specific database schema.
- `TenantIdentificationService` resolves an active `Valuer` by subdomain.

**Conclusion:** the reference's real tenancy model is **one shared database with row-level tenant columns**. The physical DB layer is legacy and not the source of truth.

### 4.2 Tenant identity

A valuer tenant is identified by:

- `valuers.id` (integer primary key)
- `valuers.ulid` (unique, used in `X-Tenant`)
- `valuers.uuid` (unique, used as the encryption secret for `X-Tenant`)
- `valuers.subdomain` (unique, used in `X-Subdomain`)
- `valuers.is_active` (workspace availability)
- `valuer_requests.status` (application lifecycle)

Public discovery of a tenant:

`GET /api/v1/valuer-admin?subdomain={subdomain}` returns:

```json
{
  "id": "<encrypted valuer ulid>",
  "domain": "workspace-subdomain",
  "name": "Valuer name",
  "logo": "..."
}
```

The `id` is produced by `ApiKeyService::encrypt($valuer->ulid, $valuer->uuid)`.

Protected valuer-admin requests require:

- `X-Tenant`: the encrypted ULID
- `X-Subdomain`: the workspace subdomain

`XTenantMiddleware` calls `TenantIdentificationService::resolve()`:

1. Load active `Valuer` by `subdomain`.
2. Require non-empty `uuid` and `ulid`.
3. Decrypt `X-Tenant` with the valuer's `uuid`.
4. Require the decrypted value to equal `valuer.ulid` using `hash_equals`.
5. If an authenticated admin is present, require the admin's `valuer_ulid` to equal the resolved valuer's ULID.
6. On success, store the tenant on `request()->attributes->get('tenant')` and `app('tenant.x_tenant')`.

### 4.3 Data isolation

The real isolation mechanism is `app/Traits/BelongsToCurrentValuer.php`:

- It registers a global Eloquent scope named `current_valuer`.
- The scope applies only when the authenticated Sanctum user is a `ValuerAdmins` instance.
- It resolves the current `valuer_id` from the authenticated admin.
- If the admin has no `valuer_id`, the scope adds `whereRaw('1 = 0')` — fail closed.
- Otherwise it qualifies the model's `valuer_id` column and adds `where valuer_id = ?`.

Models using the trait include:

- `ValuerAdmins`, `ValuerRole`, `ValuerPermission`, `ValuerModel`, `ValuerAdminRoles`, and translation/pivot models.
- `Service`, `Form`, `FormStep`, `FormQuestion`, `FormQuestionOption`, `ServiceBenefit`, `ServiceTranslation`, `FormTranslation`.
- `Tag`, `TagTranslate`.

Tenant-owned tables carry a `valuer_id` column. `Modules/ValuerAdmins/database/migrations/2026_10_21_000001_add_valuer_id_to_valuer_admins_module_tables.php` adds `valuer_id` to every module table and backfills it from parent rows.

`ValuerCatalogService` also gives each tenant its own copy of the global permission/model catalog:

- `provision($valuerId)` checks whether `valuer_permissions` already has rows for the tenant.
- If not, it copies permissions, translations, models, model-permission matrix, base roles, role translations, and role permissions.
- `ValuerAdminRepository::save()` calls `ValuerCatalogService::provision()` when creating a member.
- `TenantProvisioningService::provision()` calls it on approval.

This means role and permission rows are tenant-owned, not global.

### 4.4 Tenant-aware authentication

- `App\Providers\AppServiceProvider::registerDynamicGuards()` reads `config/auth_guards.php` and creates a session guard plus Eloquent provider for every actor type. This makes `Auth::guard($guard)->attempt()` work for login while Sanctum tokens are issued for API calls.
- `AuthController::login()` calls `AuthFactory`, then checks `belongsToActiveTenant($authData['user'])`.
- `belongsToActiveTenant()` only applies when the user object has a `valuer_ulid` property. It resolves the tenant from `X-Tenant`/`X-Subdomain` via `TenantIdentificationService` and requires a match.
- If the tenant check fails, the freshly minted token is deleted.
- Tokens are created with the guard name as a Sanctum ability: `createToken($guard.'-token', [$guard])`.
- Routes use `abilities:admins`, `abilities:users`, or `abilities:valuer_admins`.

The valuer portal login is separate: `ValuerController::login()` checks email/password and optionally checks `X-Subdomain`/`?subdomain`. If the header is absent, the workspace check is skipped. This is intentional for the pre-approval portal, but it is weaker than the valuer-admin login.

### 4.5 Provisioning

`TenantProvisioningService::provision(ValuerRequest $valuerRequest)`:

1. Opens a DB transaction.
2. Finds `$valuerRequest->valuerAccount`.
3. `assignWorkspaceIdentity()`:
   - Prefers `requested_subdomain`, otherwise slugifies `company_name`/`individual_name` plus request ID.
   - Generates a unique subdomain.
   - Writes `ulid`, `uuid`, `name`, `subdomain`, `is_active = true`.
4. Runs `ValuerModulePermissionsSeeder` and `ValuerRolesDatabaseSeeder`.
5. Calls `ValuerCatalogService::provision($valuer->id)` to copy permissions/models/roles.
6. Finds or creates the `ValuerAdmins` owner by email + valuer ULID.
7. Assigns all base role copies to the owner.

Then `ValuerRequestRepository::approve()` (outside that transaction) updates the request status, activates the valuer, assigns a trial plan, creates the valuer's root media folder, and migrates registration documents into it.

**Important:** `provision()` commits before the approval status update. If the later approval work fails, the workspace identity and owner remain provisioned while the application is not marked approved. TheaterLink must wrap the whole approval in one transaction or use an explicit, idempotent provisioning state.

### 4.6 Multi-tenancy findings and risks

| Finding | Impact | Recommendation for TheaterLink |
|---|---|---|
| Physical DB-per-tenant is legacy; actual model is shared DB + `valuer_id` | Misdocumentation and unnecessary complexity if copied | Use shared DB + `tenant_id`; do not import `TenantApiClient`/`InitializeTenant` |
| `X-Tenant` is publicly obtainable from a subdomain | The header is an identifier, not a secret | Derive tenant from the authenticated member; use the header only as a consistency check |
| Global scope depends on `ValuerAdmins` guard and `valuer_id` | Any model without the trait or `valuer_id` is unscoped | Apply a `BelongsToTenant` trait to every tenant-owned model; add a CI isolation test matrix |
| `ValuerCatalogService` copies the whole catalog per tenant | Bloat, slow provisioning, drift when platform adds permissions | Use one global permission catalog + per-tenant roles |
| `ValuerAdminRepository::getTablesCounts()` counts all `User` rows | Cross-tenant data leak in dashboard counts | Scope every count by tenant; never expose global counts to tenant dashboards |
| Roles/permissions are mostly decorative on routes | Any tenant member can call any tenant route guarded only by `abilities:valuer_admins` | Enforce permission policies on every write and sensitive read |
| `DatabaseStatementsInitTrait` maps arbitrary request keys to SQL columns | Potential information disclosure and query errors | Whitelist filters in Form Requests; do not pass raw query params |
| `TranslateRepositoryService` calls `dd()` on exception | A translation failure kills the response with debug output | Use explicit relations or a new production-safe translator |
| Private local media may be exposed as a normal URL | License/permit leakage | Authorized download endpoints or signed temporary URLs |
| Registration payloads are cached, including the plain password | Cache compromise exposes credentials | Hash before caching or store an encrypted registration draft |
| Approval provisioning is outside the approval transaction | Partial provisioning | One transaction or idempotent provisioning state machine |
| `every()` on an empty document collection is true | Approval can pass with no documents if the relation is empty | Require a non-empty, complete document set; validate required types |

---

## 5. Valuer Registration and Admin Review Workflow

This workflow is the primary blueprint for Theater Group registration and approval.

### 5.1 Stage A — Registration submission and OTP

Entry point: `POST /api/v1/web/register` → `Modules/Valuers/app/Http/Controllers/Valuer/ValuerController.php::register()`.

Flow:

1. Resolve `RegisterValuerRequest` through the service container and call `validateResolved()`.
2. Reject a phone that already exists in `valuers`.
3. Reject a phone that already has a pending registration cache entry.
4. Persist uploaded documents temporarily via `ValuerDocumentController::persistDocuments($phone, $documents)`:
   - Directory: `valuer_otp/{first 16 chars of sha256(phone)}` on the `local` disk.
   - Stores path, original name, and MIME type per document type.
5. Validate the requested subdomain with `ValuerRequestRepository::checkSubdomain()`.
   - If unavailable, return 422 with suggestions from `suggestDomains()`.
   - If available, store `requested_subdomain`; otherwise generate one with `generateUniqueSubdomain()`.
6. Cache the full validated payload under `valuer_registration_data:{phone}` for `otp.expiry_minutes`.
7. Generate a cache OTP with `OtpService::generateByCache($phone, 'valuer_registration', $validatedData)`.
8. Send the code through `MessageFactory::make('sms')->send(...)`.
9. Return the OTP in non-production only.

The cache TTL and OTP policy come from `config/otp.php`:

- `expiry_minutes` default 5
- `cooldown_seconds` default 60
- `max_attempts` default 3
- Code is hashed with `Hash::make`
- Non-production code is always `1234`

### 5.2 Stage B — OTP verification and application creation

Entry point: `POST /api/v1/web/verify` → `ValuerController::verify()`.

Flow:

1. Validate with `VerifyValuerOtpRequest`.
2. `OtpService::verifyByCache($phone, $otp, 'valuer_registration')` returns the cached registration payload.
3. Re-check subdomain availability (another applicant may have taken it).
4. Set `phone_verified_at = now()`.
5. Rehydrate the temporary files into `UploadedFile` instances with `ValuerDocumentController::rehydrateDocuments()`.
6. Call `ValuerRequestRepository::save($data)`.
7. Delete the temporary directory and the registration cache entry.
8. Return the application ID and requested subdomain.

`ValuerRequestRepository::saveValidated()` opens a transaction and:

- Sets `status = pending_review`.
- Generates a reference with `generateReference()`: `QEEI-{year}-{6-digit sequence}`.
- Hashes the password.
- Creates `valuer_requests` with the application fields.
- Creates an **inactive** `Valuer` account with:
  - `valuer_request_id`
  - contact person name as `name`
  - email, password, phone, `phone_verified_at`
  - `is_active = false`
  - `subdomain = requested_subdomain`
- Syncs the selected categories onto the valuer.
- For each document:
  - Creates `valuer_request_documents` with status `pending`.
  - Uploads the file through `MediaService::upload()` to `valuer_documents/{requestId}/{type}`.
  - Stores the returned media path on the document.

### 5.3 Application statuses

`Modules/Valuers/app/Enums/ValuerRequestStatusEnum.php`:

| Reference status | Meaning | TheaterLink equivalent |
|---|---|---|
| `pending_review` | Submitted, waiting for admin | Pending Review |
| `under_review` | Admin is reviewing, all documents pending | Pending Review (or show “Under Review”) |
| `need_reupload` | At least one document rejected or reupload requested | Requires Resubmission |
| `ready_for_approval` | All uploaded documents accepted | Ready for Approval (internal) |
| `approved` | Approved and provisioned | Approved |
| `rejected` | Rejected with reason | Rejected |
| `tenant_provisioning` | Internal provisioning state | Provisioning (internal) |
| `workspace_active` | Workspace live | Approved / Active |
| `suspended` | Workspace suspended after approval | Suspended |

`isRejectable()` returns false once the application is in `approved`, `tenant_provisioning`, `workspace_active`, or `suspended`. `approvedStatuses()` implements this. `internalStatuses()` hides provisioning/active from admin filter options.

### 5.4 Document statuses

`Modules/Valuers/app/Enums/DocumentStatusEnum.php`:

- `pending`
- `accepted`
- `rejected`
- `need_reupload`

Documents store:

- `document_type`
- `file_path`
- `status`
- `extracted_value`
- `admin_note`
- `rejection_reason`

`ValuerRequestRepository::updateRequestStatus()` recomputes the application status after every document action:

- All documents accepted → `ready_for_approval`.
- Any rejected or need-reupload → `need_reupload`.
- Otherwise → `under_review`.

This is the core resubmission rule. TheaterLink should keep the same behavior but:

- Require at least one document and all required document types present.
- Store document review history (who reviewed, when, previous status).
- Expose a visible `review_feedback` field separately from internal admin notes.
- Rename `need_reupload` to `requires_resubmission` in the public API (or map it).

### 5.5 Admin review endpoints

`Modules/Valuers/routes/api.php` central admin routes:

```text
GET    /api/v1/admin/valuer-requests
GET    /api/v1/admin/valuer-requests/new-valuers
GET    /api/v1/admin/valuer-requests/statuses
GET    /api/v1/admin/valuer-requests/contact-us-messages
GET    /api/v1/admin/valuer-requests/{id}
PUT    /api/v1/admin/valuer-requests/{id}
GET    /api/v1/admin/valuer-requests/{id}/documents
POST   /api/v1/admin/valuer-requests/{id}/documents/{docId}/accept
POST   /api/v1/admin/valuer-requests/{id}/documents/{docId}/reject
PUT    /api/v1/admin/valuer-requests/{id}/documents/{docId}/extracted-value
POST   /api/v1/admin/valuer-requests/{id}/documents/{docId}/request-reupload
POST   /api/v1/admin/valuer-requests/{id}/approve
POST   /api/v1/admin/valuer-requests/{id}/suspend
PATCH  /api/v1/admin/valuer-requests/{id}/toggle-active
POST   /api/v1/admin/valuer-requests/{id}/reject
POST   /api/v1/admin/valuer-requests/{id}/reopen
POST   /api/v1/admin/valuer-requests/{id}/note
```

All of these are protected only by `security`, `auth:sanctum`, and `abilities:admins`. There is no per-module permission check in the route definitions. A central admin with a valid admin token can call them regardless of role permissions. TheaterLink must add permission checks.

`AdminController` methods:

- `index()` — filters by status/category/search via `ValuerRequestRepository::data()`.
- `newValuers()` — pending/under-review/ready-for-approval list.
- `show()` — application with documents, account, categories, country, city, subscription.
- `documents()` — document list.
- `acceptDocument()`, `rejectDocument()`, `requestReupload()`, `updateExtractedValue()`.
- `approve()`, `reject()`, `reopen()`, `suspend()`, `toggleSuspend()`, `addNote()`.

`ValuerRequestRepository::approve()` enforces:

- Application not already approved.
- Every request document has status `accepted`.
- Application status is `ready_for_approval`.
- The valuer account exists and `phone_verified_at` is set.
- Then provisions and activates.

`reject()`:

- Uses `lockForUpdate`.
- Refuses if status is in `approvedStatuses()`.
- Stores `rejection_reason`.
- Stores `rejected_from_status` only if the current status is not already `rejected` (a repeated rejection never overwrites the original review state).
- Logs through `ActivityLogService`.

`reopen()`:

- Refuses approved statuses.
- Refuses if not `rejected`.
- Restores `rejected_from_status` or falls back to `under_review`.
- Keeps the rejection reason in history.

### 5.6 Post-approval provisioning and access

After approval:

- Request status becomes `approved`.
- `approved_at` is set.
- `admin_notes` is optionally stored.
- `valuers.is_active` becomes true.
- Trial plan is assigned if configured.
- Root media folder is created.
- Registration documents move from per-document folders to the valuer root folder and get category `registration_document`.

The valuer portal account (`valuers` table) can log in before approval because `ValuerController::login()` does not require `is_active`. The portal routes are limited to:

- `GET /v1/valuer/dashboard`
- `POST /v1/valuer/profile`
- `GET /v1/valuer/application`
- `GET /v1/valuer/documents`
- `GET /v1/valuer/documents/{id}/download`
- `POST /v1/valuer/documents/{id}/reupload`
- `GET /v1/valuer/review-status`
- `GET /v1/valuer/workspace`
- `POST /v1/valuer/contact-us`

There is no valuer dashboard/workspace route in that portal group. The actual workspace is the separate `ValuerAdmins` guard and `/v1/valuer-admin` routes. `TenantProvisioningService::resolveAdmin()` creates the owner with `is_active = true`, but that owner does not exist before approval. This is how the “pending applicant cannot access the main dashboard” rule is implemented: the portal account exists, the workspace member account does not.

`ValuerController::workspace()` returns:

- `workspace_status`: active/inactive based on `valuers.is_active`
- `domain`: `config('tenant.app_base_url')` + subdomain
- `subdomain`
- `rejection_reason`
- `application_status`

There is a small consistency issue: `ValuerController::workspaceDomain()` builds `base/subdomain`, while `ValuerRequestDTO::workspaceDomain()` builds `subdomain.base`. TheaterLink should define one workspace URL format and use it everywhere.

`ValuerDashboardResource` adds:

- identity and contact information
- application reference and submission date
- status and rejection reason
- document status (“3 of 5 approved”)
- workspace status
- required actions (reupload/missing documents)
- nested application

This is the exact pattern Theater Group needs for its registration status page.

### 5.7 Gaps in the reference workflow

The following must be corrected in TheaterLink:

1. **Approval transaction boundary** — provisioning commits before the application status update. Use one transaction or an explicit `provisioning → active` state machine.
2. **Empty documents** — `every()` on an empty collection returns true. Require required document types.
3. **Concurrent approvals** — no `lockForUpdate` in `approve()`. Two admins can provision twice. Lock the application row and make provisioning idempotent.
4. **Document review races** — document review and status recomputation are not wrapped in a transaction. Wrap and lock the application.
5. **Internal notes leakage** — `ValuerRequestDTO` includes `admin_notes`, and the portal application resource may expose it. Separate internal notes from visible review feedback.
6. **No notification service** — status changes are not pushed to a general notification system. Add one.
7. **No audit of all transitions** — only reject/reopen are logged in the repository. Log submit, document review, approve, resubmit, suspend, and reopen.
8. **No permission checks** — `abilities:admins` only. Add policies/permissions for review actions.
9. **No signed downloads** — document download returns a path; resources return public URLs. Use authorized downloads.
10. **Plain password in cache** — hash before storing, or store a DB draft with encrypted fields.

---

## 6. Admin Architecture

### 6.1 Central admin module

`Modules/Admins` owns the central super-admin domain:

- `Admins` model/table
- `Roles`, `RoleTranslate`
- `Permissions`, `PermissionsTranslate`
- `Models` (platform modules) and `ModelTranslate`
- `ModelPermissions` (module ↔ permission matrix)
- `RolePermissions` (role ↔ model ↔ permission)
- `AdminRoles` (admin ↔ role)

Routes (`Modules/Admins/routes/api.php`):

- `/api/v1/dashboard/admins`, `/roles`, `/models`, `/counts`, `/activity-logs`, `/profile`, `/change-password`
- `/api/v1/login`, `/logout`, `/forget-password`, `/verify-forget-password-otp`, `/reset-password`, `/resend-otp`
- Protected by `security`, `auth:sanctum`, `abilities:admins`.

`AdminsController` extends `App\Classes\Authentication\AuthController`, uses `AuthFactory` with `admins`, and returns `AdminResource`/`AdminLoginResource`.

### 6.2 How central permissions are modeled

`Modules/Admins/config/config.php` defines:

- Permissions: `fetch`, `create`, `read`, `update`, `delete`.
- Modules: `users`, `valuers`, `requests`, `reports`, `team_management`, `finance`, `reviews`.
- For every module, all five permissions are created as `<module>-<permission>` in `model_permissions`.

`ModulePermissionsSeeder` creates the permissions, modules, translations, and matrix. `RolesDatabaseSeeder` creates the base roles and grants every module permission to each base role.

`Admins::hasPermission($permission)`:

```php
return $this->roles()
    ->whereHas('permissions', function ($q) use ($permission) {
        $q->where('name', $permission);
    })
    ->exists();
```

`AdminsPolicy`, `RolesPolicy`, and other policies use `hasPermission()` with names such as `admins-fetch`, `roles_read`, etc.

**However**, `app/Providers/AuthServiceProvider.php` has the policy mapping commented out, and the admin routes do not call `authorize()`. The only enforced gate is the Sanctum `abilities:admins` check. The permission tables are therefore not currently an authorization boundary.

### 6.3 What to reuse for TheaterLink central admin

Reuse as-is:

- `Modules/Admins` models, migrations, seeders, auth controller, routes, resources.
- Central admin login/forgot-password/refresh/logout.
- Activity log endpoint.

Add:

- New permission modules for TheaterLink:
  - `group_applications`
  - `theaters`
  - `events` (optional moderation)
  - `reservations` (optional oversight)
  - `bookings` (optional oversight)
  - `reports`
- Permissions per module using the existing five-action convention, or more explicit actions for review (`approve`, `reject`, `request_resubmission`).
- A `GroupApplicationPolicy` that checks `group_applications-fetch/read/approve/reject/request_resubmission`.
- `Gate::policy()` registration in `AuthServiceProvider` or explicit `$this->authorize()` in controllers.
- An admin route group under `/api/v1/admin/group-applications` using the existing `abilities:admins` plus the new policy checks.
- Audit logging for every review transition.

### 6.4 Admin review route shape (recommended)

```text
GET    /api/v1/admin/group-applications
GET    /api/v1/admin/group-applications/statuses
GET    /api/v1/admin/group-applications/{id}
GET    /api/v1/admin/group-applications/{id}/documents
GET    /api/v1/admin/group-applications/{id}/documents/{docId}/download
POST   /api/v1/admin/group-applications/{id}/documents/{docId}/accept
POST   /api/v1/admin/group-applications/{id}/documents/{docId}/reject
POST   /api/v1/admin/group-applications/{id}/documents/{docId}/request-resubmission
POST   /api/v1/admin/group-applications/{id}/approve
POST   /api/v1/admin/group-applications/{id}/reject
POST   /api/v1/admin/group-applications/{id}/reopen
POST   /api/v1/admin/group-applications/{id}/notes
PATCH  /api/v1/admin/group-applications/{id}/suspend
```

This mirrors the valuer review API but uses dedicated permission checks and clearer status names.

---

## 7. OTP and Media Implementation Details

### 7.1 OTP service

`App\Services\OtpService` has two modes.

**Model-based** (for existing accounts):

- `generate($model, $type, $identifier)`
- `verify($model, $code, $type, $withToken = false)`
- Uses the model's `otps()` `morphMany` relation.
- Existing OTP: cooldown check; if locked after max attempts, regenerate immediately; otherwise regenerate after cooldown.
- Code generation: `random_int(1000, 9999)` in production, always `1234` otherwise.
- `Otp` model casts `code` to `hashed`, so `Hash::make`/`Hash::check` are automatic.
- `verify()` checks existence, verified flag, expiry, attempts, then `Hash::check`. On success, marks `is_verified = true`. With `withToken`, creates a Sanctum token.
- On failure, increments attempts and returns remaining attempts.

**Cache-based** (for pre-account registration):

- `generateByCache($identifier, $type, $extraData)`
- `verifyByCache($identifier, $code, $type)`
- Cache key: `otp:cache:{type}:{identifier}`.
- Stores hashed code, attempts, original data, last sent timestamp.
- Cooldown and max attempts are the same.
- On success, deletes the cache key and returns the original data.

**TheaterLink reuse:**

- Theater registration: `generateByCache($phone, 'theater_registration', $payload)` then a model OTP for login/password reset after account creation.
- Group registration: `generateByCache($phone, 'group_registration', $payload)`.
- Individual registration: reuse the `users` registration/`verifyAccount` pattern from `Modules/Users`.
- Add login/OTP rate limiting at the route level; the OTP service handles code attempts but not transport abuse.
- Do not cache plain passwords. Hash the password before putting the payload in cache, or store a registration draft row with encrypted fields.

### 7.2 Media service

`MediaService` is a fluent orchestration layer over `FileService` and the `media_files`/`media_folders` tables.

Main upload signature:

```php
$media = app(MediaService::class)
    ->useDisk('private')
    ->withVisibility('private')
    ->withType('registration_document')
    ->withCategory('license')
    ->upload($file, $relatable, 'permits', 'theater-groups/123/permits');
```

It:

- Uploads through `FileService::uploadWithMeta()`.
- Resolves or creates a polymorphic `MediaFolder` for the relatable model.
- Creates a `MediaFile` with disk, visibility, path, extension, MIME, original name, size, variants, tags, version, and current flag.
- Resets fluent options in `finally`.

`MediaFile::getUrlAttribute()` returns a normal URL for public/local files and an S3 temporary URL for private S3 files. For local private files, it does **not** produce a signed URL. TheaterLink must add an authorized download controller or a signed route for permits and registration documents.

`HasMediaFiles` gives models:

- `mediaFolders()` morphMany
- `mediaFiles()` hasManyThrough

The reference uses this for valuer logos/covers and documents. TheaterLink can use it for theater logos/covers, event posters, group permits, and document attachments.

### 7.3 Registration document temporary storage

`ValuerDocumentController`:

- `persistDocuments(string $phone, array $documents)` stores each file on the local disk under a hashed phone directory and returns metadata.
- `rehydrateDocuments(array $documents)` rebuilds `UploadedFile` instances with the original name/MIME.
- `registrationCacheKey(string $phone)` returns `valuer_registration_data:{phone}`.
- `tempDirectoryFor(?string $phone)` returns `valuer_otp/{sha256(phone) first 16 chars}`.

This is a good pattern for TheaterLink group registration, but:

- Use the application reference or a random draft UUID instead of a phone hash where possible.
- Store the draft in a DB table with a status, so a cache flush does not orphan files.
- Clean up abandoned drafts with a scheduled command.
- Validate MIME/size before persisting.
- Keep documents private and scan them if that becomes a business requirement.

---

## 8. Roles and Permissions Structure

### 8.1 Two independent RBAC systems exist

**Central admin RBAC** (`Modules/Admins`):

```text
admins
  └── admin_roles ──► roles
                        └── role_permissions ──► permissions
                                              └── models (platform modules)
```

- Permissions are global.
- Modules are global.
- Roles are global.
- Admin roles are global.
- Permission names: `<module>-<action>`, e.g. `users-fetch`.
- Enforcement: `Admins::hasPermission()` and policies, but policies are not currently registered/applied.

**Tenant RBAC** (`Modules/ValuerAdmins`):

```text
valuer_admins
  └── valuer_admin_roles ──► valuer_roles
                               └── valuer_role_permissions ──► valuer_permissions
                                                             └── valuer_models
```

- Every row has `valuer_id`.
- Every table is filtered by `BelongsToCurrentValuer`.
- The catalog is copied per tenant by `ValuerCatalogService`.
- Role names/descriptions are translated in `valuer_roles_translate`.
- Permissions are translated in `valuer_permissions_translate`.
- Modules are translated in `valuer_models_translate`.
- Enforcement: only `abilities:valuer_admins` on routes; policies exist (`ValuerAdminPolicy`, `ValuerRolePolicy`) but are not applied. So tenant roles do not actually restrict endpoints.

### 8.2 Recommended TheaterLink RBAC

Use **one global permission catalog** and **per-tenant roles**:

```text
permissions (global, platform-defined)
  id, name (e.g. halls.create), module, action, is_active

permission_translations
  permission_id, lang, title

tenant_roles
  id, tenant_id, name, is_system, is_active, timestamps, softDeletes

tenant_role_translations
  role_id, lang, name, description

tenant_role_permissions
  tenant_id, role_id, permission_id

tenant_members
  id, tenant_id, name, email, phone, password, is_owner, is_active, ...

tenant_member_roles
  tenant_id, member_id, role_id
```

Why this differs from `ValuerCatalogService`:

- Permissions are platform capabilities, not tenant data. Copying them per tenant creates thousands of duplicate rows and makes platform upgrades hard.
- Tenants need custom **roles**, not custom permission definitions. A tenant can create a “Box Office Manager” role and choose from the global permission list.
- Role names/descriptions can still be translated per tenant.
- The permission catalog can be seeded once and extended by deployment.
- `tenant_role_permissions.tenant_id` is denormalized for fast scoping and defense in depth.

Suggested permission names:

```text
theater.profile.read/update
team.fetch/create/read/update/delete
roles.fetch/create/read/update/delete
halls.fetch/create/read/update/delete
hall_schedules.fetch/create/read/update/delete
events.fetch/create/read/update/delete/publish
ticket_tiers.fetch/create/read/update/delete
reservations.fetch/read/create/approve/reject/cancel
bookings.fetch/read/cancel
reports.read
group.profile.read/update
group.application.read/resubmit
```

System roles seeded per tenant:

- `Owner` — implicit all permissions; cannot be removed or demoted below one owner.
- `Manager` — all operational permissions except ownership/team deletion.
- `Operations` — halls, schedules, events, reservations.
- `Box Office` — bookings and ticket tiers read/update.
- `Viewer` — read-only.
- `Group Coordinator` — group profile, team read, reservations create/read.

Enforcement:

- Policies: `HallPolicy`, `EventPolicy`, `TicketTierPolicy`, `ReservationPolicy`, `BookingPolicy`, `TeamMemberPolicy`, `RolePolicy`.
- Every policy checks `$member->tenant_id === $model->tenant_id` and `$member->hasPermission('...')`.
- Controllers call `$this->authorize()` or use `->middleware('can:...')`.
- Route middleware `abilities:tenant_members` only proves the actor type; it is not a permission check.
- Add tests that a member with only `events.read` cannot create a hall or approve a reservation.

---

## 9. Recommended TheaterLink Architecture

### 9.1 Tenancy decision: shared database, strict row-level isolation

**Recommendation: shared database, shared schema, `tenant_id` row-level isolation.**

Why:

1. **Cross-tenant marketplace** — individuals must browse published events across all theaters, and groups must browse all theaters. A shared database makes search, filtering, and pagination straightforward.
2. **Cross-tenant transactions** — a group reservation links a group tenant to a theater tenant; an individual booking links a user to a theater tenant and a ticket tier. Cross-tenant foreign keys and transactions are natural in one database.
3. **Central admin review** — one query can list all applications across tenants with status filters and counts.
4. **Operational simplicity** — one schema, one migration pipeline, one backup/restore process, one reporting database.
5. **Reference precedent** — the reference already works this way in practice.

Do not build a physical database-per-tenant layer for v1. If a future contract requires physical isolation, it can be added as a deployment variant behind the same repository/service interfaces, but it will require a central catalog and cross-database aggregation for discovery.

### 9.2 Tenant model

Use a generic tenant boundary plus type-specific profiles.

```text
tenants
  id, ulid, uuid, type (theater|group), name, subdomain,
  status (pending_review|active|suspended|rejected),
  approved_at, timestamps, softDeletes

theaters
  id, tenant_id (unique FK), name, about, logo_media_id, cover_media_id,
  email, phone, address, country_id, city_id, latitude, longitude,
  is_active, timestamps, softDeletes

theater_groups
  id, tenant_id (unique FK), name, about, logo_media_id,
  contact_person_name, email, phone, timestamps, softDeletes

theater_group_applications
  id, tenant_id (unique FK), reference (unique), group_name, group_info,
  contact_person_name, email, phone, license_media_id,
  status (pending_review|under_review|requires_resubmission|ready_for_review|approved|rejected|provisioning|suspended),
  rejection_reason, review_feedback, internal_notes,
  rejected_from_status, reviewed_by, reviewed_at, approved_at,
  timestamps, softDeletes

theater_group_application_documents
  id, application_id, document_type, media_id, original_name,
  status (pending|accepted|rejected|requires_resubmission),
  rejection_reason, admin_note, reviewed_by, reviewed_at, timestamps
```

Why a generic `tenants` table:

- One auth boundary for both theater and group teams.
- One `tenant_id` column convention for global scopes, indexes, and tests.
- One provisioning service with a type strategy.
- One notification target (`tenant_id`).
- One subdomain/workspace mechanism.
- Type-specific features are enforced by middleware/policies, not by duplicating the team/permission stack.

The group tenant exists from registration with `status = pending_review`, but it is **not a live workspace**. All workspace endpoints check `status = active`; only application/status endpoints are allowed while pending. This preserves the “pending group sees only the registration status page” requirement without creating a separate applicant identity system.

Alternative if the business insists that no tenant row exists before approval:

- Create `theater_group_applications` with an `applicant_user_id` and documents.
- On approval, create the tenant, profile, owner member, and roles.
- The applicant logs in through a separate applicant guard and sees status.
- This adds a second identity and token type. It is more complex and not recommended unless legally required.

### 9.3 Actors, guards, and tokens

| Actor | Table/model | Guard | Token ability | Route prefix |
|---|---|---|---|---|
| Super admin | `admins` / `Admins` | `admins` | `admins` | `/api/v1/admin` |
| Individual | `users` / `User` | `users` | `users` | `/api/v1/public` (read), `/api/v1/individual` (auth) |
| Theater member | `tenant_members` / `TenantMember` (tenant type theater) | `tenant_members` | `tenant_members` | `/api/v1/theater` |
| Group member | `tenant_members` / `TenantMember` (tenant type group) | `tenant_members` | `tenant_members` | `/api/v1/group` |
| Pending group owner | `tenant_members` (same) | `tenant_members` | `tenant_members` | `/api/v1/group/application` only |

Add `tenant_members` to `config/auth_guards.php`:

```php
'tenant_members' => [
    'model'    => \Modules\Tenancy\Models\TenantMember::class,
    'guard'    => 'tenant_members',
    'dtoClass' => \Modules\Tenancy\DTOs\TenantMemberDTO::class,
],
```

`AppServiceProvider::registerDynamicGuards()` will create the session guard/provider automatically, and `AuthFactory::make('sanctum', 'tenant_members')` can issue Sanctum tokens with the `tenant_members` ability.

Login requires a workspace:

```text
POST /api/v1/theater/login
POST /api/v1/group/login
body: { subdomain, email, password }
```

Rules:

- Resolve tenant by `subdomain` + expected type.
- Find member by `tenant_id` + `email`.
- Require `tenant.status` in `active|pending_review` (pending allowed only for the group application portal) and `member.is_active`.
- Issue a token bound to the member; the member row carries `tenant_id`.
- If the tenant is a pending group, the token is valid but `EnsureTenantActive` blocks all routes except the application/status group.
- Never let the client choose `tenant_id` in the body. The subdomain identifies the workspace for login only; after login, the token is authoritative.

Tenant resolution middleware:

```text
auth:sanctum (token → TenantMember)
  → EnsureTenantMemberActive
  → ResolveTenantContext (loads member.tenant, checks type/status, sets context)
  → EnsureTenantType:theater|group
  → EnsureTenantActive (except application routes)
  → permission/policy checks
```

If an `X-Tenant` header is kept for frontend compatibility, it is only a consistency check:

- Decode/validate it against the token's tenant.
- If it does not match, return 404 (not 403, to avoid tenant enumeration).
- Never use it to select the tenant for authorization.

### 9.4 Recommended module map

```text
app/
  Services/
    Tenancy/
      TenantContext.php              # request-scoped tenant/member
      TenantResolver.php             # token → tenant
      WorkspaceProvisioningService.php
      TenantMemberService.php
      TenantRoleService.php
    Notifications/
      NotificationService.php
    MediaSystem/                     # reuse
    Messages/                        # reuse
    ActivityLog/                     # reuse
  Traits/
    BelongsToTenant.php              # new, generic

Modules/
  Admins/                            # reuse central admin
  Users/                             # reuse individuals
  Settings/                          # reuse countries/cities
  Tenancy/                           # tenant members, roles, permissions, auth
  Theaters/                          # theater profile, halls, schedules, slots
  Events/                            # events, shows, ticket tiers, public catalog
  TheaterGroups/                     # group applications, review, profile, discovery
  Reservations/                      # hall reservation lifecycle
  Bookings/                          # individual ticket booking
```

If the team prefers fewer modules, merge `Events` into `Theaters` initially. Keep `Reservations` and `Bookings` separate because their concurrency and lifecycle rules are distinct.

Module ownership:

- **Tenancy** owns `tenants`, `tenant_members`, `tenant_roles`, `permissions`, role/member pivots, tenant auth, team management endpoints, and shared middleware.
- **Theaters** owns theater profile, halls, hall schedules, hall slot blocks, and hall time-slot generation.
- **Events** owns events, translations, shows, ticket tiers, and public event discovery.
- **TheaterGroups** owns group registration, group profile, application review (admin controller), group discovery, and group status endpoints.
- **Reservations** owns reservation requests and theater decisions, using halls/slots from Theaters and tenant IDs from Tenancy.
- **Bookings** owns bookings, booking items, and ticket inventory updates, using ticket tiers from Events.

Cross-module communication:

- Inject interfaces/services, not concrete models from another module.
- Example: `Reservations` depends on `HallAvailabilityService` from Theaters and `TenantContext` from app/Tenancy.
- Example: `Bookings` depends on `TicketInventoryService` from Events.
- Use domain events (`ReservationApproved`, `BookingConfirmed`, `ApplicationApproved`) for notifications and audit, dispatched after commit.

### 9.5 Database design (recommended)

All tenant-owned tables carry `tenant_id BIGINT NOT NULL` with a foreign key to `tenants.id`. Add composite indexes that start with `tenant_id`. Use `restrictOnDelete` for tenant parents and `cascadeOnDelete` for child rows where appropriate.

#### Tenancy tables

| Table | Key columns | Constraints/indexes |
|---|---|---|
| `tenants` | `id`, `ulid`, `uuid`, `type`, `name`, `subdomain`, `status`, `approved_at`, timestamps, `deleted_at` | `unique(ulid)`, `unique(uuid)`, `unique(subdomain)`, `index(type,status)`, `index(status)` |
| `theaters` | `tenant_id`, name, about, logo/cover media, email, phone, address, `country_id`, `city_id`, lat/lng, `is_active` | `unique(tenant_id)`, `index(country_id,city_id)`, FK country/city |
| `theater_groups` | `tenant_id`, name, about, logo, contact person, email, phone | `unique(tenant_id)` |
| `tenant_members` | `tenant_id`, name, email, phone, password, `is_owner`, `is_active`, last login, failed attempts, locked until | `unique(tenant_id,email)`, `unique(tenant_id,phone)`, `index(tenant_id,is_active)` |
| `permissions` | `name`, `module`, `action`, `is_active` | `unique(name)`, `index(module)` |
| `permission_translations` | `permission_id`, `lang`, `title` | `unique(permission_id,lang)` |
| `tenant_roles` | `tenant_id`, `name`, `is_system`, `is_active` | `unique(tenant_id,name)`, `index(tenant_id,is_active)` |
| `tenant_role_translations` | `role_id`, `lang`, `name`, `description` | `unique(role_id,lang)` |
| `tenant_role_permissions` | `tenant_id`, `role_id`, `permission_id` | `unique(role_id,permission_id)`, `index(tenant_id)` |
| `tenant_member_roles` | `tenant_id`, `member_id`, `role_id` | `unique(member_id,role_id)`, `index(tenant_id)` |
| `notifications` | `notifiable_type`, `notifiable_id`, `tenant_id`, `type`, `data`, `read_at` | `index(notifiable_type,notifiable_id,read_at)`, `index(tenant_id)` |

#### Group application tables

| Table | Key columns | Constraints/indexes |
|---|---|---|
| `theater_group_applications` | `tenant_id`, `reference`, `group_name`, `group_info`, `contact_person_name`, `email`, `phone`, `license_media_id`, `status`, `rejection_reason`, `review_feedback`, `internal_notes`, `rejected_from_status`, `reviewed_by`, `reviewed_at`, `approved_at` | `unique(tenant_id)`, `unique(reference)`, `index(status,created_at)`, FK `reviewed_by` → admins |
| `theater_group_application_documents` | `application_id`, `document_type`, `media_id`, `original_name`, `status`, `rejection_reason`, `admin_note`, `reviewed_by`, `reviewed_at` | `unique(application_id,document_type)`, `index(status)`, FKs |
| `theater_documents` (optional) | `tenant_id`, `document_type`, `media_id`, `status`, `note` | `index(tenant_id)`, optional only if theaters must upload compliance documents |

#### Theater operations tables

| Table | Key columns | Constraints/indexes |
|---|---|---|
| `halls` | `tenant_id`, `name`, `description`, `capacity`, `is_active` | `unique(tenant_id,name)`, `index(tenant_id,is_active)`, `check(capacity >= 0)` |
| `hall_schedules` | `tenant_id`, `hall_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration_minutes`, `price`, `currency`, `is_active` | `index(tenant_id,hall_id,day_of_week)`, `check(end_time > start_time)`, `check(slot_duration_minutes > 0)`, `check(price >= 0)` |
| `hall_slot_blocks` | `tenant_id`, `hall_id`, `starts_at`, `ends_at`, `reason`, `created_by_member_id` | `index(tenant_id,hall_id,starts_at,ends_at)`, `check(ends_at > starts_at)` |
| `hall_time_slots` | `tenant_id`, `hall_id`, `starts_at`, `ends_at`, `price`, `currency`, `status`, `active_reservation_id`, `hold_expires_at`, `occupancy_type`, `occupancy_id`, `version` | `unique(hall_id,starts_at)`, `index(tenant_id,hall_id,status,starts_at)`, `index(status,hold_expires_at)`, `check(ends_at > starts_at)` |

#### Event and ticket tables

| Table | Key columns | Constraints/indexes |
|---|---|---|
| `events` | `tenant_id`, `hall_id` (nullable), `category_id` (nullable), `poster_media_id`, `status`, `published_at` | `index(tenant_id,status,published_at)`, FK hall/category |
| `event_translations` | `event_id`, `tenant_id`, `lang`, `title`, `description`, `slug` | `unique(event_id,lang)`, `unique(tenant_id,lang,slug)` |
| `event_shows` | `tenant_id`, `event_id`, `hall_id`, `starts_at`, `ends_at`, `status` | `index(tenant_id,event_id,starts_at)`, `index(tenant_id,hall_id,starts_at)`, `check(ends_at > starts_at)` |
| `ticket_tiers` | `tenant_id`, `show_id`, `name`, `description`, `price`, `currency`, `quantity_total`, `quantity_sold`, `quantity_held`, `max_per_booking`, `sales_start_at`, `sales_end_at`, `is_active` | `unique(show_id,name)`, `index(tenant_id,show_id,is_active)`, `check(quantity_total >= 0)`, `check(quantity_sold >= 0)`, `check(quantity_held >= 0)`, `check(quantity_sold + quantity_held <= quantity_total)`, `check(price >= 0)` |
| `ticket_tier_translations` (optional) | `ticket_tier_id`, `lang`, `name`, `description` | `unique(ticket_tier_id,lang)` |

#### Reservation tables

| Table | Key columns | Constraints/indexes |
|---|---|---|
| `reservations` | `theater_tenant_id`, `group_tenant_id`, `hall_id`, `hall_time_slot_id`, `requested_by_member_id`, `starts_at`, `ends_at`, `price`, `currency`, `status`, `rejection_reason`, `approved_by_member_id`, `approved_at`, `cancelled_at`, `completed_at` | `index(theater_tenant_id,status,created_at)`, `index(group_tenant_id,status,created_at)`, `index(hall_time_slot_id)`, FKs |
| `reservation_status_histories` | `reservation_id`, `from_status`, `to_status`, `changed_by_type`, `changed_by_id`, `reason` | `index(reservation_id,created_at)` |

#### Booking tables

| Table | Key columns | Constraints/indexes |
|---|---|---|
| `bookings` | `tenant_id`, `user_id`, `reference`, `status`, `subtotal`, `total`, `currency`, `idempotency_key`, `booked_at`, `cancelled_at` | `unique(reference)`, `unique(user_id,idempotency_key)`, `index(user_id,status)`, `index(tenant_id,status,booked_at)` |
| `booking_items` | `tenant_id`, `booking_id`, `show_id`, `ticket_tier_id`, `quantity`, `unit_price`, `total` | `index(ticket_tier_id)`, `check(quantity > 0)` |
| `booking_status_histories` | `booking_id`, `from_status`, `to_status`, `changed_by_type`, `changed_by_id`, `reason` | `index(booking_id,created_at)` |

#### Media and central tables

Reuse the existing `media_folders`, `media_files`, `otps`, `admins`, `roles`, `permissions`, `models`, `settings`, and `users` tables. Do not duplicate them.

### 9.6 Tenant isolation implementation rules

1. **`tenant_id` is non-null on every tenant-owned table.** No nullable tenant scopes.
2. **`BelongsToTenant` global scope** on every tenant-owned model:
   - Resolves the current tenant from `TenantContext`.
   - If no tenant context and the model is tenant-owned, return no rows (`whereRaw('1 = 0')`).
   - Central admin queries explicitly opt out with `withoutGlobalScope` only inside central-admin services.
3. **Route model binding is tenant-scoped.** Do not let Laravel resolve `{hall}` by ID alone. Use explicit repository methods that add `tenant_id`, or a scoped binding:
   ```php
   Route::bind('hall', fn ($id) => Hall::where('tenant_id', tenant()->id)->findOrFail($id));
   ```
4. **Policies check ownership even for nested resources.**
   - `ReservationPolicy::approve($member, $reservation)` must check `$reservation->theater_tenant_id === $member->tenant_id`.
   - `BookingPolicy::cancel($user, $booking)` must check `$booking->user_id === $user->id`.
   - `HallPolicy` must check `$hall->tenant_id`.
5. **Never accept a tenant ID from the client for writes.**
   - The tenant comes from the token.
   - For reservations, `group_tenant_id` comes from the token; `theater_tenant_id` comes from the selected hall/event, and the hall must be verified as belonging to that theater.
   - For bookings, `tenant_id` comes from the show's event; the user ID comes from the token.
6. **Central admin bypass is explicit.**
   - The central admin guard has no tenant scope.
   - Any cross-tenant query in an admin service must be intentional and covered by tests.
7. **Isolation tests are mandatory.**
   - For every tenant endpoint, create two tenants A and B and assert A gets 404/403 for B's resource IDs.
   - Include nested IDs (`/halls/{A}/events/{B}`), document IDs, member IDs, reservation IDs, booking IDs, and ticket tier IDs.
   - Test that a group cannot read another group's applications or reservations.
   - Test that a theater cannot read another theater's halls/events/reservations.
   - Test that a pending group can only access application/status routes.
   - Test that individuals cannot access tenant dashboards.

### 9.7 Registration and provisioning workflows

#### Theater registration (no admin approval)

1. `POST /api/v1/theater/register`
2. Validate with `RegisterTheaterRequest`:
   - theater name, address, location (country/city), contact info
   - account credentials
   - optional documents
   - requested subdomain
3. Check unique subdomain/email/phone.
4. If OTP is required (recommended), cache the payload and send OTP; on verify, persist.
   If the business explicitly does not require OTP for theaters, skip OTP but keep `phone_verified_at = null` and restrict sensitive actions until verified.
5. In one transaction:
   - Create `tenants` with `type = theater`, `status = active`.
   - Create `theaters` profile.
   - Create owner `tenant_members`.
   - Create system roles and assign owner role (`ValuerCatalogService`-like provisioning, but generic).
   - Create the tenant storage root folder.
   - Log and notify.
6. Return token + tenant + member + permissions.

#### Theater group registration (admin approval)

1. `POST /api/v1/group/register` with group name, group info, contact info, license document, credentials, requested subdomain.
2. Validate and store the license temporarily (cache/DB draft); send OTP.
3. On `POST /api/v1/group/verify`:
   - Consume OTP.
   - In one transaction:
     - Create `tenants` with `type = group`, `status = pending_review`.
     - Create `theater_groups` profile.
     - Create `theater_group_applications` with `status = pending_review`.
     - Create owner `tenant_members`.
     - Create application document rows and upload the license via `MediaService` (private disk).
     - Seed system roles but do not activate workspace access.
     - Log and notify central admins.
   - Return application reference and a token (or require login).
4. Pending group login:
   - `POST /api/v1/group/login` with subdomain/email/password.
   - Token issued.
   - All dashboard routes blocked by `EnsureTenantActive`; only `/api/v1/group/application/*` allowed.
5. Status page endpoints:
   - `GET /api/v1/group/application` — status, reference, submitted date, feedback, rejection reason, required actions.
   - `GET /api/v1/group/application/documents` — document statuses and feedback.
   - `POST /api/v1/group/application/documents/{id}/resubmit` — replace a document.
   - `GET /api/v1/group/application/workspace` — workspace status and domain.
   - `GET /api/v1/group/application/support` — support contact info (or static config).
6. Admin review:
   - `GET /api/v1/admin/group-applications` with filters.
   - Document accept/reject/request resubmission.
   - Application approve/reject/reopen/notes.
   - Every action checks the admin permission and writes an activity log.
7. Approval transaction:
   - Lock application row.
   - Verify `status = ready_for_review`, all required documents accepted, owner phone verified.
   - Set `tenants.status = active`, `approved_at`.
   - Set application `status = approved`.
   - Ensure system roles and owner role assignment.
   - Create storage root folder if not created.
   - Commit.
   - After commit: notify group members and central admin; queue any external messages.
8. Rejection:
   - Lock application.
   - Refuse if already approved.
   - Store `rejection_reason`, `review_feedback`, `rejected_from_status`.
   - Set `status = rejected`.
   - Keep tenant in `rejected` or `pending_review`? Recommended: `tenants.status = rejected` and block all workspace routes; the owner can still access the status page read-only. If the business allows resubmission after rejection, add a `reopen` action that returns the application to `ready_for_review` or `pending_review` and sets tenant back to `pending_review`.
9. Resubmission:
   - Admin requests document resubmission → document `requires_resubmission`, application `requires_resubmission`.
   - Group uploads a replacement → document `pending`, application `pending_review`.
   - Admin re-reviews; on all accepted → `ready_for_review`.
   - Keep prior document rows/versions for audit (do not overwrite history; mark old version superseded).

#### State machine

```text
Group:
pending_review ──► under_review ──► ready_for_review ──► approved
      │                 │
      │                 └──► requires_resubmission ──► pending_review
      │
      └──► rejected ──► (reopen) ──► pending_review/under_review
approved ──► suspended ──► active (optional reactivation)
```

Public statuses exposed to the group:

- `pending_review`
- `approved`
- `rejected`
- `requires_resubmission`

Internal states `under_review`, `ready_for_review`, `provisioning`, `suspended` can be mapped to these four or shown as sub-stages.

### 9.8 Hall scheduling and concurrency

#### Schedule model

- A hall has recurring weekly availability windows (`hall_schedules`): day of week, start/end time, slot duration, price, currency.
- A hall can have explicit unavailable blocks (`hall_slot_blocks`): maintenance, private use, holidays.
- The system materializes concrete `hall_time_slots` for a rolling horizon (e.g., 90 days) from schedules, subtracting blocks.
- Event shows reserve slots too. When a show is scheduled, the overlapping slots become `unavailable`/`reserved` for events, so group reservations cannot collide with performances.

#### Slot generation

A scheduled command (`hall:generate-slots`) runs daily:

1. For each active hall, read schedules.
2. Generate slots for the next N days.
3. Apply `hall_slot_blocks`.
4. Apply `event_shows` that occupy the hall.
5. Upsert slots with `unique(hall_id, starts_at)`.
6. Never overwrite a slot that is `held` or `reserved`; log conflicts.

The command must be idempotent. It can also be triggered when a theater changes a schedule or creates an event show.

#### Reservation request algorithm

```text
DB::transaction:
  slot = HallTimeSlot::whereKey($slotId)
      ->where('tenant_id', $theaterTenantId)
      ->lockForUpdate()
      ->firstOrFail()

  abort unless slot.status === 'available'
  abort if slot.starts_at <= now()

  slot.update(status = 'held', hold_expires_at = now() + holdMinutes)

  reservation = Reservation::create([
      theater_tenant_id, group_tenant_id, hall_id, hall_time_slot_id,
      starts_at, ends_at, price, currency, status = 'pending',
      requested_by_member_id, hold_expires_at
  ])

  slot.update(active_reservation_id = reservation.id)

commit
```

- `lockForUpdate` serializes concurrent requests for the same slot.
- The unique `(hall_id, starts_at)` constraint prevents duplicate slot rows.
- Only one pending/approved reservation can hold a slot.
- A rejected/cancelled/expired reservation releases the slot back to `available` in a transaction.
- A scheduled `reservations:expire-holds` job releases expired holds; see Section 15.2.
- Approval locks the reservation and slot; sets reservation `approved`, slot `reserved`, clears `hold_expires_at`.
- Cancellation:
  - Pending → cancelled; slot `available`.
  - Approved → cancelled (if allowed); slot `available`; notify both tenants.
- Completion:
  - Approved → completed after the slot end; slot remains `reserved` historically or becomes `available` for future generation. Keep history.
- Rejection:
  - Pending → rejected; slot `available`; store reason; notify group.
- Prevent double approval: check reservation status inside the transaction and use `lockForUpdate`.

#### Overlap protection beyond slots

If the business allows arbitrary start/end times instead of generated slots:

- Normalize all requests to slot boundaries, or
- Use a transaction with `lockForUpdate` on the hall row plus an overlap query:
  ```sql
  WHERE hall_id = ?
    AND status IN ('held','reserved')
    AND starts_at < :requested_end
    AND ends_at > :requested_start
  ```
- MySQL has no exclusion constraints, so the hall row lock is the serialization point. Materialized slots are safer and recommended.

### 9.9 Ticket inventory and overselling prevention

#### Inventory model

- `ticket_tiers.quantity_total` — total tickets offered.
- `ticket_tiers.quantity_sold` — sold/confirmed tickets.
- `ticket_tiers.quantity_held` — reserved for future hold mode; `0` in the v1 immediate-confirmation flow.
- `quantity_available` — derived as `quantity_total - quantity_sold - quantity_held`; do not use a single mutable available counter as the source of truth.
- `max_per_booking` — optional per-user/per-booking limit.
- `sales_start_at`/`sales_end_at` — sale window.
- `ticket_tiers.quantity_total` sum for a show must not exceed the hall capacity (validate in service).

#### Booking algorithm

```text
DB::transaction:
  // Idempotency: return existing booking for the same user + key
  existing = Booking::where(user_id, idempotency_key)->lockForUpdate()->first()
  return existing if present

  foreach requested tier:
      tier = TicketTier::whereKey($tierId)
          ->where('tenant_id', $showTenantId)
          ->lockForUpdate()
          ->firstOrFail()

      abort unless tier.is_active
      abort unless now between sales_start_at and sales_end_at
      abort if qty > tier.max_per_booking
      abort if tier.quantity_sold + tier.quantity_held + qty > tier.quantity_total

      tier.increment('quantity_sold', qty)

  create booking + booking_items
  commit
```

Alternative for high throughput: a conditional atomic update per tier:

```sql
UPDATE ticket_tiers
SET quantity_sold = quantity_sold + :qty
WHERE id = :id
  AND is_active = 1
  AND quantity_sold + quantity_held + :qty <= quantity_total
```

Check the affected row count; if 0, fail with “not enough tickets.” This avoids long row locks while remaining atomic. Either approach is safe if wrapped in a transaction and the DB check constraint is present.

Additional rules:

- Booking status is `confirmed` immediately, because there is no online payment. External payment is handled outside the platform.
- Store `unit_price` and `total` snapshots on `booking_items`; never recompute historical totals from current tier prices.
- Cancellation returns inventory only if the business allows it; define a cancellation policy. If tickets are non-refundable, do not return inventory automatically.
- Add a DB check constraint `quantity_sold >= 0 AND quantity_held >= 0 AND quantity_sold + quantity_held <= quantity_total`.
- Add a unique constraint on `bookings.reference`.
- Add an idempotency key from the client or generate one server-side; prevent duplicate submissions on retry.
- Do not use `decrement`/`increment` without a condition inside the same locked transaction; always verify the new value. See Section 15.3 for the full concurrency analysis.

#### Ticket tier and show validation

- Sum of active tier quantities for a show ≤ hall capacity.
- A tier cannot be deleted if it has bookings; deactivate it instead.
- A show cannot be deleted if it has confirmed bookings; cancel it.
- An event cannot be unpublished while it has upcoming confirmed shows/bookings unless the business allows it. At minimum, return a clear conflict error and require cancellation.

### 9.10 Notifications

The reference has no general notification service. TheaterLink needs:

```text
notifications
  id, notifiable_type, notifiable_id, tenant_id, type, data, read_at, timestamps
```

`NotificationService`:

- `sendToMember(TenantMember $member, string $type, array $data, array $channels = ['database'])`
- `sendToTenant(Tenant $tenant, string $type, array $data, array $channels = ['database'])`
- `sendToUser(User $user, ...)`
- `sendToAdmins(...)`
- Channels: `database` (in-app), `mail` (via `MessageFactory::make('email')`), `sms`, `push`.
- Queue outbound channels; do not block the HTTP request.
- Dispatch after commit (`DB::afterCommit`) so a rolled-back transaction never sends a false notification.

Events to notify:

- Group application submitted → central admins.
- Document accepted/rejected/resubmission requested → group owner/members with permission.
- Application approved/rejected/reopened → group owner.
- Reservation requested → theater members with `reservations.approve`.
- Reservation approved/rejected/cancelled → group members.
- Booking confirmed/cancelled → individual.
- Team member invited/activated/deactivated → member/owner.

Keep the notification payload small and localized on read; store a `type` + JSON data, and render translations from the module lang files.

### 9.11 Localization

- Every module has `lang/en/messages.php` and `lang/ar/messages.php`.
- Use `__('module::messages.key')` for all user-facing strings.
- Use translation tables for entity content:
  - `event_translations` (title, description, slug)
  - `ticket_tier_translations` (name, description)
  - `theater`/`group` name/about if they need multilingual display
- Use `HasTranslation` for status enums, or map status labels in resources.
- Keep API error messages in translation keys; do not hardcode English in controllers.
- Test both locales for validation, status labels, and enum labels.

### 9.12 Security and privacy

- **Tenant context from token only.** Never authorize from `X-Tenant`, `tenant_id` body fields, or route IDs alone.
- **Private media.** Group permits, theater registration documents, and internal documents live on a private disk. Downloads go through a controller that checks membership/permission and returns a temporary URL or stream. Do not expose raw storage paths.
- **Admin document preview.** Central admins can preview/download via an authorized admin route; log access.
- **Mass assignment.** Use Form Requests with explicit fields; never `$request->all()` into `updateOrCreate` without validation.
- **Password safety.** Strong password rule; hash; never log; never cache plaintext.
- **Rate limiting.** Login, OTP send/verify, registration, and booking endpoints need route-level throttles in addition to OTP attempt limits.
- **Enumeration.** Return 404 for resources outside the tenant; do not reveal whether another tenant's ID exists.
- **Dashboard counts.** Scope all counts to the tenant; do not copy `User::count()` from the reference.
- **Sensitive fields.** Hide passwords, remember tokens, license internals, internal notes, storage credentials, and database details in resources.
- **Audit.** Log application review, reservation transitions, ticket inventory changes, team/role changes, and suspensions.
- **Database constraints.** Use FKs, unique composite keys, check constraints, and indexes. Do not rely only on application checks.
- **Transactions.** Wrap multi-step writes; use `lockForUpdate`; dispatch notifications after commit.

---

## 10. Reuse / Modify / New Implementation Matrix

### 10.1 Reuse directly

| Component | Reference | Action |
|---|---|---|
| OTP service | `app/Services/OtpService.php` | Use as-is |
| OTP model/table | `app/Models/Otp.php`, `otps` migration | Use as-is |
| Media service/files/folders | `app/Services/MediaSystem/*` | Use as-is |
| Messaging factory | `app/Services/Messages/*` | Use as-is |
| Auth factory/driver | `app/Services/Authentication/*` | Use as-is; add actor type |
| Dynamic guards | `app/Providers/AppServiceProvider.php` | Use as-is; add config |
| Response trait | `app/Http/Traits/HttpResponsesTrait.php` | Use as-is |
| Activity log | `app/Services/ActivityLog/*` | Use as-is |
| Countries/cities | `Modules/Settings/*`, `CountryService` | Use as-is |
| Validation rules | `RegexPatterns`, `StrongPassword`, `HasPhoneValidation` | Use as-is |
| Locale middleware | `SetLocaleFromHeader` | Use as-is |
| Central admin module | `Modules/Admins/*` | Use as-is; add permissions/routes |
| Individual users module | `Modules/Users/*` | Use as-is; adapt fields if needed |

### 10.2 Reuse with modification

| Component | Reference | Modification |
|---|---|---|
| Registration OTP flow | `ValuerController::register/verify/resendOtp` | Generalize to theater/group registration; no plaintext password in cache; DB draft or encrypted cache |
| Temporary documents | `ValuerDocumentController` | Generalize; use application draft; clean up abandoned files |
| Application repository | `ValuerRequestRepository` | New `TheaterGroupApplicationRepository`; same status/doc logic; fix empty-doc and transaction issues |
| Review controller | `Valuers\Admin\AdminController` | New `TheaterGroups\Admin\ApplicationController`; enforce permissions; add document history |
| Tenant identification | `XTenantMiddleware`, `TenantIdentificationService` | New `ResolveTenant`; token-driven; header only as consistency check |
| Tenant provisioning | `TenantProvisioningService` | New `WorkspaceProvisioningService`; type strategy; idempotent; one transaction |
| Tenant RBAC | `ValuerAdmins/*`, `ValuerCatalogService` | New `Tenancy` module; global permissions + tenant roles; enforce policies |
| Tenant global scope | `BelongsToCurrentValuer` | New `BelongsToTenant`; do not modify reference |
| Repository filter helper | `DatabaseStatementsInitTrait` | Use only with whitelisted filters; avoid raw client keys |
| Translation helper | `TranslateRepositoryService` | Prefer explicit relations; remove `dd()` behavior in new code |
| Media download | `ValuerDocumentController::download`, `MediaFile::getUrlAttribute` | Authorized private download |

### 10.3 New implementation

| Component | Why new |
|---|---|
| Generic `tenants` table with `type` | Reference has no generic tenant entity; valuer is the tenant |
| Generic `tenant_members` and tenant auth | Reference has valuer-specific `ValuerAdmins` |
| Generic tenant roles/permissions with global catalog | Reference copies catalog per tenant; not sustainable |
| Theater profile, halls, schedules, slot generation | No equivalent in reference |
| Event/show/ticket-tier management | No equivalent in reference |
| Public event discovery/search | No equivalent in reference |
| Reservation request/approval lifecycle | No equivalent in reference |
| Ticket booking and inventory concurrency | No equivalent in reference |
| In-app notifications | Reference only has outbound message senders |
| Group application status portal (adapted) | Reference has valuer portal; domain-specific |
| Group review admin routes | Reference has valuer review; domain-specific |
| Tenant isolation test matrix | Reference lacks comprehensive isolation tests |
| Permission enforcement policies | Reference policies mostly unused |
| Arabic/English translations for all new modules | New domain content |
| API documentation / Postman for new endpoints | New API surface |

### 10.4 Components not to carry over

`Paymob`, `ManagePlans`, subscriptions, invoices, `MoneyCircle`, `Budgets`, `AssetDocs`, `BusinessCards`, `Service`, `Tags`, `FAQ`, `StaticPages`, `Client`, `TenantApiClient`, `TenantResolver`, `InitializeTenant`, `TenantDTO`.

---

## 11. Recommended Implementation Phases

### Phase 2 — Core architecture, auth, registrations, tenancy

1. Scaffold TheaterLink from the reference foundations:
   - Laravel 12 + nwidart modules
   - Copy shared services (`OtpService`, `MediaSystem`, `Messages`, `ForgeRepository` helpers, `ActivityLog`, `Settings`/`CountryService`)
   - Keep `Modules/Admins` and `Modules/Users`
2. Build `app/Services/Tenancy`:
   - `TenantContext`, `TenantResolver`, `WorkspaceProvisioningService`
   - `BelongsToTenant` trait
   - `ResolveTenant`, `EnsureTenantActive`, `EnsureTenantType` middleware
3. Build `Modules/Tenancy`:
   - `tenants`, `tenant_members`, `tenant_roles`, `permissions`, pivots
   - Tenant auth (`tenant_members` guard), login/logout/refresh/forgot password
   - Team management endpoints (list, invite/create, update, activate/deactivate, assign roles, remove)
   - Role management endpoints (list, create, update, delete, permissions)
4. Implement theater registration (no admin approval) and individual registration (reuse `Users`).
5. Implement group registration + OTP + pending status page endpoints.
6. Add translations for auth, tenancy, registration, team, roles.
7. Add feature tests for auth, OTP, tenant member isolation, and pending group access.

### Phase 3 — Admin review, documents, notifications, provisioning

1. Central admin permissions for `group_applications`.
2. Group application list/show/filter/status endpoints.
3. Document preview/download (authorized, private).
4. Document accept/reject/request resubmission.
5. Application approve/reject/reopen/notes.
6. Approval provisioning transaction + idempotency.
7. In-app notifications + email/SMS via `MessageFactory`.
8. Activity logging for every transition.
9. Tests: pending → resubmission → approved; rejection/reopen; duplicate approval; unauthorized admin; cross-tenant document access.

### Phase 4 — Theater management

1. Theater profile management.
2. Hall CRUD + capacity validation.
3. Hall weekly schedules + unavailable blocks.
4. Hall time-slot generation command + manual regeneration.
5. Event CRUD + translations + poster uploads.
6. Event shows + hall assignment + overlap checks.
7. Ticket tier CRUD + inventory validation + sales window.
8. Publish/unpublish/cancel event rules.
9. Theater dashboard metrics scoped to tenant.
10. Tests: schedule generation, conflict detection, ticket tier capacity, tenant isolation.

### Phase 5 — Group management and reservations

1. Approved group profile management.
2. Group team/roles (reuse Tenancy).
3. Theater discovery: list active theaters, halls, available slots.
4. Reservation request creation with slot locking.
5. Group reservation list/status/cancel.
6. Theater reservation inbox.
7. Approve/reject with reason.
8. Reservation lifecycle: pending → approved/rejected/cancelled/completed.
9. Notifications for both sides.
10. Tests: double booking, concurrent requests, unauthorized theater/group access, status transitions.

### Phase 6 — Individual events and tickets

1. Public event discovery: theaters, events, search/filter, event details.
2. Published shows and available ticket tiers.
3. Booking creation with idempotency and row-locked inventory.
4. Booking confirmation response with ticket information.
5. Booking list/detail/cancel (policy-dependent).
6. Inventory release rules.
7. Notifications.
8. Tests: overselling prevention, concurrent bookings, max-per-booking, sales window, tenant isolation, public visibility rules.

### Cross-cutting throughout

- Arabic/English translations.
- API Resources/DTOs and response consistency.
- Form Requests for every write.
- Policies/permissions for every sensitive action.
- DB transactions/locks/constraints.
- API documentation and Postman collections.
- Isolation and concurrency tests.

---

## 12. Test Plan (Minimum)

### 12.1 Tenant isolation

For each tenant-owned resource, create theater A, theater B, group A, group B, user A, user B:

- Theater A cannot read/update/delete theater B's halls, schedules, events, shows, tiers, reservations.
- Group A cannot read/update/cancel group B's reservations or applications.
- Group A cannot read a theater's private reservation notes or another group's requests.
- Individual A cannot read individual B's bookings.
- Tenant member cannot access another tenant's member/role IDs.
- Pending group member gets 403 on dashboard routes and 200 on application/status routes.
- Central admin can access cross-tenant review data; a non-admin cannot.

Use nested-resource ID substitution to catch route-binding leaks.

### 12.2 Registration and approval

- OTP expiry, cooldown, max attempts, replay.
- Duplicate phone/email/subdomain.
- Document MIME/size validation.
- Pending → accepted docs → ready → approved.
- Rejected document → requires resubmission → reupload → pending review.
- Application rejection with reason; reopen only rejected.
- Approval blocked if documents incomplete or phone unverified.
- Duplicate approval does not provision twice.
- Notification dispatched after commit, not on rollback.
- Audit log written for each transition.

### 12.3 Reservations

- Slot unavailable/released correctly.
- Two concurrent requests for the same slot: one succeeds, one fails.
- Approve only pending; reject/cancel releases slot.
- Overlapping event show blocks group reservation.
- Unauthorized group cannot reserve another group's slot; unauthorized theater cannot approve another theater's reservation.
- Price snapshot is stored.

### 12.4 Ticket inventory

- Concurrent bookings cannot exceed `quantity_total`.
- `max_per_booking` enforced.
- Sales window enforced.
- Idempotent retry returns the same booking.
- Cancellation policy releases or retains inventory as defined.
- Tier total ≤ show/hall capacity.
- Price snapshot is immutable.

### 12.5 Permissions

- Member with `events.read` cannot create/update/delete events.
- Member with `reservations.create` cannot approve.
- Member with `team.manage` can invite; member without cannot.
- Owner has all permissions; non-owner cannot remove the last owner.
- Central admin review requires `group_applications.approve`.

### 12.6 Localization

- `Accept-Language: ar` returns Arabic messages and status labels.
- `Accept-Language: en` returns English.
- Validation errors are translated.
- Enum labels are translated.

---

## 13. Risks and Open Decisions

These decisions affect schema or behavior and should be confirmed before implementation. The three critical decisions requested for this phase are analyzed in depth in **Section 15** and summarized in **Section 16**; the list below is the remaining business-confirmation checklist.

1. **Physical database isolation** — recommended shared DB. If a hard requirement for separate databases exists, the architecture must add a central catalog and cross-database aggregation for discovery.
2. **Theater approval** — the brief says theaters do not require admin approval. Should theaters still have a verification/suspension status? Recommendation: workspace active immediately, but keep `tenants.status` and a suspension mechanism.
3. **OTP for theaters** — the brief only explicitly requires OTP for individuals. Recommendation: require OTP for theater/group owner phone verification as well; if not, restrict sensitive actions until verified.
4. **Pending reservations** — should a pending request block the slot (recommended: yes, status `held`), or should multiple groups be allowed to request the same slot and the theater choose one? The brief says prevent conflicting reservations; blocking on request is the safest.
5. **Reservation duration** — are reservations always slot-aligned, or arbitrary start/end times? Recommendation: slot-aligned; simpler and safer.
6. **Event shows and hall reservations** — event shows should block hall slots. Confirm that a theater cannot schedule a show over a confirmed group reservation and vice versa.
7. **Ticket holds** — no online payment means no long checkout. Recommendation: booking confirms immediately and decrements inventory; optional short hold only if the business adds payment later.
8. **Cancellation/refund policy** — not specified. Define whether ticket cancellation returns inventory and whether reservation cancellation has fees (external).
9. **Group events/shows** — the brief only describes groups requesting hall reservations. If groups also publish events/tickets, add a group event model or reuse theater events with a group tenant.
10. **Seat-level ticketing** — the brief describes tier quantities, not seat maps. Recommendation: tier inventory only for v1.
11. **Multiple currencies** — theaters may operate in different currencies. Store currency on schedules/ticket tiers/bookings; do not assume SAR globally. Use `settings.data.currency` for defaults.
12. **Timezone** — store UTC in the database and render in the theater's timezone. Add `timezone` to `theaters`/`tenants` if theaters can be outside one timezone.
13. **Subdomain vs path-based workspace** — the reference uses subdomains. Confirm TheaterLink's deployment. The recommended model works with subdomains or path prefixes; the token remains authoritative.
14. **Support contact** — static config vs a support-message table. The reference has `valuer_contact_messages`; TheaterLink can reuse the pattern if inbound support messages are required.
15. **Notification channels** — in-app + email + SMS recommended. Push only if a client channel needs it.
16. **Existing frontend contract** — if a TheaterLink frontend already expects specific field names/status labels, the API contract must be fixed before Phase 2. No frontend was inspected in this phase.

---

## 14. Direct Answers to the Ten Questions

> The three critical architecture decisions are expanded with options, comparisons, and implementation guidance in **Section 15**, and summarized in the final decision table in **Section 16**.

### 1. Which existing services can be reused directly?

Direct reuse:

- `App\Services\OtpService`
- `App\Models\Otp` and OTP migrations
- `App\Services\MediaSystem\MediaService`, `FileService`, `MediaFile`, `MediaFolder`, `HasMediaFiles`
- `App\Services\Messages\MessageFactory` and channel services
- `App\Services\Authentication\AuthFactory`, `AuthInterface`, `SanctumRepository`
- Dynamic guard registration in `App\Providers\AppServiceProvider`
- `App\Http\Traits\HttpResponsesTrait`
- `App\Services\ActivityLog\ActivityLogService`
- `Modules\Settings` and `App\Services\CountryService`
- `App\Constants\RegexPatterns`, `App\Rules\StrongPassword`, `App\Traits\HasPhoneValidation`
- `App\Http\Middleware\SetLocaleFromHeader`
- `Modules\Admins` central admin module
- `Modules\Users` individual user module
- The repository/DTO/resource/translation conventions, with filter whitelisting

### 2. How does the current Valuer multi-tenancy work?

It is a **shared database + row-level isolation** model, despite legacy documentation describing database-per-tenant:

- A `Valuer` row is the tenant; it owns `ulid`, `uuid`, `subdomain`, `is_active`.
- Workspace identity is resolved by subdomain, and `X-Tenant` is an encrypted ULID validated by `TenantIdentificationService`.
- The old `tenants` table was merged into `valuers` and dropped.
- `TENANCY_ENABLED=false`; `InitializeTenant` is effectively a no-op.
- Tenant data isolation is enforced by `BelongsToCurrentValuer`, a global Eloquent scope that filters models by `valuer_id` when the authenticated user is a `ValuerAdmins`.
- Each tenant has its own copied permission/model/role catalog (`ValuerCatalogService`).
- Tenant admins are `ValuerAdmins` rows with `valuer_id`/`valuer_ulid`; login is bound to the workspace through `AuthController::belongsToActiveTenant()`.

### 3. How does Valuer registration and tenant provisioning work?

- `ValuerController::register` validates, stores documents temporarily, caches the payload, generates a cache OTP, and sends SMS.
- `ValuerController::verify` consumes the OTP, rehydrates documents, and calls `ValuerRequestRepository::save()`.
- `saveValidated()` creates a `valuer_requests` row with status `pending_review` and an **inactive** `Valuer` account, uploads documents via `MediaService`, and stores document rows with status `pending`.
- `TenantProvisioningService::provision()` writes `ulid`, `uuid`, `subdomain`, name, and `is_active = true`; seeds the catalog; copies permissions/models/roles; creates/finds the owner `ValuerAdmins`; assigns base roles.
- `ValuerRequestRepository::approve()` checks all documents accepted, status `ready_for_approval`, and phone verified; then provisions, sets request status `approved`, activates the valuer, assigns a trial plan, creates the root media folder, and migrates documents.
- `reject()` stores the reason and `rejected_from_status`; `reopen()` restores the previous status. Provisioning currently commits outside the approval transaction.

### 4. How does the admin review and approval workflow work?

- Central admin routes under `/api/v1/admin/valuer-requests` use `auth:sanctum` + `abilities:admins`.
- `AdminController` provides list, new-request list, show, update, document list, accept/reject/request-reupload, extracted value, approve, reject, reopen, suspend/toggle, and notes.
- Document statuses are `pending`, `accepted`, `rejected`, `need_reupload`.
- `updateRequestStatus()` recalculates the application status after each document action.
- Approval requires all documents accepted, status `ready_for_approval`, and phone verification.
- Rejection is blocked after approval; reopening is allowed only for rejected applications and restores `rejected_from_status`.
- The group portal sees status, documents, feedback, rejection reason, workspace status, and required actions through `ValuerDashboardResource` and the portal endpoints.
- The reference does not enforce per-review permissions beyond the admin token ability, and does not have a general notification service.

### 5. How are OTP and media uploads implemented?

**OTP:**

- `OtpService` supports model-based OTPs (existing accounts) and cache-based OTPs (pre-account registration).
- Codes are hashed, expire after `otp.expiry_minutes`, have a resend cooldown, and allow `otp.max_attempts` wrong tries.
- Registration uses `generateByCache`/`verifyByCache`; login and password reset use `generate`/`verify` with the polymorphic `otps` table.
- Non-production code is `1234`; production uses `random_int(1000, 9999)`.

**Media:**

- `MediaService` orchestrates `FileService` uploads and `media_files`/`media_folders` persistence.
- It supports polymorphic relatable folders, disks, visibility, categories, tags, versions, image resizing, and URLs.
- Registration documents are first stored temporarily under `valuer_otp/{phone-hash}` and later uploaded through `MediaService` into `valuer_documents/{requestId}/{type}`.
- Private local files are not signed automatically; TheaterLink needs authorized download endpoints.

### 6. How are roles and permissions structured?

There are two independent RBAC systems:

**Central admin:** `admins` → `admin_roles` → `roles` → `role_permissions` → `permissions` and `models` (platform modules). Permission names are `<module>-<action>`; `Admins::hasPermission()` checks them.

**Tenant (ValuerAdmins):** `valuer_admins` → `valuer_admin_roles` → `valuer_roles` → `valuer_role_permissions` → `valuer_permissions` and `valuer_models`. Every row is tenant-scoped by `valuer_id` and `BelongsToCurrentValuer`. `ValuerCatalogService` copies the global catalog into each tenant.

The critical gap: tenant routes only check `abilities:valuer_admins`; policies exist but are not registered/applied. Tenant roles do not actually restrict endpoints today. TheaterLink must enforce policies/permissions.

### 7. What is the best way to support both Theater and Theater Group tenants?

Use one generic tenant boundary:

- `tenants` table with `type = theater | group`, `status`, `subdomain`, `ulid`, `uuid`.
- `theaters` and `theater_groups` profile tables, one-to-one with `tenants`.
- `tenant_members` for both theater and group teams, with per-tenant roles.
- A global permission catalog + per-tenant roles (not per-tenant permission copies).
- Type-specific middleware/policies: theater members can manage halls/events/reservations; group members can manage group profile and request reservations.
- Group tenant starts as `pending_review`; only application/status routes are allowed until approved.
- Theater tenant starts `active` (unless the business adds verification).
- Central admin reviews group applications through `Modules/Admins`.
- This avoids duplicating team management, auth, and permissions for the two actor types.

### 8. Which components need modification?

If using the reference as the starting point:

- Add `tenant_members` to `config/auth_guards.php` and the `AuthController` type map; or create a dedicated tenant auth controller.
- Add new middleware aliases in `bootstrap/app.php` (`tenant`, `tenant.active`, `tenant.type`, permission middleware).
- Create `BelongsToTenant`; do not modify `BelongsToCurrentValuer`.
- Create `WorkspaceProvisioningService`; do not extend `TenantProvisioningService` in place.
- Replace/adapt `XTenantMiddleware` and `TenantIdentificationService` with token-driven tenant resolution.
- Adapt the registration controllers/repositories from `Modules/Valuers` into `Modules/TheaterGroups`.
- Adapt the review controller into a group-application admin controller.
- Replace `ValuerCatalogService` with a global permission catalog + tenant roles.
- Add policy registration and permission checks.
- Add notification infrastructure.
- Add filter whitelisting where `DatabaseStatementsInitTrait` is used.
- Add private media download handling.
- Add theater, event, reservation, and booking modules.

### 9. Which components need completely new implementation?

- Generic `tenants`, `tenant_members`, `tenant_roles`, `permissions` schema and services.
- Tenant context/middleware and `BelongsToTenant`.
- Theater profile, halls, schedules, slot blocks, and materialized time slots.
- Events, translations, shows, ticket tiers, and public discovery.
- Reservation request/approval lifecycle and slot locking.
- Ticket booking and inventory concurrency.
- In-app notifications.
- Group application admin review tailored to TheaterLink.
- Group status portal tailored to TheaterLink (can be adapted from the valuer portal).
- Tenant isolation and concurrency test suites.
- API documentation and Postman collections for the new surface.

### 10. What database structure and module architecture are recommended?

**Database:** shared database, `tenant_id` on every tenant-owned table, FKs/unique composite indexes/check constraints, `tenants` + profiles + members + roles/permissions, `theaters`/`halls`/`hall_schedules`/`hall_slot_blocks`/`hall_time_slots`, `events`/`event_translations`/`event_shows`/`ticket_tiers`, `reservations`/`reservation_status_histories`, `bookings`/`booking_items`, `notifications`, and the existing media/OTP/settings/users/admins tables.

**Modules:**

```text
Modules/
  Admins/          # central admin (reuse)
  Users/           # individuals (reuse)
  Settings/        # countries/cities (reuse)
  Tenancy/         # tenants, members, roles, permissions, auth
  Theaters/        # theater profile, halls, schedules, slots
  Events/          # events, shows, ticket tiers, public catalog
  TheaterGroups/   # group applications, review, profile, discovery
  Reservations/    # hall reservation lifecycle
  Bookings/        # individual ticket booking
```

**Shared:** `app/Services/Tenancy`, `app/Services/Notifications`, and the existing `app/Services/MediaSystem`, `Messages`, `ActivityLog`, and `Tenancy` foundations.

---

## 15. Critical Architecture Decisions (Required Deep Dive)

> This section evaluates the three architecture decisions requested for Phase 1. It was prepared before implementation; the decisions below are now the working architecture and their implementation status is tracked in [`phase-2`](phase-2/README.md) and the later phase folders.

### 15.1 Theater and Theater Group Multi-Tenancy

#### 15.1.1 Existing implementation findings

The reference has a working single-type tenant model, not a generic multi-type tenant model:

- `Valuer` is the tenant. Workspace identity columns live directly on `valuers`: `ulid`, `uuid`, `subdomain`, `is_active`.
- The old `tenants` table was merged into `valuers` and dropped by `Modules/Valuers/database/migrations/2026_10_21_000001_merge_tenants_into_valuers_table.php`.
- `TenantProvisioningService::provision()` provisions a valuer workspace only: it writes identity fields, seeds the catalog, copies permissions/models/roles, and creates a `ValuerAdmins` owner.
- `BelongsToCurrentValuer` is hardcoded to `ValuerAdmins` and `valuer_id`. It cannot isolate a different tenant type without modification.
- `XTenantMiddleware` and `TenantIdentificationService` resolve an active `Valuer` by subdomain. There is no `type` column and no group equivalent.
- `ValuerCatalogService` copies the entire permission/model/role catalog into each tenant.
- The real isolation is shared database plus `valuer_id`; `TENANCY_ENABLED=false` and `InitializeTenant` is effectively a no-op.
- Physical-database remnants still exist in `app/Models/Tenant.php`, `app/Console/Commands/CreateTenant.php`, and `app/Console/Commands/CleanupFailedTenants.php`, but the current schema has dropped the `tenants` table. These commands/model are legacy and should not be revived for TheaterLink.
- There is no theater-group equivalent, no tenant type, no cross-tenant reservation relation, and no group application workflow separate from the valuer onboarding workflow.

The reference therefore proves the **shared tenant infrastructure** pattern but not a **multi-type tenant** pattern. TheaterLink must add the type dimension and split domain responsibilities.

#### 15.1.2 Options evaluated

**Option A — Shared Tenant Architecture**

One tenant table, one tenant provisioning flow, one set of tenant services. Both theater and group are rows with `type = theater | group`. Business logic branches on type inside shared controllers, services, and policies.

**Option B — Separate Tenant Architectures**

Theaters and groups have independent tenant structures, independent provisioning workflows, independent member tables, independent role/permission tables, and independent portals. The two systems may share only low-level infrastructure such as OTP, media, and messaging.

**Option C — Hybrid Architecture**

Shared tenant infrastructure and provisioning framework, but separate business modules and domain logic:

- Shared: `tenants`, `tenant_members`, tenant authentication, roles/permissions foundation, tenant context, provisioning orchestration, notifications, media, OTP.
- Separate: `theaters` profile, `theater_groups` profile, group application workflow, theater operations, event management, reservation workflows, ticket booking.
- Type-specific middleware, policies, and provisioning steps.

#### 15.1.3 Comparison

| Criterion | Option A: Shared | Option B: Separate | Option C: Hybrid |
|---|---|---|---|
| Code reusability | Highest — one auth/team/role stack | Lowest — duplicate auth, team, role, provisioning, middleware | High — shared infrastructure, domain-specific modules |
| Database isolation | Shared DB, `tenant_id`, type column | Shared DB possible but separate tables; physical DB possible at high cost | Shared DB, `tenant_id`, generic tenant boundary + type profiles |
| Scalability | Good, but type branching grows in shared services | Scales by duplicating subsystems; operational overhead grows | Good; shared infrastructure scales once, domains remain bounded |
| Security | Risk of accidental cross-type access if guards are weak | Strong type separation, but duplicate security logic can drift | Strong if policies/type middleware enforce both tenant and type; least duplication |
| Maintainability | Becomes a large conditional system | Two systems to maintain; changes must be duplicated | Best balance: shared foundations, separate domain code |
| Complexity | Low initially, high as type-specific rules accumulate | High duplication from day one | Moderate; requires clear contracts and type enforcement |
| Compatibility with Valuer architecture | High conceptually, but `Valuer` is single-type and must be generalized | Low — would fork the existing pattern | High — preserves the valuer pattern as the shared tenant/member foundation |
| Cross-tenant interactions | Easier with one tenant table and one reservation relation | Harder; need explicit cross-system contracts and duplicate read models | Easiest: one tenant identity, one reservation relation, separate domain services |

#### 15.1.4 Recommendation: Option C — Hybrid

Recommend **Option C**.

Reasons:

1. Theater and group teams need the same fundamentals: login, account status, members, roles, permissions, invitations, password reset, audit. Duplicating these in Option B creates two security surfaces and guarantees drift.
2. Theater and group business logic is genuinely different. Putting halls, events, schedules, reservations, and ticket tiers into one shared service with `if ($tenant->type === ...)` branches in Option A creates a fragile god-service and makes permission mistakes more likely.
3. The reference already has a reusable tenant/member pattern in `ValuerAdmins`; the right move is to generalize that foundation, not clone it twice.
4. Cross-tenant reservations require a single tenant identity. A group reservation must reference both a group tenant and a theater tenant. A shared `tenants` table with one `tenant_id` convention makes this a normal foreign-key relation; separate tenant architectures require a mapping layer.
5. Shared database is required for practical cross-tenant discovery and transaction integrity. Separate physical databases are not recommended for v1.

**Decision:** Shared tenant infrastructure + shared database + separate business modules and profiles (Hybrid).

#### 15.1.5 Recommended database design

```text
tenants
  id, ulid, uuid, type (theater|group), name, subdomain unique,
  status (pending_review|active|suspended|rejected),
  approved_at, timestamps, deleted_at
  index(type, status), index(status), unique(subdomain), unique(ulid), unique(uuid)

theaters
  id, tenant_id unique FK, name, about, logo_media_id, cover_media_id,
  email, phone, address, country_id, city_id, latitude, longitude,
  is_active, timestamps, deleted_at

theater_groups
  id, tenant_id unique FK, name, about, logo_media_id,
  contact_person_name, email, phone, timestamps, deleted_at

tenant_members
  id, tenant_id FK, name, email, phone, password, is_owner, is_active,
  last_login_at, failed_login_attempts, locked_until, timestamps, deleted_at
  unique(tenant_id, email), unique(tenant_id, phone), index(tenant_id, is_active)

permissions
  id, name unique, module, action, is_active
  # global platform permission catalog

permission_tenant_types
  permission_id, tenant_type
  unique(permission_id, tenant_type)
  # prevents assigning theater-only permissions to group roles

tenant_roles
  id, tenant_id FK, name, template_key, is_system, is_active, timestamps, deleted_at
  unique(tenant_id, name)

tenant_role_translations
  id, role_id FK, lang, name, description
  unique(role_id, lang)

tenant_role_permissions
  id, tenant_id FK, role_id FK, permission_id FK
  unique(role_id, permission_id), index(tenant_id)

tenant_member_roles
  id, tenant_id FK, member_id FK, role_id FK
  unique(member_id, role_id), index(tenant_id)

theater_group_applications
  id, tenant_id unique FK, reference unique, group_name, group_info,
  contact_person_name, email, phone, license_media_id,
  status (pending_review|under_review|requires_resubmission|ready_for_review|approved|rejected|provisioning|suspended),
  rejection_reason, review_feedback, internal_notes, rejected_from_status,
  reviewed_by FK admins, reviewed_at, approved_at, timestamps, deleted_at

theater_group_application_documents
  id, application_id FK, document_type, media_id, original_name,
  status (pending|accepted|rejected|requires_resubmission),
  rejection_reason, admin_note, reviewed_by, reviewed_at, timestamps
  unique(application_id, document_type)
```

Key constraints:

- `tenants.type` is immutable. Changing a theater into a group requires a new tenant, not a row update.
- Every tenant-owned table carries `tenant_id NOT NULL`, even when a domain-specific foreign key also exists.
- Composite indexes start with `tenant_id`, e.g. `(tenant_id, status, created_at)`.
- Unique business keys are tenant-scoped, e.g. `unique(tenant_id, name)` for halls, events, roles.
- `tenant_members` uses `unique(tenant_id, email)` and `unique(tenant_id, phone)`, matching the reference's per-workspace identity rule. The same person can have separate memberships in different tenants.
- The owner is a `tenant_members` row with `is_owner = true` and an owner system role. At least one owner must remain.
- `theater_group_applications` is one-to-one with a group tenant. The group tenant exists from registration in `pending_review` so the applicant can log in and see the status page; it is not a live workspace until `active`.

#### 15.1.6 Separate tenant databases?

**No for v1.** Use one shared database with `tenant_id` row isolation.

Why not separate databases:

- Individuals must browse published events across all theaters.
- Groups must browse all active theaters and halls.
- A reservation references a group tenant, a theater tenant, a hall, and a slot in one transaction.
- Central admin review needs cross-tenant lists, filters, counts, and document access.
- Separate databases require a central catalog, cross-database queries or API aggregation, distributed transactions, and duplicate migration/ops pipelines.

Physical database separation can be revisited later as an enterprise deployment variant, but it is not compatible with the simplest v1 marketplace. If it is ever required, keep the same repository/service contracts and introduce a central catalog plus per-tenant connections; do not build it into the first implementation.

#### 15.1.7 How users and team members are associated with tenants

- **Theater owner:** `tenant_members` row with `tenant_id = theater tenant`, `is_owner = true`, owner role.
- **Theater team member:** `tenant_members` row in the theater tenant, one or more tenant roles.
- **Group owner:** `tenant_members` row in the group tenant, `is_owner = true`.
- **Group team member:** `tenant_members` row in the group tenant.
- **Individual:** separate `users` row. Individuals are not tenant members and never receive tenant roles.
- **Super admin:** separate `admins` row. Admins are not tenant members and do not receive tenant roles.
- A person who belongs to multiple tenants has a separate `tenant_members` row per tenant. Login requires the workspace subdomain, so the account is unambiguous. A future global identity layer can link these rows without weakening tenant isolation; it is not required for v1.
- Team invitations create an inactive member row or a signed invitation token; the invitee sets a password and activates. The invitation must be scoped to the tenant.
- Team member removal is a soft delete; active reservations/history remain intact. The member's tokens must be revoked.

#### 15.1.8 How tenant types are identified

- The authoritative source is `tenants.type` and the authenticated `TenantMember.tenant_id`.
- Tenant type is never accepted from the request body, query string, or a client-supplied `tenant_id`.
- Login resolves the tenant by subdomain plus expected type; the token is bound to the member and tenant.
- Middleware chain:
  - `ResolveTenantContext`: token → member → tenant; sets a request-scoped `TenantContext`.
  - `EnsureTenantMemberActive`: member must be active.
  - `EnsureTenantType:theater` or `EnsureTenantType:group`.
  - `EnsureTenantActive`: tenant must be active, except for the group application/status routes.
- Route prefixes and policies provide a second layer: theater routes never resolve a group tenant, and vice versa.
- If an `X-Tenant` header is retained, it is only a consistency check against the token's tenant. It is not an authorization input.

#### 15.1.9 How roles and permissions differ between tenant types

Use **one global permission catalog** plus **per-tenant roles**, with a type allowlist.

- `permissions` defines platform capabilities, not tenant data.
- `permission_tenant_types` says which tenant types may receive a permission.
  - Theater-only: `halls.*`, `hall_schedules.*`, `events.*`, `ticket_tiers.*`, `reservations.approve`, `reservations.reject`, `theater.profile.*`.
  - Group-only: `group.profile.*`, `group.application.read`, `group.application.resubmit`, `reservations.create`, `reservations.read_own`, `reservations.cancel_own`.
  - Shared: `team.*`, `roles.*`, `notifications.*`.
- System role templates are seeded per tenant type:
  - Theater: Owner, Manager, Operations, Box Office, Viewer.
  - Group: Owner, Coordinator, Viewer.
- `tenant_roles` belongs to one tenant. A tenant can create custom roles, but may only select permissions allowed for its type.
- The owner role is immutable and implicitly has all allowed permissions. A tenant cannot remove its last owner.
- Enforcement is by policies, not by UI hiding:
  - `HallPolicy`, `EventPolicy`, `TicketTierPolicy`, `ReservationPolicy`, `TeamMemberPolicy`, `RolePolicy`.
  - Every policy checks `tenant_id` and `hasPermission(...)`.
  - Controllers call `$this->authorize()` or route middleware `can:...`.
- Central admin permissions remain in `Modules/Admins`; they are never mixed with tenant roles.

This differs from `ValuerCatalogService` deliberately: permissions are platform features, while roles are tenant-owned. Copying the permission catalog per tenant makes platform upgrades and auditing harder.

#### 15.1.10 Cross-tenant interactions

The critical rule: **a group can interact with multiple theaters without reading their private tenant data.**

Use read-only public projections for discovery:

- `GET /api/v1/public/theaters` — active theaters, public profile fields only.
- `GET /api/v1/public/theaters/{id}` — public profile, active halls, public availability.
- `GET /api/v1/public/theaters/{id}/halls` — hall name, capacity, public schedule/availability.
- `GET /api/v1/public/halls/{id}/availability?from=&to=` — available slots only.
- `GET /api/v1/public/events` and `/events/{slug}` — published events, shows, ticket tiers.

These endpoints must never expose:

- theater team members or roles
- theater internal notes
- private documents or storage paths
- other groups' reservations
- draft/unpublished events
- internal pricing rules or blocked-slot reasons

Reservation relation:

```text
reservations
  theater_tenant_id  FK tenants
  group_tenant_id    FK tenants
  hall_id            FK halls
  hall_time_slot_id  FK hall_time_slots
  requested_by_member_id FK tenant_members
  price, currency    price snapshot at request time
  status             pending|approved|rejected|cancelled|expired|completed
```

- The group creates a reservation against a theater's public hall/slot. The backend verifies the hall and slot belong to `theater_tenant_id`.
- The theater sees only reservations where `theater_tenant_id = current tenant`.
- The group sees only reservations where `group_tenant_id = current tenant`.
- The theater can see the group's public profile/contact details needed to evaluate the request, but not the group's team, documents, or other reservations.
- The group can see the theater's public profile, hall, slot, and its own reservation, but not the theater's other reservations or internal notes.
- The central admin can view both sides for oversight, with audit logging.

Use service contracts/DTOs for the cross-tenant read models. Do not let the Reservations module import Theater models directly in a way that bypasses tenant scoping. A `TheaterCatalogService` and `HallAvailabilityService` should expose only public DTOs.

#### 15.1.11 Edge cases

- Same email belongs to a theater and a group: separate `tenant_members` rows; login requires the workspace subdomain.
- A person owns a theater and is a group member: two memberships, separate tokens, no cross-tenant role inheritance.
- Theater suspended while a group has a pending reservation: policy must decide whether pending requests remain or are cancelled; recommend blocking new requests and allowing the admin to cancel or resolve existing pending requests, not auto-approving.
- Group rejected/suspended: cancel pending requests and notify theaters; do not allow new requests.
- Deleted hall with future reservations: soft-delete and block new requests; existing reservations must be cancelled or rescheduled by the theater with notifications.
- Last owner leaves: block deletion/demotion; require ownership transfer.
- Type change: not allowed in place; create a new tenant and migrate intentionally, or reject.
- Cross-tenant ID substitution in nested routes: must return 404, not another tenant's data.
- Central admin accidentally scoped: admin routes must not use the tenant global scope or should explicitly bypass it in a controlled service.

#### 15.1.12 Security and data integrity

- `tenant_id` non-null on every tenant-owned table.
- `BelongsToTenant` global scope on every tenant-owned model; fail closed when there is no tenant context.
- Scope route bindings by tenant.
- Policies must check both tenant ownership and permission. A valid `tenant_members` token with no permission must not pass.
- Unique indexes must include `tenant_id` where the business key is per-tenant.
- Foreign keys should restrict deletes where history matters; soft delete tenants/members/halls before hard deletion.
- Central admin bypass is explicit and audited.
- Add isolation tests for every endpoint, including nested IDs.
- Never expose tenant IDs as authorization inputs; they are output identifiers only.
- Log tenant membership changes, role changes, application decisions, and suspension.

#### 15.1.13 Laravel implementation recommendations

- Add `tenant_members` to `config/auth_guards.php`; `AppServiceProvider::registerDynamicGuards()` will create the session guard, while API tokens are issued with the `tenant_members` ability.
- Add `TenantMember` as an `Authenticatable` model with `HasApiTokens`, `otps()`, roles, and tenant relation.
- Create `app/Services/Tenancy/TenantContext.php` and middleware `ResolveTenantContext`, `EnsureTenantType`, `EnsureTenantActive`, `EnsureTenantMemberActive`.
- Create `app/Traits/BelongsToTenant.php`; do not modify `BelongsToCurrentValuer`.
- Create `WorkspaceProvisioningService` with `TenantProvisioner` contract and `TheaterProvisioner`/`GroupProvisioner` implementations:
  - Shared steps: tenant row, owner member, system roles, storage folder, audit, notifications.
  - Theater steps: theater profile, active status.
  - Group steps: group profile, application, documents, pending status.
- Use `DB::transaction` with `lockForUpdate` on the application/tenant row for approval; make provisioning idempotent.
- Add `Gate::policy` registrations and permission middleware.
- Keep `Modules/Admins` for central admin review; add `GroupApplicationPolicy` and new permissions.
- Use domain events and `DB::afterCommit` for notifications and audit side effects.

#### 15.1.14 Required infrastructure

- Queue connection: the reference uses `QUEUE_CONNECTION=database`; keep that for v1 unless Redis queues are operationally preferred.
- Scheduler: Laravel scheduler for provisioning retries, application reminders, and notifications.
- Cache: the reference uses file cache; do not use cache as a source of truth for membership or tenant status. Read tenant/member status from the database for authorization.
- Storage: private disk for group permits and theater documents; public disk/CDN for event posters and logos.
- Notifications: in-app notification table plus queued email/SMS via `MessageFactory`.
- Subdomains: keep the reference's subdomain workspace model; token remains authoritative.

---

### 15.2 Hall Scheduling and Reservation Conflict Management

#### 15.2.1 Existing implementation findings

There is **no hall, schedule, reservation, event, or ticket domain implementation** in `qeei_backend_admin`. The reference contains no tables, models, controllers, or migrations for these concepts.

The reusable findings are the concurrency and scheduling patterns:

- `ValuerRequestRepository::reject()` and `reopen()` use `DB::transaction` plus `lockForUpdate()` on the application row.
- `SubscriptionCheckoutService::lockCompanySubs()` uses `lockForUpdate()` to serialize checkout changes for a company.
- `ValuerStorageAddonService::purchaseCheckout()` and `confirmPurchase()` lock pending purchase rows.
- `Modules/MoneyCircle/app/Repositories/SeatRepository.php` locks seat rows and documents the intent: “Atomic + locked: assigning several seats must be all-or-nothing, and the row lock serializes concurrent assignments so two members can't claim the same seat.”
- `Modules/MoneyCircle` uses unique constraints such as `unique(['circle_id', 'user_id', 'active_state'])` and `unique(['turn_id', 'member_id'])`.
- `bootstrap/app.php` registers a daily `subscriptions:process` schedule; `app/Console/Commands/ProcessSubscriptions.php` exists. There is no `withoutOverlapping()` or reservation-expiry job.
- `QUEUE_CONNECTION=database` and `CACHE_STORE=file`; Redis is configured but not the default queue/cache.
- There is no general background-job pattern for expiry beyond the daily subscription command.

The reference therefore gives us reliable transaction/locking precedents but no scheduling domain decisions. TheaterLink must define the reservation model from first principles.

#### 15.2.2 Options evaluated

**Approach A — Pending reservations block availability**

When a group submits a request, the slot becomes unavailable immediately. No other group can request it. If the theater rejects or the group cancels, the slot becomes available. There is no automatic expiry unless added.

**Approach B — Pending reservations do not block availability**

Multiple groups may request the same slot. The slot stays available for requests. Only one request can be approved. Approval uses an atomic transaction and row lock. Losing pending requests must be rejected/notified after confirmation.

**Approach C — Temporary reservation lock**

A pending request locks the slot temporarily. The lock has an expiration time configured by the theater/platform. If the theater does not respond before expiry, the request expires and the slot becomes available automatically. This is effectively A plus automatic expiry and a response SLA.

#### 15.2.3 Comparison

| Criterion | A: Pending blocks indefinitely | B: Pending does not block | C: Temporary lock |
|---|---|---|---|
| Double-request prevention | Strong | Weak — many pending requests for one slot | Strong while hold is active |
| Theater control | High, but can block inventory forever | High; chooses among requests | High, with an automatic response deadline |
| Group experience | Clear “slot held” state | Confusing; many groups may compete | Clear, with countdown/expiry |
| Data integrity | Easy — slot state changes once | Requires atomic approval and loser cleanup | Easy — same slot state machine, plus expiry |
| Stale requests | Major risk | Major risk | Controlled by expiry job |
| Complexity | Low | Medium-high | Medium |
| Fairness | First-come-first-served | Theater can choose, but losers wait | First-come-first-served with a deadline |
| Operational burden | Theater must remember to act | Theater must resolve many requests | Scheduled expiry job and notifications |
| Recommendation | Not recommended alone | Not recommended for v1 | Recommended |

#### 15.2.4 Recommendation: Approach C — temporary reservation lock

Recommend **Approach C**, with the hold implemented as a slot state, not an in-memory or cache lock.

Reasons:

1. It preserves the strongest business rule: only one group can hold a given slot at a time, so simultaneous requests cannot both succeed.
2. It prevents a theater from accidentally blocking a hall forever because it ignored a request.
3. It gives the group a clear state and a countdown, which is better UX than competing silently.
4. It keeps approval simple: the slot is already held by this reservation; approval revalidates the hold and confirms it.
5. It avoids the complexity of resolving multiple pending requests for the same slot and notifying losers.

Do not use Approach B for v1. It is viable only if the business explicitly wants a “request for proposal” model where theaters choose among competing groups. That model needs waitlists, loser notifications, and a different UX.

#### 15.2.5 Reservation lifecycle and slot states

Slot states:

| Slot state | Meaning | Who can change it |
|---|---|---|
| `available` | Open for a group request | System |
| `held` | Temporarily locked by a pending reservation; has `hold_expires_at` | System on request; system on expiry/rejection/cancellation |
| `reserved` | Confirmed reservation | System on theater approval |
| `blocked` | Theater blocked it for maintenance/private use/internal activity | Theater |
| `completed` | Historical slot whose reservation is completed | System |

Reservation states:

| Reservation state | Meaning |
|---|---|
| `pending` | Submitted; slot `held`; waiting for theater |
| `approved` | Theater accepted; slot `reserved` |
| `rejected` | Theater rejected; slot released |
| `cancelled` | Group/theater cancelled; slot released if future |
| `expired` | Hold expired before theater decision; slot released |
| `completed` | Reservation end time passed while approved |

Lifecycle:

```text
available
   │ group requests (row lock)
   ▼
 held (pending, hold_expires_at)
   ├── theater approves ──► reserved (approved)
   ├── theater rejects  ──► available (rejected)
   ├── group cancels    ──► available (cancelled)
   └── hold expires     ──► available (expired)

reserved
   ├── end time passes  ──► completed
   └── cancellation     ──► cancelled + available (if future)
```

The slot row is the source of truth for physical availability. The reservation row is the business record and audit trail.

#### 15.2.6 Slot availability calculation

Recommend **predefined, materialized time slots** for v1.

Generation:

1. Theater defines weekly `hall_schedules`:
   - day of week
   - start time
   - end time
   - slot duration in minutes
   - price and currency
2. A scheduled command materializes `hall_time_slots` for a rolling horizon (e.g. 90 days):
   - Split each schedule window into fixed-duration slots.
   - Apply `hall_slot_blocks` (maintenance/private use).
   - Apply `event_shows` that occupy the hall; those slots become `blocked` or `reserved` for the event.
3. Availability query returns only `status = available` and `starts_at > now + lead_time`.

Formula:

```text
available(slot) =
    slot.status = available
    AND slot.starts_at > now() + theater.booking_lead_time
    AND NOT EXISTS active hold/reservation for the slot
    AND NOT blocked by theater block
    AND NOT overlapping an event show
```

Because a slot row has a unique `(hall_id, starts_at)`, the database itself prevents duplicate slot definitions. The row lock plus status check prevents concurrent holds.

Do not compute availability only from a frontend query. The booking endpoint must re-check inside a transaction with a row lock.

#### 15.2.7 Expiration policy

- Each theater has a `reservation_hold_minutes` value (default recommendation: 48 hours; allow 1–168 hours).
- On request, set `hold_expires_at = now() + reservation_hold_minutes`.
- A scheduled job `reservations:expire-holds` runs every 5 minutes:
  - Select pending reservations whose `hold_expires_at <= now()`.
  - Process each in its own transaction with `lockForUpdate`.
  - Recheck status and expiry.
  - Set reservation `expired`; release slot to `available`; write status history; notify group and theater.
- The theater may approve before expiry. If approval happens after expiry, approval must fail and release the slot; the job and approval path both lock the same rows, so only one wins.
- Optional reminder notifications at 50% and 80% of the hold window.
- Do not auto-approve on expiry.
- If the business wants a shorter response SLA, configure it per theater; do not hardcode.

#### 15.2.8 Conflict detection and simultaneous requests

Request transaction:

```text
DB::transaction:
  slot = HallTimeSlot::whereKey($slotId)
      ->where('tenant_id', $theaterTenantId)
      ->lockForUpdate()
      ->firstOrFail()

  if slot.status !== 'available':
      throw Conflict('This slot is no longer available.')

  if slot.starts_at <= now():
      throw Conflict('This slot has already started.')

  slot.update(status='held', hold_expires_at=now()+holdMinutes)
  reservation = Reservation::create(status='pending', ...)
  slot.update(active_reservation_id=reservation.id)
commit
```

Concurrent requests:

- The first transaction locks the slot and changes it to `held`.
- The second waits on the row lock, then sees `status = held` and fails with 409/422.
- There is never more than one pending reservation for a slot.
- No frontend check is trusted.

Approval transaction:

```text
DB::transaction:
  reservation = Reservation::whereKey($id)
      ->where('theater_tenant_id', currentTenant)
      ->lockForUpdate()
      ->firstOrFail()

  slot = HallTimeSlot::whereKey($reservation->hall_time_slot_id)
      ->lockForUpdate()
      ->firstOrFail()

  abort unless reservation.status === 'pending'
  abort unless slot.status === 'held'
  abort unless slot.active_reservation_id === reservation.id
  abort if slot.hold_expires_at <= now()
  abort if event show/block now overlaps the slot

  reservation.update(status='approved', approved_at=now(), approved_by=...)
  slot.update(status='reserved', hold_expires_at=null)
commit
```

Lock ordering must be consistent per flow. Recommended:

- **Request path:** lock the slot first, because no reservation exists yet. The slot lock serializes competing requests.
- **Existing-reservation actions** (approve, reject, cancel, expire): lock the reservation first, then its slot. This matches the pseudocode above and keeps one order for every path that mutates an existing reservation.
- **Never mix orders within the same flow.** The request path does not wait on an existing reservation, so it cannot form a lock cycle with the reservation-first paths. Document the chosen order and enforce it in every service method; add a concurrency test that runs request, approve, reject, and expiry in parallel.

Rejection/cancellation transaction:

```text
DB::transaction:
  reservation = lock reservation
  slot = lock slot (same order)
  verify reservation belongs to current tenant and status is allowed
  release slot if it is held/reserved by this reservation
  update reservation status and status history
commit
```

- Rejection: only pending; set `rejected`, store reason, release slot.
- Group cancellation: pending or approved depending on policy; release slot if future.
- Theater cancellation: approved/future depending on policy; release slot; notify group.
- Completion: after end time, approved → completed; slot remains historical `completed` or becomes `available` for future generation. Do not reuse a past slot row for a new date.

#### 15.2.9 Predefined vs dynamically calculated slots; custom times

Recommend predefined materialized slots for v1.

Why:

- Fixed-duration slots match the brief (“time slot duration”).
- A unique `(hall_id, starts_at)` constraint makes duplicate definitions impossible.
- Row locking on a slot is straightforward.
- UI can show discrete available slots and a countdown for holds.
- Event shows can block the exact slot rows.

Custom start/end times are not recommended for v1. If the business later requires arbitrary durations:

- Option 1: normalize the requested range to the nearest available slot boundaries (reject if it does not align).
- Option 2: add `hall_availability_windows` and perform an overlap query inside a transaction after locking the hall row:
  ```sql
  SELECT ... FROM reservations
  WHERE hall_id = ?
    AND status IN ('held','reserved')
    AND starts_at < :requested_end
    AND ends_at > :requested_start
  FOR UPDATE
  ```
  MySQL has no exclusion constraint, so the hall row lock is the serialization point. Add indexes on `(hall_id, status, starts_at, ends_at)`.
- Option 3: PostgreSQL with an exclusion constraint (`tsrange`) would be ideal, but the reference is MySQL; do not introduce a new database just for this.

#### 15.2.10 Handling event shows and theater blocks

- Event shows occupy hall time. When a show is scheduled, the overlapping slots must become `blocked` or `reserved-for-event` in the same transaction.
- Theater `hall_slot_blocks` are explicit unavailable periods; the generator marks overlapping slots `blocked` and stores the block reason internally.
- A group request must fail if a new event show or block was created after the slot was held; approval revalidates.
- When a theater deletes/moves an event show, release only the slots owned by that show; never release slots held by group reservations.
- Add `source_type`/`source_id` to slot occupancy or a dedicated `hall_slot_occupancies` table if both events and reservations can occupy the same slot. Recommended: one slot row with `status` and a single `occupancy_type`/`occupancy_id`, plus audit history.

#### 15.2.11 Edge cases

- Hold expires exactly while the theater clicks approve: both paths lock the same rows; one wins. Approval fails cleanly if expiry won.
- Double-click request: first creates hold; second sees held and fails or returns the existing reservation if the same group/idempotency key is used.
- Theater changes the slot price after a request: store the price snapshot on the reservation; do not re-price existing pending requests.
- Theater changes schedule after slots are materialized: regenerate future available slots only; never overwrite held/reserved/blocked slots; log conflicts.
- Group or theater suspended mid-flow: block new actions; decide whether to cancel pending requests and notify. Do not silently delete history.
- Timezone/DST: store UTC; render in theater timezone; generate slots in theater local time then convert to UTC; test DST transitions.
- Past slot: reject requests and approvals after `starts_at`.
- Hall deactivated/deleted: block new requests; cancel pending holds with notification; keep historical reservations.
- Reservation crosses midnight: avoid in v1 by materializing slots within a day; if allowed, store UTC timestamps and test.
- Duplicate pending request by the same group: allow only one active request per group/hall/slot; use a unique/idempotency key or query check under lock.
- Price display mismatch: server response is authoritative; frontend prices are display only.
- Event show and group reservation race: both use the same slot lock/occupancy; one wins.

#### 15.2.12 Security and data integrity

- The backend is the source of truth; never trust `slot.status`, `price`, `tenant_id`, or `reservation.status` from the client.
- Verify the group tenant type and membership; only approved/active groups may request.
- Verify the theater tenant is active and the hall belongs to it.
- Verify the slot belongs to the hall and theater.
- Use `DB::transaction`; lock the slot row; use unique constraints and check constraints.
- Use `409 Conflict` for unavailable slots, `422` for validation/business rule failures, `403` for unauthorized actor, `404` for cross-tenant resource access.
- Audit every state transition with actor, tenant, timestamp, and reason.
- Log conflicts and failed approvals with safe context (no sensitive data).
- Rate-limit request creation to prevent slot hoarding; optionally limit active holds per group.

#### 15.2.13 Laravel implementation recommendations

Suggested services:

- `HallAvailabilityService` — generates/reads slots, checks schedule blocks, event shows, and availability.
- `ReservationService` — `request`, `approve`, `reject`, `cancel`, `expire`, `complete`.
- `HallSlotGenerator` — materializes slots from schedules; invoked by command and after schedule changes.
- `ReservationExpiryJob` — releases expired holds.
- `ReservationCompletedJob` — marks approved reservations completed after end time.
- `ReservationPolicy` — tenant ownership + permissions.
- `HallSlotResource`/`ReservationResource` — public/private projections.

Commands and scheduler:

```text
hall:generate-slots            daily, withoutOverlapping()
reservations:expire-holds      every 5 minutes, withoutOverlapping()
reservations:complete          every 15 minutes, withoutOverlapping()
```

Queue:

- Use `QUEUE_CONNECTION=database` initially; notifications and emails can be queued.
- Do not put slot state in cache. Cache may cache public availability lists with a very short TTL, but the request/approve path always uses DB locks.
- Use `DB::afterCommit` for notifications and events.

Database:

- `hall_time_slots` with `unique(hall_id, starts_at)`, `index(tenant_id, hall_id, status, starts_at)`.
- `reservations` with `index(theater_tenant_id, status, created_at)`, `index(group_tenant_id, status, created_at)`, `index(hall_time_slot_id)`.
- `reservation_status_histories` for audit.
- Check constraints: `ends_at > starts_at`, `price >= 0`.

Tests:

- Concurrent request test: two requests, one succeeds, one fails.
- Approval after expiry test.
- Reject/cancel releases slot.
- Event show blocks slot.
- Theater cannot approve another theater's reservation.
- Group cannot request a blocked/unavailable/held slot.
- DST/timezone tests.

#### 15.2.14 Required infrastructure

- Laravel scheduler running every minute (`php artisan schedule:run` cron).
- Queue worker for notifications and non-critical jobs.
- Database indexes and row locks.
- No Redis requirement for correctness; Redis can improve cache/queue throughput but is optional.
- Monitoring/alerting for expired holds and failed jobs.

---

### 15.3 Ticket Inventory and Concurrent Booking Protection

#### 15.3.1 Existing implementation findings

There is **no event, show, ticket-tier, booking, or ticket domain implementation** in `qeei_backend_admin`.

Reusable findings:

- `SubscriptionCheckoutService` uses `DB::transaction` and `lockForUpdate()` for subscription rows and pending checkouts; it treats an already-active subscription idempotently.
- `ValuerStorageAddonService` uses `DB::transaction` and `lockForUpdate()` for pending purchases and status transitions.
- `Modules/MoneyCircle/app/Repositories/SeatRepository.php` locks seat rows to serialize concurrent seat assignment.
- `Modules/MoneyCircle/database/migrations/2026_07_20_000001_add_active_membership_unique_to_circle_members.php` uses a stored generated column (`active_state`) plus a unique index to allow one active membership while preserving soft-deleted history. This is a useful pattern for “one active hold/reservation per resource” if needed.
- `app/Services/RoxCustodyIntegration` contains idempotency/deduplication precedents:
  - `RoxCustodyIntegration/database/migrations/0001_01_01_000000_create_rox_custody_webhook_events_table.php` has a `dedupe_hash` unique column.
  - `ProcessWebhookJob` is explicitly idempotent and skips already-processed events.
  - `CreateTransactionData` documents an `idempotencyKey` for safe retries.
- `QUEUE_CONNECTION=database`; the scheduler currently runs `subscriptions:process` daily. There is no ticket-expiry job.

The reference therefore confirms the locking and idempotency patterns but provides no inventory model.

#### 15.3.2 Options evaluated

**Option A — Immediate confirmation with atomic inventory decrement**

A successful booking immediately decrements available inventory and creates a confirmed booking. There is no temporary hold and no automatic expiry. Cancellation restores inventory according to policy.

**Option B — Temporary reservation hold**

A booking request temporarily reserves inventory for a configured period (e.g. 15 minutes). Inventory is shown as held, not sold. A background job expires unpaid/unconfirmed holds. A separate confirmation step (external payment, staff confirmation, or automatic after hold) converts the hold to a confirmed booking.

**Option C — Hybrid**

Use immediate confirmation for the standard no-payment flow, with an optional hold mode for future external-payment or high-demand scenarios. The schema supports `quantity_held`, but the v1 behavior uses immediate confirmation.

#### 15.3.3 Comparison

| Criterion | A: Immediate confirmation | B: Temporary reservation | C: Hybrid |
|---|---|---|---|
| Matches “no online payment” | Yes — booking is the commitment | Yes, but requires a confirmation trigger | Yes |
| Overselling protection | Strong with atomic decrement | Strong with atomic hold | Strong |
| User experience | Simple, instant confirmation | Countdown, risk of losing tickets | Simple by default, optional hold |
| Abandoned bookings | Inventory stays sold until cancellation | Expired holds release automatically | Policy-dependent |
| Operational complexity | Low | Medium-high: expiry job, notifications, statuses | Medium |
| External payment support | Requires manual cancellation | Natural fit | Future-proof |
| Recommended for v1 | Yes | No, unless external payment confirmation is defined | Yes as an extension path |

#### 15.3.4 Recommendation: immediate confirmation for v1

Recommend **Option A / C**: immediate confirmation with atomic inventory decrement. Design the schema so holds can be added later without a breaking migration (include `quantity_held` and booking status `pending`/`expired` as reserved values, but do not use them in v1).

Reasons:

1. The application does not process payments. A temporary hold exists to protect inventory during a payment window; without a payment step, there is no reliable event to confirm the hold.
2. The brief says booking and payment collection are separate. A confirmed booking is the correct record; external payment is tracked outside the platform.
3. Immediate confirmation is simpler for individuals and theaters and avoids expiration edge cases.
4. If the business later adds an external payment reference or staff confirmation, the hold mode can be enabled with the same inventory schema and a new job.

#### 15.3.5 Inventory model

Recommended columns on `ticket_tiers`:

| Column | Meaning |
|---|---|
| `quantity_total` | Total tickets configured for this show/tier |
| `quantity_sold` | Confirmed/issued tickets |
| `quantity_held` | Temporarily held tickets; 0 in v1 immediate mode |
| `quantity_available` | Derived: `quantity_total - quantity_sold - quantity_held`; do not store unless generated |
| `max_per_booking` | Optional per-booking limit |
| `sales_start_at`, `sales_end_at` | Sale window |
| `is_active` | Tier availability |

Inventory statuses:

- **Total** — configured capacity.
- **Available** — can still be booked.
- **Held** — reserved by a temporary hold; 0 in v1.
- **Sold/Confirmed** — belongs to a confirmed booking.
- **Cancelled** — released by a cancellation; tracked in booking history, not a separate counter.
- **Expired** — a hold that expired; only in hold mode.

Do not store a mutable `available` counter as the only source of truth. Use `total`, `sold`, `held` and derive available. This makes the invariant explicit:

```text
0 <= quantity_sold
0 <= quantity_held
quantity_sold + quantity_held <= quantity_total
```

Add a database check constraint if the MySQL version enforces CHECK constraints (MySQL 8.0.16+). Also enforce in the service for older versions.

#### 15.3.6 Booking workflow (v1)

1. User selects event → show → ticket tier → quantity.
2. Backend validates:
   - user authenticated and active
   - event published, show scheduled and not cancelled
   - tier active, belongs to the show
   - sale window open
   - quantity > 0 and ≤ `max_per_booking`
   - show capacity/tier total constraints
   - idempotency key if provided
3. `DB::transaction`:
   - Lock each requested tier row with `lockForUpdate` in deterministic ID order.
   - Recheck `quantity_sold + qty <= quantity_total`.
   - Increment `quantity_sold`.
   - Create `bookings` (status `confirmed`) and `booking_items` with price snapshots.
   - Create `tickets` rows if per-ticket issuance is required.
   - Write status history.
4. Commit.
5. Return booking confirmation and ticket information.
6. After commit: queue notification/email.

No payment status is stored as a gate. Optionally store `external_payment_reference` and `external_payment_status` for theater records, but never use it to allow overselling.

#### 15.3.7 Concurrency algorithm and scenarios

Preferred algorithm: pessimistic row locks in a transaction.

```php
DB::transaction(function () use ($items, $userId, $idempotencyKey) {
    // Return an existing booking for a retry.
    $existing = Booking::where('user_id', $userId)
        ->where('idempotency_key', $idempotencyKey)
        ->lockForUpdate()
        ->first();
    if ($existing) {
        return $existing;
    }

    $tiers = TicketTier::whereIn('id', $ids)
        ->orderBy('id')          // deterministic lock order
        ->lockForUpdate()
        ->get()
        ->keyBy('id');

    foreach ($items as $item) {
        $tier = $tiers[$item['ticket_tier_id']] ?? null;
        abort_unless($tier && $tier->is_active, 422);
        abort_if($tier->quantity_sold + $tier->quantity_held + $item['quantity'] > $tier->quantity_total, 422);
    }

    foreach ($items as $item) {
        $tier->increment('quantity_sold', $item['quantity']);
    }

    $booking = Booking::create([... status = confirmed ...]);
    foreach ($items as $item) {
        $booking->items()->create([... price snapshot ...]);
    }

    return $booking;
});
```

Alternative for high throughput: conditional atomic update.

```sql
UPDATE ticket_tiers
SET quantity_sold = quantity_sold + :qty
WHERE id = :id
  AND is_active = 1
  AND quantity_sold + quantity_held + :qty <= quantity_total
```

Check the affected row count. If 0, the tier is sold out or insufficient; roll back the whole booking. This avoids holding a row lock for the full request but still requires a transaction across all tiers.

**Scenario 1 — Last ticket, two users:**

- User A locks the tier first, sees 1 available, increments to sold = total, commits.
- User B waits, then sees sold = total, fails with “sold out.”
- Exactly one booking succeeds.

**Scenario 2 — Only 3 tickets, request 5:**

- The transaction locks the tier, computes `sold + 5 > total`, and throws before any update.
- No booking, no inventory change, no ticket rows.

**Scenario 3 — 10 available, many concurrent bookings:**

- Each transaction locks the tier in order and rechecks the invariant.
- Total confirmed quantity never exceeds `quantity_total`.
- If a booking spans multiple tiers, all tiers are checked and updated in one transaction; any failure rolls back every tier.

Deadlock avoidance:

- Lock tiers in ascending `id` order.
- Keep transactions short; do not send notifications or call external services inside the transaction.
- Use `DB::afterCommit` for side effects.
- Avoid locking unrelated rows.

#### 15.3.8 Duplicate booking requests and idempotency

- Require an `Idempotency-Key` header for booking creation, or generate a client request ID.
- Store `bookings.idempotency_key`; add `unique(user_id, idempotency_key)`.
- Under a transaction, check for an existing booking with the same key and return it instead of creating a second one.
- Handle the race where two requests with the same key arrive simultaneously: the unique index causes one insert to fail; catch the unique violation, reload the existing booking, and return it. Do not create duplicate tickets.
- Do not rely only on a “check then insert” without a unique constraint.
- Apply the same idea to reservation requests if duplicate submissions are a concern.

#### 15.3.9 Ticket issuance, unique identifiers, and QR codes

If the business needs per-ticket entry:

- Create `tickets`:
  - `id`
  - `booking_item_id` FK
  - `ticket_number` unique (human-readable)
  - `qr_token` unique (random, opaque)
  - `status` (`valid`, `cancelled`, `used`, `expired`)
  - `used_at`
  - `issued_at`
- Generate one ticket per unit of quantity.
- Use a unique constraint on `(booking_item_id, sequence)` or `ticket_number` to prevent duplicates.
- Store `qr_token` as a random 32-byte value; optionally store a hash and show the raw token only once. If staff scan it, validate the token hash and ticket status.
- Never encode personal data or tenant secrets in the QR payload.
- Generate tickets in the same transaction as the booking for consistency. If generation is heavy, create them via a queued job with an idempotent unique constraint; the booking remains the source of truth.
- Ticket cancellation updates both the booking item and ticket statuses; do not delete ticket rows.

If per-ticket entry is not required, a booking-level QR/confirmation is enough; still store a unique booking reference.

#### 15.3.10 Availability calculation and caching

- `available = quantity_total - quantity_sold - quantity_held`.
- Public event/show APIs may return `quantity_total`, `quantity_available` (or “sold out”), and price. Do not expose `quantity_held`/`quantity_sold` internals unless needed for theater reporting.
- Cache public availability with a short TTL (e.g. 5–15 seconds) only if load requires it. Never use cached availability for the booking write path.
- Recompute inside the transaction after locking.
- Do not cache “last ticket” decisions.

#### 15.3.11 Cancellation and inventory restoration

Define a policy before implementation:

- Who can cancel: individual, theater admin, central admin.
- When: before show start only, or any time.
- Does cancellation restore inventory automatically? Recommended for individual cancellation before a cutoff; theater/admin cancellation restores inventory and triggers notifications/refunds externally.
- Is there a fee? Payments are external; the platform only records the booking status.
- On cancellation:
  - `DB::transaction` lock the booking and tiers in deterministic order.
  - Recheck booking status (idempotent).
  - Decrement `quantity_sold` by the item quantity (or `quantity_held` in hold mode).
  - Mark booking/items/tickets cancelled.
  - Write status history and audit.
  - Never allow counters below zero; check constraint and service guard.
- Cancellation after show start: keep sold; do not restore inventory.
- Cancellation of one item in a multi-item booking: restore only that item; keep the booking status consistent (partially cancelled may need a separate status; otherwise cancel whole booking).

#### 15.3.12 Expiration policy (only if hold mode is enabled)

- `bookings.status = pending`, `booking_items.quantity_held`, `hold_expires_at`.
- `bookings:expire-holds` runs every minute/5 minutes with `withoutOverlapping()`.
- For each expired pending booking:
  - Lock booking and tiers; recheck pending/expiry.
  - Decrement `quantity_held`; set booking `expired`; write history; notify user.
- On confirmation before expiry, convert held to sold and set `confirmed`.
- No expiration in v1 immediate mode.

#### 15.3.13 Edge cases

- Last ticket race: one succeeds; one gets sold out.
- Insufficient inventory: fail with no mutation.
- Multi-tier booking partially unavailable: all-or-nothing rollback.
- Duplicate/retried request: same booking returned; no duplicate tickets.
- Sale window boundary: use `now()` in the same transaction; reject before start/after end.
- Tier deactivated while in cart: reject at booking.
- Show cancelled/event unpublished: reject or cancel per policy.
- Hall capacity vs tier totals: sum of tier totals must not exceed hall capacity; validate when creating/updating tiers and shows.
- User books more than `max_per_booking`: reject.
- Multiple bookings by same user: enforce per-user limits if configured.
- External payment not completed: business policy decides whether to cancel the booking; the platform does not process payment.
- Ticket QR reuse: status check (`valid` only) and `used_at`; make scan endpoint idempotent.
- Timezone/DST: store UTC; render local.
- Sold-out cache stale: never trust cache for writes.
- Booking reference collision: unique constraint + retry with a new random reference.
- Partial ticket generation failure: rollback or idempotent job; booking and tickets must not diverge.

#### 15.3.14 Security and data integrity

- Validate all IDs and quantities; ensure the show/tier belongs to the event/tenant and is published/active.
- Never accept price from the client; use the tier price snapshot from the database.
- Enforce `max_per_booking` and user-level limits server-side.
- Rate-limit booking creation and add CAPTCHA/bot protection if public.
- Use `DB::transaction`, row locks or atomic conditional updates, and check constraints.
- Add `unique(bookings.reference)` and `unique(user_id, idempotency_key)`.
- Lock tiers in deterministic order to avoid deadlocks.
- Do not expose inventory counters or booking details of other users.
- Audit booking/cancellation/ticket status changes.
- Store only necessary personal data; ticket QR tokens are opaque.
- Handle unique violations gracefully; do not return 500 for duplicate submissions.
- Use `DB::afterCommit` for notifications; never send an email before the booking commits.

#### 15.3.15 Laravel implementation recommendations

Suggested services:

- `BookingService` — create, cancel, confirm (if hold mode), expire.
- `TicketInventoryService` — reserve/release/check availability; all writes inside transactions.
- `TicketIssuanceService` — generate ticket rows and QR tokens idempotently.
- `BookingPolicy` — user ownership, cancellation window, tenant rules.
- `BookingRequest` — quantity, show, tier, idempotency key.
- `BookingConfirmed` event/listener — notifications, email, ticket PDF later.
- `ExpireBookingHolds` job (optional).
- `GenerateTicketPdf` job (optional, queued; not required for v1).

Commands/scheduler:

```text
bookings:expire-holds     every minute/5 minutes, withoutOverlapping() (only if hold mode)
```

Database:

- `ticket_tiers` with check constraints and indexes.
- `bookings` with unique reference/idempotency.
- `booking_items` with price snapshots.
- `tickets` with unique ticket number/QR token and status.
- `booking_status_histories` for audit.

Tests:

- Concurrent last-ticket booking.
- Insufficient inventory.
- Multi-tier rollback.
- Idempotent retry.
- Cancellation restoration.
- Max-per-booking.
- Sales window.
- Cross-tenant/tier IDOR.
- Ticket uniqueness and QR validation.

#### 15.3.16 Required infrastructure

- Database transactions and row locking (required).
- Scheduler/queue only if hold mode or asynchronous notifications/ticket generation are used.
- `QUEUE_CONNECTION=database` is sufficient initially.
- No payment gateway, no Paymob integration, no online payment dependency.
- Monitoring for inventory invariant violations and failed booking jobs.

---

## 16. Final Architecture Decision Table

| Topic | Options evaluated | Recommended decision | Why | Implementation consequence |
|---|---|---|---|---|
| Multi-tenancy | Shared, separate, hybrid | **Hybrid** | Shared identity/team/RBAC/provisioning, separate theater/group domain modules; shared DB required for marketplace and reservations | `tenants(type)` + `tenant_members` + global permissions/per-tenant roles; separate `Theaters`, `TheaterGroups`, `Events`, `Reservations`, `Bookings` modules |
| Tenant database isolation | Shared DB vs database-per-tenant | **Shared database** | Cross-tenant discovery, reservations, bookings, central review, and transactions | `tenant_id` on every tenant-owned table; global scope + policies + scoped route bindings; no physical DB layer in v1 |
| Team membership | Separate admin tables vs unified members | **Unified `tenant_members`** | One auth/team/RBAC stack for both tenant types; per-tenant email/phone uniqueness | `tenant_members` with `tenant_id`, `is_owner`, roles; individuals remain in `users`; admins remain in `admins` |
| Tenant type detection | Client-provided vs token-derived | **Token-derived** | Prevent tenant/type spoofing | `tenant_members` token → tenant; `EnsureTenantType` middleware; `X-Tenant` only a consistency check |
| Tenant RBAC | Per-tenant catalog copies vs global catalog | **Global permissions + per-tenant roles** | Avoid duplication/drift; tenants customize roles, not platform permissions | `permissions`, `permission_tenant_types`, `tenant_roles`, `tenant_role_permissions`, `tenant_member_roles`; policies enforced |
| Cross-tenant reservations | Direct model access vs contracts/projections | **Explicit reservation relation + read-only public projections** | A group can interact with many theaters without private-data access | `reservations` with `theater_tenant_id` + `group_tenant_id`; `TheaterCatalogService` returns public DTOs |
| Hall scheduling model | Dynamic vs materialized | **Materialized fixed-duration slots** | Unique constraints, simple locking, predictable UI, event-show blocking | `hall_schedules` → `hall_time_slots`; unique `(hall_id, starts_at)`; generator command |
| Pending reservation strategy | A blocks forever, B does not block, C temporary lock | **C — temporary lock with expiry** | Prevents double requests, avoids permanent blocking, gives clear countdown and automatic cleanup | Pending sets slot `held` + `hold_expires_at`; expiry job releases; approval revalidates under lock |
| Reservation conflict detection | App check vs DB transaction/lock | **DB transaction + row lock + unique slot** | Backend source of truth; safe under concurrency | `lockForUpdate` on slot; status check; unique `(hall_id, starts_at)`; approval recheck; consistent lock order |
| Custom reservation times | Fixed slots vs arbitrary ranges | **Fixed slots for v1** | Simpler integrity; no MySQL exclusion constraints | Reject non-aligned ranges; revisit with interval locking if required |
| Ticket inventory model | Immediate confirm vs temporary hold | **Immediate confirmation for v1** (hold-ready schema) | No online payment to trigger confirmation; simplest correct flow | Atomic decrement of `quantity_sold`; booking status confirmed; optional `quantity_held` reserved for future |
| Oversell protection | Check-then-update vs atomic lock/update | **Row lock or conditional atomic update** | Guarantees invariant under concurrency | `lockForUpdate` per tier in ID order, or conditional `UPDATE ... WHERE sold + qty <= total`; DB check constraint |
| Duplicate bookings | No protection vs idempotency key | **Idempotency key + unique index** | Safe retries and double-submit protection | `unique(user_id, idempotency_key)`; return existing booking on retry |
| Ticket issuance | No ticket rows vs per-ticket records | **Per-ticket records if entry control is required** | Unique ticket/QR, status, duplicate prevention | `tickets` with unique `ticket_number`, `qr_token`, `(booking_item_id, sequence)`; generated in transaction or idempotent job |
| Expiration jobs | None vs scheduled | **Reservation hold expiry required; booking expiry only if hold mode** | Hold mode needs automatic release; v1 immediate booking does not | `reservations:expire-holds` every 5 min; `bookings:expire-holds` only if enabled; `withoutOverlapping()` |
| Notifications | Synchronous vs queued | **Queued after commit** | Avoid false notifications on rollback and blocking requests | `DB::afterCommit`; database queue; `MessageFactory` for email/SMS/push |

**Overall recommendation:** adopt the hybrid tenant architecture, shared database, temporary reservation locks, and immediate confirmed ticket bookings with atomic inventory updates. These choices maximize reuse of the reference foundations, preserve backend-enforced tenant isolation, and keep the no-payment business model simple while remaining safe under concurrency.

**Implementation status:** Phase 2 is complete with these decisions. The multi-tenancy foundation, theater/group/individual registration, tenant authentication, team management, invitations, custom roles, password reset, dashboards, and backend isolation are implemented and verified. Admin review/approval and the operational domain phases are tracked in the later phase folders.

---

## Appendix A — Key Source Files Inspected

### Shared application

- `composer.json`, `modules_statuses.json`, `bootstrap/app.php`, `bootstrap/providers.php`
- `config/auth.php`, `config/auth_guards.php`, `config/tenant.php`, `config/otp.php`
- `app/Providers/AppServiceProvider.php`
- `app/Classes/Authentication/AuthController.php`
- `app/Services/Authentication/AuthFactory.php`, `AuthInterface.php`, `types/SanctumRepository.php`
- `app/Services/OtpService.php`, `app/Models/Otp.php`
- `app/Services/MediaSystem/MediaService.php`, `FileService.php`, `Models/MediaFile.php`, `Models/MediaFolder.php`, `Traits/HasMediaFiles.php`
- `app/Services/Messages/MessageFactory.php`, `types/SmsMessageService.php`, `types/EmailMessageService.php`, `types/PushNotificationService.php`
- `app/Services/ForRepository/RepositoryService.php`, `DatabaseStatementsInitTrait.php`, `TranslateRepositoryService.php`
- `app/Http/Traits/HttpResponsesTrait.php`, `TranslateTrait.php`, `Controller.php`
- `app/Services/ActivityLog/ActivityLogService.php`
- `app/Services/CountryService.php`
- `app/Traits/BelongsToCurrentValuer.php`
- `app/Constants/RegexPatterns.php`, `app/Rules/StrongPassword.php`, `app/Traits/HasPhoneValidation.php`
- `app/Http/Middleware/SetLocaleFromHeader.php`

### Tenancy

- `app/Services/Tenant/DTOs/TenantDTO.php`
- `app/Services/Tenant/Middleware/InitializeTenant.php`
- `app/Services/Tenant/Middleware/XTenantMiddleware.php`
- `app/Services/Tenant/Services/TenantResolver.php`
- `app/Services/Tenant/Services/TenantApiClient.php`
- `app/Services/Tenant/Services/TenantIdentificationService.php`
- `app/Services/Tenant/Services/TenantProvisioningService.php`
- `app/Services/Tenant/Providers/TenantServiceProvider.php`
- `app/Services/Tenant/Console/TenantRunCommand.php`
- `docs/tenant-service.md`

### Valuers

- `Modules/Valuers/routes/api.php`
- `Modules/Valuers/app/Http/Controllers/Valuer/ValuerController.php`
- `Modules/Valuers/app/Http/Controllers/Valuer/ValuerDocumentController.php`
- `Modules/Valuers/app/Http/Controllers/Admin/AdminController.php`
- `Modules/Valuers/app/Repositories/ValuerRequestRepository.php`
- `Modules/Valuers/app/Repositories/ValuerRepository.php`
- `Modules/Valuers/app/Models/Valuer.php`
- `Modules/Valuers/app/Models/ValuerRequest.php`
- `Modules/Valuers/app/Models/ValuerRequestDocument.php`
- `Modules/Valuers/app/Enums/ValuerRequestStatusEnum.php`
- `Modules/Valuers/app/Enums/DocumentStatusEnum.php`
- `Modules/Valuers/app/Enums/DocumentTypeEnum.php`
- `Modules/Valuers/app/Resources/ValuerRequestResource.php`
- `Modules/Valuers/app/Resources/ValuerDashboardResource.php`
- `Modules/Valuers/app/Resources/ValuerDocumentResource.php`
- `Modules/Valuers/app/Http/Requests/RegisterValuerRequest.php`
- `Modules/Valuers/database/migrations/*`
- `Modules/Valuers/lang/{en,ar}/messages.php`

### ValuerAdmins

- `Modules/ValuerAdmins/routes/api.php`
- `Modules/ValuerAdmins/app/Http/Controllers/ValuerAdminController.php`
- `Modules/ValuerAdmins/app/Http/Controllers/ValuerRoleController.php`
- `Modules/ValuerAdmins/app/Repositories/ValuerAdminRepository.php`
- `Modules/ValuerAdmins/app/Repositories/ValuerRoleRepository.php`
- `Modules/ValuerAdmins/app/Services/ValuerCatalogService.php`
- `Modules/ValuerAdmins/app/Models/ValuerAdmins.php`
- `Modules/ValuerAdmins/app/Models/ValuerRole.php`
- `Modules/ValuerAdmins/app/Models/ValuerPermission.php`
- `Modules/ValuerAdmins/app/Models/ValuerModel.php`
- `Modules/ValuerAdmins/app/Models/ValuerAdminRoles.php`
- `Modules/ValuerAdmins/database/migrations/*`
- `Modules/ValuerAdmins/database/seeders/ValuerModulePermissionsSeeder.php`
- `Modules/ValuerAdmins/database/seeders/ValuerRolesDatabaseSeeder.php`
- `Modules/ValuerAdmins/config/config.php`
- `Modules/ValuerAdmins/app/Policies/ValuerAdminPolicy.php`
- `Modules/ValuerAdmins/app/Policies/ValuerRolePolicy.php`
- `Modules/ValuerAdmins/tests/Feature/LoginTenantBindingTest.php`

### Admins

- `Modules/Admins/routes/api.php`
- `Modules/Admins/app/Http/Controllers/AdminsController.php`
- `Modules/Admins/app/Http/Controllers/RolesController.php`
- `Modules/Admins/app/Repositories/AdminsRepository.php`
- `Modules/Admins/app/Repositories/RolesRepository.php`
- `Modules/Admins/app/Models/Admins.php`
- `Modules/Admins/app/Models/Roles.php`
- `Modules/Admins/app/Models/Permissions.php`
- `Modules/Admins/app/Models/Models.php`
- `Modules/Admins/app/Models/RolePermissions.php`
- `Modules/Admins/config/config.php`
- `Modules/Admins/database/seeders/ModulePermissionsSeeder.php`
- `Modules/Admins/database/seeders/RolesDatabaseSeeder.php`
- `Modules/Admins/app/Policies/*`
- `Modules/Admins/database/migrations/*`

### Users and settings

- `Modules/Users/routes/api.php`
- `Modules/Users/app/Http/Controllers/Mobile/UsersController.php`
- `Modules/Users/app/Models/User.php`
- `Modules/Users/app/Repositories/UsersRepository.php`
- `app/Classes/Authentication/Types/Users/RegisterRequest.php`
- `app/Classes/Authentication/Types/Users/VerifyOtpRequest.php`
- `Modules/Settings/app/Models/Settings.php`
- `Modules/Settings/app/Http/Controllers/SettingsController.php`
- `Modules/Settings/routes/api.php`
- `Modules/Settings/database/migrations/2026_02_28_085121_create_settings_table.php`
- `app/Services/CountryService.php`

### Concurrency, scheduler, and idempotency precedents

- `Modules/ManagePlans/app/Services/SubscriptionCheckoutService.php` (`lockCompanySubs`, `checkout`, `confirm`)
- `Modules/ManagePlans/app/Services/ValuerStorageAddonService.php` (`purchaseCheckout`, `confirmPurchase`, `removeScheduled`)
- `Modules/ManagePlans/app/Services/SubscriptionDowngradeService.php`
- `Modules/MoneyCircle/app/Repositories/SeatRepository.php` (seat row locking)
- `Modules/MoneyCircle/app/Repositories/RequestRepository.php` (turn/circle request locking)
- `Modules/MoneyCircle/database/migrations/2026_07_20_000001_add_active_membership_unique_to_circle_members.php` (generated active-state unique index)
- `Modules/MoneyCircle/database/migrations/2026_08_20_000002_create_turn_members_table.php` (unique pivot)
- `Modules/Budgets/app/Repositories/BudgetShareLinksRepository.php` (`lockForUpdate` on share token)
- `app/Services/RoxCustodyIntegration/Webhooks/*` (dedupe hash and idempotent job)
- `app/Services/RoxCustodyIntegration/database/migrations/0001_01_01_000000_create_rox_custody_webhook_events_table.php` (`dedupe_hash` unique)
- `app/Console/Commands/ProcessSubscriptions.php`
- `bootstrap/app.php` (`withSchedule`, `subscriptions:process` daily)
- `config/queue.php`, `.env` (`QUEUE_CONNECTION=database`, `CACHE_STORE=file`)
- `app/Models/Tenant.php`, `app/Console/Commands/CreateTenant.php`, `app/Console/Commands/CleanupFailedTenants.php` (legacy physical-DB remnants; not for TheaterLink)

### Reference and conventions

- `AGENTS.md`
- `.agents/naming_conventions.md`
- `README.md`
- `docs/business_context.md`
- `docs/business-flow.md`
- `docs/tenant-service.md`
- `docs/tasks/tenant-to-valuer-belongs-to-current-valuer.md`
- `changes.md`

---

## Appendix B — Source-Backed Findings Summary

| # | Finding | Source |
|---|---|---|
| 1 | Project is Laravel 12 + nwidart modules | `composer.json`, `module.json` files |
| 2 | Real tenancy is shared DB, not DB-per-tenant | `2026_10_21_000001_merge_tenants_into_valuers_table.php`, `TenantProvisioningService::provision()` |
| 3 | `TENANCY_ENABLED=false`; `InitializeTenant` is effectively inactive | `.env`, `config/tenant.php`, `InitializeTenant` |
| 4 | Tenant isolation is a `valuer_id` global scope | `app/Traits/BelongsToCurrentValuer.php` |
| 5 | Tenant header is an encrypted ULID, publicly obtainable | `TenantIdentificationService`, `ValuerAdminRepository::valuerSubdomain`, `ApiKeyService` |
| 6 | Registration uses cache OTP + temp documents | `ValuerController::register/verify`, `ValuerDocumentController` |
| 7 | Application status is recomputed from document statuses | `ValuerRequestRepository::updateRequestStatus` |
| 8 | Approval requires all docs accepted + ready + phone verified | `ValuerRequestRepository::approve` |
| 9 | Rejection/reopen preserves `rejected_from_status` | `ValuerRequestRepository::reject/reopen` |
| 10 | Provisioning commits before approval status update | `TenantProvisioningService::provision`, `ValuerRequestRepository::approve` |
| 11 | Tenant RBAC is copied per tenant | `ValuerCatalogService` |
| 12 | Tenant permissions are not enforced on routes | `Modules/ValuerAdmins/routes/api.php`, unused policies |
| 13 | Central admin permissions are not enforced on routes | `Modules/Admins/routes/api.php`, unused policies |
| 14 | Private local media is not signed | `MediaFile::getUrlAttribute`, `ValuerDocumentController::download` |
| 15 | Generic filter builder maps client keys to columns | `DatabaseStatementsInitTrait` |
| 16 | No general notification service | only `MessageFactory` and one budget notification |
| 17 | OTP service is production-ready and reusable | `app/Services/OtpService.php` |
| 18 | Media system is production-ready and reusable | `app/Services/MediaSystem/*` |
| 19 | Arabic/English conventions exist across modules | `Modules/*/lang/{en,ar}`, `config/app.php['languages']` |
| 20 | Naming conventions require singular models, `{Noun}Resource`, `{Noun}Repository`, no `Enum` suffix | `.agents/naming_conventions.md` |
| 21 | No hall, schedule, reservation, event, ticket-tier, or booking implementation exists | `Modules/*` and migrations contain no such domain |
| 22 | The reference does contain strong locking precedents | `ValuerRequestRepository::reject/reopen`, `SubscriptionCheckoutService`, `ValuerStorageAddonService`, `SeatRepository` |
| 23 | Scheduler and queue infrastructure exist but are minimal | `bootstrap/app.php` (`subscriptions:process` daily), `QUEUE_CONNECTION=database`, `CACHE_STORE=file` |
| 24 | Idempotency/dedupe precedent exists in RoxCustody | `dedupe_hash` unique migration, `ProcessWebhookJob`, `idempotencyKey` DTOs |
| 25 | Physical-database tenant commands/model remain as dead legacy code | `app/Models/Tenant.php`, `CreateTenant`, `CleanupFailedTenants`; current `tenants` table is dropped |
