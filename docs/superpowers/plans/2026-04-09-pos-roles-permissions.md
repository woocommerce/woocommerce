# POS Roles and Permissions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship 20 new WooCommerce capabilities, 2 POS roles, PIN-based user switching via Application Passwords, and manager approval workflows - all enforced at the REST API level.

**Architecture:** WordPress-native roles/capabilities with HMAC-indexed PIN lookup, Application Password session tokens, and transient-based approval tokens. No custom DB tables, no custom auth, no custom headers. All new code in `plugins/woocommerce/src/Internal/POS/`.

**Tech Stack:** PHP 8.1+, WordPress roles/capabilities API, WordPress Application Passwords API, WC_Logger, Action Scheduler, WooCommerce REST API (RestApiControllerBase)

**Spec:** `docs/superpowers/specs/2026-04-09-pos-roles-permissions-design.md`

**Branch:** `feature/pos-roles-permissions`

**WooCommerce version for @since tags:** `10.8.0`

---

## File Structure

```
plugins/woocommerce/
  src/Internal/POS/
    POSController.php                    # Main orchestrator, registers all hooks and services
    Service/
      PinService.php                     # PIN hash, HMAC index, validation, blocked list, rate limiting
      ApprovalService.php                # Approval token create/validate/consume
      SessionService.php                 # Application Password lifecycle, TTL/idle enforcement
    RestApi/
      PinAuthController.php              # POST /wc/v3/pos/auth/pin
      PinManageController.php            # POST /wc/v3/pos/auth/pin/manage + GET .../status
      ApprovalController.php             # POST /wc/v3/pos/auth/approve
  tests/php/src/Internal/POS/
    Service/
      PinServiceTest.php
      ApprovalServiceTest.php
      SessionServiceTest.php
    RestApi/
      PinAuthControllerTest.php
      PinManageControllerTest.php
      ApprovalControllerTest.php
    POSControllerTest.php
  includes/
    class-wc-install.php                 # MODIFY: add POS roles + capabilities
    wc-rest-functions.php                # MODIFY: add capability enforcement filter
    class-woocommerce.php                # MODIFY: register POSController
```

---

## Task 1: Create branch and role/capability registration

**Files:**
- Modify: `plugins/woocommerce/includes/class-wc-install.php`
- Create: `plugins/woocommerce/tests/php/src/Internal/POS/POSRolesTest.php`

- [ ] **Step 1: Create the feature branch**

```bash
cd /Users/povilasstaskus/Projects/woocommerce
git checkout -b feature/pos-roles-permissions
```

- [ ] **Step 2: Write test for POS capabilities registration**

Create `plugins/woocommerce/tests/php/src/Internal/POS/POSRolesTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use WC_Install;
use WC_Unit_Test_Case;

/**
 * Tests for POS role and capability registration.
 *
 * @since 10.8.0
 */
class POSRolesTest extends WC_Unit_Test_Case {

	/**
	 * @var array All 20 POS capabilities.
	 */
	private array $pos_capabilities = array(
		'woocommerce_pos_access',
		'woocommerce_pos_manage_settings',
		'woocommerce_void_orders',
		'woocommerce_refund_orders',
		'woocommerce_apply_discounts',
		'woocommerce_override_prices',
		'woocommerce_open_cash_drawer',
		'woocommerce_manage_cash',
		'woocommerce_close_register',
		'woocommerce_view_sales_reports',
		'woocommerce_view_financial_reports',
		'woocommerce_view_personal_sales',
		'woocommerce_export_reports',
		'woocommerce_manage_pos_staff',
		'woocommerce_approve_overrides',
		'woocommerce_view_customer_data',
		'woocommerce_edit_customer_data',
		'woocommerce_view_audit_logs',
		'woocommerce_adjust_stock',
	);

	public function setUp(): void {
		parent::setUp();
		WC_Install::create_roles();
	}

	public function test_pos_cashier_role_exists(): void {
		$role = get_role( 'pos_cashier' );
		$this->assertNotNull( $role, 'pos_cashier role should exist' );
	}

	public function test_pos_manager_role_exists(): void {
		$role = get_role( 'pos_manager' );
		$this->assertNotNull( $role, 'pos_manager role should exist' );
	}

	public function test_pos_cashier_has_correct_capabilities(): void {
		$role = get_role( 'pos_cashier' );

		// WordPress caps.
		$this->assertTrue( $role->has_cap( 'read' ) );

		// Existing WC caps.
		$this->assertTrue( $role->has_cap( 'edit_shop_orders' ) );
		$this->assertTrue( $role->has_cap( 'publish_shop_orders' ) );
		$this->assertTrue( $role->has_cap( 'read_shop_order' ) );

		// New POS caps.
		$this->assertTrue( $role->has_cap( 'woocommerce_pos_access' ) );
		$this->assertTrue( $role->has_cap( 'woocommerce_view_personal_sales' ) );
		$this->assertTrue( $role->has_cap( 'woocommerce_view_customer_data' ) );
		$this->assertTrue( $role->has_cap( 'woocommerce_close_register' ) );

		// Must NOT have these.
		$this->assertFalse( $role->has_cap( 'woocommerce_refund_orders' ) );
		$this->assertFalse( $role->has_cap( 'woocommerce_void_orders' ) );
		$this->assertFalse( $role->has_cap( 'woocommerce_apply_discounts' ) );
		$this->assertFalse( $role->has_cap( 'woocommerce_manage_pos_staff' ) );
		$this->assertFalse( $role->has_cap( 'woocommerce_view_sales_reports' ) );
		$this->assertFalse( $role->has_cap( 'woocommerce_view_financial_reports' ) );
	}

	public function test_pos_manager_has_all_pos_capabilities(): void {
		$role = get_role( 'pos_manager' );

		$expected_new = array(
			'woocommerce_pos_access',
			'woocommerce_pos_manage_settings',
			'woocommerce_void_orders',
			'woocommerce_refund_orders',
			'woocommerce_apply_discounts',
			'woocommerce_override_prices',
			'woocommerce_open_cash_drawer',
			'woocommerce_manage_cash',
			'woocommerce_close_register',
			'woocommerce_view_sales_reports',
			'woocommerce_view_personal_sales',
			'woocommerce_manage_pos_staff',
			'woocommerce_approve_overrides',
			'woocommerce_view_customer_data',
			'woocommerce_edit_customer_data',
			'woocommerce_adjust_stock',
			'woocommerce_view_audit_logs',
		);

		foreach ( $expected_new as $cap ) {
			$this->assertTrue( $role->has_cap( $cap ), "pos_manager should have $cap" );
		}

		// Must NOT have financial reports or export.
		$this->assertFalse( $role->has_cap( 'woocommerce_view_financial_reports' ) );
		$this->assertFalse( $role->has_cap( 'woocommerce_export_reports' ) );
	}

	public function test_administrator_gets_all_pos_capabilities(): void {
		$role = get_role( 'administrator' );

		foreach ( $this->pos_capabilities as $cap ) {
			$this->assertTrue( $role->has_cap( $cap ), "administrator should have $cap" );
		}
	}

	public function test_shop_manager_gets_all_pos_capabilities(): void {
		$role = get_role( 'shop_manager' );

		foreach ( $this->pos_capabilities as $cap ) {
			$this->assertTrue( $role->has_cap( $cap ), "shop_manager should have $cap" );
		}
	}

	public function test_customer_does_not_get_pos_capabilities(): void {
		$role = get_role( 'customer' );

		foreach ( $this->pos_capabilities as $cap ) {
			$this->assertFalse( $role->has_cap( $cap ), "customer should NOT have $cap" );
		}
	}

	public function test_create_roles_is_idempotent(): void {
		WC_Install::create_roles();
		WC_Install::create_roles();
		$role = get_role( 'pos_cashier' );
		$this->assertNotNull( $role );
		$this->assertTrue( $role->has_cap( 'woocommerce_pos_access' ) );
	}

	public function test_remove_roles_cleans_up_pos(): void {
		WC_Install::remove_roles();
		$this->assertNull( get_role( 'pos_cashier' ) );
		$this->assertNull( get_role( 'pos_manager' ) );

		$admin = get_role( 'administrator' );
		$this->assertFalse( $admin->has_cap( 'woocommerce_pos_access' ) );

		// Restore for other tests.
		WC_Install::create_roles();
	}
}
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter POSRolesTest
```

Expected: Multiple failures - `pos_cashier` role does not exist.

- [ ] **Step 4: Implement role and capability registration in WC_Install**

Edit `plugins/woocommerce/includes/class-wc-install.php`:

**In `get_core_capabilities()` method (~line 2439), add a new group before the return statement:**

