# WooCommerce POS Roles and Permissions - Design Spec

**Date:** 2026-04-09
**Status:** Final Draft (reviewed by product, security, backend, QA, and business analyst)
**Scope:** WooCommerce Core backend only. Mobile (iOS) and WooPayments changes are out of scope.

---

## 1. Problem Statement

WooCommerce ships only two custom roles (customer, shop_manager). Merchants running POS operations need granular role-based access control: cashiers who can process sales but not refunds, managers who can approve overrides, finance staff with read-only report access, and inventory staff who can adjust stock without touching orders.

The system must support PIN-based fast user switching on shared POS devices, manager approval workflows for restricted actions, and per-employee audit trails - all enforced at the REST API level.

## 2. Goals

- Register 20 new WordPress capabilities covering POS, order actions, cash management, financial reporting, staff management, customer data, audit, and inventory
- Ship 2 new default POS roles (POS Cashier, POS Manager) with appropriate capability assignments. Existing WooCommerce roles (Administrator, Shop Manager) gain the new capabilities. Additional specialized roles can be added in future releases without rebuilding.
- Enforce all new capabilities at the REST API level (not just UI)
- Provide PIN-based user authentication for shared POS devices using WordPress Application Passwords
- Support manager approval workflows for restricted actions
- Log all POS security events using existing WC_Logger and order notes infrastructure
- Design for iOS POS consumption without requiring mobile code changes in this scope

## 3. Non-Goals

- Custom role editor UI in wp-admin (merchants can use existing plugins like User Role Editor)
- Threshold-based permissions (e.g., "approve refunds up to $75") - future phase
- Three-tier permission model (Allowed / Approval Required / Denied) as a configurable system - future phase
- Multi-location role scoping - future phase
- Time tracking / clock in-out - future phase
- Training mode - future phase
- Register open/close cash reconciliation flow (separate spec - capabilities are defined here)
- Offline POS behavior (iOS implementation concern - Application Passwords can be cached client-side)
- WooPayments integration
- iOS / mobile app changes
- Custom audit log tables or endpoints

## 4. Architecture Overview

All new code lives in `plugins/woocommerce/src/Internal/POS/`. The system uses exclusively existing WordPress and WooCommerce infrastructure:

- **WordPress roles and capabilities** for permission enforcement
- **WordPress Application Passwords** for POS session tokens after PIN validation
- **WC_Logger** (`source: woocommerce-pos`) for security event logging
- **Order notes** (`$order->add_order_note()`) for order-related audit trail
- **User meta** for PIN hash storage and session metadata
- **WordPress transients** for approval tokens and rate limiting state
- **Action Scheduler** (bundled with WooCommerce) for stale session cleanup
- **WooCommerce REST API patterns** (V3 namespace, `RestApiControllerBase`) for new endpoints

No custom database tables. No custom authentication mechanisms. No custom HTTP headers.

### 4.1 Scope

This spec ships 2 POS roles (Cashier, Manager) that prove the capability system works end-to-end. Existing WooCommerce roles (Administrator, Shop Manager, Customer) continue to behave as before, with Administrator and Shop Manager gaining all 20 new capabilities. Additional specialized roles (Back Office Manager, Finance Viewer, Inventory Manager) can be added in future releases as simple `add_role()` calls - no architectural changes needed.

---

## 5. New Capabilities (20 total)

### POS Access
| Capability | Description |
|---|---|
| `woocommerce_pos_access` | Can use the POS interface |
| `woocommerce_pos_manage_settings` | Can configure POS registers, receipts, hardware |

### Order Actions
| Capability | Description |
|---|---|
| `woocommerce_void_orders` | Can void/cancel orders |
| `woocommerce_refund_orders` | Can process refunds |
| `woocommerce_apply_discounts` | Can apply manual discounts |
| `woocommerce_override_prices` | Can override item prices |

### Cash Management
| Capability | Description |
|---|---|
| `woocommerce_open_cash_drawer` | Can open cash drawer without a sale (no-sale) |
| `woocommerce_manage_cash` | Can do cash drops, payouts, float adjustments |
| `woocommerce_close_register` | Can perform end-of-day register close |

