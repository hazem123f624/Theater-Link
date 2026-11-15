# Phase 2 Test Cases

Test specification for core architecture, authentication, registration, tenancy, team management, and roles.

**Status legend:**

- `Ready` — implemented and executable now.
- `Gap` — implementation exists but does not meet the expected result.
- `Planned` — deferred to a later phase (for example, optional theater documents).

**Environment:**

- Base URL: `http://127.0.0.1:8000/api/v1`
- Local/testing OTP: `1234`
- Password used in examples: `P@ssw0rd123`
- Seeded admin: `admin@base.com` / `P@ssw0rd`
- Tenant login headers: `X-Subdomain` and optional `X-Tenant` consistency header
- Authenticated tenant requests: `Authorization: Bearer {{token}}`
- Postman: import `docs/postman/TheaterLink-Phase-2.postman_collection.json`

---

## 1. Tenant Authentication

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| TEN-01 | Theater owner signs in | Active theater tenant and active owner exist | `200`; token, tenant, roles, permissions returned | Ready |
| TEN-02 | Group owner signs in while pending | Pending group tenant and active owner exist | `200`; token returned; workspace remains restricted | Ready |
| TEN-03 | Theater account uses group login endpoint | Active theater tenant and owner exist | `422`; no token issued | Ready |
| TEN-04 | Group account uses theater login endpoint | Group tenant and owner exist | `422`; no token issued | Ready |
| TEN-05 | Wrong password | Active tenant member exists | `422`; failed-attempt counter increases | Ready |
| TEN-06 | Repeated wrong passwords lock the account | Five or more wrong passwords | `422`; account locked temporarily | Ready |
| TEN-07 | Inactive member signs in | Member `is_active = false` | `422`; no token | Ready |
| TEN-08 | Inactive workspace signs in | Tenant status is suspended/rejected | `403`; no token | Ready |
| TEN-09 | Access tenant profile without a token | No bearer token | `401` | Ready |
| TEN-10 | Access tenant profile with a valid token | Active tenant member | `200`; own member and tenant returned | Ready |
| TEN-11 | Send a foreign `X-Subdomain` | Valid token; header names another tenant | `404`; no foreign tenant data | Ready |
| TEN-12 | Send a foreign `X-Tenant` | Valid token; header has another tenant ULID | `404`; no foreign tenant data | Ready |
| TEN-13 | Logout revokes the current token | Valid tenant token | `200`; token cannot access `/tenant/me` afterward | Ready |
| TEN-14 | Refresh token revokes the old token | Valid tenant token | `200`; new token works; old token fails | Ready |

---

## 2. Tenant Provisioning

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| PRO-01 | Provision a theater | Unique subdomain and owner email | Tenant active; owner is owner; system roles exist; owner role assigned | Ready |
| PRO-02 | Provision a group | Unique subdomain and owner email | Tenant pending review; owner can sign in; system roles exist | Ready |
| PRO-03 | Duplicate subdomain | Another tenant has the subdomain | Validation error; no second tenant created | Ready |
| PRO-04 | Owner password is hashed | Provision a tenant | Stored password is hashed and never returned | Ready |
| PRO-05 | Owner can log in with the provisioned password | Tenant provisioned | Login succeeds | Ready |
| PRO-06 | Tenant type cannot be changed in place | Existing theater | No API path changes type; a new tenant is required | Ready |

---

## 3. Theater Registration

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| THR-01 | Register a valid theater | Unique subdomain, email, phone | `200`; active tenant; owner token returned | Ready |
| THR-02 | Register with a duplicate subdomain | Subdomain is taken | `422`; suggestions or clear error | Ready |
| THR-03 | Register with an email used by another tenant | Email exists in a different tenant | `200`/OTP sent; separate membership is allowed | Ready |
| THR-04 | Verify the owner phone with a wrong OTP | Pending registration | `422`; attempts increase | Ready |
| THR-05 | Register without required fields | Missing name/address/password | `422`; validation errors | Ready |
| THR-06 | Upload optional theater documents | Valid documents | Deferred to Phase 4 theater profile/media scope | Planned |

---

## 4. Group Registration and Pending Access

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| GRP-01 | Register a valid group | Unique subdomain and valid permit | `200`; tenant pending; application reference returned | Ready |
| GRP-02 | Register without a permit | Missing required document | `422` | Ready |
| GRP-03 | Verify owner phone | Pending registration; correct OTP | Application created; owner can sign in | Ready |
| GRP-04 | Pending owner opens status page | Pending group token | `200`; status, documents, feedback, required actions | Ready |
| GRP-05 | Pending owner opens dashboard | Pending group token | `403`; workspace not active | Ready |
| GRP-06 | Pending owner accesses another group's application | Foreign application id | `404`; no data exposed | Ready |
| GRP-07 | Invalid permit file type or size | Oversized/non-document file | `422`; no file stored | Ready |

