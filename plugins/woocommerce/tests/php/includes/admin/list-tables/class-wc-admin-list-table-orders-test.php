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
	 * Creates a paid order with date_paid set from a local-time string.
	 *
	 * @param string $local_datetime Local date/time string, e.g. '2023-07-20 21:00:00'.
	 * @return WC_Order
	 */
	private function create_order_paid_at( string $local_datetime ): WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->set_date_paid( ( new DateTime( $local_datetime, wp_timezone() ) )->getTimestamp() );
		$order->save();

		return $order;
	}

	/**
	 * Runs an order list table date query.
	 *
	 * @param string $date_type Order date field to filter.
	 * @param string $date      Date in Ymd format.
	 * @return int[] Matching order IDs.
	 */
	private function query_order_ids_with_date_filter( string $date_type, string $date ): array {
		$_GET['order_date_type'] = $date_type;
		$_GET['m']               = $date;
		$GLOBALS['pagenow']      = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		new WC_Admin_List_Table_Orders();
		$query = new WP_Query(
			array(
				'post_type'   => 'shop_order',
				'post_status' => 'all',
				'fields'      => 'ids',
				'm'           => $date,
			)
		);

		$results = $query->get_posts();

		unset( $_GET['order_date_type'], $_GET['m'], $GLOBALS['pagenow'] );

		return $results;
	}

	/**
	 * @testdox Should interpret the date_paid day filter in the store timezone, not UTC.
	 */
	public function test_date_paid_filter_uses_store_timezone(): void {
		update_option( 'timezone_string', 'America/New_York' );

		$morning_order      = $this->create_order_paid_at( '2023-07-20 09:00:00' );
		$evening_order      = $this->create_order_paid_at( '2023-07-20 21:00:00' );
		$previous_day_order = $this->create_order_paid_at( '2023-07-19 21:00:00' );

		$results = $this->query_order_ids_with_date_filter( 'date_paid', '20230720' );

		$this->assertContains( $morning_order->get_id(), $results, 'Order paid in the morning (store time) should be listed for its local day.' );
		$this->assertContains( $evening_order->get_id(), $results, 'Order paid late evening (store time) should be listed for its local day even though it falls on the next day in UTC.' );
		$this->assertNotContains( $previous_day_order->get_id(), $results, 'Order paid the previous local day should not be listed even though it falls on the filtered day in UTC.' );

		update_option( 'timezone_string', '' );
		foreach ( array( $morning_order, $evening_order, $previous_day_order ) as $order ) {
			wp_delete_post( $order->get_id(), true );
		}
	}

	/**
	 * @testdox Should respect a manual UTC offset (empty timezone_string) when filtering by date_paid.
	 */
	public function test_date_paid_filter_uses_store_timezone_with_manual_utc_offset(): void {
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', -4 );

		$evening_order      = $this->create_order_paid_at( '2023-07-20 21:00:00' );
		$previous_day_order = $this->create_order_paid_at( '2023-07-19 21:00:00' );

		$results = $this->query_order_ids_with_date_filter( 'date_paid', '20230720' );

		$this->assertContains( $evening_order->get_id(), $results, 'Order paid late evening (offset local time) should be listed for its local day.' );
		$this->assertNotContains( $previous_day_order->get_id(), $results, 'Order paid the previous local day should not be listed.' );

		update_option( 'gmt_offset', 0 );
		foreach ( array( $evening_order, $previous_day_order ) as $order ) {
			wp_delete_post( $order->get_id(), true );
		}
	}

	/**
	 * @testdox Should filter date_completed day ranges in the store timezone as well.
	 */
	public function test_date_completed_filter_uses_store_timezone(): void {
		update_option( 'timezone_string', 'America/New_York' );

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->set_date_completed( ( new DateTime( '2023-07-20 21:00:00', wp_timezone() ) )->getTimestamp() );
		$order->save();

		$previous_day_order = WC_Helper_Order::create_order();
		$previous_day_order->set_status( 'completed' );
		$previous_day_order->set_date_completed( ( new DateTime( '2023-07-19 21:00:00', wp_timezone() ) )->getTimestamp() );
		$previous_day_order->save();

		$results = $this->query_order_ids_with_date_filter( 'date_completed', '20230720' );

		$this->assertContains( $order->get_id(), $results, 'Order completed late evening (store time) should be listed for its local day.' );
		$this->assertNotContains( $previous_day_order->get_id(), $results, 'Order completed the previous local day should not be listed even though it falls on the filtered day in UTC.' );

		update_option( 'timezone_string', '' );
		foreach ( array( $order, $previous_day_order ) as $created_order ) {
			wp_delete_post( $created_order->get_id(), true );
		}
	}

	/**
	 * @testdox Should cover the whole local day when DST ends at midnight and the day lasts 25 hours.
	 */
	public function test_date_paid_filter_covers_dst_extended_day(): void {
		// Chile ends DST at midnight, so 2022-04-02 lasts 25 hours and repeats its 23:00 hour.
		update_option( 'timezone_string', 'America/Santiago' );

		$next_midnight = ( new DateTime( '2022-04-02 00:00:00', wp_timezone() ) )->modify( '+1 day' );

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->set_date_paid( $next_midnight->getTimestamp() - 1800 );
		$order->save();

		$results = $this->query_order_ids_with_date_filter( 'date_paid', '20220402' );

		$this->assertContains( $order->get_id(), $results, 'An order paid during the repeated final hour of a 25-hour local day should still be listed for that day.' );

		update_option( 'timezone_string', '' );
		wp_delete_post( $order->get_id(), true );
	}

	/**
	 * @testdox Should not spill into the next day when DST starts at midnight and the day has no 00:00.
	 */
	public function test_date_paid_filter_does_not_overlap_dst_shortened_day(): void {
		// Chile starts DST at midnight, so 2022-09-11 has no 00:00 hour and begins at 01:00.
		update_option( 'timezone_string', 'America/Santiago' );

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->set_date_paid( ( new DateTime( '2022-09-12 00:30:00', wp_timezone() ) )->getTimestamp() );
		$order->save();

		$previous_day_results = $this->query_order_ids_with_date_filter( 'date_paid', '20220911' );
		$own_day_results      = $this->query_order_ids_with_date_filter( 'date_paid', '20220912' );

		$this->assertNotContains( $order->get_id(), $previous_day_results, 'An order paid after midnight the following day should not also be listed under the previous day.' );
		$this->assertContains( $order->get_id(), $own_day_results, 'The order should be listed under the day it was actually paid.' );

		update_option( 'timezone_string', '' );
		wp_delete_post( $order->get_id(), true );
	}

	/**
	 * @testdox Should treat the next local midnight as the exclusive end of the day.
	 */
	public function test_date_paid_filter_excludes_the_next_midnight_instant(): void {
		update_option( 'timezone_string', 'America/New_York' );

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->set_date_paid( ( new DateTime( '2026-07-21 00:00:00', wp_timezone() ) )->getTimestamp() );
		$order->save();

		$filtered_day_results = $this->query_order_ids_with_date_filter( 'date_paid', '20260720' );
		$own_day_results      = $this->query_order_ids_with_date_filter( 'date_paid', '20260721' );

		$this->assertNotContains( $order->get_id(), $filtered_day_results, 'An order paid exactly at midnight belongs to the day starting then, not the one ending then.' );
		$this->assertContains( $order->get_id(), $own_day_results, 'An order paid exactly at midnight should be listed under the day that starts at that instant.' );

		update_option( 'timezone_string', '' );
		wp_delete_post( $order->get_id(), true );
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

		try {
			// Do not set post_type in the query.
			new WP_Query(
				array(
					'post_status' => 'all',
					'fields'      => 'ids',
				)
			);
		} finally {
			restore_error_handler();
		}

		// Check no warnings were triggered.
		$this->assertEmpty(
			$warnings,
			'No PHP warnings or notices should be triggered when no post_type is set in WP_Query for admin order search.'
		);

		// Cleanup.
		unset( $GLOBALS['pagenow'] );
	}

	/**
	 * @testdox Should not warn or drop orders when the m parameter is not a string.
	 */
	public function test_date_filter_with_non_string_m_does_not_trigger_warning(): void {
		$order = $this->create_order_paid_at( '2023-07-20 21:00:00' );

		$_GET['order_date_type'] = 'date_paid';
		$_GET['m']               = array( '20230720' );
		$GLOBALS['pagenow']      = 'edit.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		new WC_Admin_List_Table_Orders();

		$errors = array();
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			function ( $errno, $errstr ) use ( &$errors ) {
				$errors[] = "[$errno] $errstr";

				return true;
			}
		);

		try {
			$query = new WP_Query(
				array(
					'post_type'      => 'shop_order',
					'post_status'    => 'all',
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'm'              => array( '20230720' ),
				)
			);

			$results = $query->get_posts();
		} finally {
			restore_error_handler();
		}

		$this->assertSame( array(), $errors, 'An array "m" parameter should not raise an "Array to string conversion" warning.' );
		$this->assertEmpty( $query->query_vars['meta_key'], 'An unusable "m" parameter should not build a date meta query.' );
		$this->assertContains( $order->get_id(), $results, 'An unusable "m" parameter should leave the list unfiltered rather than silently dropping orders.' );

		unset( $_GET['order_date_type'], $_GET['m'], $GLOBALS['pagenow'] );
		wp_delete_post( $order->get_id(), true );
	}
}
