<?php

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

//phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Legacy class name.
/**
 * Class WC_Order_Data_Store_CPT_Test.
 *
 * @group order-query-tests
 */
class WC_Order_Data_Store_CPT_Test extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;

	/**
	 * Store the COT state before the test.
	 *
	 * @var bool
	 */
	private $prev_cot_state;

	/**
	 * Store the COT state before the test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->prev_cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		OrderHelper::toggle_cot_feature_and_usage( false );
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
	}

	/**
	 * Restore the COT state after the test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		OrderHelper::toggle_cot_feature_and_usage( $this->prev_cot_state );
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
		$this->disable_cogs_feature();
		parent::tearDown();
	}

	/**
	 * Test that refund cache are invalidated correctly when refund is deleted.
	 */
	public function test_refund_cache_invalidation() {
		$order = WC_Helper_Order::create_order();

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'reason'   => 'testing',
				'amount'   => 1,
			)
		);

		$this->assertNotWPError( $refund );

		// Prime cache.
		$fetched_order = wc_get_orders(
			array(
				'post__in' => array( $order->get_id() ),
				'type'     => 'shop_order',
			)
		)[0];

		$refund_cache_key = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'refunds' . $order->get_id();
		$cached_refunds   = wp_cache_get( $refund_cache_key, 'orders' );

		$this->assertEquals( $cached_refunds[0]->get_id(), $fetched_order->get_refunds()[0]->get_id() );

		$refund->delete( true );

		// Cache should be cleared now.
		$cached_refunds = wp_cache_get( $refund_cache_key, 'orders' );
		$this->assertEquals( false, $cached_refunds );
	}

	/**
	 * Test that props set by datastores can be set and get by using any of metadata, object props or from data store setters.
	 * Ideally, this should be possible only from getters and setters for objects, but for backward compatibility, earlier ways are also supported.
	 */
	public function test_internal_ds_getters_and_setters() {
		$props_to_test = array(
			'_download_permissions_granted',
			'_recorded_sales',
			'_recorded_coupon_usage_counts',
			'_new_order_email_sent',
			'_order_stock_reduced',
		);

		$ds_getter_setter_names = array(
			'_order_stock_reduced'  => 'stock_reduced',
			'_new_order_email_sent' => 'email_sent',
		);

		$order = WC_Helper_Order::create_order();

		// set everything to true via props.
		foreach ( $props_to_test as $prop ) {
			$order->{"set$prop"}( true );
			$order->save();
		}
		$this->assert_get_prop_via_ds_object_and_metadata( $props_to_test, $order, true, $ds_getter_setter_names );

		// set everything to false, via metadata.
		foreach ( $props_to_test as $prop ) {
			$order->update_meta_data( $prop, false );
			$order->save();
		}
		$this->assert_get_prop_via_ds_object_and_metadata( $props_to_test, $order, false, $ds_getter_setter_names );

		// set everything to true again, via datastore setter.
		foreach ( $props_to_test as $prop ) {
			if ( in_array( $prop, array_keys( $ds_getter_setter_names ), true ) ) {
				$setter = $ds_getter_setter_names[ $prop ];
				$order->get_data_store()->{"set_$setter"}( $order, true );
				continue;
			}
			$order->get_data_store()->{"set$prop"}( $order, true );
		}
		$this->assert_get_prop_via_ds_object_and_metadata( $props_to_test, $order, true, $ds_getter_setter_names );

		// set everything to false again, via props.
		foreach ( $props_to_test as $prop ) {
			$order->{"set$prop"}( false );
			$order->save();
		}
		$this->assert_get_prop_via_ds_object_and_metadata( $props_to_test, $order, false, $ds_getter_setter_names );
	}

	/**
	 * Helper method to assert props are set.
	 *
	 * @param array    $props List of props to test.
	 * @param WC_Order $order Order object.
	 * @param mixed    $value Value to assert.
	 * @param array    $ds_getter_setter_names List of props with custom getter/setter names.
	 */
	private function assert_get_prop_via_ds_object_and_metadata( array $props, WC_Order $order, $value, array $ds_getter_setter_names ) {
		wp_cache_flush();
		$refreshed_order = wc_get_order( $order->get_id() );
		$value           = wc_bool_to_string( $value );
		// assert via metadata.
		foreach ( $props as $prop ) {
			$this->assertEquals( $value, wc_bool_to_string( $refreshed_order->get_meta( $prop ) ), "Failed getting $prop from metadata" );
		}

		// assert via datastore object.
		foreach ( $props as $prop ) {
			if ( in_array( $prop, array_keys( $ds_getter_setter_names ), true ) ) {
				$getter = $ds_getter_setter_names[ $prop ];
				$this->assertEquals( $value, wc_bool_to_string( $refreshed_order->get_data_store()->{"get_$getter"}( $refreshed_order ) ), "Failed getting $prop from datastore" );
				continue;
			}
			$this->assertEquals( $value, wc_bool_to_string( $refreshed_order->get_data_store()->{"get$prop"}( $order ) ), "Failed getting $prop from datastore" );
		}

		// assert via order object.
		foreach ( $props as $prop ) {
			$this->assertEquals( $value, wc_bool_to_string( $refreshed_order->{"get$prop"}() ), "Failed getting $prop from object" );
		}
	}

	/**
	 * Legacy getters and setters for props migrated from data stores should be set/reset properly.
	 */
	public function test_legacy_getters_setters() {
		$order_id   = OrderHelper::create_complex_wp_post_order();
		$order      = wc_get_order( $order_id );
		$bool_props = array(
			'_download_permissions_granted' => 'download_permissions_granted',
			'_recorded_sales'               => 'recorded_sales',
			'_recorded_coupon_usage_counts' => 'recorded_coupon_usage_counts',
			'_order_stock_reduced'          => 'order_stock_reduced',
			'_new_order_email_sent'         => 'new_order_email_sent',
		);
		// This prop is special, because for backward compatibility reasons we have to store in DB as 'true'|'false' string instead of 'yes'|'no' like we do for other props.
		$special_prop      = array(
			'_new_order_email_sent' => 'new_order_email_sent',
		);
		$props_to_validate = array_diff( $bool_props, $special_prop );

		$this->set_props_via_data_store( $order, $bool_props, true );

		$this->assert_props_value_via_data_store( $order, $bool_props, true );

		$this->assert_props_value_via_order_object( $order, $bool_props, true );

		$this->assert_props_value_via_metadata( $order, $props_to_validate, 'yes' );
		$this->assert_props_value_via_metadata( $order, $special_prop, 'true' );

		// Let's repeat for false value.

		$this->set_props_via_data_store( $order, $bool_props, false );

		$this->assert_props_value_via_data_store( $order, $bool_props, false );

		$this->assert_props_value_via_order_object( $order, $bool_props, false );

		$this->assert_props_value_via_metadata( $order, $props_to_validate, 'no' );
		$this->assert_props_value_via_metadata( $order, $special_prop, 'false' );

		// Let's repeat for true value but setting via order object.

		$this->set_props_via_order_object( $order, $bool_props, true );

		$this->assert_props_value_via_data_store( $order, $bool_props, true );

		$this->assert_props_value_via_order_object( $order, $bool_props, true );

		$this->assert_props_value_via_metadata( $order, $props_to_validate, 'yes' );
		$this->assert_props_value_via_metadata( $order, $special_prop, 'true' );
	}

	/**
	 * Helper function to set prop via data store.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $props List of props and their setter names.
	 * @param mixed    $value value to set.
	 */
	private function set_props_via_data_store( $order, $props, $value ) {
		foreach ( $props as $meta_key_name => $prop_name ) {
			$order->get_data_store()->{"set_$prop_name"}( $order, $value );
		}
	}

	/**
	 * Helper function to set prop value via object.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $props List of props and their setter names.
	 * @param mixed    $value value to set.
	 */
	private function set_props_via_order_object( $order, $props, $value ) {
		foreach ( $props as $meta_key_name => $prop_name ) {
			$order->{"set_$prop_name"}( $value );
		}
		$order->save();
	}

	/**
	 * Helper function to assert prop value via data store.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $props List of props and their getter names.
	 * @param mixed    $value value to assert.
	 */
	private function assert_props_value_via_data_store( $order, $props, $value ) {
		foreach ( $props as $meta_key_name => $prop_name ) {
			$this->assertEquals( $value, $order->get_data_store()->{"get_$prop_name"}( $order ), "Prop $prop_name was not set correctly." );
		}
	}

	/**
	 * Helper function to assert prop value via order object.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $props List of props and their getter names.
	 * @param mixed    $value value to assert.
	 */
	private function assert_props_value_via_order_object( $order, $props, $value ) {
		foreach ( $props as $meta_key_name => $prop_name ) {
			$this->assertEquals( $value, $order->{"get_$prop_name"}(), "Prop $prop_name was not set correctly." );
		}
	}

	/**
	 * Helper function to assert prop value via metadata.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $props List of props and their getter names.
	 * @param mixed    $value value to assert.
	 */
	private function assert_props_value_via_metadata( $order, $props, $value ) {
		foreach ( $props as $meta_key_name => $prop_name ) {
			$this->assertEquals( $value, get_post_meta( $order->get_id(), $meta_key_name, true ), "Meta key $meta_key_name was not set correctly in the DB." );
		}
	}

	/**
	 * Test the untrashing an order works as expected when done in an agnostic way (ie, not depending directly on
	 * functions such as `wp_untrash_post()`.
	 *
	 * @return void
	 */
	public function test_untrash(): void {
		$order           = WC_Helper_Order::create_order();
		$order_id        = $order->get_id();
		$original_status = $order->get_status();

		$order->delete();
		$this->assertEquals( OrderStatus::TRASH, $order->get_status(), 'The order was successfully trashed.' );

		$order = wc_get_order( $order_id );
		$this->assertTrue( $order->untrash(), 'The order was restored from the trash.' );
		$this->assertEquals( $original_status, $order->get_status(), 'The original order status is restored following untrash.' );
	}

	/**
	 * @testDox A 'suppress_filters' argument can be passed to 'delete', if true no 'woocommerce_(before_)trash/delete_order' actions will be fired.
	 *
	 * @testWith [null, true]
	 *           [true, true]
	 *           [false, true]
	 *           [null, false]
	 *           [true, false]
	 *           [false, false]
	 *
	 * @param bool|null $suppress True or false to use a 'suppress_filters' argument with that value, null to not use it.
	 * @param bool      $force_delete True to delete the order, false to trash it.
	 * @return void
	 */
	public function test_filters_can_be_suppressed_when_trashing_or_deleting_an_order( ?bool $suppress, bool $force_delete ) {
		$order_id_from_before_delete = null;
		$order_id_from_after_delete  = null;
		$order_from_before_delete    = null;

		$trash_or_delete = $force_delete ? 'delete' : 'trash';

		add_action(
			"woocommerce_before_{$trash_or_delete}_order",
			function ( $order_id, $order ) use ( &$order_id_from_before_delete, &$order_from_before_delete ) {
				$order_id_from_before_delete = $order_id;
				$order_from_before_delete    = $order;
			},
			10,
			2
		);

		add_action(
			"woocommerce_{$trash_or_delete}_order",
			function ( $order_id ) use ( &$order_id_from_after_delete ) {
				$order_id_from_after_delete = $order_id;
			}
		);

		$args = array( 'force_delete' => $force_delete );
		if ( null !== $suppress ) {
			$args['suppress_filters'] = $suppress;
		}

		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$sut = new WC_Order_Data_Store_CPT();
		$sut->delete( $order, $args );

		if ( true === $suppress ) {
			$this->assertNull( $order_id_from_before_delete );
			$this->assertNull( $order_id_from_after_delete );
			$this->assertNull( $order_from_before_delete );
		} else {
			$this->assertEquals( $order_id, $order_id_from_before_delete );
			$this->assertEquals( $order_id, $order_id_from_after_delete );
			$this->assertSame( $order, $order_from_before_delete );
		}
	}

	/**
	 * @testDox Deleting order items should only delete items of the specified type.
	 */
	public function test_delete_items() {
		$order        = WC_Helper_Order::create_order();
		$product      = WC_Helper_Product::create_simple_product();
		$product_item = new WC_Order_Item_Product();
		$product_item->set_product( $product );
		$product_item->set_quantity( 1 );
		$product_item->save();

		$fee_item_1 = new WC_Order_Item_Fee();
		$fee_item_1->set_amount( 20 );
		$fee_item_1->save();

		$fee_item_2 = new WC_Order_Item_Fee();
		$fee_item_2->set_amount( 30 );
		$fee_item_2->save();

		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_name( 'dummy shipping' );
		$shipping_item->set_total( 20 );
		$shipping_item->save();

		$order->add_item( $product_item );
		$order->add_item( $fee_item_1 );
		$order->add_item( $fee_item_2 );
		$order->add_item( $shipping_item );

		$order->save();

		$r_order = wc_get_order( $order->get_id() );
		$this->assertTrue( $r_order->get_item( $fee_item_1->get_id() )->get_id() === $fee_item_1->get_id() );
		$this->assertTrue( $r_order->get_item( $fee_item_2->get_id() )->get_id() === $fee_item_2->get_id() );
		$this->assertTrue( $r_order->get_item( $product_item->get_id() )->get_id() === $product_item->get_id() );
		$this->assertTrue( $r_order->get_item( $shipping_item->get_id() )->get_id() === $shipping_item->get_id() );

		// Deleting single item type should only delete that item type.
		$r_order->get_data_store()->delete_items( $r_order, $fee_item_1->get_type() );
		$this->assertFalse( $r_order->get_item( $fee_item_1->get_id() ) );
		$this->assertFalse( $r_order->get_item( $fee_item_2->get_id() ) );
		$this->assertTrue( $r_order->get_item( $product_item->get_id() )->get_id() === $product_item->get_id() );
		$this->assertTrue( $r_order->get_item( $shipping_item->get_id() )->get_id() === $shipping_item->get_id() );

		// Deleting all items should all items.
		$r_order->get_data_store()->delete_items( $r_order );
		$this->assertFalse( $r_order->get_item( $fee_item_1->get_id() ) );
		$this->assertFalse( $r_order->get_item( $fee_item_2->get_id() ) );
		$this->assertFalse( $r_order->get_item( $product_item->get_id() ) );
		$this->assertFalse( $r_order->get_item( $shipping_item->get_id() ) );
	}

	/**
	 * @testDox Deleting order item should delete items from only that order.
	 */
	public function test_delete_items_multi_order() {
		$order_1        = WC_Helper_Order::create_order();
		$product        = WC_Helper_Product::create_simple_product();
		$product_item_1 = new WC_Order_Item_Product();
		$product_item_1->set_product( $product );
		$product_item_1->set_quantity( 1 );
		$product_item_1->save();

		$order_2        = WC_Helper_Order::create_order();
		$product_item_2 = new WC_Order_Item_Product();
		$product_item_2->set_product( $product );
		$product_item_2->set_quantity( 1 );
		$product_item_2->save();

		$order_1->add_item( $product_item_1 );
		$order_1->save();
		$order_2->add_item( $product_item_2 );
		$order_2->save();

		$this->assertTrue( $order_1->get_item( $product_item_1->get_id() )->get_id() === $product_item_1->get_id() );
		$this->assertTrue( $order_2->get_item( $product_item_2->get_id() )->get_id() === $product_item_2->get_id() );

		$order_1->get_data_store()->delete_items( $order_1 );

		$this->assertFalse( $order_1->get_item( $product_item_1->get_id() ) );
		$this->assertTrue( $order_2->get_item( $product_item_2->get_id() )->get_id() === $product_item_2->get_id() );
	}

	/**
	 * @testDox Creating an order with a draft status should not trigger the "woocommerce_new_order" action.
	 */
	public function test_create_draft_order_doesnt_trigger_hook() {

		$new_count = 0;

		$callback = function () use ( &$new_count ) {
			++$new_count;
		};

		add_action( 'woocommerce_new_order', $callback );

		$draft_statuses = array( OrderStatus::AUTO_DRAFT, 'checkout-draft' );

		$order_data_store_cpt = new WC_Order_Data_Store_CPT();

		foreach ( $draft_statuses as $status ) {
			$order = new WC_Order();
			$order->set_status( $status );
			$order_data_store_cpt->create( $order );
		}

		$this->assertEquals( 0, $new_count );

		remove_action( 'woocommerce_new_order', $callback );
	}

	/**
	 * @testDox Updating an order status correctly triggers the "woocommerce_new_order" action.
	 */
	public function test_update_order_status_correctly_triggers_new_order_hook() {

		$new_count = 0;

		$callback = function () use ( &$new_count ) {
			++$new_count;
		};

		add_action( 'woocommerce_new_order', $callback );

		$order_data_store_cpt = new WC_Order_Data_Store_CPT();

		$order = new WC_Order();
		$order->set_status( OrderStatus::DRAFT );

		$this->assertEquals( 0, $new_count );

		$order->set_status( 'checkout-draft' );
		$order_data_store_cpt->update( $order );
		$order->save();
		$this->assertEquals( 0, $new_count );

		$triggering_order_statuses = array( OrderStatus::PENDING, OrderStatus::ON_HOLD, OrderStatus::COMPLETED, OrderStatus::PROCESSING );

		foreach ( $triggering_order_statuses as $k => $status ) {
			$current_status = $order->get_status( 'edit' );
			$order->set_status( $status );
			$order_data_store_cpt->update( $order );
			$order->set_status( 'checkout-draft' ); // Revert back to draft.
			$order->save();
			$this->assertEquals(
				$k + 1,
				$new_count,
				'Failed to trigger new order hook changing status: ' . $current_status . ' -> ' . $status
			);
		}

		remove_action( 'woocommerce_new_order', $callback );
	}

	/**
	 * @testDox Create a new order with processing status without saving and updating it should trigger the "woocommerce_new_order" action.
	 */
	public function test_update_new_processing_order_correctly_triggers_new_order_hook() {

		$new_count = 0;

		$callback = function () use ( &$new_count ) {
			++$new_count;
		};

		add_action( 'woocommerce_new_order', $callback );

		$order_data_store_cpt = new WC_Order_Data_Store_CPT();

		$order = new WC_Order();
		$order->set_status( OrderStatus::PROCESSING );

		$this->assertEquals( 0, $new_count );

		$order_data_store_cpt->update( $order );

		$this->assertEquals( 1, $new_count );

		remove_action( 'woocommerce_new_order', $callback );
	}

	/**
	 * Test total filtering with operators works as expected for CPT storage.
	 */
	public function test_total_filtering_with_operators() {
		$order_totals_to_test = array( 5, 10, 50, 100.00, 100.00, 250.50, 250.50, 500.75, 1000.00 );
		foreach ( $order_totals_to_test as $order_total ) {
			$order = OrderHelper::create_order();
			$order->set_total( $order_total );
			$order->save();
		}

		$test_matrix = array(
			array(
				'value'          => 250.50,
				'operator'       => '=',
				'expected_count' => 2,
			),
			array(
				'value'          => 250.50,
				'operator'       => '!=',
				'expected_count' => 7,
			),
			array(
				'value'          => 250.50,
				'operator'       => '>',
				'expected_count' => 2,
			),
			array(
				'value'          => 250.50,
				'operator'       => '>=',
				'expected_count' => 4,
			),
			array(
				'value'          => 250.50,
				'operator'       => '<',
				'expected_count' => 5,
			),
			array(
				'value'          => 250.50,
				'operator'       => '<=',
				'expected_count' => 7,
			),
			array(
				'value'          => array( 100, 500 ),
				'operator'       => 'BETWEEN',
				'expected_count' => 4,
			),
			array(
				'value'          => array( 100, 500 ),
				'operator'       => 'NOT BETWEEN',
				'expected_count' => 5,
			),
		);

		foreach ( $test_matrix as $test ) {
			$orders = wc_get_orders(
				array(
					'total' => array(
						'value'    => $test['value'],
						'operator' => $test['operator'],
					),
				)
			);
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$this->assertCount( $test['expected_count'], $orders, print_r( $test, true ) );
		}
	}

	/**
	 * Test that order props saved by data stores are read correctly.
	 */
	public function test_reading_order_basic_props() {
		$order = WC_Helper_Order::create_order();
		$order->set_currency( 'EUR' );
		$order->set_discount_tax( 2 );
		$order->set_discount_total( 3 );
		$order->set_shipping_total( 4 );
		$order->set_shipping_tax( 5 );
		$order->set_cart_tax( 6 );
		$order->set_total( 100 );
		$order->set_prices_include_tax( true );
		$order->save();
		$order_id = $order->get_id();

		$read_order = wc_get_order( $order_id );

		$this->assertEquals( 'EUR', $read_order->get_currency() );
		$this->assertEquals( 2, $read_order->get_discount_tax() );
		$this->assertEquals( 3, $read_order->get_discount_total() );
		$this->assertEquals( 4, $read_order->get_shipping_total() );
		$this->assertEquals( 5, $read_order->get_shipping_tax() );
		$this->assertEquals( 6, $read_order->get_cart_tax() );
		$this->assertEquals( 100, $read_order->get_total() );
		$this->assertEquals( WC_VERSION, $read_order->get_version() );
		$this->assertTrue( $read_order->get_prices_include_tax() );
	}

	/**
	 * Test that order props saved by data stores are read correctly.
	 */
	public function test_reading_complete_order_data() {
		$order = WC_Helper_Order::create_order();
		$order->set_order_key( 'wc_order_test_key_123' );
		$order->set_customer_id( 1 );

		$order->set_billing_first_name( 'John' );
		$order->set_billing_last_name( 'Doe' );
		$order->set_billing_company( 'Acme Inc' );
		$order->set_billing_address_1( '123 Main St' );
		$order->set_billing_address_2( 'Apt 4B' );
		$order->set_billing_city( 'New York' );
		$order->set_billing_state( 'NY' );
		$order->set_billing_postcode( '10001' );
		$order->set_billing_country( 'US' );
		$order->set_billing_email( 'john@example.com' );
		$order->set_billing_phone( '555-1234' );

		$order->set_shipping_first_name( 'Jane' );
		$order->set_shipping_last_name( 'Smith' );
		$order->set_shipping_company( 'Tech Corp' );
		$order->set_shipping_address_1( '456 Oak Ave' );
		$order->set_shipping_address_2( 'Suite 200' );
		$order->set_shipping_city( 'Boston' );
		$order->set_shipping_state( 'MA' );
		$order->set_shipping_postcode( '02101' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_phone( '555-5678' );

		$order->set_payment_method( 'stripe' );
		$order->set_payment_method_title( 'Credit Card (Stripe)' );
		$order->set_transaction_id( 'txn_abc123def456' );

		$order->set_customer_ip_address( '192.168.1.1' );
		$order->set_customer_user_agent( 'Mozilla/5.0' );
		$order->set_created_via( 'checkout' );

		$date_completed = '2024-01-15 10:30:00';
		$date_paid      = '2024-01-15 10:25:00';
		$order->set_date_completed( $date_completed );
		$order->set_date_paid( $date_paid );

		$order->set_cart_hash( 'cart_hash_xyz789' );

		$order->set_customer_note( 'Please ring doorbell twice' );

		$order->set_download_permissions_granted( true );

		$order->save();
		$order_id = $order->get_id();

		$read_order = wc_get_order( $order_id );

		$this->assertEquals( 'wc_order_test_key_123', $read_order->get_order_key() );
		$this->assertEquals( 1, $read_order->get_customer_id() );

		$this->assertEquals( 'John', $read_order->get_billing_first_name() );
		$this->assertEquals( 'Doe', $read_order->get_billing_last_name() );
		$this->assertEquals( 'Acme Inc', $read_order->get_billing_company() );
		$this->assertEquals( '123 Main St', $read_order->get_billing_address_1() );
		$this->assertEquals( 'Apt 4B', $read_order->get_billing_address_2() );
		$this->assertEquals( 'New York', $read_order->get_billing_city() );
		$this->assertEquals( 'NY', $read_order->get_billing_state() );
		$this->assertEquals( '10001', $read_order->get_billing_postcode() );
		$this->assertEquals( 'US', $read_order->get_billing_country() );
		$this->assertEquals( 'john@example.com', $read_order->get_billing_email() );
		$this->assertEquals( '555-1234', $read_order->get_billing_phone() );

		$this->assertEquals( 'Jane', $read_order->get_shipping_first_name() );
		$this->assertEquals( 'Smith', $read_order->get_shipping_last_name() );
		$this->assertEquals( 'Tech Corp', $read_order->get_shipping_company() );
		$this->assertEquals( '456 Oak Ave', $read_order->get_shipping_address_1() );
		$this->assertEquals( 'Suite 200', $read_order->get_shipping_address_2() );
		$this->assertEquals( 'Boston', $read_order->get_shipping_city() );
		$this->assertEquals( 'MA', $read_order->get_shipping_state() );
		$this->assertEquals( '02101', $read_order->get_shipping_postcode() );
		$this->assertEquals( 'US', $read_order->get_shipping_country() );
		$this->assertEquals( '555-5678', $read_order->get_shipping_phone() );

		$this->assertEquals( 'stripe', $read_order->get_payment_method() );
		$this->assertEquals( 'Credit Card (Stripe)', $read_order->get_payment_method_title() );
		$this->assertEquals( 'txn_abc123def456', $read_order->get_transaction_id() );

		$this->assertEquals( '192.168.1.1', $read_order->get_customer_ip_address() );
		$this->assertEquals( 'Mozilla/5.0', $read_order->get_customer_user_agent() );
		$this->assertEquals( 'checkout', $read_order->get_created_via() );

		$this->assertEquals( $date_completed, $read_order->get_date_completed()->date( 'Y-m-d H:i:s' ) );
		$this->assertEquals( $date_paid, $read_order->get_date_paid()->date( 'Y-m-d H:i:s' ) );

		$this->assertEquals( 'cart_hash_xyz789', $read_order->get_cart_hash() );

		$this->assertEquals( 'Please ring doorbell twice', $read_order->get_customer_note() );

		$this->assertTrue( $read_order->get_download_permissions_granted() );
	}

	/**
	 * Test reading refund data.
	 */
	public function test_reading_refund_data() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$refund = new WC_Order_Refund();
		$refund->set_parent_id( $order->get_id() );
		$refund->set_amount( 50.00 );
		$refund->set_refunded_by( 8 );
		$refund->set_refunded_payment( true );
		$refund->set_reason( 'Customer requested refund' );
		$refund->save();

		$read_refund = wc_get_order( $refund->get_id() );

		$this->assertEquals( 50.00, $read_refund->get_amount() );
		$this->assertEquals( 8, $read_refund->get_refunded_by() );
		$this->assertTrue( $read_refund->get_refunded_payment() );
		$this->assertEquals( 'Customer requested refund', $read_refund->get_reason() );
	}

	/**
	 * Test orderby total functionality works as expected for CPT storage.
	 */
	public function test_orderby_total() {
		// Create orders with different totals.
		$order_totals = array( 100.00, 50.00, 250.50, 75.25, 500.00 );
		$orders       = array();
		foreach ( $order_totals as $order_total ) {
			$order = OrderHelper::create_order();
			$order->set_total( $order_total );
			$order->save();
			$orders[] = $order;
		}

		// Test ascending order.
		$orders_asc = wc_get_orders(
			array(
				'orderby' => 'total',
				'order'   => 'asc',
				'return'  => 'ids',
			)
		);

		$this->assertCount( 5, $orders_asc );

		// Verify ascending order by checking totals.
		$totals_asc = array();
		foreach ( $orders_asc as $order_id ) {
			$order        = wc_get_order( $order_id );
			$totals_asc[] = $order->get_total();
		}

		$expected_totals_asc = array( 50.00, 75.25, 100.00, 250.50, 500.00 );
		$this->assertEquals( $expected_totals_asc, $totals_asc, 'Orders should be sorted by total in ascending order' );

		// Test descending order.
		$orders_desc = wc_get_orders(
			array(
				'orderby' => 'total',
				'order'   => 'desc',
				'return'  => 'ids',
			)
		);

		$this->assertCount( 5, $orders_desc );

		// Verify descending order by checking totals.
		$totals_desc = array();
		foreach ( $orders_desc as $order_id ) {
			$order         = wc_get_order( $order_id );
			$totals_desc[] = $order->get_total();
		}

		$expected_totals_desc = array( 500.00, 250.50, 100.00, 75.25, 50.00 );
		$this->assertEquals( $expected_totals_desc, $totals_desc, 'Orders should be sorted by total in descending order' );

		// Clean up.
		foreach ( $orders as $order ) {
			$order->delete( true );
		}
	}

	/**
	 * Helper method to add a product with COGS value to an order.
	 *
	 * @param WC_Order $order Order object.
	 * @param float    $cogs_value COGS value for the product.
	 * @param int      $quantity Quantity of the product.
	 */
	private function add_product_with_cogs_to_order( WC_Order $order, float $cogs_value, int $quantity ) {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_cogs_value( $cogs_value );
		$product->save();
		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( $quantity );
		$item->save();
		$order->add_item( $item );
	}

	/**
	 * Helper method to create a test data store with protected methods exposed as public.
	 *
	 * @return WC_Order_Data_Store_CPT Data store with public method overrides.
	 */
	private function get_test_data_store() {
		// phpcs:disable Squiz.Commenting, Generic.CodeAnalysis.UselessOverridingMethod
		return new class() extends WC_Order_Data_Store_CPT {
			public function get_internal_meta_keys() {
				return $this->internal_meta_keys;
			}

			public function update_order_meta_from_object( $order ) {
				parent::update_order_meta_from_object( $order );
			}
		};
		// phpcs:enable Squiz.Commenting, Generic.CodeAnalysis.UselessOverridingMethod
	}

	/**
	 * @testDox Saving an order does not persist its Cost of Goods Sold total value if the feature is disabled.
	 */
	public function test_saving_order_does_not_save_cogs_value_if_cogs_disabled() {
		$this->expect_doing_it_wrong_cogs_disabled( 'WC_Abstract_Order::set_cogs_total_value' );

		$order = new WC_Order();
		$order->set_cogs_total_value( 12.34 );
		$order->save();

		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_cogs_total_value' ) );
	}

	/**
	 * @testDox Saving an order does not persist its Cost of Goods Sold total value if the feature is enabled but the order doesn't manage it.
	 */
	public function test_saving_order_does_not_save_cogs_value_if_order_has_no_cogs() {
		$this->enable_cogs_feature();

		// phpcs:disable Squiz.Commenting
		$order = new class() extends WC_Order {
			public function has_cogs(): bool {
				return false;
			}
		};
		// phpcs:enable Squiz.Commenting
		$order->set_cogs_total_value( 12.34 );
		$order->save();

		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_cogs_total_value' ) );
	}

	/**
	 * @testDox Saving an order persists its Cost of Goods Sold total value if the feature is enabled and the order manages it.
	 */
	public function test_saving_order_saves_cogs_value_if_not_zero_and_cogs_enabled() {
		$this->enable_cogs_feature();

		$order = new WC_Order();
		$order->set_cogs_total_value( 12.34 );
		$order->save();

		$this->assertEquals( 12.34, (float) get_post_meta( $order->get_id(), '_cogs_total_value', true ) );

		$order->set_cogs_total_value( 56.78 );
		$order->save();

		$this->assertEquals( 56.78, (float) get_post_meta( $order->get_id(), '_cogs_total_value', true ) );

		$order->set_cogs_total_value( 0 );
		$order->save();

		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_cogs_total_value' ) );
	}

	/**
	 * @testDox Loading an order reads its Cost of Goods Sold value from the database if the feature is enabled and the order manages it.
	 *
	 * @testWith [true, false]
	 *           [false, true]
	 *           [true, true]
	 *           [false, false]
	 *
	 * @param bool $cogs_enabled True if the feature is enabled.
	 * @param bool $order_has_cogs True if the order manages COGS.
	 */
	public function test_loading_order_loads_cogs_value_if_cogs_enabled( bool $cogs_enabled, bool $order_has_cogs ) {
		if ( $cogs_enabled ) {
			$this->enable_cogs_feature();
		} elseif ( $order_has_cogs ) {
			$this->expect_doing_it_wrong_cogs_disabled( 'WC_Abstract_Order::get_cogs_total_value' );
		}

		$order = new WC_Order();
		$order->save();

		$saved_meta = get_post_meta( $order->get_id(), '_cogs_total_value', true );
		if ( $saved_meta ) {
			delete_post_meta( $order->get_id(), '_cogs_total_value' );
		}

		update_post_meta( $order->get_id(), '_cogs_total_value', '12.34' );

		if ( $order_has_cogs ) {
			$order2 = wc_get_order( $order->get_id() );
		} else {
			// phpcs:disable Squiz.Commenting
			$order2 = new class($order->get_id()) extends WC_Order {
				public function has_cogs(): bool {
					return false;
				}
			};
			// phpcs:enable Squiz.Commenting
		}
		$this->assertEquals( ( $cogs_enabled && $order_has_cogs ) ? 12.34 : 0, $order2->get_cogs_total_value() );
	}

	/**
	 * @testDox It's possible to modify the Cost of Goods Sold value that gets loaded from the database for an order using the 'woocommerce_load_order_cogs_value' filter.
	 */
	public function test_loaded_cogs_value_can_be_modified_via_filter() {
		$received_filter_cogs_value = null;
		$received_filter_item       = null;

		$this->enable_cogs_feature();

		$order = new WC_Order();
		$order->set_cogs_total_value( 12.34 );
		$order->save();

		add_filter(
			'woocommerce_load_order_cogs_value',
			function ( $cogs_value, $item ) use ( &$received_filter_cogs_value, &$received_filter_item ) {
				$received_filter_cogs_value = $cogs_value;
				$received_filter_item       = $item;
				return 56.78;
			},
			10,
			2
		);

		$order2 = wc_get_order( $order->get_id() );

		$this->assertEquals( 12.34, $received_filter_cogs_value );
		$this->assertSame( $order2, $received_filter_item );
		$this->assertEquals( 56.78, $order2->get_cogs_total_value() );
	}

	/**
	 * @testDox It's possible to modify the Cost of Goods Sold value that gets persisted for an order using the 'woocommerce_save_order_cogs_value' filter, returning null suppresses the saving.
	 *
	 * @testWith [56.78, "56.78"]
	 *           [null, "12.34"]
	 *
	 * @param mixed  $filter_return_value The value that the filter will return.
	 * @param string $expected_saved_value The value that is expected to be persisted after the save attempt.
	 */
	public function test_saved_cogs_value_can_be_altered_via_filter_with_null_meaning_dont_save( $filter_return_value, string $expected_saved_value ) {
		$received_filter_cogs_value = null;
		$received_filter_item       = null;

		$this->enable_cogs_feature();

		$order = new WC_Order();
		$order->set_cogs_total_value( 12.34 );
		$order->save();

		add_filter(
			'woocommerce_save_order_cogs_value',
			function ( $cogs_value, $item ) use ( &$received_filter_cogs_value, &$received_filter_item, $filter_return_value ) {
				$received_filter_cogs_value = $cogs_value;
				$received_filter_item       = $item;
				return $filter_return_value;
			},
			10,
			2
		);

		$order->set_cogs_total_value( 56.78 );
		$order->save();

		$this->assertEquals( 56.78, $received_filter_cogs_value );
		$this->assertSame( $order, $received_filter_item );

		$this->assertEquals( $expected_saved_value, (float) get_post_meta( $order->get_id(), '_cogs_total_value', true ) );
	}

	/**
	 * @testDox COGS total value is correctly calculated and persisted when HPOS is disabled.
	 */
	public function test_cogs_total_value_calculated_and_persisted_with_cpt() {
		$this->enable_cogs_feature();

		$product1_cost  = 12.34;
		$product1_qty   = 2;
		$product2_cost  = 5.50;
		$product2_qty   = 3;
		$expected_total = ( $product1_cost * $product1_qty ) + ( $product2_cost * $product2_qty );

		$order = new WC_Order();
		$this->add_product_with_cogs_to_order( $order, $product1_cost, $product1_qty );
		$this->add_product_with_cogs_to_order( $order, $product2_cost, $product2_qty );

		$order->calculate_cogs_total_value();
		$order->save();

		// Verify COGS is saved to database.
		$this->assertEquals( $expected_total, (float) get_post_meta( $order->get_id(), '_cogs_total_value', true ) );

		// Verify COGS is loaded correctly when order is retrieved.
		$loaded_order = wc_get_order( $order->get_id() );
		$this->assertEquals( $expected_total, $loaded_order->get_cogs_total_value() );
	}

	/**
	 * @testDox COGS total value is zero when order has no items with COGS.
	 */
	public function test_cogs_total_value_zero_when_no_cogs_items() {
		$this->enable_cogs_feature();

		$order = new WC_Order();
		$order->calculate_cogs_total_value();
		$order->save();

		// Verify no COGS meta is saved when value is zero.
		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_cogs_total_value' ) );

		// Verify COGS value is zero when order is retrieved.
		$loaded_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 0, $loaded_order->get_cogs_total_value() );
	}

	/**
	 * @testDox _cogs_total_value is included in internal meta keys to prevent it from showing as custom field.
	 */
	public function test_cogs_total_value_is_internal_meta() {
		$data_store = $this->get_test_data_store();

		$this->assertContains( '_cogs_total_value', $data_store->get_internal_meta_keys(), 'COGS total value should be in internal meta keys' );
	}

	/**
	 * @testDox COGS value is synced via update_order_meta_from_object for compatibility mode.
	 */
	public function test_cogs_in_meta_key_to_props_for_sync() {
		$this->enable_cogs_feature();

		$product_cost  = 10.50;
		$product_qty   = 2;
		$initial_cogs  = $product_cost * $product_qty;
		$modified_cogs = $initial_cogs * 2;

		$order = new WC_Order();
		$this->add_product_with_cogs_to_order( $order, $product_cost, $product_qty );
		$order->calculate_cogs_total_value();
		$order->save();

		$this->assertEquals( $initial_cogs, $order->get_cogs_total_value() );
		$this->assertEquals( $initial_cogs, (float) get_post_meta( $order->get_id(), '_cogs_total_value', true ) );

		// Reload the order and modify COGS value to simulate HPOS order with different value.
		$modified_order = wc_get_order( $order->get_id() );
		$modified_order->set_cogs_total_value( $modified_cogs );

		// Delete the post meta to simulate it not being synced yet.
		delete_post_meta( $order->get_id(), '_cogs_total_value' );
		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_cogs_total_value' ) );

		// Simulate what happens during compatibility mode backfill.
		$data_store = $this->get_test_data_store();

		// Call update_order_meta_from_object which should sync COGS.
		$data_store->update_order_meta_from_object( $modified_order );

		// Verify the COGS value was synced to the database.
		$this->assertEquals( $modified_cogs, (float) get_post_meta( $order->get_id(), '_cogs_total_value', true ) );

		// Reload and verify.
		$reloaded_order = wc_get_order( $order->get_id() );
		$this->assertEquals( $modified_cogs, $reloaded_order->get_cogs_total_value() );
	}

	/**
	 * @testDox COGS value is synced during backfill via update_order_meta_from_object.
	 */
	public function test_cogs_synced_via_update_order_meta_from_object() {
		$this->enable_cogs_feature();

		$product_cost  = 15.75;
		$product_qty   = 3;
		$expected_cogs = $product_cost * $product_qty;

		$order = new WC_Order();
		$this->add_product_with_cogs_to_order( $order, $product_cost, $product_qty );
		$order->calculate_cogs_total_value();
		$order->save();

		$this->assertEquals( $expected_cogs, $order->get_cogs_total_value() );

		// Verify it's in the database.
		$this->assertEquals( $expected_cogs, (float) get_post_meta( $order->get_id(), '_cogs_total_value', true ) );

		// Delete the COGS meta to simulate it not being synced yet.
		delete_post_meta( $order->get_id(), '_cogs_total_value' );
		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_cogs_total_value' ) );

		// Reload the order to get fresh state.
		$fresh_order = wc_get_order( $order->get_id() );

		// The fresh order will have 0 COGS since we deleted the meta.
		// Set it to the expected value to simulate an HPOS order with COGS that needs to be synced.
		$fresh_order->set_cogs_total_value( $expected_cogs );

		// Create a test data store to access the protected method.
		$data_store = $this->get_test_data_store();

		// Call update_order_meta_from_object which should sync COGS.
		$data_store->update_order_meta_from_object( $fresh_order );

		// Verify COGS was synced.
		$this->assertEquals( $expected_cogs, (float) get_post_meta( $order->get_id(), '_cogs_total_value', true ) );
	}

	/**
	 * @testDox Items without saved COGS metadata can calculate COGS from products.
	 */
	public function test_items_without_saved_cogs_calculate_from_product() {
		$this->enable_cogs_feature();

		$product_cost  = 15.00;
		$product_qty   = 2;
		$expected_cogs = $product_cost * $product_qty;

		// Create an order with COGS and save it.
		$order = new WC_Order();
		$this->add_product_with_cogs_to_order( $order, $product_cost, $product_qty );
		$order->calculate_totals();
		$order->save();

		// Get the item and manually delete its _cogs_value metadata to simulate an item without saved COGS.
		$items = $order->get_items();
		$item  = reset( $items );
		delete_metadata( 'order_item', $item->get_id(), '_cogs_value' );

		// Reload the order.
		$reloaded_order = wc_get_order( $order->get_id() );

		// The item should not have a saved COGS value.
		$reloaded_items = $reloaded_order->get_items();
		$reloaded_item  = reset( $reloaded_items );

		// When we call calculate_totals, it should calculate COGS from the product.
		$reloaded_order->calculate_totals();

		// Verify the COGS was calculated correctly.
		$this->assertEquals( $expected_cogs, $reloaded_item->get_cogs_value(), 'Item without saved COGS should calculate from product' );
		$this->assertEquals( $expected_cogs, $reloaded_order->get_cogs_total_value(), 'Order total should reflect calculated item COGS' );
	}

	/**
	 * @testDox Refund items always recalculate COGS based on their negative quantity.
	 */
	public function test_refund_items_recalculate_cogs() {
		$this->enable_cogs_feature();

		$product_cost         = 20.00;
		$product_qty          = 10;
		$refund_qty           = 3;
		$expected_order_cogs  = $product_cost * $product_qty;
		$expected_refund_cogs = -( $product_cost * $refund_qty );

		// Create a product with COGS and price.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( $product_cost );
		$product->set_cogs_value( $product_cost );
		$product->save();

		// Create an order with COGS.
		$order = new WC_Order();
		$order->add_product( $product, $product_qty );
		$order->calculate_totals();
		$order->save();

		$this->assertEquals( $expected_order_cogs, $order->get_cogs_total_value() );

		// Get the order item.
		$order_items = array_values( $order->get_items( 'line_item' ) );
		$order_item  = $order_items[0];

		// Create a refund.
		$refund = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $product_cost * $refund_qty,
				'reason'     => 'testing',
				'line_items' => array(
					$order_item->get_id() => array(
						'qty'          => $refund_qty,
						'refund_total' => $product_cost * $refund_qty,
					),
				),
			)
		);

		$this->assertNotInstanceOf( 'WP_Error', $refund, 'Refund creation should not return an error' );
		$refund->save();

		// Verify the refund has the correct COGS (negative value).
		$this->assertEquals( $expected_refund_cogs, $refund->get_cogs_total_value(), 'Refund should have negative COGS' );

		// Recalculate order totals and verify COGS is adjusted for the refund.
		$order->calculate_totals();
		$expected_final_cogs = $expected_order_cogs + $expected_refund_cogs;
		$this->assertEquals( $expected_final_cogs, $order->get_cogs_total_value(), 'Order COGS should be reduced by refund amount' );
	}
}
