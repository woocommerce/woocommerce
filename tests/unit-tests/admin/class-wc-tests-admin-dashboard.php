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
	 * (Re)set options used for this testcase.
	 *
	 * @beforeClass
	 */
	public static function reset_options() {

		// For the sake of these tests, "low" stock is 1, "no" stock is 0.
		update_option( 'woocommerce_notify_low_stock_amount', 1 );
		update_option( 'woocommerce_notify_no_stock_amount', 0 );
	}

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
	 * Delete transients used for dashboard items.
	 *
	 * @after
	 */
	public function clear_transients() {
		delete_transient( 'wc_low_stock_count' );
		delete_transient( 'wc_outofstock_count' );
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

	/**
	 * Test: status_widget
	 */
	public function test_status_widget() {
		$dashboard    = new WC_Admin_Dashboard();
		$action_count = did_action( 'woocommerce_after_dashboard_status_widget' );

		ob_start();
		$dashboard->status_widget();
		$output = ob_get_clean();

		$this->assertContains( '<ul class="wc_status_list">', $output );

		$this->assertEquals( $action_count + 1, did_action( 'woocommerce_after_dashboard_status_widget' ) );
	}

	/**
	 * Test: status_widget
	 */
	public function test_status_widget_conditionally_adds_sales_data() {
		$user = $this->factory()->user->create_and_get();
		$user->add_cap( 'view_woocommerce_reports', true );
		wp_set_current_user( $user->ID );

		$dashboard    = new WC_Admin_Dashboard();
		$action_count = did_action( 'woocommerce_after_dashboard_status_widget' );

		ob_start();
		$dashboard->status_widget();
		$output = ob_get_clean();

		$this->assertContains(
			'<li class="sales-this-month">',
			$output,
			'"Sales This Month" data should be visible when the current user has the "view_woocommerce_reports" capability.'
		);
	}

	/**
	 * Test: status_widget_order_rows
	 */
	public function test_status_widget_order_rows() {
		$user = $this->factory()->user->create_and_get();
		$user->add_cap( 'edit_shop_orders', true );
		wp_set_current_user( $user->ID );

		$dashboard = new WC_Admin_Dashboard();
		$method    = new ReflectionMethod( $dashboard, 'status_widget_order_rows' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $dashboard );
		$output = ob_get_clean();

		$this->assertContains( '<li class="processing-orders">', $output );
		$this->assertContains( '<li class="on-hold-orders">', $output );
	}

	/**
	 * Test: status_widget_order_rows
	 */
	public function test_status_widget_order_rows_capability_check() {
		$dashboard = new WC_Admin_Dashboard();
		$method    = new ReflectionMethod( $dashboard, 'status_widget_order_rows' );
		$method->setAccessible( true );

		$this->assertFalse( current_user_can( 'edit_shop_orders' ) );

		ob_start();
		$method->invoke( $dashboard );
		$output = ob_get_clean();

		$this->assertEmpty( $output, 'Output should be empty for users without "edit_shop_orders" cap.' );
	}

	/**
	 * Test: status_widget_stock_rows
	 */
	public function test_status_widget_stock_rows() {
		$dashboard = new WC_Admin_Dashboard();
		$method    = new ReflectionMethod( $dashboard, 'status_widget_stock_rows' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $dashboard );
		$output = ob_get_clean();

		$this->assertContains( '<li class="low-in-stock">', $output );
		$this->assertContains( '<li class="out-of-stock">', $output );

		$this->assertGreaterThanOrEqual( 0, get_transient( 'wc_low_stock_count' ), 'Expected wc_low_stock_count transient to be set.' );
		$this->assertGreaterThanOrEqual( 0, get_transient( 'wc_outofstock_count' ), 'Expected wc_outofstock_count transient to be set.' );
	}

	/**
	 * Test: status_widget_stock_rows
	 */
	public function test_status_widget_stock_rows_low_stock_query() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );
		$product->save();

		$dashboard = new WC_Admin_Dashboard();
		$method    = new ReflectionMethod( $dashboard, 'status_widget_stock_rows' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $dashboard );
		ob_end_clean();

		$this->assertEquals( 1, get_transient( 'wc_low_stock_count' ), 'One product should have low stock.' );
		$this->assertEquals( 0, get_transient( 'wc_outofstock_count' ), 'No products should be out of stock.' );
	}

	/**
	 * Test: status_widget_stock_rows
	 */
	public function test_status_widget_stock_rows_no_stock_query() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->save();

		$dashboard = new WC_Admin_Dashboard();
		$method    = new ReflectionMethod( $dashboard, 'status_widget_stock_rows' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $dashboard );
		ob_end_clean();

		$this->assertEquals( 0, get_transient( 'wc_low_stock_count' ), 'No products should have low stock.' );
		$this->assertEquals( 1, get_transient( 'wc_outofstock_count' ), 'One product should be out of stock.' );
	}
}