### Financial and Reporting
| Capability | Description |
|---|---|
| `woocommerce_view_sales_reports` | Can view transaction data, staff performance, register totals |
| `woocommerce_view_financial_reports` | Can view margins, costs, profitability data |
| `woocommerce_view_personal_sales` | Can view own sales data only |
| `woocommerce_export_reports` | Can export/download report data |

### Staff Management
| Capability | Description |
|---|---|
| `woocommerce_manage_pos_staff` | Can manage POS users, PINs, role assignment |
| `woocommerce_approve_overrides` | Can authorize restricted actions via PIN (manager approval) |

### Customer Data
| Capability | Description |
|---|---|
| `woocommerce_view_customer_data` | Can view customer details |
| `woocommerce_edit_customer_data` | Can edit customer records |

### Audit and Compliance
| Capability | Description |
|---|---|
| `woocommerce_view_audit_logs` | Can view POS action audit trail (via WooCommerce > Status > Logs, filtered by source `woocommerce-pos`) |

### Inventory
| Capability | Description |
|---|---|
| `woocommerce_adjust_stock` | Can adjust stock quantities without full product edit access |

---

## 6. Default Roles (2 new)

### 6.1 POS Cashier (`pos_cashier`)

Front-of-house employee processing sales on a shared device.

**wp-admin access:** Minimal dashboard only.

**WordPress capabilities:** `read`

**Existing WC capabilities:** `edit_shop_orders`, `publish_shop_orders`, `read_shop_order`

**New capabilities:** `woocommerce_pos_access`, `woocommerce_view_personal_sales`, `woocommerce_view_customer_data`, `woocommerce_close_register`

**Cannot:** void, refund, discount, override prices, see other staff sales, access reports, open drawer without sale, adjust stock, manage staff.

**Note:** POS Cashier includes `woocommerce_close_register` because end-of-shift drawer counting is a standard cashier responsibility in the industry (Shopify, Square, Toast, Lightspeed all allow this). For merchants who want a "Shift Lead" between Cashier and Manager, they can create a custom role by cloning Cashier and adding `woocommerce_refund_orders` + `woocommerce_approve_overrides`.

### 6.2 POS Manager (`pos_manager`)

Store manager overseeing daily POS operations, approving overrides, managing staff.

**wp-admin access:** Orders, basic reports, customers, basic product editing (prices, stock, visibility).

**WordPress capabilities:** `read`, `upload_files`

**Existing WC capabilities:** `edit_shop_orders`, `edit_others_shop_orders`, `publish_shop_orders`, `read_shop_order`, `read_private_shop_orders`, `create_customers`, `view_woocommerce_reports`, `edit_products`, `edit_published_products`, `read_product`, `read_private_products`

**New capabilities:** `woocommerce_pos_access`, `woocommerce_pos_manage_settings`, `woocommerce_void_orders`, `woocommerce_refund_orders`, `woocommerce_apply_discounts`, `woocommerce_override_prices`, `woocommerce_open_cash_drawer`, `woocommerce_manage_cash`, `woocommerce_close_register`, `woocommerce_view_sales_reports`, `woocommerce_view_personal_sales`, `woocommerce_manage_pos_staff`, `woocommerce_approve_overrides`, `woocommerce_view_customer_data`, `woocommerce_edit_customer_data`, `woocommerce_adjust_stock`, `woocommerce_view_audit_logs`

**Cannot:** see margins/cost data, export reports, manage WooCommerce settings, create/delete/publish new products.

### 6.3 Existing Roles Updated

- **Administrator** - gets all 20 new capabilities
- **Shop Manager** - gets all 20 new capabilities (matches existing pattern)

---

## 7. PIN Authentication System

### 7.1 PIN Storage