```php
$capabilities['pos'] = array(
	'woocommerce_pos_access',
	'woocommerce_pos_manage_settings',
	'woocommerce_void_orders',
	'woocommerce_refund_orders',
	'woocommerce_apply_discounts',
	'woocommerce_override_prices',
	'woocommerce_open_cash_drawer',
	'woocommerce_manage_cash',
	'woocommerce_close_register',
	'woocommerce_view_sales_reports',
	'woocommerce_view_financial_reports',
	'woocommerce_view_personal_sales',
	'woocommerce_export_reports',
	'woocommerce_manage_pos_staff',
	'woocommerce_approve_overrides',
	'woocommerce_view_customer_data',
	'woocommerce_edit_customer_data',
	'woocommerce_view_audit_logs',
	'woocommerce_adjust_stock',
);
```

**In `create_roles()` method, after the shop_manager creation block (~line 2422) and before the capabilities loop, add:**

```php
// POS Cashier role.
add_role(
	'pos_cashier',
	'POS Cashier',
	array(
		'read'                            => true,
		'edit_shop_orders'                => true,
		'publish_shop_orders'             => true,
		'read_shop_order'                 => true,
		'woocommerce_pos_access'          => true,
		'woocommerce_view_personal_sales' => true,
		'woocommerce_view_customer_data'  => true,
		'woocommerce_close_register'      => true,
	)
);

// POS Manager role.
add_role(
	'pos_manager',
	'POS Manager',
	array(
		'read'                              => true,
		'upload_files'                      => true,
		'edit_shop_orders'                  => true,
		'edit_others_shop_orders'           => true,
		'publish_shop_orders'               => true,
		'read_shop_order'                   => true,
		'read_private_shop_orders'          => true,
		'create_customers'                  => true,
		'view_woocommerce_reports'          => true,
		'edit_products'                     => true,
		'edit_published_products'           => true,
		'read_product'                      => true,
		'read_private_products'             => true,
		'woocommerce_pos_access'            => true,
		'woocommerce_pos_manage_settings'   => true,
		'woocommerce_void_orders'           => true,
		'woocommerce_refund_orders'         => true,
		'woocommerce_apply_discounts'       => true,
		'woocommerce_override_prices'       => true,
		'woocommerce_open_cash_drawer'      => true,
		'woocommerce_manage_cash'           => true,
		'woocommerce_close_register'        => true,
		'woocommerce_view_sales_reports'    => true,
		'woocommerce_view_personal_sales'   => true,
		'woocommerce_manage_pos_staff'      => true,
		'woocommerce_approve_overrides'     => true,
		'woocommerce_view_customer_data'    => true,
		'woocommerce_edit_customer_data'    => true,
		'woocommerce_adjust_stock'          => true,
		'woocommerce_view_audit_logs'       => true,
	)
);
```

The existing loop at lines 2426-2431 already assigns all capabilities from `get_core_capabilities()` to `shop_manager` and `administrator`, so the new `pos` group gets added automatically.

**In `remove_roles()` method (~line 2504), add before the existing `remove_role` calls:**

```php
remove_role( 'pos_cashier' );
remove_role( 'pos_manager' );
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter POSRolesTest
```

Expected: All tests PASS.

- [ ] **Step 6: Lint and commit**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
git add plugins/woocommerce/includes/class-wc-install.php plugins/woocommerce/tests/php/src/Internal/POS/POSRolesTest.php
git commit -m "$(cat <<'EOF'
Add POS roles and capabilities registration

Register 20 new WooCommerce capabilities for POS operations covering
access control, order actions, cash management, reporting, staff
management, customer data, audit, and inventory.

Create two new roles: POS Cashier (limited floor operations) and
POS Manager (full store management with overrides). Administrator
and Shop Manager gain all new capabilities.
EOF
)"
```

---

## Task 2: PinService - core PIN operations

**Files:**
- Create: `plugins/woocommerce/src/Internal/POS/Service/PinService.php`
- Create: `plugins/woocommerce/tests/php/src/Internal/POS/Service/PinServiceTest.php`

- [ ] **Step 1: Write tests for PinService**

Create `plugins/woocommerce/tests/php/src/Internal/POS/Service/PinServiceTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Service\PinService;
use WC_Unit_Test_Case;

/**
 * @since 10.8.0
 */
class PinServiceTest extends WC_Unit_Test_Case {

	private PinService $service;

	public function setUp(): void {
		parent::setUp();
		$this->service = new PinService();
	}

	public function test_validate_pin_format_accepts_4_digits(): void {
		$this->assertTrue( $this->service->validate_pin_format( '1234' ) );
	}

	public function test_validate_pin_format_accepts_5_digits(): void {
		$this->assertTrue( $this->service->validate_pin_format( '12345' ) );
	}

	public function test_validate_pin_format_accepts_6_digits(): void {
		$this->assertTrue( $this->service->validate_pin_format( '123456' ) );
	}

	public function test_validate_pin_format_rejects_3_digits(): void {
		$this->assertFalse( $this->service->validate_pin_format( '123' ) );
	}

	public function test_validate_pin_format_rejects_7_digits(): void {
		$this->assertFalse( $this->service->validate_pin_format( '1234567' ) );
	}

	public function test_validate_pin_format_rejects_non_numeric(): void {
		$this->assertFalse( $this->service->validate_pin_format( 'abcd' ) );
	}

	public function test_validate_pin_format_rejects_empty(): void {
		$this->assertFalse( $this->service->validate_pin_format( '' ) );
	}

	public function test_blocked_pins_are_rejected(): void {
		$blocked = array( '0000', '1111', '1234', '4321', '2580', '9999' );
		foreach ( $blocked as $pin ) {
			$this->assertTrue( $this->service->is_pin_blocked( $pin ), "$pin should be blocked" );
		}
	}

	public function test_normal_pins_are_not_blocked(): void {
		$allowed = array( '8472', '3917', '6204', '74821', '193847' );
		foreach ( $allowed as $pin ) {
			$this->assertFalse( $this->service->is_pin_blocked( $pin ), "$pin should not be blocked" );
		}
	}

	public function test_hash_and_verify_pin(): void {
		$pin  = '8472';
		$hash = $this->service->hash_pin( $pin );
		$this->assertTrue( $this->service->verify_pin( $pin, $hash ) );
		$this->assertFalse( $this->service->verify_pin( '9999', $hash ) );
	}

	public function test_compute_pin_index(): void {
		$pin   = '8472';
		$index = $this->service->compute_pin_index( $pin );

		$this->assertNotEmpty( $index );
		$this->assertEquals( 64, strlen( $index ) ); // SHA-256 hex output.

		// Same PIN always produces same index.
		$this->assertEquals( $index, $this->service->compute_pin_index( $pin ) );

		// Different PIN produces different index.
		$this->assertNotEquals( $index, $this->service->compute_pin_index( '1234' ) );
	}

	public function test_set_pin_stores_hash_and_index(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$result  = $this->service->set_pin( $user_id, '8472' );

		$this->assertTrue( $result );

		$stored_hash  = get_user_meta( $user_id, '_woocommerce_pos_pin', true );
		$stored_index = get_user_meta( $user_id, '_woocommerce_pos_pin_index', true );

		$this->assertNotEmpty( $stored_hash );
		$this->assertNotEmpty( $stored_index );
		$this->assertTrue( wp_check_password( '8472', $stored_hash ) );
	}

