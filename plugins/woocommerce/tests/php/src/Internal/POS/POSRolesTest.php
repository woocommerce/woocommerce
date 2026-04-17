<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use WC_Install;
use WC_Unit_Test_Case;

/**
 * Tests for POS role and capability registration.
 */
class POSRolesTest extends WC_Unit_Test_Case {

	/**
	 * All POS capabilities assigned to admin and shop_manager.
	 *
	 * @var string[]
	 */
	private $all_pos_caps = array(
		'view_pos',
		'view_pos_settings',
		'edit_pos_settings',
		'void_shop_orders',
		'refund_shop_orders',
	);

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->reset_roles();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->reset_roles();
		parent::tearDown();
	}

	/**
	 * Remove and recreate all WC roles with a fresh WP_Roles instance.
	 */
	private function reset_roles(): void {
		WC_Install::remove_roles();
		WC_Install::create_roles();
		// Reload WP_Roles so role objects reflect the DB state updated by create_roles.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_roles'] = new \WP_Roles();
	}

	/**
	 * @testdox POS cashier role exists with the expected WordPress capabilities.
	 */
	public function test_pos_cashier_role_exists_with_wordpress_caps(): void {
		$role = get_role( 'pos_cashier' );

		$this->assertNotNull( $role, 'pos_cashier role should exist' );
		$this->assertTrue( $role->has_cap( 'read' ), 'pos_cashier should have read capability' );
	}

	/**
	 * @testdox POS cashier role has the expected WooCommerce order capabilities.
	 */
	public function test_pos_cashier_role_has_order_caps(): void {
		$role = get_role( 'pos_cashier' );

		$this->assertNotNull( $role, 'pos_cashier role should exist' );
		$this->assertTrue( $role->has_cap( 'edit_shop_order' ), 'pos_cashier should have edit_shop_order' );
		$this->assertTrue( $role->has_cap( 'edit_shop_orders' ), 'pos_cashier should have edit_shop_orders' );
		$this->assertTrue( $role->has_cap( 'edit_published_shop_orders' ), 'pos_cashier should have edit_published_shop_orders' );
		$this->assertTrue( $role->has_cap( 'publish_shop_orders' ), 'pos_cashier should have publish_shop_orders' );
		$this->assertTrue( $role->has_cap( 'read_shop_order' ), 'pos_cashier should have read_shop_order' );
		$this->assertTrue( $role->has_cap( 'read_private_shop_orders' ), 'pos_cashier should have read_private_shop_orders' );
		$this->assertTrue( $role->has_cap( 'read_product' ), 'pos_cashier should have read_product' );
		$this->assertTrue( $role->has_cap( 'read_private_products' ), 'pos_cashier should have read_private_products' );
		$this->assertTrue( $role->has_cap( 'read_shop_coupon' ), 'pos_cashier should have read_shop_coupon' );
		$this->assertTrue( $role->has_cap( 'read_private_shop_coupons' ), 'pos_cashier should have read_private_shop_coupons' );
		$this->assertTrue( $role->has_cap( 'create_customers' ), 'pos_cashier should have create_customers' );
	}

	/**
	 * @testdox POS cashier role has the expected POS capabilities.
	 */
	public function test_pos_cashier_role_has_pos_caps(): void {
		$role = get_role( 'pos_cashier' );

		$this->assertNotNull( $role, 'pos_cashier role should exist' );
		$this->assertTrue( $role->has_cap( 'view_pos' ), 'pos_cashier should have view_pos' );
	}

	/**
	 * @testdox POS cashier role does not have manager-level capabilities.
	 */
	public function test_pos_cashier_role_lacks_manager_caps(): void {
		$role = get_role( 'pos_cashier' );

		$this->assertNotNull( $role, 'pos_cashier role should exist' );
		$this->assertFalse( $role->has_cap( 'refund_shop_orders' ), 'pos_cashier should not have refund_shop_orders' );
		$this->assertFalse( $role->has_cap( 'void_shop_orders' ), 'pos_cashier should not have void_shop_orders' );
		$this->assertFalse( $role->has_cap( 'view_pos_settings' ), 'pos_cashier should not have view_pos_settings' );
		$this->assertFalse( $role->has_cap( 'edit_pos_settings' ), 'pos_cashier should not have edit_pos_settings' );
		$this->assertFalse( $role->has_cap( 'edit_shop_coupons' ), 'pos_cashier should not have edit_shop_coupons' );
		$this->assertFalse( $role->has_cap( 'publish_shop_coupons' ), 'pos_cashier should not have publish_shop_coupons' );
	}

	/**
	 * @testdox POS manager role exists with the expected WordPress capabilities.
	 */
	public function test_pos_manager_role_exists_with_wordpress_caps(): void {
		$role = get_role( 'pos_manager' );

		$this->assertNotNull( $role, 'pos_manager role should exist' );
		$this->assertTrue( $role->has_cap( 'read' ), 'pos_manager should have read capability' );
		$this->assertTrue( $role->has_cap( 'upload_files' ), 'pos_manager should have upload_files capability' );
	}

	/**
	 * @testdox POS manager role has the expected WooCommerce capabilities.
	 */
	public function test_pos_manager_role_has_wc_caps(): void {
		$role = get_role( 'pos_manager' );

		$this->assertNotNull( $role, 'pos_manager role should exist' );
		$this->assertTrue( $role->has_cap( 'edit_shop_order' ), 'pos_manager should have edit_shop_order' );
		$this->assertTrue( $role->has_cap( 'edit_shop_orders' ), 'pos_manager should have edit_shop_orders' );
		$this->assertTrue( $role->has_cap( 'edit_others_shop_orders' ), 'pos_manager should have edit_others_shop_orders' );
		$this->assertTrue( $role->has_cap( 'edit_published_shop_orders' ), 'pos_manager should have edit_published_shop_orders' );
		$this->assertTrue( $role->has_cap( 'publish_shop_orders' ), 'pos_manager should have publish_shop_orders' );
		$this->assertTrue( $role->has_cap( 'read_shop_order' ), 'pos_manager should have read_shop_order' );
		$this->assertTrue( $role->has_cap( 'read_private_shop_orders' ), 'pos_manager should have read_private_shop_orders' );
		$this->assertTrue( $role->has_cap( 'create_customers' ), 'pos_manager should have create_customers' );
		$this->assertTrue( $role->has_cap( 'view_woocommerce_reports' ), 'pos_manager should have view_woocommerce_reports' );
		$this->assertTrue( $role->has_cap( 'edit_products' ), 'pos_manager should have edit_products' );
		$this->assertTrue( $role->has_cap( 'edit_published_products' ), 'pos_manager should have edit_published_products' );
		$this->assertTrue( $role->has_cap( 'read_product' ), 'pos_manager should have read_product' );
		$this->assertTrue( $role->has_cap( 'read_private_products' ), 'pos_manager should have read_private_products' );
	}

	/**
	 * @testdox POS manager role has coupon capabilities.
	 */
	public function test_pos_manager_role_has_coupon_caps(): void {
		$role = get_role( 'pos_manager' );

		$this->assertNotNull( $role, 'pos_manager role should exist' );
		$this->assertTrue( $role->has_cap( 'edit_shop_coupon' ), 'pos_manager should have edit_shop_coupon' );
		$this->assertTrue( $role->has_cap( 'edit_shop_coupons' ), 'pos_manager should have edit_shop_coupons' );
		$this->assertTrue( $role->has_cap( 'edit_others_shop_coupons' ), 'pos_manager should have edit_others_shop_coupons' );
		$this->assertTrue( $role->has_cap( 'edit_published_shop_coupons' ), 'pos_manager should have edit_published_shop_coupons' );
		$this->assertTrue( $role->has_cap( 'publish_shop_coupons' ), 'pos_manager should have publish_shop_coupons' );
		$this->assertTrue( $role->has_cap( 'read_shop_coupon' ), 'pos_manager should have read_shop_coupon' );
		$this->assertTrue( $role->has_cap( 'read_private_shop_coupons' ), 'pos_manager should have read_private_shop_coupons' );
	}

	/**
	 * @testdox POS manager role has the expected POS capabilities.
	 */
	public function test_pos_manager_role_has_pos_caps(): void {
		$role = get_role( 'pos_manager' );

		$expected_caps = array(
			'view_pos',
			'view_pos_settings',
			'void_shop_orders',
			'refund_shop_orders',
		);

		$this->assertNotNull( $role, 'pos_manager role should exist' );

		foreach ( $expected_caps as $cap ) {
			$this->assertTrue( $role->has_cap( $cap ), "pos_manager should have {$cap}" );
		}
	}

	/**
	 * @testdox POS manager role does not have admin-level capabilities.
	 */
	public function test_pos_manager_role_lacks_admin_caps(): void {
		$role = get_role( 'pos_manager' );

		$this->assertNotNull( $role, 'pos_manager role should exist' );
		$this->assertFalse( $role->has_cap( 'edit_pos_settings' ), 'pos_manager should not have edit_pos_settings' );
		$this->assertFalse( $role->has_cap( 'manage_woocommerce' ), 'pos_manager should not have manage_woocommerce' );
	}

	/**
	 * @testdox Administrator gets all POS capabilities.
	 */
	public function test_administrator_gets_all_pos_caps(): void {
		$role = get_role( 'administrator' );

		$this->assertNotNull( $role, 'administrator role should exist' );

		foreach ( $this->all_pos_caps as $cap ) {
			$this->assertTrue( $role->has_cap( $cap ), "administrator should have {$cap}" );
		}
	}

	/**
	 * @testdox Shop manager gets all POS capabilities.
	 */
	public function test_shop_manager_gets_all_pos_caps(): void {
		$role = get_role( 'shop_manager' );

		$this->assertNotNull( $role, 'shop_manager role should exist' );

		foreach ( $this->all_pos_caps as $cap ) {
			$this->assertTrue( $role->has_cap( $cap ), "shop_manager should have {$cap}" );
		}
	}

	/**
	 * @testdox Customer role does not get any POS capabilities.
	 */
	public function test_customer_does_not_get_pos_caps(): void {
		$role = get_role( 'customer' );

		$this->assertNotNull( $role, 'customer role should exist' );

		foreach ( $this->all_pos_caps as $cap ) {
			$this->assertFalse( $role->has_cap( $cap ), "customer should not have {$cap}" );
		}
	}

	/**
	 * @testdox Calling create_roles twice is idempotent.
	 */
	public function test_create_roles_is_idempotent(): void {
		WC_Install::create_roles();

		$cashier = get_role( 'pos_cashier' );
		$manager = get_role( 'pos_manager' );

		$this->assertNotNull( $cashier, 'pos_cashier should still exist after second create_roles call' );
		$this->assertNotNull( $manager, 'pos_manager should still exist after second create_roles call' );
		$this->assertTrue( $cashier->has_cap( 'view_pos' ), 'pos_cashier should still have view_pos' );
		$this->assertTrue( $manager->has_cap( 'view_pos' ), 'pos_manager should still have view_pos' );
	}

	/**
	 * @testdox remove_roles cleans up POS roles and capabilities.
	 */
	public function test_remove_roles_cleans_up_pos_roles_and_caps(): void {
		WC_Install::remove_roles();
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['wp_roles'] = new \WP_Roles();

		$this->assertNull( get_role( 'pos_cashier' ), 'pos_cashier role should be removed' );
		$this->assertNull( get_role( 'pos_manager' ), 'pos_manager role should be removed' );

		$admin        = get_role( 'administrator' );
		$shop_manager = get_role( 'shop_manager' );
		$this->assertNotNull( $admin, 'administrator role should still exist' );

		foreach ( $this->all_pos_caps as $cap ) {
			$this->assertFalse( $admin->has_cap( $cap ), "administrator should not have {$cap} after remove_roles" );
			if ( $shop_manager ) {
				$this->assertFalse( $shop_manager->has_cap( $cap ), "shop_manager should not have {$cap} after remove_roles" );
			}
		}
	}

	/**
	 * @testdox Users with POS roles can exercise capabilities via current_user_can.
	 */
	public function test_current_user_can_with_pos_roles(): void {
		$cashier_user = $this->factory->user->create( array( 'role' => 'pos_cashier' ) );
		$manager_user = $this->factory->user->create( array( 'role' => 'pos_manager' ) );

		$this->assertTrue( user_can( $cashier_user, 'view_pos' ) );
		$this->assertFalse( user_can( $cashier_user, 'refund_shop_orders' ) );

		$this->assertTrue( user_can( $manager_user, 'view_pos' ) );
		$this->assertTrue( user_can( $manager_user, 'refund_shop_orders' ) );
		$this->assertTrue( user_can( $manager_user, 'void_shop_orders' ) );
		$this->assertFalse( user_can( $manager_user, 'edit_pos_settings' ) );
	}

	/**
	 * @testdox get_core_capabilities includes the pos capability group.
	 */
	public function test_get_core_capabilities_includes_pos_group(): void {
		$capabilities = WC_Install::get_core_capabilities();

		$this->assertArrayHasKey( 'pos', $capabilities );
		$this->assertCount( 5, $capabilities['pos'] );

		foreach ( $this->all_pos_caps as $cap ) {
			$this->assertContains( $cap, $capabilities['pos'], "get_core_capabilities pos group should contain {$cap}" );
		}
	}
}
