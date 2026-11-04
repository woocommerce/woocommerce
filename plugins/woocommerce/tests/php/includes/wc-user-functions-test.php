<?php

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as Download_Directories;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests for the WC_User class.
 */
class WC_User_Functions_Tests extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Ensure permanent HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		try {
			self::setup_cot_tables();
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== $previous_hpos_state ) {
				OrderHelper::toggle_cot_feature_and_usage( $previous_hpos_state );
			}
		} finally {
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

	/**
	 * Whether HPOS was authoritative before the test.
	 *
	 * @var bool
	 */
	private $previous_hpos_state;

	/**
	 * Setup COT.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->toggle_cot_feature_and_usage( true );
	}

	/**
	 * Clean COT specific things.
	 */
	public function tearDown(): void {
		$this->clean_up_cot_setup();
		$this->toggle_cot_feature_and_usage( $this->previous_hpos_state );
		remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		parent::tearDown();

		// In case `wc_update_user_last_active` test fail, clean the global state.
		global $wp_current_filter;
		$wp_current_filter = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Test wc_get_customer_order_count. Borrowed from `WC_Tests_Customer_Functions` class for COT.
	 */
	public function test_hpos_wc_customer_bought_product() {
		$customer_id_1 = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		$customer_id_2 = wc_create_new_customer( 'test2@example.com', 'testuser2', 'testpassword2' );
		$customer_id_3 = wc_create_new_customer( 'account@example.com', 'testuser3', 'testpassword3' );
		$product_1     = new WC_Product_Simple();
		$product_1->save();
		$product_id_1 = $product_1->get_id();
		$product_2    = new WC_Product_Simple();
		$product_2->save();
		$product_id_2 = $product_2->get_id();
		$product_3    = new WC_Product_Simple();
		$product_3->save();
		$product_id_3 = $product_3->get_id();
		$product_4    = new WC_Product_Simple();
		$product_4->save();
		$product_id_4 = $product_4->get_id();
		$product_5    = new WC_Product_Simple();
		$product_5->save();
		$product_id_5 = $product_5->get_id();

		$order_1 = WC_Helper_Order::create_order( $customer_id_1, $product_1 );
		$order_1->set_billing_email( 'test@example.com' );
		$order_1->set_status( OrderStatus::COMPLETED );
		$order_1->save();
		$order_2 = WC_Helper_Order::create_order( $customer_id_2, $product_2 );
		$order_2->set_billing_email( 'test2@example.com' );
		$order_2->set_status( OrderStatus::COMPLETED );
		$order_2->save();
		$order_3 = WC_Helper_Order::create_order( $customer_id_1, $product_2 );
		$order_3->set_billing_email( 'test@example.com' );
		$order_3->set_status( OrderStatus::PENDING );
		$order_3->save();
		$order_4 = wc_create_order();
		$order_4->add_product( $product_1 );
		$order_4->set_status( OrderStatus::COMPLETED );
		$order_4->save();

		$guest_order = wc_create_order();
		$guest_order->add_product( $product_3 );
		$guest_order->set_billing_email( 'guest@example.com' );
		$guest_order->set_status( OrderStatus::COMPLETED );
		$guest_order->save();

		$different_billing_email_order = WC_Helper_Order::create_order( $customer_id_3, $product_4 );
		$different_billing_email_order->set_billing_email( 'billing@example.com' );
		$different_billing_email_order->set_status( OrderStatus::COMPLETED );
		$different_billing_email_order->save();

		$unlinked_guest_order = wc_create_order();
		$unlinked_guest_order->add_product( $product_5 );
		$unlinked_guest_order->set_billing_email( 'test@example.com' );
		$unlinked_guest_order->set_status( OrderStatus::COMPLETED );
		$unlinked_guest_order->save();

		// Manually trigger the product lookup tables update, since it may take a few moments for it to happen automatically.
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		foreach ( array( '__return_true', '__return_false' ) as $lookup_tables ) {
			add_filter( 'woocommerce_customer_bought_product_use_lookup_tables', $lookup_tables );

			$this->assertTrue( wc_customer_bought_product( 'test@example.com', $customer_id_1, $product_id_1 ) );
			$this->assertTrue( wc_customer_bought_product( '', $customer_id_1, $product_id_1 ) );
			$this->assertTrue( wc_customer_bought_product( 'test@example.com', 0, $product_id_1 ) );
			$this->assertFalse( wc_customer_bought_product( 'test@example.com', $customer_id_1, $product_id_2 ) );
			$this->assertFalse( wc_customer_bought_product( 'test2@example.com', $customer_id_2, $product_id_1 ) );

			$this->assertTrue( wc_customer_bought_product( 'guest@example.com', 0, $product_id_3 ) );
			$this->assertFalse( wc_customer_bought_product( 'other@example.com', 0, $product_id_3 ) );

			$this->assertTrue( wc_customer_bought_product( 'billing@example.com', $customer_id_3, $product_id_4 ) );
			$this->assertTrue( wc_customer_bought_product( '', $customer_id_3, $product_id_4 ) );
			$this->assertTrue( wc_customer_bought_product( 'billing@example.com', 0, $product_id_4 ) );

			$this->assertTrue( wc_customer_bought_product( 'test@example.com', 0, $product_id_5 ) );
			$this->assertTrue( wc_customer_bought_product( 'test@example.com', $customer_id_1, $product_id_5 ) );
			$this->assertFalse( wc_customer_bought_product( '', $customer_id_1, $product_id_5 ) );

			remove_filter( 'woocommerce_customer_bought_product_use_lookup_tables', $lookup_tables );
		}
	}

	/**
	 * Test test_wc_get_customer_available_downloads_for_partial_refunds.
	 *
	 * @since 9.3
	 */
	public function test_wc_get_customer_available_downloads_for_partial_refunds(): void {
		$this->toggle_cot_feature_and_usage( false );

		/** @var Download_Directories $download_directories */
		$download_directories = wc_get_container()->get( Download_Directories::class );
		$download_directories->set_mode( Download_Directories::MODE_ENABLED );
		$download_directories->add_approved_directory( 'https://always.trusted' );

		$test_file = 'https://always.trusted/123.pdf';

		$customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );

		$prod_download = new WC_Product_Download();
		$prod_download->set_file( $test_file );
		$prod_download->set_id( 1 );

		$prod_download2 = new WC_Product_Download();
		$prod_download2->set_file( $test_file );
		$prod_download2->set_id( 2 );

		$product = new WC_Product_Simple();
		$product->set_regular_price( 10 );
		$product->set_downloadable( 'yes' );
		$product->set_downloads( array( $prod_download ) );
		$product->save();

		$product2 = new WC_Product_Simple();
		$product2->set_regular_price( 20 );
		$product2->set_downloadable( 'yes' );
		$product2->set_downloads( array( $prod_download2 ) );
		$product2->save();

		$order = new WC_Order();
		$order->set_customer_id( $customer_id );

		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_order_id( $order->get_id() );
		$item->set_total( 10 );
		$item->save();
		$order->add_item( $item );

		$item2 = new WC_Order_Item_Product();
		$item2->set_product( $product2 );
		$item2->set_order_id( $order->get_id() );
		$item->set_total( 20 );
		$item2->save();
		$order->add_item( $item2 );

		$order->set_total( 30 ); // 10 + 20
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$args = array(
			'amount'     => 10,
			'order_id'   => $order->get_id(),
			'line_items' => array(
				$item->get_id() => array(
					'qty'          => 1,
					'refund_total' => 10,
				),
			),
		);

		wc_create_refund( $args );

		$downloads = wc_get_customer_available_downloads( $customer_id );
		$this->assertEquals( 1, count( $downloads ) );

		$download = current( $downloads );
		$this->assertEquals( $prod_download2->get_id(), $download['download_id'] );
		$this->assertEquals( $order->get_id(), $download['order_id'] );
		$this->assertEquals( $product2->get_id(), $download['product_id'] );
	}

	/**
	 * Test `wc_update_user_last_active`: verify the applied thresholds.
	 */
	public function test_wc_update_user_last_active(): void {
		global $wp_current_filter;
		$customer    = WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();

		// Verify threshold crossing is handled as intended.
		$original = time() - 30;
		update_user_meta( $customer_id, 'wc_last_active', (string) $original );
		wc_update_user_last_active( $customer_id );
		$this->assertSame( (string) $original, get_user_meta( $customer_id, 'wc_last_active', true ) );

		// Verify fallback of one-minute update interval.
		$original = time() - MINUTE_IN_SECONDS - 1;
		update_user_meta( $customer_id, 'wc_last_active', (string) $original );
		wc_update_user_last_active( $customer_id );
		$this->assertGreaterThan( $original, get_user_meta( $customer_id, 'wc_last_active', true ) );

		// Verify immediate update after logging in.
		$original = time() - 1;
		update_user_meta( $customer_id, 'wc_last_active', (string) $original );
		$wp_current_filter = array( 'wp_login' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wc_update_user_last_active( $customer_id );
		$this->assertGreaterThan( $original, get_user_meta( $customer_id, 'wc_last_active', true ) );

		// Verify five minutes update interval for pages navigation use-case.
		$original = time() - ( 5 * MINUTE_IN_SECONDS ) - 1;
		update_user_meta( $customer_id, 'wc_last_active', (string) $original );
		$wp_current_filter = array( 'wp' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wc_update_user_last_active( $customer_id );
		$this->assertGreaterThan( $original, get_user_meta( $customer_id, 'wc_last_active', true ) );

		$customer->delete();
	}

	/**
	 * Timeframe totals reject invalid IDs, and guest timeframe totals agree with the unfiltered path.
	 */
	public function test_timeframe_total_spent_rejects_invalid_customer_ids(): void {
		foreach ( array( false, true ) as $hpos_enabled ) {
			$this->toggle_cot_feature_and_usage( $hpos_enabled );

			$storage_suffix = $hpos_enabled ? 'hpos' : 'cpt';
			$customer       = WC_Helper_Customer::create_customer(
				"timeframe-negative-$storage_suffix",
				'password',
				"timeframe-negative-$storage_suffix@example.com"
			);
			$order          = WC_Helper_Order::create_order( 0, null, array( 'status' => OrderStatus::COMPLETED ) );
			$order->set_date_paid( '2024-02-10 12:00:00' );
			$order->set_total( 20 );
			$order->save();

			$customer_order = WC_Helper_Order::create_order(
				$customer->get_id(),
				null,
				array( 'status' => OrderStatus::COMPLETED )
			);
			$customer_order->set_date_paid( '2024-02-10 12:00:00' );
			$customer_order->set_total( 30 );
			$customer_order->save();

			$timeframe = array(
				'after'  => '2024-01-01 00:00:00',
				'before' => '2024-03-01 00:00:00',
			);

			$this->assertSame(
				'0.00',
				wc_get_customer_total_spent( 0, $timeframe ),
				'Guest timeframe totals must agree with the unfiltered path and not sum guest orders.'
			);
			$this->assertSame(
				'0.00',
				wc_get_customer_total_spent( PHP_INT_MAX, $timeframe ),
				'A missing positive customer ID must not receive guest timeframe totals.'
			);
			$this->assertSame( '30.00', wc_get_customer_total_spent( $customer->get_id(), $timeframe ) );
			$this->assertSame(
				'0.00',
				wc_get_customer_total_spent( -$customer->get_id(), $timeframe ),
				'A negative customer ID must not resolve to another customer.'
			);

			// The unfiltered all-time behavior for these IDs is established behavior and stays untouched:
			// for IDs without a user account the cached-meta lookup returns false (not ''), which the
			// all-time path treats as a final value, while a negative ID normalizes to its customer.
			$this->assertSame( '0.00', wc_get_customer_total_spent( 0 ) );
			$this->assertSame( '0.00', wc_get_customer_total_spent( PHP_INT_MAX ) );
			$this->assertSame( '30.00', wc_get_customer_total_spent( -$customer->get_id() ) );

			$order->delete( true );
			$customer_order->delete( true );
			$customer->delete( true );
		}
	}

	/**
	 * Timeframe totals stay correct when the active customer data store does not implement the timeframe interface.
	 *
	 * @testdox Timeframe totals remain correct when a third-party datastore only implements the established interface.
	 */
	public function test_customer_total_spent_timeframe_falls_back_for_legacy_datastore(): void {
		foreach ( array( false, true ) as $hpos_enabled ) {
			$this->toggle_cot_feature_and_usage( $hpos_enabled );

			$storage_suffix = $hpos_enabled ? 'hpos' : 'cpt';
			$customer       = WC_Helper_Customer::create_customer( "timeframe-$storage_suffix", 'password', "timeframe-$storage_suffix@example.com" );
			$order          = WC_Helper_Order::create_order( $customer->get_id(), null, array( 'status' => OrderStatus::COMPLETED ) );
			$order->set_date_paid( '2024-02-10 12:00:00' );
			$order->set_total( 20 );
			$order->save();

			$timeframe = array(
				'after'  => '2024-01-01 00:00:00',
				'before' => '2024-03-01 00:00:00',
			);

			$legacy_store = Mockery::mock( WC_Customer_Data_Store_Interface::class . ', ' . WC_Object_Data_Store_Interface::class );
			$legacy_store->shouldReceive( 'read' )->andReturnNull();
			$legacy_store->shouldReceive( 'get_total_spent' )->andReturn( '999.00' );
			$this->assertNotInstanceOf( WC_Customer_Data_Store_Timeframe_Interface::class, $legacy_store );

			$filter = static function () use ( $legacy_store ) {
				return $legacy_store;
			};
			add_filter( 'woocommerce_customer_data_store', $filter );

			try {
				$legacy_customer = new WC_Customer( $customer->get_id() );
				$this->assertSame( $customer->get_id(), $legacy_customer->get_id(), "Legacy $storage_suffix datastore should preserve the customer ID." );
				$this->assertSame( '999.00', wc_get_customer_total_spent( $customer->get_id() ), 'The all-time total still comes from the active data store.' );
				$this->assertSame(
					'20.00',
					wc_get_customer_total_spent( $customer->get_id(), $timeframe ),
					"Legacy $storage_suffix datastore should use the core timeframe fallback."
				);
			} finally {
				remove_filter( 'woocommerce_customer_data_store', $filter );
			}

			// The all-time hooks must not fire for timeframe calls, whichever store is active.
			$all_time_value_calls  = 0;
			$all_time_query_calls  = 0;
			$all_time_value_filter = static function ( $spent ) use ( &$all_time_value_calls ) {
				++$all_time_value_calls;
				return $spent;
			};
			$all_time_query_filter = static function ( $query ) use ( &$all_time_query_calls ) {
				++$all_time_query_calls;
				return $query;
			};
			add_filter( 'woocommerce_customer_get_total_spent', $all_time_value_filter );
			add_filter( 'woocommerce_customer_get_total_spent_query', $all_time_query_filter );

			try {
				$this->assertSame( '20.00', wc_get_customer_total_spent( $customer->get_id(), $timeframe ) );
			} finally {
				remove_filter( 'woocommerce_customer_get_total_spent_query', $all_time_query_filter );
				remove_filter( 'woocommerce_customer_get_total_spent', $all_time_value_filter );
			}

			$this->assertSame( 0, $all_time_value_calls );
			$this->assertSame( 0, $all_time_query_calls );

			$order->delete( true );
			$customer->delete( true );
		}
	}

	/**
	 * Invalid timeframe dates surface as exceptions through the wrapper too.
	 */
	public function test_wc_get_customer_total_spent_timeframe_invalid_date_throws(): void {
		$customer = WC_Helper_Customer::create_customer( 'timeframe-invalid', 'password', 'timeframe-invalid@example.com' );

		try {
			$this->expectException( InvalidArgumentException::class );
			wc_get_customer_total_spent( $customer->get_id(), array( 'after' => 'not-a-date' ) );
		} finally {
			$customer->delete( true );
		}
	}
}
