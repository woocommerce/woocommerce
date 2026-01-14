<?php
/**
 * Tests for Order List Tables in WooCommerce Admin
 */

declare( strict_types = 1 );

require_once WC_ABSPATH . '/includes/admin/list-tables/class-wc-admin-list-table-orders.php';

/**
 * WC Admin List Table Orders test
 */
class WC_Admin_List_Table_Orders_Test extends WC_Unit_Test_Case {

	/**
	 * Stores the previous HPOS state.
	 * @var bool
	 */
	private static $hpos_prev_state;

	/**
	 * Prepare for running the tests. Disables HPOS, as it's not compatible with this test.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Store the previous HPOS state.
		self::$hpos_prev_state = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::toggle_cot_feature_and_usage( false );
	}

	/**
	 * Restore previous state (including HPOS) after all tests have run.
	 */
	public static function tearDownAfterClass(): void {
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::toggle_cot_feature_and_usage( self::$hpos_prev_state );
		parent::tearDownAfterClass();
	}

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		if ( \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped( 'This test is not compatible with HPOS.' );
		}

		parent::setUp();
	}


	/**
	 * Test that the order search custom fields logic works as expected. The list table makes use of wc_order_search to
	 * get the order ids and inject into the query. We'll confirm that works and that results are expected.
	 */
	public function test_order_search_custom_fields() {
		// Create an order with a unique billing first name.
		$order = WC_Helper_Order::create_order();
		$order->set_billing_first_name( 'SearchTestFirstName' );
		$order->save();

		// Create a dummy order that should NOT match.
		$dummy_order = WC_Helper_Order::create_order();
		$dummy_order->set_billing_first_name( 'NotAMatch' );
		$dummy_order->save();

		// Simulate a search for the billing first name.
		$_GET['s']          = 'SearchTestFirstName';
		$GLOBALS['pagenow'] = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Set up the query. WC_Admin_List_Table_Orders hooks into the parse_query action.
		$list_table = new WC_Admin_List_Table_Orders();
		$query      = new WP_Query(
			array(
				'post_type'   => 'shop_order',
				'post_status' => 'all',
				'fields'      => 'ids',
				's'           => 'SearchTestFirstName',
			)
		);

		$results = $query->get_posts();

		// Assert the order is found.
		$this->assertContains( $order->get_id(), $results, 'Order should be found by billing first name search.' );
		$this->assertNotContains( $dummy_order->get_id(), $results, 'Dummy order should not be found in search results' );

		// Cleanup.
		unset( $_GET['s'], $GLOBALS['pagenow'] );
		wp_delete_post( $order->get_id(), true );
		wp_delete_post( $dummy_order->get_id(), true );
	}

	/**
	 * Test that the order search custom fields logic works as expected for all address fields.
	 */
	public function test_order_search_custom_fields_for_all_address_fields() {
		$fields = array(
			'billing_first_name'  => 'James',
			'billing_last_name'   => 'Doe',
			'billing_company'     => 'Automattic',
			'billing_address_1'   => 'address1',
			'billing_address_2'   => 'address2',
			'billing_city'        => 'San Francisco',
			'billing_postcode'    => '94107',
			'billing_email'       => 'john.doe.ordersearch@example.com',
			'billing_phone'       => '123456789',
			'billing_state'       => 'CA',
			'shipping_first_name' => 'Tim',
			'shipping_last_name'  => 'Clark',
			'shipping_address_1'  => 'Oxford Ave',
			'shipping_address_2'  => 'Linwood Ave',
			'shipping_city'       => 'Buffalo',
			'shipping_postcode'   => '14201',
		);

		// Create a dummy order that should NOT match.
		$dummy_order = WC_Helper_Order::create_order();
		$dummy_order->set_billing_first_name( 'NotAMatch' );
		$dummy_order->save();

		foreach ( $fields as $field => $value ) {
			$order  = WC_Helper_Order::create_order();
			$setter = 'set_' . $field;
			$order->$setter( $value );
			$order->save();

			$_GET['s']          = $value;
			$GLOBALS['pagenow'] = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			$list_table = new WC_Admin_List_Table_Orders();
			$query      = new WP_Query(
				array(
					'post_type'   => 'shop_order',
					'post_status' => 'all',
					'fields'      => 'ids',
					's'           => $value,
				)
			);

			$results = $query->get_posts();
			$this->assertContains(
				$order->get_id(),
				$results,
				"Order should be found by searching for $field = $value"
			);
			$this->assertNotContains(
				$dummy_order->get_id(),
				$results,
				"Dummy order should not be found when searching for $field = $value"
			);

			unset( $_GET['s'], $GLOBALS['pagenow'] );
			wp_delete_post( $order->get_id(), true );
		}
		wp_delete_post( $dummy_order->get_id(), true );
	}

	/**
	 * Test that the order search by order ID logic works as expected.
	 */
	public function test_order_search_by_order_id() {
		// Create several dummy orders.
		$orders = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$orders[] = WC_Helper_Order::create_order();
		}

		// Create a dummy order that should NOT match.
		$dummy_order = WC_Helper_Order::create_order();
		$dummy_order->set_billing_first_name( 'NotAMatch' );
		$dummy_order->save();

		// Pick one to search for.
		$target_order = $orders[1];
		$order_id     = $target_order->get_id();

		$_GET['s']          = (string) $order_id;
		$GLOBALS['pagenow'] = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$list_table = new WC_Admin_List_Table_Orders();
		$query      = new WP_Query(
			array(
				'post_type'   => 'shop_order',
				'post_status' => 'all',
				'fields'      => 'ids',
				's'           => (string) $order_id,
			)
		);

		$results = $query->get_posts();

		// Assert only the correct order is found.
		$this->assertContains(
			$order_id,
			$results,
			'Order should be found by searching for its ID'
		);
		$this->assertCount(
			1,
			$results,
			'Only one order should be found when searching by order ID'
		);
		$this->assertNotContains(
			$dummy_order->get_id(),
			$results,
			'Dummy order should not be found in search results'
		);

		// Cleanup.
		unset( $_GET['s'], $GLOBALS['pagenow'] );
		foreach ( $orders as $order ) {
			wp_delete_post( $order->get_id(), true );
		}
		wp_delete_post( $dummy_order->get_id(), true );
	}

	/**
	 * Test that the order search by product name logic works as expected.
	 */
	public function test_order_search_by_product_name() {
		// Create a product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Wanted Product' );
		$product->save();

		// Create an order with that product as a line item.
		$order = WC_Helper_Order::create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$order->add_item( $item );
		$order->save();

		// Create a dummy order that should NOT match.
		$dummy_order = WC_Helper_Order::create_order();
		$dummy_order->set_billing_first_name( 'NotAMatch' );
		$dummy_order->save();

		$_GET['s']          = 'Wanted Product';
		$GLOBALS['pagenow'] = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$list_table = new WC_Admin_List_Table_Orders();
		$query      = new WP_Query(
			array(
				'post_type'   => 'shop_order',
				'post_status' => 'all',
				'fields'      => 'ids',
				's'           => 'Wanted Product',
			)
		);

		$results = $query->get_posts();

		$this->assertContains(
			$order->get_id(),
			$results,
			'Order should be found by searching for product name in line items'
		);
		$this->assertNotContains(
			$dummy_order->get_id(),
			$results,
			'Dummy order should not be found in search results'
		);

		// Cleanup.
		unset( $_GET['s'], $GLOBALS['pagenow'] );
		wp_delete_post( $order->get_id(), true );
		wp_delete_post( $product->get_id(), true );
		wp_delete_post( $dummy_order->get_id(), true );
	}

	/**
	 * Test that the search without post_type in query does not trigger warnings.
	 * This is a regression test for https://github.com/woocommerce/woocommerce/pull/55353.
	 */
	public function test_search_without_post_type_in_query_does_not_trigger_warning() {
		$GLOBALS['pagenow'] = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		new WC_Admin_List_Table_Orders();

		$warnings = array();
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			function () use ( &$warnings ) {
				$warnings[] = true;
			}
		);

		// Do not set post_type in the query.
		new WP_Query(
			array(
				'post_status' => 'all',
				'fields'      => 'ids',
			)
		);

		restore_error_handler();

		// Check no warnings were triggered.
		$this->assertEmpty(
			$warnings,
			'No PHP warnings or notices should be triggered when no post_type is set in WP_Query for admin order search.'
		);

		// Cleanup.
		unset( $GLOBALS['pagenow'] );
	}
}