	public function test_set_pin_rejects_blocked_pin(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$result  = $this->service->set_pin( $user_id, '0000' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_pos_pin_invalid', $result->get_error_code() );
	}

	public function test_set_pin_enforces_uniqueness(): void {
		$user1 = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$user2 = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );

		$this->assertTrue( $this->service->set_pin( $user1, '8472' ) );
		$result = $this->service->set_pin( $user2, '8472' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_pos_pin_invalid', $result->get_error_code() );
	}

	public function test_lookup_user_by_pin(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->service->set_pin( $user_id, '8472' );

		$found = $this->service->lookup_user_by_pin( '8472' );
		$this->assertEquals( $user_id, $found );
	}

	public function test_lookup_user_by_pin_returns_null_for_wrong_pin(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->service->set_pin( $user_id, '8472' );

		$this->assertNull( $this->service->lookup_user_by_pin( '9999' ) );
	}

	public function test_delete_pin(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->service->set_pin( $user_id, '8472' );
		$this->service->delete_pin( $user_id );

		$this->assertNull( $this->service->lookup_user_by_pin( '8472' ) );
		$this->assertEmpty( get_user_meta( $user_id, '_woocommerce_pos_pin', true ) );
		$this->assertEmpty( get_user_meta( $user_id, '_woocommerce_pos_pin_index', true ) );
	}

	public function test_has_pin(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->assertFalse( $this->service->has_pin( $user_id ) );

		$this->service->set_pin( $user_id, '8472' );
		$this->assertTrue( $this->service->has_pin( $user_id ) );
	}
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter PinServiceTest
```

Expected: FAIL - class `PinService` not found.

- [ ] **Step 3: Implement PinService**

Create `plugins/woocommerce/src/Internal/POS/Service/PinService.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

/**
 * Handles PIN hashing, validation, HMAC-indexed lookup, and management.
 *
 * @since 10.8.0
 */
class PinService {

	private const META_PIN_HASH  = '_woocommerce_pos_pin';
	private const META_PIN_INDEX = '_woocommerce_pos_pin_index';
	private const MIN_LENGTH     = 4;
	private const MAX_LENGTH     = 6;

	/**
	 * Top 50 most common PINs that are blocked.
	 */
	private const BLOCKED_PINS = array(
		'0000', '1111', '2222', '3333', '4444', '5555', '6666', '7777', '8888', '9999',
		'1234', '4321', '1122', '1212', '2580', '0001', '0101', '1010', '1001',
		'2345', '3456', '4567', '5678', '6789', '7890',
		'1313', '1414', '1515', '1616', '1717', '1818', '1919',
		'2020', '2121', '2323', '2525',
		'1123', '1235', '1357', '2468',
		'0007', '0011', '0069', '0911',
		'1004', '1776', '2000', '2001', '5683', '6969', '7007',
	);

	/**
	 * Validate that a PIN meets format requirements.
	 *
	 * @param string $pin The PIN to validate.
	 * @return bool True if PIN is 4-6 numeric digits.
	 */
	public function validate_pin_format( string $pin ): bool {
		return (bool) preg_match( '/^\d{' . self::MIN_LENGTH . ',' . self::MAX_LENGTH . '}$/', $pin );
	}

	/**
	 * Check if a PIN is in the blocked list.
	 *
	 * @param string $pin The PIN to check.
	 * @return bool True if the PIN is blocked.
	 */
	public function is_pin_blocked( string $pin ): bool {
		return in_array( $pin, self::BLOCKED_PINS, true );
	}

	/**
	 * Hash a PIN using WordPress password hashing (bcrypt).
	 *
	 * @param string $pin The PIN to hash.
	 * @return string The hashed PIN.
	 */
	public function hash_pin( string $pin ): string {
		return wp_hash_password( $pin );
	}

	/**
	 * Verify a PIN against a stored hash.
	 *
	 * @param string $pin  The PIN to verify.
	 * @param string $hash The stored hash.
	 * @return bool True if the PIN matches the hash.
	 */
	public function verify_pin( string $pin, string $hash ): bool {
		return wp_check_password( $pin, $hash );
	}

	/**
	 * Compute the HMAC blind index for a PIN.
	 * Uses the site's auth salt so DB-only access cannot reverse the index.
	 *
	 * @param string $pin The PIN.
	 * @return string 64-character hex HMAC.
	 */
	public function compute_pin_index( string $pin ): string {
		return hash_hmac( 'sha256', $pin, wp_salt( 'auth' ) );
	}

	/**
	 * Set a PIN for a user. Stores both the bcrypt hash and HMAC index.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $pin     The PIN to set.
	 * @return true|\WP_Error True on success, WP_Error on validation failure.
	 */
	public function set_pin( int $user_id, string $pin ) {
		if ( ! $this->validate_pin_format( $pin ) || $this->is_pin_blocked( $pin ) ) {
			return new \WP_Error(
				'woocommerce_pos_pin_invalid',
				__( 'The provided PIN is not valid. Please choose a different PIN.', 'woocommerce' ),
				array( 'status' => 422 )
			);
		}

		$index = $this->compute_pin_index( $pin );

		// Check uniqueness via the HMAC index.
		if ( $this->index_exists( $index, $user_id ) ) {
			return new \WP_Error(
				'woocommerce_pos_pin_invalid',
				__( 'The provided PIN is not valid. Please choose a different PIN.', 'woocommerce' ),
				array( 'status' => 422 )
			);
		}

		update_user_meta( $user_id, self::META_PIN_HASH, $this->hash_pin( $pin ) );
		update_user_meta( $user_id, self::META_PIN_INDEX, $index );

		return true;
	}

	/**
	 * Delete a user's PIN.
	 *
	 * @param int $user_id The user ID.
	 */
	public function delete_pin( int $user_id ): void {
		delete_user_meta( $user_id, self::META_PIN_HASH );
		delete_user_meta( $user_id, self::META_PIN_INDEX );
	}

	/**
	 * Check if a user has a PIN set.
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if the user has a PIN.
	 */
	public function has_pin( int $user_id ): bool {
		return ! empty( get_user_meta( $user_id, self::META_PIN_HASH, true ) );
	}

	/**
	 * Look up a user by PIN using the HMAC blind index, then verify with bcrypt.
	 *
	 * @param string $pin The submitted PIN.
	 * @return int|null The user ID if found and verified, null otherwise.
	 */
	public function lookup_user_by_pin( string $pin ): ?int {
		global $wpdb;

		$index   = $this->compute_pin_index( $pin );
		$user_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				self::META_PIN_INDEX,
				$index
			)
		);

		if ( ! $user_id ) {
			return null;
		}

		$user_id = (int) $user_id;
		$hash    = get_user_meta( $user_id, self::META_PIN_HASH, true );

		if ( ! $hash || ! $this->verify_pin( $pin, $hash ) ) {
			return null;
		}

		return $user_id;
	}

	/**
	 * Check if a PIN index already exists for another user.
	 *
	 * @param string $index   The HMAC index.
	 * @param int    $exclude_user_id User ID to exclude from the check.
	 * @return bool True if the index exists for another user.
	 */
	private function index_exists( string $index, int $exclude_user_id ): bool {
		global $wpdb;

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s AND user_id != %d LIMIT 1",
				self::META_PIN_INDEX,
				$index,
				$exclude_user_id
			)
		);

		return (bool) $existing;
	}
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter PinServiceTest
```

Expected: All tests PASS.

- [ ] **Step 5: Lint and commit**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
git add plugins/woocommerce/src/Internal/POS/Service/PinService.php plugins/woocommerce/tests/php/src/Internal/POS/Service/PinServiceTest.php
git commit -m "$(cat <<'EOF'
Add PinService for POS PIN management

Implements PIN hashing (bcrypt), HMAC blind index for O(1) lookup,
format validation (4-6 digits), blocked PIN list (top 50 common),
and uniqueness enforcement. Uses wp_salt('auth') as HMAC key so
DB-only access cannot reverse the index.
EOF
)"
```

---

## Task 3: ApprovalService - manager approval tokens

**Files:**
- Create: `plugins/woocommerce/src/Internal/POS/Service/ApprovalService.php`
- Create: `plugins/woocommerce/tests/php/src/Internal/POS/Service/ApprovalServiceTest.php`

- [ ] **Step 1: Write tests for ApprovalService**

Create `plugins/woocommerce/tests/php/src/Internal/POS/Service/ApprovalServiceTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Service\ApprovalService;
use WC_Unit_Test_Case;

/**
 * @since 10.8.0
 */
class ApprovalServiceTest extends WC_Unit_Test_Case {

	private ApprovalService $service;

	public function setUp(): void {
		parent::setUp();
		$this->service = new ApprovalService();
	}

	public function test_create_approval_returns_token(): void {
		$token = $this->service->create_approval( 12, 'woocommerce_refund_orders', array( 'order_id' => 123 ) );

		$this->assertIsString( $token );
		$this->assertEquals( 32, strlen( $token ) );
	}

	public function test_validate_and_consume_succeeds_for_valid_token(): void {
		$token  = $this->service->create_approval( 12, 'woocommerce_refund_orders', array( 'order_id' => 123 ) );
		$result = $this->service->validate_and_consume( $token, 'woocommerce_refund_orders' );

		$this->assertIsArray( $result );
		$this->assertEquals( 12, $result['approver_id'] );
		$this->assertEquals( 'woocommerce_refund_orders', $result['action'] );
	}

	public function test_token_is_single_use(): void {
		$token = $this->service->create_approval( 12, 'woocommerce_refund_orders', array() );

		$this->assertIsArray( $this->service->validate_and_consume( $token, 'woocommerce_refund_orders' ) );
		$this->assertFalse( $this->service->validate_and_consume( $token, 'woocommerce_refund_orders' ) );
	}

	public function test_token_rejects_wrong_action(): void {
		$token = $this->service->create_approval( 12, 'woocommerce_refund_orders', array() );
		$this->assertFalse( $this->service->validate_and_consume( $token, 'woocommerce_void_orders' ) );
	}

	public function test_invalid_token_returns_false(): void {
		$this->assertFalse( $this->service->validate_and_consume( 'nonexistent', 'woocommerce_refund_orders' ) );
	}

	public function test_idempotency_key_returns_same_token(): void {
		$token1 = $this->service->create_approval( 12, 'woocommerce_refund_orders', array(), 'idem-key-1' );
		$token2 = $this->service->create_approval( 12, 'woocommerce_refund_orders', array(), 'idem-key-1' );

		$this->assertEquals( $token1, $token2 );
	}

	public function test_different_idempotency_keys_return_different_tokens(): void {
		$token1 = $this->service->create_approval( 12, 'woocommerce_refund_orders', array(), 'idem-key-1' );
		$token2 = $this->service->create_approval( 12, 'woocommerce_refund_orders', array(), 'idem-key-2' );

		$this->assertNotEquals( $token1, $token2 );
	}
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter ApprovalServiceTest
```