- **User meta key:** `_woocommerce_pos_pin` (bcrypt hash) and `_woocommerce_pos_pin_index` (HMAC blind index)
- **Hash algorithm:** `wp_hash_password()` (bcrypt via phpass) for verification. `hash_hmac('sha256', $pin, wp_salt('auth'))` for indexed lookup.
- **PIN length:** 4-6 digits. No store-level setting - users simply enter a PIN of 4 to 6 digits and it is accepted.
- **Uniqueness:** Enforced at set time via the HMAC index. No two users with `woocommerce_pos_access` can share a PIN on the same site.
- **Prerequisite:** Only users with `woocommerce_pos_access` capability can have a PIN set
- **Blocked PINs:** Top 50 most common PINs rejected (0000, 1111, 1234, 4321, 2580, all repeating digits, simple sequences)

### 7.2 PIN Validation and User Switching Flow

1. Shared POS device is already authenticated (store owner set up the app with their credentials)
2. Cashier enters their PIN on the lock screen
3. App sends PIN to `POST /wc/v3/pos/auth/pin` using the device credential
4. Backend validates PIN against all users with `woocommerce_pos_access`
5. On match, backend creates a WordPress Application Password for the matched user via `WP_Application_Passwords::create_new_application_password()`
6. Backend returns user info, capabilities, and the Application Password
7. App stores the Application Password and uses it for all subsequent POS API calls
8. The cashier's requests now carry their identity and capabilities natively - `current_user_can()` resolves against their actual role on every request

### 7.3 PIN Lookup via HMAC Blind Index

PIN validation uses a keyed HMAC as a database lookup index, avoiding the need to iterate all users with bcrypt.

**How it works:**
1. On PIN creation, two values are stored: a bcrypt hash (for verification) and an HMAC index (for lookup)
2. On PIN validation, the submitted PIN is HMAC'd with the site's auth salt, then looked up directly in the database (O(1) query)
3. If a matching user is found, the submitted PIN is verified against the bcrypt hash (single bcrypt call)

```php
// Lookup:
$pin_index = hash_hmac( 'sha256', $submitted_pin, wp_salt( 'auth' ) );
$user = $wpdb->get_row( $wpdb->prepare(
    "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_woocommerce_pos_pin_index' AND meta_value = %s",
    $pin_index
) );
// Then verify with wp_check_password() against stored bcrypt hash
```

**Security:** The HMAC key (`wp_salt('auth')`) lives in `wp-config.php`, not in the database. An attacker with DB-only access sees HMAC values they cannot reverse. An attacker with file access too could compute all 10,000 4-digit PINs, but at that point they have full system access anyway. The bcrypt hash remains the real protection.

**Performance:** PIN validation is ~100ms regardless of user count (one DB lookup + one bcrypt call). No scalability ceiling.

### 7.4 Rate Limiting

- **Primary key:** IP address (hard limit of 20 attempts per 15 minutes regardless of device)
- **Secondary key:** Device identifier from authenticated session (additional per-device tracking)
- Exponential backoff: after 5 failures 30-second lockout, after 10 failures 5-minute lockout, after 15 failures account lock requiring admin intervention
- **Counter reset:** After lockout period expires. Does NOT reset on successful login.
- State stored in WordPress transients (acceptable because TTL enforcement happens per-request independently - see 7.6)

### 7.5 Constant-Time Response

To prevent timing side-channel attacks, PIN validation responses are padded to a constant duration (500ms minimum). The system iterates all candidates in shuffled order and pads response time regardless of when a match is found or whether all candidates are exhausted.

### 7.6 Application Password Lifecycle

- **Naming:** `WooCommerce POS - {register_id} - {date}` for identification in wp-admin
- **One active per user per device:** On new PIN entry, any existing POS Application Password for that user on that device is revoked before creating a new one
- **Session metadata:** `_woocommerce_pos_session_created` and `_woocommerce_pos_session_last_active` user meta fields track session timing
- **Idle timeout:** 30 minutes default (configurable via `woocommerce_pos_idle_timeout`). Checked server-side on each authenticated POS request. If exceeded, the request returns 401 and the Application Password is revoked. This is the primary timeout enforcement - not dependent on WP-Cron.
- **Absolute TTL:** 12 hours default (configurable via `woocommerce_pos_session_ttl`). Checked per-request alongside idle timeout.
- **Lock/logout:** App discards credential. Optionally calls `DELETE /wp/v2/users/{id}/application-passwords/{uuid}` using the device credential to revoke server-side.
- **Stale cleanup:** Action Scheduler recurring action runs daily as belt-and-suspenders, revoking POS Application Passwords older than 24 hours via `as_schedule_recurring_action()`. Primary enforcement is per-request TTL check. Action Scheduler is database-backed and does not depend on page hits (unlike raw WP-Cron).
- **Concurrent sessions:** Same user can be active on multiple devices simultaneously (manager on floor register + back office). PIN re-auth on device A does NOT invalidate device B.
- **Role change mid-session:** Capabilities are checked per-request via `current_user_can()`. If a cashier is promoted or demoted, their next API call reflects the new capabilities immediately. No stale session.

