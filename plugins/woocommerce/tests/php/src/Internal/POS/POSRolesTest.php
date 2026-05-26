<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use WC_Install;
use WC_Unit_Test_Case;

/**
 * Tests that the M1 POS capability matrix is correctly installed onto the four
 * relevant roles (pos_cashier, pos_manager, administrator, shop_manager) after
 * WC_Install::create_roles() runs.
 *
 * Matrix being verified (M1 — override rows resolve to "no" until M3):
 *
 *     Action / capability         Cashier   Manager   Admin/SM
 *     publish_shop_orders           yes       yes       yes
 *     read_shop_order               yes       yes       yes
 *     read_shop_coupon              yes       yes       yes
 *     publish_shop_coupons          no        yes       yes
 *     refund_shop_orders            no        yes       yes
 *     view_pos                      yes       yes       yes
 *     view_pos_settings             no        yes       yes
 *     edit_pos_settings             no        no        yes
 *     manage_pos_staff              no        no        yes
 *     exit_pos                      no        no        yes
 */
class POSRolesTest extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		WC_Install::create_roles();
	}

	/**
	 * @testdox Should register pos_cashier with cashier matrix and no admin caps.
	 */
	public function test_pos_cashier_matrix(): void {
		$role = get_role( Capabilities::ROLE_CASHIER );
		$this->assertNotNull( $role, 'pos_cashier role should exist.' );

		// Should-have caps.
		foreach (
			array(
				'view_pos',
				'publish_shop_orders',
				'read_shop_order',
				'read_shop_coupon',
			) as $cap
		) {
			$this->assertTrue( $role->has_cap( $cap ), "pos_cashier should have $cap." );
		}

		// Should-NOT-have caps (override rows in M1 = no).
		foreach (
			array(
				'publish_shop_coupons',
				'refund_shop_orders',
				'view_pos_settings',
				'edit_pos_settings',
				'manage_pos_staff',
				'exit_pos',
				'manage_woocommerce',
			) as $cap
		) {
			$this->assertFalse( $role->has_cap( $cap ), "pos_cashier should NOT have $cap in M1." );
		}
	}

	/**
	 * @testdox Should register pos_manager with manager matrix and no admin-only caps.
	 */
	public function test_pos_manager_matrix(): void {
		$role = get_role( Capabilities::ROLE_MANAGER );
		$this->assertNotNull( $role, 'pos_manager role should exist.' );

		// Should-have caps.
		foreach (
			array(
				'view_pos',
				'view_pos_settings',
				'refund_shop_orders',
				'publish_shop_orders',
				'read_shop_order',
				'read_shop_coupon',
				'publish_shop_coupons',
			) as $cap
		) {
			$this->assertTrue( $role->has_cap( $cap ), "pos_manager should have $cap." );
		}

		// Should-NOT-have caps (admin-only rows in M1).
		foreach (
			array(
				'edit_pos_settings',
				'manage_pos_staff',
				'exit_pos',
				'manage_woocommerce',
			) as $cap
		) {
			$this->assertFalse( $role->has_cap( $cap ), "pos_manager should NOT have $cap in M1." );
		}
	}

	/**
	 * @testdox Should grant the full POS cap surface to administrator and shop_manager.
	 */
	public function test_admin_and_shop_manager_get_full_pos_caps(): void {
		foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
			$role = get_role( $role_name );
			$this->assertNotNull( $role, "$role_name should exist." );

			foreach ( Capabilities::pos_specific_capabilities() as $cap ) {
				$this->assertTrue( $role->has_cap( $cap ), "$role_name should have $cap." );
			}
		}
	}

	/**
	 * @testdox Should remove POS roles and POS-specific capabilities when remove_roles() runs.
	 */
	public function test_remove_roles_strips_pos_roles_and_caps(): void {
		WC_Install::remove_roles();

		$this->assertNull( get_role( Capabilities::ROLE_CASHIER ) );
		$this->assertNull( get_role( Capabilities::ROLE_MANAGER ) );

		// Reinstate roles so the WP test harness doesn't carry the cleanup state
		// into subsequent test files.
		WC_Install::create_roles();
	}
}