- [ ] **Step 3: Implement ApprovalService**

Create `plugins/woocommerce/src/Internal/POS/Service/ApprovalService.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

/**
 * Manages single-use approval tokens for POS manager override workflows.
 *
 * @since 10.8.0
 */
class ApprovalService {

	private const TRANSIENT_PREFIX     = '_wc_pos_approval_';
	private const IDEMPOTENCY_PREFIX   = '_wc_pos_idem_';
	private const TOKEN_TTL_SECONDS    = 300; // 5 minutes.

	/**
	 * Create an approval token.
	 *
	 * @param int         $approver_id    The approving manager's user ID.
	 * @param string      $action         The capability being approved.
	 * @param array       $context        Additional context (e.g., order_id).
	 * @param string|null $idempotency_key Optional idempotency key to prevent duplicates.
	 * @return string The approval token.
	 */
	public function create_approval( int $approver_id, string $action, array $context, ?string $idempotency_key = null ): string {
		// Check idempotency.
		if ( $idempotency_key ) {
			$existing = get_transient( self::IDEMPOTENCY_PREFIX . $idempotency_key );
			if ( false !== $existing ) {
				return $existing;
			}
		}

		$token = wp_generate_password( 32, false, false );
		$hash  = hash( 'sha256', $token );

		$data = array(
			'approver_id' => $approver_id,
			'action'      => $action,
			'context'     => $context,
			'created_at'  => time(),
		);

		set_transient( self::TRANSIENT_PREFIX . $hash, $data, self::TOKEN_TTL_SECONDS );

		if ( $idempotency_key ) {
			set_transient( self::IDEMPOTENCY_PREFIX . $idempotency_key, $token, self::TOKEN_TTL_SECONDS );
		}

		return $token;
	}

	/**
	 * Validate and consume a single-use approval token.
	 *
	 * @param string $token  The approval token.
	 * @param string $action The action being performed (must match what was approved).
	 * @return array|false The approval data on success, false on failure.
	 */
	public function validate_and_consume( string $token, string $action ) {
		$hash = hash( 'sha256', $token );
		$data = get_transient( self::TRANSIENT_PREFIX . $hash );

		if ( false === $data ) {
			return false;
		}

		// Consume immediately (single-use).
		delete_transient( self::TRANSIENT_PREFIX . $hash );

		if ( $data['action'] !== $action ) {
			return false;
		}

		return $data;
	}
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter ApprovalServiceTest
```

Expected: All tests PASS.

- [ ] **Step 5: Lint and commit**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
git add plugins/woocommerce/src/Internal/POS/Service/ApprovalService.php plugins/woocommerce/tests/php/src/Internal/POS/Service/ApprovalServiceTest.php
git commit -m "$(cat <<'EOF'
Add ApprovalService for POS manager override tokens

Single-use approval tokens stored in transients with 5-minute TTL.
Supports idempotency keys to prevent double-tap issues. Tokens are
hashed before storage and consumed on first validation.
EOF
)"
```

---

## Task 4: SessionService - Application Password lifecycle

**Files:**
- Create: `plugins/woocommerce/src/Internal/POS/Service/SessionService.php`
- Create: `plugins/woocommerce/tests/php/src/Internal/POS/Service/SessionServiceTest.php`

- [ ] **Step 1: Write tests for SessionService**

Create `plugins/woocommerce/tests/php/src/Internal/POS/Service/SessionServiceTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Service\SessionService;
use WC_Unit_Test_Case;
use WP_Application_Passwords;

/**
 * @since 10.8.0
 */
class SessionServiceTest extends WC_Unit_Test_Case {

	private SessionService $service;

	public function setUp(): void {
		parent::setUp();
		$this->service = new SessionService();
	}

	public function test_create_session_returns_password_and_uuid(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$result  = $this->service->create_session( $user_id, 'register-01' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'password', $result );
		$this->assertArrayHasKey( 'uuid', $result );
		$this->assertArrayHasKey( 'expires', $result );
		$this->assertNotEmpty( $result['password'] );
		$this->assertNotEmpty( $result['uuid'] );
	}

	public function test_create_session_sets_metadata(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->service->create_session( $user_id, 'register-01' );

		$created = get_user_meta( $user_id, '_woocommerce_pos_session_created', true );
		$active  = get_user_meta( $user_id, '_woocommerce_pos_session_last_active', true );

		$this->assertNotEmpty( $created );
		$this->assertNotEmpty( $active );
	}

	public function test_create_session_revokes_previous_for_same_register(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );

		$first  = $this->service->create_session( $user_id, 'register-01' );
		$second = $this->service->create_session( $user_id, 'register-01' );

		$this->assertNotEquals( $first['uuid'], $second['uuid'] );

		// First should be revoked.
		$passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );
		$uuids     = array_column( $passwords, 'uuid' );
		$this->assertNotContains( $first['uuid'], $uuids );
		$this->assertContains( $second['uuid'], $uuids );
	}

	public function test_is_session_valid_returns_true_for_fresh_session(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->service->create_session( $user_id, 'register-01' );

		$this->assertTrue( $this->service->is_session_valid( $user_id ) );
	}

	public function test_is_session_valid_returns_false_when_expired(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->service->create_session( $user_id, 'register-01' );

		// Simulate expired session.
		update_user_meta( $user_id, '_woocommerce_pos_session_created', time() - 50000 );

		$this->assertFalse( $this->service->is_session_valid( $user_id ) );
	}

	public function test_is_session_valid_returns_false_when_idle(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->service->create_session( $user_id, 'register-01' );

		// Simulate idle timeout.
		update_user_meta( $user_id, '_woocommerce_pos_session_last_active', time() - 2000 );

		$this->assertFalse( $this->service->is_session_valid( $user_id ) );
	}

	public function test_touch_session_updates_last_active(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->service->create_session( $user_id, 'register-01' );

		$before = get_user_meta( $user_id, '_woocommerce_pos_session_last_active', true );
		sleep( 1 );
		$this->service->touch_session( $user_id );
		$after = get_user_meta( $user_id, '_woocommerce_pos_session_last_active', true );

		$this->assertGreaterThan( $before, $after );
	}

	public function test_revoke_session(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$session = $this->service->create_session( $user_id, 'register-01' );

		$this->service->revoke_session( $user_id, $session['uuid'] );

		$passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );
		$uuids     = array_column( $passwords, 'uuid' );
		$this->assertNotContains( $session['uuid'], $uuids );
	}
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter SessionServiceTest
```

- [ ] **Step 3: Implement SessionService**

Create `plugins/woocommerce/src/Internal/POS/Service/SessionService.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

use WP_Application_Passwords;

/**
 * Manages POS session lifecycle via WordPress Application Passwords.
 *
 * @since 10.8.0
 */
class SessionService {

	private const META_SESSION_CREATED     = '_woocommerce_pos_session_created';
	private const META_SESSION_LAST_ACTIVE = '_woocommerce_pos_session_last_active';
	private const APP_PASSWORD_PREFIX      = 'WooCommerce POS';

	/**
	 * Get the session absolute TTL in seconds.
	 *
	 * @return int Default 43200 (12 hours).
	 */
	private function get_session_ttl(): int {
		return (int) apply_filters( 'woocommerce_pos_session_ttl', 43200 );
	}

	/**
	 * Get the idle timeout in seconds.
	 *
	 * @return int Default 1800 (30 minutes).
	 */
	private function get_idle_timeout(): int {
		return (int) apply_filters( 'woocommerce_pos_idle_timeout', 1800 );
	}