### 7.7 Jetpack-Connected Site Compatibility

The iOS app already has a production system (`appPasswordsWithJetpack` mode) that:
1. Detects Application Password support on Jetpack-connected sites via REST API root discovery
2. Routes requests directly to the site using Application Passwords, bypassing the Jetpack tunnel

For POS PIN auth:
- PIN validation request goes through the Jetpack tunnel (using the device's WPCOM credential)
- The backend creates an Application Password on the local WordPress site
- Subsequent POS requests use the Application Password directly to the site (bypassing the tunnel)
- The iOS app already supports this dual-credential, dual-transport pattern

---

## 8. Manager Approval System

### 8.1 Flow

1. Cashier attempts a restricted action (e.g., refund)
2. POS app shows PIN prompt for manager approval
3. Manager enters their PIN
4. App sends PIN + action details to `POST /wc/v3/pos/auth/approve`
5. Backend validates PIN, confirms the matched user has `woocommerce_approve_overrides` AND the specific capability (e.g., `woocommerce_refund_orders`)
6. Backend returns an approval token (short-lived, single-use)
7. App includes the approval token when performing the restricted action
8. Backend validates the approval token and allows the action
9. Manager stays unauthenticated - the cashier's session continues

### 8.2 Approval Token

- Generated via `wp_generate_password(32, false, false)`
- Stored in a transient: `_wc_pos_approval_{hash}` with 5-minute TTL
- Contains: approver_user_id, approved_action, context (e.g., order_id), timestamp
- Single-use: deleted after consumption
- Passed by the client as a request parameter `_pos_approval` on the approved action
- **No idempotency key:** Double-tap prevention belongs in the iOS client (disable button after first tap). Backend idempotency was evaluated and removed because it stored raw tokens in transients, creating a token leakage path.

### 8.3 Self-Approval

Managers can approve their own actions. This is industry standard (Shopify, Square, Toast all allow it).

### 8.4 Approval Timeout

If the approval prompt sits for 5 minutes without input, it expires. The transient TTL enforces this server-side.

---

## 9. REST API Endpoints (4 new)

All endpoints are under the `wc/v3` namespace. Controllers extend `RestApiControllerBase` (modern, DI-aware pattern).

### 9.1 `POST /wc/v3/pos/auth/pin`

**Purpose:** Validate PIN and issue credentials for the matched user.

**Authentication:** Requires an authenticated request. Caller must have `woocommerce_pos_access`.

**Request:**
```json
{ "pin": "1234" }
```

**Success Response (200):**
```json
{
  "user_id": 7,
  "user_login": "cashier-jane",
  "display_name": "Jane Smith",
  "role": "pos_cashier",
  "capabilities": {
    "woocommerce_pos_access": true,
    "woocommerce_close_register": true,
    "woocommerce_view_personal_sales": true,
    "woocommerce_view_customer_data": true
  },
  "application_password": "xxxx xxxx xxxx xxxx",
  "application_password_uuid": "uuid-here",
  "session_expires": "2026-04-10T03:30:00Z",
  "idle_timeout_seconds": 1800
}
```

**Error Responses:**
- 401: Invalid or expired device credentials
- 403: Caller lacks `woocommerce_pos_access`
- 422: Invalid PIN (format error, no match, or blocked PIN - same generic message for all to prevent enumeration)
- 429: Rate limited (too many failed attempts)

**Side effects:**
- Revokes any existing POS Application Password for the matched user on this device
- Creates new Application Password via `WP_Application_Passwords::create_new_application_password()`
- Logs `pin_validate_success` or `pin_validate_failure` via WC_Logger
- Sets session metadata in user meta

### 9.2 `POST /wc/v3/pos/auth/pin/manage`

**Purpose:** Set or delete a PIN for a user.

**Authentication:** Required. Two modes:
- **Manager flow:** Caller has `woocommerce_manage_pos_staff`. Must provide `user_id`.
- **Self-update flow:** Caller sets their own PIN. Must provide `current_pin` for verification. No `user_id` needed.

**Set PIN Request:**
```json
{ "user_id": 7, "pin": "5678" }
```

**Delete PIN Request:**
```json
{ "user_id": 7, "action": "delete" }
```

**Success Response (200):**
```json
{ "success": true }
```

**Error Responses:**
- 403: Insufficient permissions
- 422: PIN fails validation (identical message for format errors, blocked PINs, and uniqueness collisions to prevent enumeration)

**Side effects:**
- Hashes PIN with `wp_hash_password()`
- Stores/updates `_woocommerce_pos_pin` user meta
- Validates target user has `woocommerce_pos_access`
- Logs via WC_Logger

### 9.3 `GET /wc/v3/pos/auth/pin/status`

**Purpose:** List which POS users have PINs set (for manager dashboard).

**Authentication:** Required. Caller must have `woocommerce_manage_pos_staff`.

**Response (200):**
```json
{
  "users": [
    { "user_id": 7, "display_name": "Jane Smith", "role": "pos_cashier", "has_pin": true },
    { "user_id": 8, "display_name": "Bob Jones", "role": "pos_cashier", "has_pin": false },
    { "user_id": 12, "display_name": "Store Manager", "role": "pos_manager", "has_pin": true }
  ]
}
```

Returns only users with `woocommerce_pos_access` capability. Never returns PIN values.

### 9.4 `POST /wc/v3/pos/auth/approve`

**Purpose:** Manager enters PIN to approve a restricted action for the current cashier.

**Authentication:** Required. Caller must have `woocommerce_pos_access`.

**Request:**
```json
{
  "pin": "5678",
  "action": "woocommerce_refund_orders",
  "context": { "order_id": 123 }
}
```

**Success Response (200):**
```json
{
  "approved": true,
  "approver_id": 12,
  "approver_name": "Store Manager",
  "approval_token": "random-32-char-token",
  "expires_in": 300
}
```

**Error Responses:**
- 403: Matched user lacks `woocommerce_approve_overrides` or the specific action capability
- 422: No matching user or invalid PIN (same generic message to prevent enumeration)
- 429: Rate limited

**Side effects:**
- Stores approval token in transient (5-minute TTL)
- Logs approval via WC_Logger
- If the action has an associated order, adds an order note recording the approval

---

## 10. REST API Capability Enforcement

### 10.1 Enforcement via Existing Filter

Capability checks are added to existing WooCommerce REST API endpoints using the `woocommerce_rest_check_permissions` filter (already exists in `wc-rest-functions.php`). This avoids modifying legacy controller methods directly.

For new POS endpoints, checks are in the `permission_callback` of each route registration.

### 10.2 Specific Enforcement Points

**Order endpoints (`/wc/v3/orders`):**
- Creating a refund: check `woocommerce_refund_orders` (or valid approval token)
- Setting order status to cancelled: check `woocommerce_void_orders` (or valid approval token)
- Applying a discount line item: check `woocommerce_apply_discounts` (or valid approval token)

**Product endpoints (`/wc/v3/products`):**
- Updating `stock_quantity` without other product fields: allowed with just `woocommerce_adjust_stock` (does not require full `edit_products`)

**Report endpoints (`/wc-analytics/*`):**
- Responses containing cost/margin data: stripped unless caller has `woocommerce_view_financial_reports`
- Report scope: filtered to current user's transactions only if caller has `woocommerce_view_personal_sales` but not `woocommerce_view_sales_reports`

**Customer endpoints (`/wc/v3/customers`):**
- Read access: requires `woocommerce_view_customer_data`
- Write access: requires `woocommerce_edit_customer_data`

### 10.3 Approval Token Validation

When an endpoint check fails (user lacks the required capability), the endpoint checks for a `_pos_approval` parameter:

```php
if ( ! current_user_can( 'woocommerce_refund_orders' ) ) {
    $approval = $request->get_param( '_pos_approval' );
    if ( ! $this->pos_approval_service->validate_and_consume( $approval, 'woocommerce_refund_orders' ) ) {
        return new WP_Error( 'woocommerce_rest_cannot_refund', 'Sorry, you are not allowed to process refunds.', array( 'status' => 403 ) );
    }
}
```

The approval token is single-use (consumed on validation).

---

## 11. Logging

All POS logging uses existing WooCommerce infrastructure. No custom tables or endpoints.

### 11.1 Security Events via WC_Logger

```php
$logger = wc_get_logger();
$logger->info( 'PIN validated for user cashier-jane', array(
    'source'  => 'woocommerce-pos',
    'user_id' => 7,
    'ip'      => $ip_address,
) );
```

**Events logged:**
| Event | Level | Details |
|---|---|---|
| PIN validation success | `info` | user_id, ip, register_id |
| PIN validation failure | `warning` | ip, register_id, attempt_count |
| Rate limit triggered | `warning` | ip, register_id, lockout_duration |
| PIN set/changed | `info` | manager_id, target_user_id |
| Session started | `info` | user_id, register_id |
| Session locked/expired | `info` | user_id, reason (timeout/manual/idle) |
| Application Password created for POS | `info` | user_id, uuid |
| Application Password revoked | `info` | user_id, uuid, reason |

**Never logged:** PIN values (even failed ones), Application Password values.

**Source:** All entries use `source => 'woocommerce-pos'` for easy filtering in WooCommerce > Status > Logs.

**Retention:** Stores should configure WC_Logger retention to meet their compliance requirements. PCI DSS requires 12-month retention for security logs. A `woocommerce_pos_log_retention_days` filter is provided for stores to set POS-specific retention (default: 365 days).

### 11.2 Order Action Audit via Order Notes

When POS actions affect orders, standard order notes are added:

```php
$order->add_order_note(
    sprintf( 'Refund of %s processed by %s (POS). Approved by %s via PIN override.', $amount, $cashier_name, $manager_name )
);
```

This uses the existing order notes system which is already exposed via `GET /wc/v3/orders/{id}/notes`.

---

## 12. Code Organization

```
plugins/woocommerce/src/Internal/POS/
    POSController.php              # Main controller, registers hooks, cron, and endpoints
    Service/
        PinService.php             # PIN hashing, validation, lookup, rate limiting
        ApprovalService.php        # Manager approval token creation and validation
        SessionService.php         # Application Password lifecycle, idle/TTL enforcement
    RestApi/
        PinAuthController.php      # POST /pos/auth/pin
        PinManageController.php    # POST /pos/auth/pin/manage, GET /pos/auth/pin/status
        ApprovalController.php     # POST /pos/auth/approve
```

All classes follow WooCommerce conventions:
- PSR-4 autoloading under `Automattic\WooCommerce\Internal\POS`
- DI container with `init()` method for dependency injection
- Controllers extend `RestApiControllerBase` (modern, DI-aware)
- Registered in `class-woocommerce.php` via `$container->get( POSController::class )->register()`

---

## 13. Installation and Migration

### 13.1 Role Creation

Roles and capabilities are registered in `WC_Install::create_roles()` following the existing pattern. The 2 new POS roles are created with their capability sets. All 20 new capabilities are added to Administrator and Shop Manager roles via explicit `$wp_roles->add_cap()` calls.

### 13.2 Upgrade Path

A version-gated updater (e.g., `wc_update_XYZ_add_pos_roles`) calls `add_cap()` for each new capability on Administrator and Shop Manager. `add_role()` is a no-op if the role already exists, so upgrades only add the new capabilities to existing roles.

### 13.3 Deactivation

`WC_Install::remove_roles()` is updated to remove the 2 new POS roles and all 20 new capabilities on plugin deactivation.

### 13.4 Multisite

Roles are per-site (WordPress default). A user who is pos_manager on Site A and pos_cashier on Site B gets the correct capabilities for each site. Super admin capabilities follow WordPress core behavior.

---

## 14. Security Summary

| Concern | Mitigation |
|---|---|
| PIN brute force | Rate limiting per IP (hard) + per device (soft), exponential backoff, blocked common PINs |
| PIN timing attack | Constant-time response padding (500ms), shuffled iteration order |
| PIN enumeration on set | Identical error messages for format and uniqueness errors |
| PIN storage | bcrypt via `wp_hash_password()` for verification. HMAC blind index via `hash_hmac('sha256', $pin, wp_salt('auth'))` for O(1) lookup. |
| Token security | WordPress Application Passwords (core, battle-tested since WP 5.6) |
| Token lifetime | 12h absolute TTL + 30min idle timeout, enforced per-request. Action Scheduler cleanup as backup. |
| Credential exposure | PINs and Application Passwords never in logs or error responses |
| Transport security | HTTPS enforced on PIN endpoints |
| Capability enforcement | `current_user_can()` at REST API level via existing `woocommerce_rest_check_permissions` filter |
| Role change mid-session | Capabilities checked per-request. No stale sessions. |
| Concurrent sessions | Allowed. One Application Password per user per device. |
| Audit trail | WC_Logger for security events, order notes for order actions |
| Jetpack compatibility | Uses existing Application Password + `appPasswordsWithJetpack` iOS infrastructure |
| PCI DSS Req 7 (RBAC) | Granular capabilities, need-to-know basis |
| PCI DSS Req 8 (Unique IDs) | Each employee has unique PIN, individual Application Passwords |
| PCI DSS Req 10 (Audit) | All PIN attempts and approvals logged with timestamps. 365-day default retention. |

---

## 15. Testing Strategy

### Essential (must-have before merge)

**Unit tests:**
- PIN hashing and comparison (correct PIN matches, wrong PIN rejects)
- Blocked PIN rejection (all 50 common PINs)
- PIN uniqueness enforcement
- Rate limiting state machine (all transitions: normal, 30s lockout, 5min lockout, admin lock)
- Approval token creation, validation, single-use consumption, expiry
- Capability checks for each new capability on relevant endpoints
- Role creation with correct capability sets
- Role creation idempotency (repeated activation)
- Session TTL and idle timeout enforcement

**Integration tests:**
- Full PIN validation -> Application Password creation -> API call as switched user
- Manager approval -> token creation -> restricted action with token -> token consumed
- Capability enforcement: cashier blocked from refund, allowed with approval token
- Application Password revocation on PIN re-entry (one active per user per device)

**Security tests:**
- Rate limiting: confirm lockout after N failures from same IP
- Anti-enumeration: confirm identical error responses for format/uniqueness/not-found
- Constant-time: confirm response time does not vary with candidate position

### Nice-to-have

- Idle timeout precision (30-minute boundary)
- PIN complexity validation edge cases
- Multisite role isolation
- WP-Cron stale cleanup execution
- Concurrent session behavior (same user, two devices)

---

## 16. Future Enhancements (documented, not in scope)

**High priority (from merchant feedback):**
- Threshold-based permissions (discount up to X%, refund under $Y) - the most requested capability from merchant interviews. The current architecture supports this without rebuild: capabilities remain binary (can/can't), thresholds are stored as user or role meta, and the existing manager approval flow is the mechanism triggered when a threshold is exceeded.
- Remote manager approval via mobile notification - competitive differentiator over Square
- Scoped customer data for cashiers (lookup-only view vs. full profile access)
- End-of-shift per-cashier summary (transaction count, total sales, drawer reconciliation)

**Medium priority:**
- Additional specialized roles: Back Office Manager, Finance Viewer, Inventory Manager, Shift Lead (add via `add_role()` when needed, no architectural changes)
- Three-tier configurable permission model (Allowed / Approval Required / Denied)
- Multi-location role scoping (per-store permissions)
- PIN expiration/rotation policy

**Lower priority:**
- Time tracking / clock in-out capabilities
- Training mode (sandbox transactions)
- Custom role editor UI in wp-admin
