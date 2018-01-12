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
	 * Trigger any method that conditionally loads PHP includes.
	 *
	 * @beforeClass
	 */
	public static function trigger_includes() {
		$dashboard = new WC_Admin_Dashboard();

		ob_start();
		$dashboard->status_widget(); // Loads includes/admin/reports/class-wc-admin-report.php.
		ob_end_clean();
	}

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

	/**
	 * Test: get_top_seller
	 */
	public function test_get_top_seller() {
		$top_product   = WC_Helper_Product::create_simple_product();
		$other_product = WC_Helper_Product::create_simple_product();

		$order1 = WC_Helper_Order::create_order( 0, $top_product );
		$order1->set_status( 'completed' );
		$order1->save();
		$order2 = WC_Helper_Order::create_order( 0, $other_product );
		$order2->set_status( 'completed' );
		$order2->save();
		$order3 = WC_Helper_Order::create_order( 0, $top_product );
		$order3->set_status( 'completed' );
		$order3->save();

		// Refresh the top product.
		$top_product = wc_get_product( $top_product->get_id() );

		$dashboard = new WC_Admin_Dashboard();
		$method    = new ReflectionMethod( $dashboard, 'get_top_seller' );
		$method->setAccessible( true );

		$result = $method->invoke( $dashboard );

		$this->assertEquals(
			$top_product->get_id(),
			$result->product_id,
			'Expected $top_product to be the top product.'
		);

		$this->assertEquals(
			$top_product->get_total_sales(),
			$result->qty,
			'Expected a number of sales equal to $order1 + $order3.'
		);
	}

	/**
	 * Test: get_top_seller
	 */
	public function test_get_top_seller_enables_query_to_be_filtered() {
		$dashboard = new WC_Admin_Dashboard();
		$method    = new ReflectionMethod( $dashboard, 'get_top_seller' );
		$method->setAccessible( true );

		add_filter( 'woocommerce_dashboard_status_widget_top_seller_query', '__return_empty_array' );

		$this->assertNull(
			$method->invoke( $dashboard ),
			'Method get_top_seller() did not call the "woocommerce_dashboard_status_widget_top_seller_query" filter.'
		);
	}

	/**
	 * Test: get_sales_report_data
	 */
	public function test_get_sales_report_data() {
		$dashboard = new WC_Admin_Dashboard();
		$report    = new stdClass();
		$method    = new ReflectionMethod( $dashboard, 'get_sales_report_data' );
		$method->setAccessible( true );

		add_filter( 'woocommerce_admin_report_data', function () use ( $report ) {
			return $report;
		} );

		$result = $method->invoke( $dashboard );

		$this->assertSame( $report, $result );
	}
}