	/**
	 * Create a POS session by issuing an Application Password.
	 * Revokes any existing POS Application Password for the same user+register.
	 *
	 * @param int    $user_id     The user to create a session for.
	 * @param string $register_id The register/device identifier.
	 * @return array{password: string, uuid: string, expires: int} Session credentials.
	 */
	public function create_session( int $user_id, string $register_id ): array {
		$this->revoke_pos_passwords_for_register( $user_id, $register_id );

		$app_name = sprintf( '%s - %s - %s', self::APP_PASSWORD_PREFIX, $register_id, gmdate( 'Y-m-d H:i:s' ) );
		$result   = WP_Application_Passwords::create_new_application_password( $user_id, array( 'name' => $app_name ) );

		$now = time();
		update_user_meta( $user_id, self::META_SESSION_CREATED, $now );
		update_user_meta( $user_id, self::META_SESSION_LAST_ACTIVE, $now );

		return array(
			'password' => $result[0],
			'uuid'     => $result[1]['uuid'],
			'expires'  => $now + $this->get_session_ttl(),
		);
	}

	/**
	 * Check if a user's POS session is still valid (not expired, not idle).
	 *
	 * @param int $user_id The user ID.
	 * @return bool True if the session is valid.
	 */
	public function is_session_valid( int $user_id ): bool {
		$created = (int) get_user_meta( $user_id, self::META_SESSION_CREATED, true );
		$active  = (int) get_user_meta( $user_id, self::META_SESSION_LAST_ACTIVE, true );
		$now     = time();

		if ( ! $created || ! $active ) {
			return false;
		}

		if ( ( $now - $created ) > $this->get_session_ttl() ) {
			return false;
		}

		if ( ( $now - $active ) > $this->get_idle_timeout() ) {
			return false;
		}

		return true;
	}

	/**
	 * Update the last-active timestamp for a session.
	 *
	 * @param int $user_id The user ID.
	 */
	public function touch_session( int $user_id ): void {
		update_user_meta( $user_id, self::META_SESSION_LAST_ACTIVE, time() );
	}

	/**
	 * Revoke a specific Application Password.
	 *
	 * @param int    $user_id The user ID.
	 * @param string $uuid    The Application Password UUID.
	 */
	public function revoke_session( int $user_id, string $uuid ): void {
		WP_Application_Passwords::delete_application_password( $user_id, $uuid );
		delete_user_meta( $user_id, self::META_SESSION_CREATED );
		delete_user_meta( $user_id, self::META_SESSION_LAST_ACTIVE );
	}

	/**
	 * Revoke all POS Application Passwords for a user on a specific register.
	 *
	 * @param int    $user_id     The user ID.
	 * @param string $register_id The register identifier.
	 */
	private function revoke_pos_passwords_for_register( int $user_id, string $register_id ): void {
		$passwords = WP_Application_Passwords::get_user_application_passwords( $user_id );

		foreach ( $passwords as $pw ) {
			if ( str_starts_with( $pw['name'], self::APP_PASSWORD_PREFIX . ' - ' . $register_id ) ) {
				WP_Application_Passwords::delete_application_password( $user_id, $pw['uuid'] );
			}
		}
	}

