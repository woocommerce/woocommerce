<?php

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

//phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Legacy class name.
/**
 * Class WC_Order_Data_Store_CPT_Test.
 */
class WC_Order_Data_Store_CPT_Test extends WC_Unit_Test_Case {
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
		$this->assertEquals( 'trash', $order->get_status(), 'The order was successfully trashed.' );

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
}
