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

		$order = WC_Helper_Order::create_order();
		foreach ( $fields as $field => $value ) {
			$setter = 'set_' . $field;
			$order->$setter( $value );
		}
		$order->save();

		foreach ( $fields as $field => $value ) {
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
		}
		wp_delete_post( $order->get_id(), true );
		wp_delete_post( $dummy_order->get_id(), true );
	}

	/**
	 * Test that the order search by order ID logic works as expected.
	 */
	public function test_order_search_by_order_id() {
		// Create several dummy orders.
		$orders = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$orders[] = wc_create_order();
		}

		// Create a dummy order that should NOT match.
		$dummy_order = wc_create_order();
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

	/**
	 * @testdox Order previews pass positive refund totals and explicit negative display intent to wc_price.
	 */
	public function test_order_preview_passes_explicit_negative_display_intent_to_wc_price(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		wc_create_refund(
			array(
				'amount'        => 5,
				'order_id'      => $order->get_id(),
				'line_items'    => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 5,
						'refund_tax'   => array(),
					),
				),
				'restock_items' => false,
			)
		);

		$price_filter = static function ( $price_html, $formatted_price, $args, $unformatted_price, $original_price ) {
			unset( $formatted_price );

			return true === ( $args['is_negative'] ?? false ) ? 'localized-negative-price:' . (float) $original_price . ':' . $unformatted_price : $price_html;
		};
		add_filter( 'wc_price', $price_filter, 10, 5 );

		$preview_html = WC_Admin_List_Table_Orders::get_order_preview_item_html( $order );

		remove_filter( 'wc_price', $price_filter );
		$order->delete( true );
		$product->delete( true );

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<!DOCTYPE html><html><body>' . $preview_html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The order preview output should be valid enough for DOM parsing.' );

		$xpath          = new DOMXPath( $document );
		$refunded_nodes = $xpath->query( "//small[contains(concat(' ', normalize-space(@class), ' '), ' refunded ') and normalize-space(.) = 'localized-negative-price:5:5']" );

		$this->assertNotFalse( $refunded_nodes, 'The refunded price XPath query should be valid.' );
		$this->assertSame( 1, $refunded_nodes->length, 'The preview should pass the positive refund amount and explicit negative display intent to the wc_price filter.' );
	}

	/**
	 * @testdox Order previews pass explicit negative display intent for zero-total refund items.
	 */
	public function test_order_preview_passes_negative_display_intent_for_zero_total_refund(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		wc_create_refund(
			array(
				'amount'        => 0,
				'order_id'      => $order->get_id(),
				'line_items'    => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 0,
						'refund_tax'   => array(),
					),
				),
				'restock_items' => false,
			)
		);

		$price_filter = static function ( $price_html, $formatted_price, $args, $unformatted_price, $original_price ) {
			unset( $formatted_price );

			return true === ( $args['is_negative'] ?? false ) ? 'localized-negative-price:' . (float) $original_price . ':' . $unformatted_price : $price_html;
		};
		add_filter( 'wc_price', $price_filter, 10, 5 );

		$preview_html = WC_Admin_List_Table_Orders::get_order_preview_item_html( $order );

		remove_filter( 'wc_price', $price_filter );
		$order->delete( true );
		$product->delete( true );

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<!DOCTYPE html><html><body>' . $preview_html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The order preview output should be valid enough for DOM parsing.' );

		$xpath          = new DOMXPath( $document );
		$refunded_nodes = $xpath->query( "//small[contains(concat(' ', normalize-space(@class), ' '), ' refunded ') and normalize-space(.) = 'localized-negative-price:0:0']" );

		$this->assertNotFalse( $refunded_nodes, 'The zero refund XPath query should be valid.' );
		$this->assertSame( 1, $refunded_nodes->length, 'The preview should pass explicit negative display intent without changing the zero-valued filter arguments.' );
	}
}