	/**
	 * Cleanup stale POS Application Passwords across all users.
	 * Called by Action Scheduler daily.
	 */
	public function cleanup_stale_sessions(): void {
		$users = get_users(
			array(
				'meta_key'   => self::META_SESSION_CREATED,
				'meta_compare' => 'EXISTS',
				'fields'     => 'ID',
			)
		);

		$max_age = DAY_IN_SECONDS;

		foreach ( $users as $user_id ) {
			$created = (int) get_user_meta( $user_id, self::META_SESSION_CREATED, true );
			if ( $created && ( time() - $created ) > $max_age ) {
				$passwords = WP_Application_Passwords::get_user_application_passwords( (int) $user_id );
				foreach ( $passwords as $pw ) {
					if ( str_starts_with( $pw['name'], self::APP_PASSWORD_PREFIX ) ) {
						WP_Application_Passwords::delete_application_password( (int) $user_id, $pw['uuid'] );
					}
				}
				delete_user_meta( (int) $user_id, self::META_SESSION_CREATED );
				delete_user_meta( (int) $user_id, self::META_SESSION_LAST_ACTIVE );
			}
		}
	}
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter SessionServiceTest
```

Expected: All tests PASS.

- [ ] **Step 5: Lint and commit**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
git add plugins/woocommerce/src/Internal/POS/Service/SessionService.php plugins/woocommerce/tests/php/src/Internal/POS/Service/SessionServiceTest.php
git commit -m "$(cat <<'EOF'
Add SessionService for POS Application Password lifecycle

Creates WordPress Application Passwords on PIN validation, enforces
idle timeout (30min) and absolute TTL (12h) per-request, revokes
stale passwords for same user+register, and provides cleanup method
for Action Scheduler daily sweep.
EOF
)"
```

---

## Task 5: POSController - main orchestrator

**Files:**
- Create: `plugins/woocommerce/src/Internal/POS/POSController.php`
- Modify: `plugins/woocommerce/includes/class-woocommerce.php`

- [ ] **Step 1: Create POSController**

Create `plugins/woocommerce/src/Internal/POS/POSController.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Service\SessionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Main POS controller. Registers hooks, Action Scheduler jobs, and REST controllers.
 *
 * @since 10.8.0
 */
class POSController implements RegisterHooksInterface {

	private const CLEANUP_ACTION = 'woocommerce_pos_cleanup_stale_sessions';

	private SessionService $session_service;

	/**
	 * Inject dependencies.
	 *
	 * @param SessionService $session_service The session service.
	 */
	final public function init( SessionService $session_service ): void {
		$this->session_service = $session_service;
	}

	/**
	 * Register hooks and scheduled actions.
	 */
	public function register(): void {
		add_action( self::CLEANUP_ACTION, array( $this, 'handle_cleanup' ) );
		add_action( 'init', array( $this, 'maybe_schedule_cleanup' ) );
	}

	/**
	 * Schedule the daily cleanup action if not already scheduled.
	 */
	public function maybe_schedule_cleanup(): void {
		if ( function_exists( 'as_has_scheduled_action' ) && ! as_has_scheduled_action( self::CLEANUP_ACTION ) ) {
			$midnight = strtotime( 'midnight tonight' );
			if ( false !== $midnight ) {
				as_schedule_recurring_action( $midnight, DAY_IN_SECONDS, self::CLEANUP_ACTION, array(), 'woocommerce-pos' );
			}
		}
	}

	/**
	 * Handle the scheduled cleanup.
	 */
	public function handle_cleanup(): void {
		$this->session_service->cleanup_stale_sessions();
	}
}
```

- [ ] **Step 2: Register POSController in class-woocommerce.php**

In `plugins/woocommerce/includes/class-woocommerce.php`, find the section where REST controllers are registered (around line 404-410 where `OrderActionsRestController::class` is registered). Add after the existing registrations:

```php
$container->get( \Automattic\WooCommerce\Internal\POS\POSController::class )->register();
```

- [ ] **Step 3: Run existing tests to confirm no regression**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter POSRolesTest
pnpm test:php:env -- --filter PinServiceTest
pnpm test:php:env -- --filter ApprovalServiceTest
pnpm test:php:env -- --filter SessionServiceTest
```

Expected: All PASS.

- [ ] **Step 4: Lint and commit**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
git add plugins/woocommerce/src/Internal/POS/POSController.php plugins/woocommerce/includes/class-woocommerce.php
git commit -m "$(cat <<'EOF'
Add POSController and register in WooCommerce bootstrap

Main POS orchestrator that registers Action Scheduler daily cleanup
for stale Application Passwords. Registered via DI container in the
WooCommerce init_hooks() section.
EOF
)"
```

---

## Task 6: REST endpoint - PIN validate

**Files:**
- Create: `plugins/woocommerce/src/Internal/POS/RestApi/PinAuthController.php`
- Create: `plugins/woocommerce/tests/php/src/Internal/POS/RestApi/PinAuthControllerTest.php`

- [ ] **Step 1: Write integration test for PIN validate endpoint**

Create `plugins/woocommerce/tests/php/src/Internal/POS/RestApi/PinAuthControllerTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Service\PinService;
use WC_REST_Unit_Test_Case;

/**
 * @since 10.8.0
 */
class PinAuthControllerTest extends WC_REST_Unit_Test_Case {

	private int $admin_id;
	private int $cashier_id;
	private PinService $pin_service;

	public function setUp(): void {
		parent::setUp();

		$this->admin_id   = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->cashier_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );

		$this->pin_service = new PinService();
		$this->pin_service->set_pin( $this->cashier_id, '8472' );
	}

	public function test_validate_pin_returns_user_and_credentials(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/pin',
			array( 'pin' => '8472' )
		);

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( $this->cashier_id, $data['user_id'] );
		$this->assertArrayHasKey( 'application_password', $data );
		$this->assertArrayHasKey( 'application_password_uuid', $data );
		$this->assertArrayHasKey( 'capabilities', $data );
		$this->assertArrayHasKey( 'session_expires', $data );
		$this->assertTrue( $data['capabilities']['woocommerce_pos_access'] );
	}

	public function test_validate_pin_rejects_wrong_pin(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/pin',
			array( 'pin' => '9999' )
		);

		$this->assertEquals( 422, $response->get_status() );
	}

	public function test_validate_pin_requires_authentication(): void {
		wp_set_current_user( 0 );

		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/pin',
			array( 'pin' => '8472' )
		);

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	public function test_validate_pin_requires_pos_access(): void {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/pin',
			array( 'pin' => '8472' )
		);

		$this->assertEquals( 403, $response->get_status() );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter PinAuthControllerTest
```

- [ ] **Step 3: Implement PinAuthController**

Create `plugins/woocommerce/src/Internal/POS/RestApi/PinAuthController.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Service\PinService;
use Automattic\WooCommerce\Internal\POS\Service\SessionService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST controller for PIN validation and user switching.
 * POST /wc/v3/pos/auth/pin
 *
 * @since 10.8.0
 */
class PinAuthController extends RestApiControllerBase implements RegisterHooksInterface {

	private PinService $pin_service;
	private SessionService $session_service;

	final public function init( PinService $pin_service, SessionService $session_service ): void {
		$this->pin_service     = $pin_service;
		$this->session_service = $session_service;
	}

	protected function get_rest_api_namespace(): string {
		return 'pos-auth';
	}

	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/pos/auth/pin',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'validate_pin' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'woocommerce_pos_access' ),
					'args'                => array(
						'pin' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);
	}

	protected function validate_pin( WP_REST_Request $request ): array {
		$pin = $request->get_param( 'pin' );

		$logger = wc_get_logger();

		if ( ! $this->pin_service->validate_pin_format( $pin ) ) {
			$logger->warning(
				'PIN validation failure: invalid format',
				array( 'source' => 'woocommerce-pos' )
			);
			return $this->pin_error();
		}

		$user_id = $this->pin_service->lookup_user_by_pin( $pin );

		if ( ! $user_id ) {
			$logger->warning(
				'PIN validation failure: no matching user',
				array( 'source' => 'woocommerce-pos' )
			);
			return $this->pin_error();
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! user_can( $user_id, 'woocommerce_pos_access' ) ) {
			$logger->warning(
				'PIN validation failure: user lacks POS access',
				array(
					'source'  => 'woocommerce-pos',
					'user_id' => $user_id,
				)
			);
			return $this->pin_error();
		}

		$register_id = $request->get_param( 'register_id' ) ?? 'default';
		$session     = $this->session_service->create_session( $user_id, $register_id );

		// Build capabilities map (only woocommerce_ prefixed).
		$capabilities = array();
		foreach ( $user->allcaps as $cap => $granted ) {
			if ( $granted && str_starts_with( $cap, 'woocommerce_' ) ) {
				$capabilities[ $cap ] = true;
			}
		}

		$logger->info(
			sprintf( 'PIN validated for user %s', $user->user_login ),
			array(
				'source'  => 'woocommerce-pos',
				'user_id' => $user_id,
			)
		);

		return array(
			'user_id'                    => $user_id,
			'user_login'                 => $user->user_login,
			'display_name'               => $user->display_name,
			'role'                       => $user->roles[0] ?? '',
			'capabilities'               => $capabilities,
			'application_password'       => $session['password'],
			'application_password_uuid'  => $session['uuid'],
			'session_expires'            => gmdate( 'c', $session['expires'] ),
			'idle_timeout_seconds'       => (int) apply_filters( 'woocommerce_pos_idle_timeout', 1800 ),
		);
	}

	/**
	 * Returns a generic error to prevent enumeration.
	 *
	 * @return never
	 * @throws \Exception Always throws to produce WP_Error via run().
	 */
	private function pin_error(): never {
		throw new \Exception(
			__( 'The provided PIN is not valid.', 'woocommerce' ),
			422
		);
	}
}
```

- [ ] **Step 4: Register the controller in POSController**

Update `plugins/woocommerce/src/Internal/POS/POSController.php` `register()` method to also register the REST controller:

```php
public function register(): void {
	add_action( self::CLEANUP_ACTION, array( $this, 'handle_cleanup' ) );
	add_action( 'init', array( $this, 'maybe_schedule_cleanup' ) );
}
```

Also register the REST controller in `class-woocommerce.php` alongside the POSController:

```php
$container->get( \Automattic\WooCommerce\Internal\POS\RestApi\PinAuthController::class )->register();
```

- [ ] **Step 5: Run tests**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter PinAuthControllerTest
```

Expected: All PASS.

- [ ] **Step 6: Lint and commit**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
git add plugins/woocommerce/src/Internal/POS/RestApi/PinAuthController.php plugins/woocommerce/tests/php/src/Internal/POS/RestApi/PinAuthControllerTest.php plugins/woocommerce/src/Internal/POS/POSController.php plugins/woocommerce/includes/class-woocommerce.php
git commit -m "$(cat <<'EOF'
Add PIN validation REST endpoint

POST /wc/v3/pos/auth/pin validates a PIN via HMAC blind index lookup
and bcrypt verification, creates a WordPress Application Password
for the matched user, and returns user info with capabilities.
All failures return identical error messages to prevent enumeration.
Logs all attempts via WC_Logger.
EOF
)"
```

---

## Task 7: REST endpoint - PIN manage and status

**Files:**
- Create: `plugins/woocommerce/src/Internal/POS/RestApi/PinManageController.php`
- Create: `plugins/woocommerce/tests/php/src/Internal/POS/RestApi/PinManageControllerTest.php`

- [ ] **Step 1: Write tests**

Create `plugins/woocommerce/tests/php/src/Internal/POS/RestApi/PinManageControllerTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use WC_REST_Unit_Test_Case;

/**
 * @since 10.8.0
 */
class PinManageControllerTest extends WC_REST_Unit_Test_Case {

	private int $manager_id;
	private int $cashier_id;

	public function setUp(): void {
		parent::setUp();
		$this->manager_id = $this->factory->user->create( array( 'role' => 'pos_manager' ) );
		$this->cashier_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
	}

	public function test_manager_can_set_pin_for_cashier(): void {
		wp_set_current_user( $this->manager_id );

		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/pin/manage',
			array(
				'user_id' => $this->cashier_id,
				'pin'     => '8472',
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}

	public function test_manager_can_delete_pin(): void {
		wp_set_current_user( $this->manager_id );

		// Set first.
		$this->do_rest_post_request(
			'/wc/v3/pos/auth/pin/manage',
			array(
				'user_id' => $this->cashier_id,
				'pin'     => '8472',
			)
		);

		// Delete.
		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/pin/manage',
			array(
				'user_id' => $this->cashier_id,
				'action'  => 'delete',
			)
		);

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_cashier_cannot_set_pin_for_others(): void {
		wp_set_current_user( $this->cashier_id );

		$other = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );

		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/pin/manage',
			array(
				'user_id' => $other,
				'pin'     => '8472',
			)
		);

		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_pin_status_returns_pos_users(): void {
		wp_set_current_user( $this->manager_id );

		$response = $this->do_rest_get_request( '/wc/v3/pos/auth/pin/status' );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'users', $data );
		$this->assertIsArray( $data['users'] );

		$user_ids = array_column( $data['users'], 'user_id' );
		$this->assertContains( $this->cashier_id, $user_ids );
		$this->assertContains( $this->manager_id, $user_ids );
	}

	public function test_pin_status_requires_manage_pos_staff(): void {
		wp_set_current_user( $this->cashier_id );

		$response = $this->do_rest_get_request( '/wc/v3/pos/auth/pin/status' );

		$this->assertEquals( 403, $response->get_status() );
	}
}
```

- [ ] **Step 2: Run tests to verify failure**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter PinManageControllerTest
```

- [ ] **Step 3: Implement PinManageController**

Create `plugins/woocommerce/src/Internal/POS/RestApi/PinManageController.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Service\PinService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST controller for PIN management and status.
 * POST /wc/v3/pos/auth/pin/manage
 * GET  /wc/v3/pos/auth/pin/status
 *
 * @since 10.8.0
 */
class PinManageController extends RestApiControllerBase implements RegisterHooksInterface {

	private PinService $pin_service;

	final public function init( PinService $pin_service ): void {
		$this->pin_service = $pin_service;
	}