---

## 5. Team Management

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| TEAM-01 | List team members | Authorized owner/manager | `200`; only own tenant members, paginated | Ready |
| TEAM-02 | Create a member | Authorized; unique email in tenant | Member created; role assigned | Ready |
| TEAM-03 | Create a duplicate email in the same tenant | Member already exists | `422` | Ready |
| TEAM-04 | Invite the same email in another tenant | Email exists elsewhere | Allowed; separate membership | Ready |
| TEAM-05 | Deactivate a member | Authorized | Member inactive; existing tokens revoked | Ready |
| TEAM-06 | Remove the last owner | Owner is the only owner | `422`; owner preserved | Ready |
| TEAM-07 | Manager edits another tenant's member | Member id from a foreign tenant | `404` | Ready |
| TEAM-08 | Member without `team.update` edits a member | Role lacks permission | `403` | Ready |

---

## 6. Roles and Permissions

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| ROLE-01 | List tenant roles | Authorized member | `200`; only own tenant roles | Ready |
| ROLE-02 | Create a theater role with theater permissions | Theater owner | `200`; role created | Ready |
| ROLE-03 | Assign a group-only permission to a theater role | Theater owner | `422`; permission not allowed for type | Ready |
| ROLE-04 | Create a group role with reservation-create permission | Group owner | `200`; role created | Ready |
| ROLE-05 | Delete a system role | Owner attempts deletion | `422` or forbidden; system role protected | Ready |
| ROLE-06 | Member performs an action without permission | Member role lacks permission | `403`; backend rejects | Ready |
| ROLE-07 | Owner performs the same action | Owner role | `200`; owner has implicit full access | Ready |

---

## 7. Individual and Cross-Actor Isolation

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| ISO-01 | Individual token accesses tenant profile | Individual bearer token | `401`/`403`; tenant ability required | Ready |
| ISO-02 | Tenant token accesses individual profile | Tenant bearer token | `401`/`403`; individual ability required | Ready |
| ISO-03 | Tenant token accesses admin dashboard | Tenant bearer token | `401`/`403`; admin ability required | Ready |
| ISO-04 | Admin token accesses tenant profile | Admin bearer token | `401`/`403`; tenant ability required | Ready |
| ISO-05 | Theater token accesses another theater's resource by ID | Foreign tenant id in a nested route | `404`; no resource data | Ready |
| ISO-06 | Group token accesses another group's application | Foreign application id | `404`; no data | Ready |

---

## 8. Individual Registration

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| IND-01 | Register an individual with a valid phone and password | Location data is seeded; phone/email are unused | `200`; registration OTP is sent; no user row exists yet | Ready |
| IND-02 | Verify the individual registration OTP | Pending registration exists; OTP is `1234` | `200`; user created with E.164 phone; bearer token returned | Ready |
| IND-03 | Log in with the registered individual phone | Individual is verified | `200`; token returned | Ready |
| IND-04 | Use an individual token on a tenant route | Individual token | `403`; tenant ability is required | Ready |
| IND-05 | Use a tenant token on an individual route | Tenant token | `403`; individual ability is required | Ready |
| IND-06 | Register with an already used phone | Phone exists | `422`; uniqueness error | Ready |
| IND-07 | Verify with a wrong OTP | Pending registration exists | `422`; attempt counter increases | Ready |
| IND-08 | Verify after OTP expiry/cooldown | OTP is expired or max attempts reached | `422`; a new OTP is required | Ready |

---

## 9. Dashboard Entry Points

| ID | Test case | Preconditions | Expected result | Status |
|---|---|---|---|---|
| DASH-01 | User opens the user dashboard | Valid individual token | `200`; user profile and empty booking summary | Ready |
| DASH-02 | Theater member opens the theater dashboard | Active theater tenant; valid member token | `200`; theater summary and zeroed counts | Ready |
| DASH-03 | Group member opens the group dashboard | Active group tenant; valid member token | `200`; group summary and zeroed counts | Ready |
| DASH-04 | Pending group opens the group dashboard | Pending group token | `403`; workspace not active | Ready |
| DASH-05 | User token opens a tenant dashboard | Individual token | `403`; tenant ability required | Ready |
| DASH-06 | Theater token opens the group dashboard | Theater token | `404`; tenant type mismatch | Ready |
