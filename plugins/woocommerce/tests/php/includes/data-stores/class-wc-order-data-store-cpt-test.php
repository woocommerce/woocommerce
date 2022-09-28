<?php

/**
 * Class WC_Order_Data_Store_CPT_Test.
 */
class WC_Order_Data_Store_CPT_Test extends WC_Unit_Test_Case {

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
		$order_id   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_complex_wp_post_order();
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

}