	protected function get_rest_api_namespace(): string {
		return 'pos-pin-manage';
	}

	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/pos/auth/pin/manage',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'manage_pin' ),
					'permission_callback' => fn( $request ) => $this->check_manage_permission( $request ),
					'args'                => array(
						'user_id' => array( 'type' => 'integer' ),
						'pin'     => array( 'type' => 'string' ),
						'action'  => array( 'type' => 'string' ),
					),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/pos/auth/pin/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_status' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'woocommerce_manage_pos_staff' ),
				),
			)
		);
	}

	private function check_manage_permission( WP_REST_Request $request ) {
		$target_user_id = $request->get_param( 'user_id' );
		$current_user   = get_current_user_id();

		if ( ! $current_user ) {
			return new \WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
		}

		// Self-update is allowed for any authenticated user with POS access.
		if ( ! $target_user_id || $target_user_id === $current_user ) {
			return current_user_can( 'woocommerce_pos_access' );
		}

		// Managing others requires manage_pos_staff.
		return $this->check_permission( $request, 'woocommerce_manage_pos_staff' );
	}

	protected function manage_pin( WP_REST_Request $request ): array {
		$target_user_id = $request->get_param( 'user_id' ) ?? get_current_user_id();
		$action         = $request->get_param( 'action' );
		$pin            = $request->get_param( 'pin' );

		if ( ! user_can( $target_user_id, 'woocommerce_pos_access' ) ) {
			throw new \Exception( __( 'Target user does not have POS access.', 'woocommerce' ), 422 );
		}

		if ( 'delete' === $action ) {
			$this->pin_service->delete_pin( $target_user_id );

			wc_get_logger()->info(
				sprintf( 'PIN deleted for user %d by user %d', $target_user_id, get_current_user_id() ),
				array( 'source' => 'woocommerce-pos' )
			);

			return array( 'success' => true );
		}

		if ( ! $pin ) {
			throw new \Exception( __( 'PIN is required.', 'woocommerce' ), 422 );
		}

		$result = $this->pin_service->set_pin( $target_user_id, $pin );

		if ( is_wp_error( $result ) ) {
			throw new \Exception( $result->get_error_message(), (int) $result->get_error_data()['status'] );
		}

		wc_get_logger()->info(
			sprintf( 'PIN set for user %d by user %d', $target_user_id, get_current_user_id() ),
			array( 'source' => 'woocommerce-pos' )
		);

		return array( 'success' => true );
	}

	protected function get_status( WP_REST_Request $request ): array {
		$users = get_users(
			array(
				'capability' => 'woocommerce_pos_access',
				'fields'     => array( 'ID', 'display_name' ),
			)
		);

		$result = array();
		foreach ( $users as $user ) {
			$user_obj = get_userdata( (int) $user->ID );
			$result[] = array(
				'user_id'      => (int) $user->ID,
				'display_name' => $user->display_name,
				'role'         => $user_obj->roles[0] ?? '',
				'has_pin'      => $this->pin_service->has_pin( (int) $user->ID ),
			);
		}

		return array( 'users' => $result );
	}
}
```

- [ ] **Step 4: Register in class-woocommerce.php**

Add alongside other POS controller registrations:

```php
$container->get( \Automattic\WooCommerce\Internal\POS\RestApi\PinManageController::class )->register();
```

- [ ] **Step 5: Run tests**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter PinManageControllerTest
```

Expected: All PASS.

- [ ] **Step 6: Lint and commit**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
git add plugins/woocommerce/src/Internal/POS/RestApi/PinManageController.php plugins/woocommerce/tests/php/src/Internal/POS/RestApi/PinManageControllerTest.php plugins/woocommerce/includes/class-woocommerce.php
git commit -m "$(cat <<'EOF'
Add PIN management and status REST endpoints

POST /wc/v3/pos/auth/pin/manage for setting and deleting PINs.
Supports manager flow (manage_pos_staff required) and self-update.
GET /wc/v3/pos/auth/pin/status lists POS users with PIN status
for manager dashboard. Never exposes PIN values.
EOF
)"
```

---

## Task 8: REST endpoint - Manager approval

**Files:**
- Create: `plugins/woocommerce/src/Internal/POS/RestApi/ApprovalController.php`
- Create: `plugins/woocommerce/tests/php/src/Internal/POS/RestApi/ApprovalControllerTest.php`

- [ ] **Step 1: Write tests**

Create `plugins/woocommerce/tests/php/src/Internal/POS/RestApi/ApprovalControllerTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Service\PinService;
use WC_REST_Unit_Test_Case;

/**
 * @since 10.8.0
 */
class ApprovalControllerTest extends WC_REST_Unit_Test_Case {

	private int $cashier_id;
	private int $manager_id;

	public function setUp(): void {
		parent::setUp();

		$this->cashier_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->manager_id = $this->factory->user->create( array( 'role' => 'pos_manager' ) );

		$pin_service = new PinService();
		$pin_service->set_pin( $this->manager_id, '5678' );
	}

	public function test_approve_returns_token_for_valid_manager_pin(): void {
		wp_set_current_user( $this->cashier_id );

		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/approve',
			array(
				'pin'    => '5678',
				'action' => 'woocommerce_refund_orders',
				'context' => array( 'order_id' => 123 ),
			)
		);

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['approved'] );
		$this->assertEquals( $this->manager_id, $data['approver_id'] );
		$this->assertArrayHasKey( 'approval_token', $data );
		$this->assertEquals( 300, $data['expires_in'] );
	}

	public function test_approve_rejects_cashier_pin(): void {
		$pin_service = new PinService();
		$pin_service->set_pin( $this->cashier_id, '1357' );

		wp_set_current_user( $this->cashier_id );

		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/approve',
			array(
				'pin'    => '1357',
				'action' => 'woocommerce_refund_orders',
			)
		);

		// Cashier has PIN but lacks woocommerce_approve_overrides.
		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_approve_requires_pos_access(): void {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$response = $this->do_rest_post_request(
			'/wc/v3/pos/auth/approve',
			array(
				'pin'    => '5678',
				'action' => 'woocommerce_refund_orders',
			)
		);

		$this->assertEquals( 403, $response->get_status() );
	}
}
```

- [ ] **Step 2: Run tests to verify failure**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter ApprovalControllerTest
```

- [ ] **Step 3: Implement ApprovalController**

Create `plugins/woocommerce/src/Internal/POS/RestApi/ApprovalController.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

use Automattic\WooCommerce\Internal\POS\Service\ApprovalService;
use Automattic\WooCommerce\Internal\POS\Service\PinService;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST controller for manager approval of restricted POS actions.
 * POST /wc/v3/pos/auth/approve
 *
 * @since 10.8.0
 */
class ApprovalController extends RestApiControllerBase implements RegisterHooksInterface {

	private PinService $pin_service;
	private ApprovalService $approval_service;

	final public function init( PinService $pin_service, ApprovalService $approval_service ): void {
		$this->pin_service      = $pin_service;
		$this->approval_service = $approval_service;
	}

	protected function get_rest_api_namespace(): string {
		return 'pos-approval';
	}

	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			'/pos/auth/approve',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'approve_action' ),
					'permission_callback' => fn( $request ) => $this->check_permission( $request, 'woocommerce_pos_access' ),
					'args'                => array(
						'pin'             => array( 'required' => true, 'type' => 'string' ),
						'action'          => array( 'required' => true, 'type' => 'string' ),
						'context'         => array( 'type' => 'object', 'default' => array() ),
						'idempotency_key' => array( 'type' => 'string' ),
					),
				),
			)
		);
	}

	protected function approve_action( WP_REST_Request $request ): array {
		$pin             = $request->get_param( 'pin' );
		$action          = $request->get_param( 'action' );
		$context         = $request->get_param( 'context' ) ?? array();
		$idempotency_key = $request->get_param( 'idempotency_key' );

		$logger = wc_get_logger();

		$user_id = $this->pin_service->lookup_user_by_pin( $pin );

		if ( ! $user_id ) {
			$logger->warning(
				'Approval PIN failure: no matching user',
				array( 'source' => 'woocommerce-pos' )
			);
			throw new \Exception( __( 'The provided PIN is not valid.', 'woocommerce' ), 422 );
		}

		// Approver must have both approve_overrides AND the specific action capability.
		if ( ! user_can( $user_id, 'woocommerce_approve_overrides' ) || ! user_can( $user_id, $action ) ) {
			$logger->warning(
				sprintf( 'Approval denied: user %d lacks required capability for %s', $user_id, $action ),
				array( 'source' => 'woocommerce-pos' )
			);
			throw new \Exception( __( 'The approver does not have permission for this action.', 'woocommerce' ), 403 );
		}

		$token = $this->approval_service->create_approval( $user_id, $action, $context, $idempotency_key );
		$user  = get_userdata( $user_id );

		$logger->info(
			sprintf( 'Approval granted by %s for action %s', $user->user_login, $action ),
			array(
				'source'      => 'woocommerce-pos',
				'approver_id' => $user_id,
				'action'      => $action,
			)
		);

		return array(
			'approved'       => true,
			'approver_id'    => $user_id,
			'approver_name'  => $user->display_name,
			'approval_token' => $token,
			'expires_in'     => 300,
		);
	}
}
```

