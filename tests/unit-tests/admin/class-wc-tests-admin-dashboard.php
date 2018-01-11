<?php
/**
 * Class WC_Tests_Admin_Dashboard file.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * Tests for the WC_Admin_Dashboard class.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Tests_Admin_Dashboard extends WC_Unit_Test_Case {

	/**
	 * Test: __construct
	 *
	 * @dataProvider construct_user_cap_provider()
	 *
	 * @param string $cap The specific capability the user should be granted.
	 * @param bool   $should_load Whether or not the init() method be hooked to 'wp_dashboard_load'.
	 */
	public function test__construct_checks_user_capabilities( $cap, $should_load ) {
		$user = $this->factory()->user->create_and_get();
		$user->add_cap( $cap, true );
		wp_set_current_user( $user->ID );

		$dashboard = new WC_Admin_Dashboard();

		$this->assertEquals( $should_load, has_action( 'wp_dashboard_setup', array( $dashboard, 'init' ) ) );
	}

	/**
	 * Data provider for test__construct_checks_user_capabilities().
	 *
	 * @return array An array of test cases, with two keys: the capability to check and whether or
	 *               not a user with the given capability should see the admin dashboard items.
	 */
	public function construct_user_cap_provider() {
		return array(
			'view_woocommerce_reports' => array( 'view_woocommerce_reports', true ),
			'manage_woocommerce'       => array( 'manage_woocommerce', true ),
			'publish_shop_orders'      => array( 'publish_shop_orders', true ),

			// Some more common capabilities, to ensure we're checking specific caps, not roles.
			'edit_others_posts'        => array( 'edit_others_posts', false ),
			'can_edit_posts'           => array( 'can_edit_posts', false ),
		);
	}
}