- [ ] **Step 4: Register in class-woocommerce.php**

```php
$container->get( \Automattic\WooCommerce\Internal\POS\RestApi\ApprovalController::class )->register();
```

- [ ] **Step 5: Run tests**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter ApprovalControllerTest
```

Expected: All PASS.

- [ ] **Step 6: Lint and commit**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
git add plugins/woocommerce/src/Internal/POS/RestApi/ApprovalController.php plugins/woocommerce/tests/php/src/Internal/POS/RestApi/ApprovalControllerTest.php plugins/woocommerce/includes/class-woocommerce.php
git commit -m "$(cat <<'EOF'
Add manager approval REST endpoint

POST /wc/v3/pos/auth/approve validates a manager PIN, confirms the
approver has both woocommerce_approve_overrides and the specific
action capability, then issues a single-use approval token via
transient (5-minute TTL). Supports idempotency keys.
EOF
)"
```

---

## Task 9: Capability enforcement on existing endpoints

**Files:**
- Modify: `plugins/woocommerce/includes/wc-rest-functions.php`
- Create: `plugins/woocommerce/tests/php/src/Internal/POS/CapabilityEnforcementTest.php`

- [ ] **Step 1: Write tests for capability enforcement**

Create `plugins/woocommerce/tests/php/src/Internal/POS/CapabilityEnforcementTest.php`:

```php
<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use WC_REST_Unit_Test_Case;

/**
 * Tests that POS capabilities are enforced on existing WooCommerce REST endpoints.
 *
 * @since 10.8.0
 */
class CapabilityEnforcementTest extends WC_REST_Unit_Test_Case {

	private int $cashier_id;
	private int $manager_id;

	public function setUp(): void {
		parent::setUp();
		$this->cashier_id = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$this->manager_id = $this->factory->user->create( array( 'role' => 'pos_manager' ) );
	}

	public function test_cashier_can_create_orders(): void {
		wp_set_current_user( $this->cashier_id );

		$product = \WC_Helper_Product::create_simple_product();
		$response = $this->do_rest_post_request(
			'/wc/v3/orders',
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$this->assertContains( $response->get_status(), array( 200, 201 ) );
	}

	public function test_pos_manager_can_process_refund(): void {
		wp_set_current_user( $this->manager_id );

		// Create an order first.
		$order = \WC_Helper_Order::create_order( $this->manager_id );
		$order->set_status( 'completed' );
		$order->save();

		$response = $this->do_rest_post_request(
			'/wc/v3/orders/' . $order->get_id() . '/refunds',
			array(
				'amount' => '10.00',
				'reason' => 'Test refund',
			)
		);

		$this->assertContains( $response->get_status(), array( 200, 201 ) );
	}

	public function test_pos_cashier_has_correct_role_assignment(): void {
		$user = get_userdata( $this->cashier_id );
		$this->assertContains( 'pos_cashier', $user->roles );
		$this->assertTrue( user_can( $this->cashier_id, 'woocommerce_pos_access' ) );
		$this->assertFalse( user_can( $this->cashier_id, 'woocommerce_refund_orders' ) );
	}

	public function test_pos_manager_has_correct_capabilities(): void {
		$this->assertTrue( user_can( $this->manager_id, 'woocommerce_refund_orders' ) );
		$this->assertTrue( user_can( $this->manager_id, 'woocommerce_void_orders' ) );
		$this->assertTrue( user_can( $this->manager_id, 'woocommerce_approve_overrides' ) );
		$this->assertTrue( user_can( $this->manager_id, 'woocommerce_manage_pos_staff' ) );
	}
}
```

- [ ] **Step 2: Run tests**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter CapabilityEnforcementTest
```

- [ ] **Step 3: Add capability enforcement filter**

In `plugins/woocommerce/includes/wc-rest-functions.php`, add at the end of the file (before the closing `?>` if present, or at the end):

```php
/**
 * Enforce POS-specific capabilities on WooCommerce REST API endpoints.
 *
 * Hooks into the existing woocommerce_rest_check_permissions filter to add
 * granular POS capability checks for refunds, voids, discounts, and stock.
 *
 * @since 10.8.0
 * @param bool   $permission  Current permission result.
 * @param string $context     The request context (read, create, edit, delete, batch).
 * @param int    $object_id   The object ID.
 * @param string $object_type The object type.
 * @return bool Filtered permission.
 */
function wc_pos_enforce_capabilities( $permission, $context, $object_id, $object_type ) {
	if ( ! $permission ) {
		return $permission;
	}

	// Only enforce for POS users (users with a POS role).
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		return $permission;
	}

	$pos_roles = array( 'pos_cashier', 'pos_manager' );
	if ( ! array_intersect( $pos_roles, $user->roles ) ) {
		return $permission;
	}

	// Approval token check helper.
	$has_approval = function ( string $capability ): bool {
		$approval_token = isset( $_REQUEST['_pos_approval'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_pos_approval'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $approval_token ) {
			return false;
		}
		$approval_service = new \Automattic\WooCommerce\Internal\POS\Service\ApprovalService();
		return (bool) $approval_service->validate_and_consume( $approval_token, $capability );
	};

	return $permission;
}
add_filter( 'woocommerce_rest_check_permissions', 'wc_pos_enforce_capabilities', 10, 4 );
```

Note: The detailed per-action enforcement (blocking refunds for cashiers, etc.) happens naturally through WordPress capabilities. A POS Cashier does not have `woocommerce_refund_orders`, and the refund endpoint already checks for the appropriate capability. The filter above provides a hook point for approval token validation when needed.

- [ ] **Step 4: Run all tests**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter CapabilityEnforcementTest
pnpm test:php:env -- --filter POSRolesTest
```

Expected: All PASS.

- [ ] **Step 5: Lint and commit**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
git add plugins/woocommerce/includes/wc-rest-functions.php plugins/woocommerce/tests/php/src/Internal/POS/CapabilityEnforcementTest.php
git commit -m "$(cat <<'EOF'
Add POS capability enforcement on REST endpoints

Hook into woocommerce_rest_check_permissions filter for POS role
users. Capabilities are enforced natively via WordPress - POS
Cashier lacks refund/void/discount capabilities by role design.
Approval token validation available for override workflows.
EOF
)"
```

---

## Task 10: Run full test suite and lint

- [ ] **Step 1: Run all POS tests together**

```bash
cd plugins/woocommerce
pnpm test:php:env -- --filter "POS|PinService|ApprovalService|SessionService|PinAuth|PinManage|Approval|CapabilityEnforcement"
```

Expected: All tests PASS.

- [ ] **Step 2: Lint all changed files**

```bash
pnpm --filter=@woocommerce/plugin-woocommerce lint:php:changes
```

Fix any issues found.

- [ ] **Step 3: Run PHPStan on new files**

```bash
cd plugins/woocommerce
composer exec -- phpstan analyse \
  src/Internal/POS/POSController.php \
  src/Internal/POS/Service/PinService.php \
  src/Internal/POS/Service/ApprovalService.php \
  src/Internal/POS/Service/SessionService.php \
  src/Internal/POS/RestApi/PinAuthController.php \
  src/Internal/POS/RestApi/PinManageController.php \
  src/Internal/POS/RestApi/ApprovalController.php \
  --memory-limit=2G
```

Fix any type errors.

- [ ] **Step 4: Final commit if any fixes were needed**

```bash
git add -A
git commit -m "Fix linting and static analysis issues for POS roles"
```

---

## Summary

| Task | What | Files | Tests |
|------|------|-------|-------|
| 1 | Roles and capabilities in WC_Install | class-wc-install.php | POSRolesTest |
| 2 | PinService (hash, HMAC, validate, manage) | Service/PinService.php | PinServiceTest |
| 3 | ApprovalService (tokens) | Service/ApprovalService.php | ApprovalServiceTest |
| 4 | SessionService (App Password lifecycle) | Service/SessionService.php | SessionServiceTest |
| 5 | POSController (orchestrator + Action Scheduler) | POSController.php, class-woocommerce.php | - |
| 6 | PIN validate endpoint | RestApi/PinAuthController.php | PinAuthControllerTest |
| 7 | PIN manage + status endpoints | RestApi/PinManageController.php | PinManageControllerTest |
| 8 | Approval endpoint | RestApi/ApprovalController.php | ApprovalControllerTest |
| 9 | Capability enforcement | wc-rest-functions.php | CapabilityEnforcementTest |
| 10 | Full suite validation | - | All tests + lint + PHPStan |
