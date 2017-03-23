<?php

/**
 * Meta
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_CRUD_Orders extends WC_Unit_Test_Case {

	/**
	 * Test: get_type
	 */
	function test_get_type() {
		$object = new WC_Order();
		$this->assertEquals( 'shop_order', $object->get_type() );
	}

	/**
	 * Test: get_data
	 */
	function test_get_data() {
		$object = new WC_Order();
		$this->assertInternalType( 'array', $object->get_data() );
	}

	/**
	 * Test: get_id
	 */
	function test_get_id() {
		$object = new WC_Order();
		$id = $object->save();
		$this->assertEquals( $id, $object->get_id() );
	}

	/**
	 * Test: get_parent_id
	 */
	function test_get_parent_id() {
		$object1   = new WC_Order();
		$parent_id = $object1->save();
		$object = new WC_Order();
		$object->set_parent_id( $parent_id );
		$this->assertEquals( $parent_id, $object->get_parent_id() );
	}

	/**
	 * Test: get_order_number
	 */
	function test_get_order_number() {
		$object = new WC_Order();
		$id     = $object->save();
		$this->assertEquals( $id, $object->get_order_number() );
	}

	/**
	 * Test: get_order_key
	 */
	function test_get_order_key() {
		$object = new WC_Order();
		$set_to = 'some_key';
		$object->set_order_key( $set_to );
		$this->assertEquals( $set_to, $object->get_order_key() );
	}

	/**
	 * Test: get_currency
	 */
	function test_get_currency() {
		$object = new WC_Order();
		$set_to = 'USD';
		$object->set_currency( $set_to );
		$this->assertEquals( $set_to, $object->get_currency() );
	}

	/**
	 * Test: get_version
	 */
	function test_get_version() {
		$object = new WC_Order();
		$set_to = '3.0.0';
		$object->set_version( $set_to );
		$this->assertEquals( $set_to, $object->get_version() );
	}

	/**
	 * Test: get_prices_include_tax
	 */
	function test_get_prices_include_tax() {
		$object = new WC_Order();
		$set_to = 'USD';
		$object->set_prices_include_tax( 1 );
		$this->assertTrue( $object->get_prices_include_tax() );
	}

	/**
	 * Test: get_date_created
	 */
	function test_get_date_created() {
		$object = new WC_Order();
		$object->set_date_created( '2016-12-12' );
		$this->assertEquals( '1481500800', $object->get_date_created()->getOffsetTimestamp() );

		$object->set_date_created( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_created()->getTimestamp() );
	}

	/**
	 * Test: get_date_modified
	 */
	function test_get_date_modified() {
		$object = new WC_Order();
		$object->set_date_modified( '2016-12-12' );
		$this->assertEquals( '1481500800', $object->get_date_modified()->getOffsetTimestamp() );

		$object->set_date_modified( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_modified()->getTimestamp() );
	}

	/**
	 * Test: get_customer_id
	 */
	function test_get_customer_id() {
		$object = new WC_Order();
		$set_to = 10;
		$object->set_customer_id( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_id() );
	}

	/**
	 * Test: get_user
	 */
	function test_get_user() {
		$object = new WC_Order();
		$this->assertFalse( $object->get_user() );
		$set_to = '1';
		$object->set_customer_id( $set_to );
		$this->assertInstanceOf( 'WP_User', $object->get_user() );
	}

	/**
	 * Test: get_discount_total
	 */
	function test_get_discount_total() {
		$object = new WC_Order();
		$object->set_discount_total( 50 );
		$this->assertEquals( 50, $object->get_discount_total() );
	}

	/**
	 * Test: get_discount_tax
	 */
	function test_get_discount_tax() {
		$object = new WC_Order();
		$object->set_discount_tax( 5 );
		$this->assertEquals( 5, $object->get_discount_tax() );
	}

	/**
	 * Test: get_shipping_total
	 */
	function test_get_shipping_total() {
		$object = new WC_Order();
		$object->set_shipping_total( 5 );
		$this->assertEquals( 5, $object->get_shipping_total() );
	}

	/**
	 * Test: get_shipping_tax
	 */
	function test_get_shipping_tax() {
		$object = new WC_Order();
		$object->set_shipping_tax( 5 );
		$this->assertEquals( 5, $object->get_shipping_tax() );
	}

	/**
	 * Test: get_cart_tax
	 */
	function test_get_cart_tax() {
		$object = new WC_Order();
		$object->set_cart_tax( 5 );
		$this->assertEquals( 5, $object->get_cart_tax() );
	}

	/**
	 * Test: get_total
	 */
	function test_get_total() {
		$object = new WC_Order();
		$object->set_total( 5 );
		$this->assertEquals( 5, $object->get_total() );
	}

	/**
	 * Test: get_total_tax
	 */
	function test_get_total_tax() {
		$object = new WC_Order();
		$object->set_cart_tax( 5 );
		$object->set_shipping_tax( 5 );
		$this->assertEquals( 10, $object->get_total_tax() );
	}

	/**
	 * Test: get_total_discount
	 */
	function test_get_total_discount() {
		$object = new WC_Order();
		$object->set_discount_total( 50 );
		$object->set_discount_tax( 5 );
		$this->assertEquals( 50, $object->get_total_discount() );
		$this->assertEquals( 55, $object->get_total_discount( false ) );
	}

	/**
	 * Test: get_subtotal
	 */
	function test_get_subtotal() {
		$object = WC_Helper_Order::create_order();
		$this->assertEquals( 40, $object->get_subtotal() );
	}

	/**
	 * Test: get_tax_totals
	 */
	function test_get_tax_totals() {
		$object = WC_Helper_Order::create_order();
		$this->assertEquals( array(), $object->get_tax_totals() );
	}

	/**
	 * Test: remove_order_items
	 */
	function test_remove_order_items() {
		$product = WC_Helper_Product::create_simple_product();
		$object  = new WC_Order();
		$item_1  = new WC_Order_Item_Product();
		$item_1->set_props( array(
			'product'  => $product,
			'quantity' => 4,
		) );
		$item_2  = new WC_Order_Item_Product();
		$item_2->set_props( array(
			'product'  => $product,
			'quantity' => 2,
		) );
		$object->add_item( $item_1 );
		$object->add_item( $item_2 );
		$object->save();
		$this->assertCount( 2, $object->get_items() );
		$object->remove_order_items();
		$this->assertCount( 0, $object->get_items() );
	}

	/**
	 * Test: get_items
	 */
	function test_get_items() {
		$object  = new WC_Order();
		$item_1  = new WC_Order_Item_Product();
		$item_1->set_props( array(
			'product'  => WC_Helper_Product::create_simple_product(),
			'quantity' => 4,
		) );
		$item_2  = new WC_Order_Item_Product();
		$item_2->set_props( array(
			'product'  => WC_Helper_Product::create_simple_product(),
			'quantity' => 2,
		) );

		$object->add_item( $item_1 );
		$object->add_item( $item_2 );
		$object->save();
		$this->assertCount( 2, $object->get_items() );
	}

	/**
	 * Test: get_fees
	 */
	function test_get_fees() {
		$object  = new WC_Order();
		$item    = new WC_Order_Item_Fee();
		$item->set_props( array(
			'name'       => 'Some Fee',
			'tax_status' => 'taxable',
			'total'      => '100',
			'tax_class'  => '',
		) );
		$object->add_item( $item );
		$this->assertCount( 1, $object->get_fees() );
	}

	/**
	 * Test: get_taxes
	 */
	function test_get_taxes() {
		global $wpdb;

		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$object  = new WC_Order();
		$item_1  = new WC_Order_Item_Product();
		$item_1->set_props( array(
			'product'  => WC_Helper_Product::create_simple_product(),
			'quantity' => 4,
		) );
		$object->add_item( $item_1 );
		$object->calculate_totals();
		$this->assertCount( 1, $object->get_taxes() );

		$item = new WC_Order_Item_Tax();
		$item->set_rate( 100 );
		$item->set_tax_total( 100 );
		$item->set_shipping_tax_total( 100 );
		$object->add_item( $item );
		$object->save();

		$this->assertCount( 2, $object->get_taxes() );

		// Cleanup
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test mapping from old tax array keys to CRUD functions.
	 */
	function test_tax_legacy_arrayaccess() {
		$tax = new WC_Order_item_Tax();
		$tax->set_rate_id( 5 );
		$tax->set_compound( true );
		$tax->set_tax_total( 2.00 );
		$tax->set_shipping_tax_total( 1.50 );

		$this->assertEquals( $tax->get_rate_id(), $tax['rate_id'] );
		$this->assertEquals( $tax->get_compound(), $tax['compound'] );
		$this->assertEquals( $tax->get_tax_total(), $tax['tax_total'] );
		$this->assertEquals( $tax->get_tax_total(), $tax['tax_amount'] );
		$this->assertEquals( $tax->get_shipping_tax_total(), $tax['shipping_tax_total'] );
		$this->assertEquals( $tax->get_shipping_tax_total(), $tax['shipping_tax_amount'] );
	}

	/**
	 * Test: get_shipping_methods
	 */
	function test_get_shipping_methods() {
		$object = new WC_Order();
		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item   = new WC_Order_Item_Shipping();
		$item->set_props( array(
			'method_title' => $rate->label,
			'method_id'    => $rate->id,
			'total'        => wc_format_decimal( $rate->cost ),
			'taxes'        => $rate->taxes,
		) );
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );
		$object->save();
		$this->assertCount( 1, $object->get_shipping_methods() );
	}

	/**
	 * Test: get_shipping_method
	 */
	function test_get_shipping_method() {
		$object = new WC_Order();
		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item   = new WC_Order_Item_Shipping();
		$item->set_props( array(
			'method_title' => $rate->label,
			'method_id'    => $rate->id,
			'total'        => wc_format_decimal( $rate->cost ),
			'taxes'        => $rate->taxes,
		) );
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );
		$object->save();
		$this->assertEquals( 'Flat rate shipping', $object->get_shipping_method() );

		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping 2', '10', array(), 'flat_rate' );
		$item   = new WC_Order_Item_Shipping();
		$item->set_props( array(
			'method_title' => $rate->label,
			'method_id'    => $rate->id,
			'total'        => wc_format_decimal( $rate->cost ),
			'taxes'        => $rate->taxes,
		) );
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );
		$object->save();
		$this->assertEquals( 'Flat rate shipping, Flat rate shipping 2', $object->get_shipping_method() );
	}

	/**
	 * Test: get_used_coupons
	 */
	function test_get_used_coupons() {
		$object = new WC_Order();
		$item   = new WC_Order_Item_Coupon();
		$item->set_props( array(
			'code'         => '12345',
			'discount'     => 10,
			'discount_tax' => 5,
		) );
		$object->add_item( $item );
		$object->save();
		$this->assertCount( 1, $object->get_used_coupons() );
	}

	/**
	 * Test: get_item_count
	 */
	function test_get_item_count() {
		$object  = new WC_Order();
		$item_1  = new WC_Order_Item_Product();
		$item_1->set_props( array(
			'product'  => WC_Helper_Product::create_simple_product(),
			'quantity' => 4,
		) );
		$item_2  = new WC_Order_Item_Product();
		$item_2->set_props( array(
			'product'  => WC_Helper_Product::create_simple_product(),
			'quantity' => 2,
		) );
		$object->add_item( $item_1 );
		$object->add_item( $item_2 );
		$object->save();
		$this->assertEquals( 6, $object->get_item_count() );
	}

	/**
	 * Test: get_item
	 */
	function test_get_item() {
		$object = new WC_Order();
		$item   = new WC_Order_Item_Product();
		$item->set_props( array(
			'product'  => WC_Helper_Product::create_simple_product(),
			'quantity' => 4,
		) );
		$item->save();
		$object->add_item( $item->get_id() );
		$object->save();
		$this->assertTrue( $object->get_item( $item->get_id() ) instanceOf WC_Order_Item_Product );

		$object = new WC_Order();
		$item   = new WC_Order_Item_Coupon();
		$item->set_props( array(
			'code'         => '12345',
			'discount'     => 10,
			'discount_tax' => 5,
		) );
		$item_id = $item->save();
		$object->add_item( $item );
		$object->save();
		$this->assertTrue( $object->get_item( $item_id ) instanceOf WC_Order_Item_Coupon );
	}

	/**
	 * Test: add_payment_token
	 */
	function test_add_payment_token() {
		$object  = new WC_Order();
		$object->save();
		$this->assertFalse( $object->add_payment_token( 'fish' ) );
		$token = new WC_Payment_Token_Stub();
		$token->set_extra( __FUNCTION__ );
		$token->set_token( time() );
		$token->save();
		$this->assertTrue( 0 < $object->add_payment_token( $token ) );
	}

	/**
	 * Test: get_payment_tokens
	 */
	function test_get_payment_tokens() {
		$object  = new WC_Order();
		$object->save();
		$token = new WC_Payment_Token_Stub();
		$token->set_extra( __FUNCTION__ );
		$token->set_token( time() );
		$token->save();
		$object->add_payment_token( $token );
		$this->assertCount( 1, $object->get_payment_tokens() );
	}

	/**
	 * Test: calculate_shipping
	 */
	function test_calculate_shipping() {
		$object = new WC_Order();
		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item_1 = new WC_Order_Item_Shipping();
		$item_1->set_props( array(
			'method_title' => $rate->label,
			'method_id'    => $rate->id,
			'total'        => wc_format_decimal( $rate->cost ),
			'taxes'        => $rate->taxes,
		) );
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item_1->add_meta_data( $key, $value, true );
		}
		$item_2 = new WC_Order_Item_Shipping();
		$item_2->set_props( array(
			'method_title' => $rate->label,
			'method_id'    => $rate->id,
			'total'        => wc_format_decimal( $rate->cost ),
			'taxes'        => $rate->taxes,
		) );
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item_1 );
		$object->add_item( $item_2 );
		$object->save();
		$object->calculate_shipping();
		$this->assertEquals( 20, $object->get_shipping_total() );
	}

	/**
	 * Test: calculate_taxes
	 */
	function test_calculate_taxes() {
		global $wpdb;
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$object = new WC_Order();
		$object->add_product( WC_Helper_Product::create_simple_product(), 4 );

		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item   = new WC_Order_Item_Shipping();
		$item->set_props( array(
			'method_title' => $rate->label,
			'method_id'    => $rate->id,
			'total'        => wc_format_decimal( $rate->cost ),
			'taxes'        => $rate->taxes,
		)  );
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );

		$object->calculate_taxes();
		$this->assertEquals( 5, $object->get_total_tax() );

		// Cleanup
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test: calculate_totals
	 */
	function test_calculate_totals() {
		global $wpdb;
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$object  = new WC_Order();
		$object->add_product( WC_Helper_Product::create_simple_product(), 4 );

		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item   = new WC_Order_Item_Shipping();
		$item->set_props( array(
			'method_title' => $rate->label,
			'method_id'    => $rate->id,
			'total'        => wc_format_decimal( $rate->cost ),
			'taxes'        => $rate->taxes,
		) );
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );

		$object->calculate_totals();
		$this->assertEquals( 55, $object->get_total() );

		// Cleanup
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test: has_status
	 */
	function test_has_status() {
		$object = new WC_Order();
		$this->assertFalse( $object->has_status( 'completed' ) );
		$this->assertFalse( $object->has_status( array( 'processing', 'completed' ) ) );
		$this->assertTrue( $object->has_status( 'pending' ) );
		$this->assertTrue( $object->has_status( array( 'processing', 'pending' ) ) );
	}

	/**
	 * Test: has_shipping_method
	 */
	function test_has_shipping_method() {
		$object = new WC_Order();
		$object->save();

		$this->assertFalse( $object->has_shipping_method( 'flat_rate_shipping' ) );

		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping:1', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item   = new WC_Order_Item_Shipping();
		$item->set_props( array(
			'method_title' => $rate->label,
			'method_id'    => $rate->id,
			'total'        => wc_format_decimal( $rate->cost ),
			'taxes'        => $rate->taxes,
		) );
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );
		$object->save();

		$this->assertTrue( $object->has_shipping_method( 'flat_rate_shipping' ) );
	}

	/**
	 * Test: key_is_valid
	 */
	function test_key_is_valid() {
		$object = new WC_Order();
		$object->save();
		$this->assertFalse( $object->key_is_valid( '1234' ) );
		$object->set_order_key( '1234' );
		$this->assertTrue( $object->key_is_valid( '1234' ) );
	}

	/**
	 * Test: has_free_item
	 */
	function test_has_free_item() {
		$object = new WC_Order();
		$object->add_product( WC_Helper_Product::create_simple_product(), 4 );
		$this->assertFalse( $object->has_free_item() );

		$free_product = WC_Helper_Product::create_simple_product();
		$free_product->set_price( 0 );
		$object->add_product( $free_product, 4 );
		$this->assertTrue( $object->has_free_item() );
	}

	/**
	 * Test: CRUD
	 */
	function test_CRUD() {
		$object = new WC_Order();

		// Save + create
		$save_id = $object->save();
		$post    = get_post( $save_id );
		$this->assertEquals( 'shop_order', $post->post_type );
		$this->assertEquals( 'shop_order', $post->post_type );

		// Update
		$update_id = $object->save();
		$this->assertEquals( $update_id, $save_id );

		// Delete
		$object->delete( true );
		$post = get_post( $save_id );
		$this->assertNull( $post );
	}

	/**
	 * Test: payment_complete
	 */
	function test_payment_complete() {
		$object = new WC_Order();
		$this->assertFalse( $object->payment_complete() );
		$object->save();
		$this->assertTrue( $object->payment_complete( '12345' ) );
		$this->assertEquals( 'completed', $object->get_status() );
		$this->assertEquals( '12345', $object->get_transaction_id() );
	}

	/**
	 * Test: get_formatted_order_total
	 */
	function test_get_formatted_order_total() {
		$object = new WC_Order();
		$object->set_total( 100 );
		$object->set_currency( 'USD' );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#36;</span>100.00</span>', $object->get_formatted_order_total() );
	}

	/**
	 * Test: set_status
	 */
	function test_set_status() {
		$object = new WC_Order();
		$object->set_status( 'on-hold' );
		$this->assertEquals( 'on-hold', $object->get_status() );
	}

	/**
	 * Test: update_status
	 */
	function test_update_status() {
		$object = new WC_Order();
		$this->assertFalse( $object->update_status( 'on-hold' ) );
		$object->save();
		$this->assertTrue( $object->update_status( 'on-hold' ) );
		$this->assertEquals( 'on-hold', $object->get_status() );
	}

	/**
	 * Test: get_billing_first_name
	 */
	function test_get_billing_first_name() {
		$object = new WC_Order();
		$set_to = 'Fred';
		$object->set_billing_first_name( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_first_name() );
	}

	/**
	 * Test: get_billing_last_name
	 */
	function test_get_billing_last_name() {
		$object = new WC_Order();
		$set_to = 'Flintstone';
		$object->set_billing_last_name( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_last_name() );
	}

	/**
	 * Test: get_billing_company
	 */
	function test_get_billing_company() {
		$object = new WC_Order();
		$set_to = 'Bedrock Ltd.';
		$object->set_billing_company( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_company() );
	}

	/**
	 * Test: get_billing_address_1
	 */
	function test_get_billing_address_1() {
		$object = new WC_Order();
		$set_to = '34 Stonepants avenue';
		$object->set_billing_address_1( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_address_1() );
	}

	/**
	 * Test: get_billing_address_2
	 */
	function test_get_billing_address_2() {
		$object = new WC_Order();
		$set_to = 'Rockville';
		$object->set_billing_address_2( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_address_2() );
	}

	/**
	 * Test: get_billing_city
	 */
	function test_get_billing_city() {
		$object = new WC_Order();
		$set_to = 'Bedrock';
		$object->set_billing_city( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_city() );
	}

	/**
	 * Test: get_billing_state
	 */
	function test_get_billing_state() {
		$object = new WC_Order();
		$set_to = 'Oregon';
		$object->set_billing_state( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_state() );
	}

	/**
	 * Test: get_billing_postcode
	 */
	function test_get_billing_postcode() {
		$object = new WC_Order();
		$set_to = '00001';
		$object->set_billing_postcode( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_postcode() );
	}

	/**
	 * Test: get_billing_country
	 */
	function test_get_billing_country() {
		$object = new WC_Order();
		$set_to = 'US';
		$object->set_billing_country( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_country() );
	}

	/**
	 * Test: get_billing_email
	 */
	function test_get_billing_email() {
		$object = new WC_Order();
		$set_to = 'test@test.com';
		$object->set_billing_email( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_email() );

		$set_to = 'not an email';
		$this->setExpectedException( 'WC_Data_Exception' );
		$object->set_billing_email( $set_to );
		$this->assertEquals( 'test@test.com', $object->get_billing_email() );
	}

	/**
	 * Test: get_billing_phone
	 */
	function test_get_billing_phone() {
		$object = new WC_Order();
		$set_to = '123456678';
		$object->set_billing_phone( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_phone() );
	}

	/**
	 * Test: Setting/getting billing settings after an order is saved
	 */
	function test_set_billing_after_save() {
		$object = new WC_Order();
		$phone = '123456678';
		$object->set_billing_phone( $phone );
		$object->save();
		$state = 'Oregon';
		$object->set_billing_state( $state );

		$this->assertEquals( $phone, $object->get_billing_phone() );
		$this->assertEquals( $state, $object->get_billing_state() );
	}

	/**
	 * Test: get_shipping_first_name
	 */
	function test_get_shipping_first_name() {
		$object = new WC_Order();
		$set_to = 'Fred';
		$object->set_shipping_first_name( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_first_name() );
	}

	/**
	 * Test: get_shipping_last_name
	 */
	function test_get_shipping_last_name() {
		$object = new WC_Order();
		$set_to = 'Flintstone';
		$object->set_shipping_last_name( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_last_name() );
	}

	/**
	 * Test: get_shipping_company
	 */
	function test_get_shipping_company() {
		$object = new WC_Order();
		$set_to = 'Bedrock Ltd.';
		$object->set_shipping_company( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_company() );
	}

	/**
	 * Test: get_shipping_address_1
	 */
	function test_get_shipping_address_1() {
		$object = new WC_Order();
		$set_to = '34 Stonepants avenue';
		$object->set_shipping_address_1( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_address_1() );
	}

	/**
	 * Test: get_shipping_address_2
	 */
	function test_get_shipping_address_2() {
		$object = new WC_Order();
		$set_to = 'Rockville';
		$object->set_shipping_address_2( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_address_2() );
	}

	/**
	 * Test: get_shipping_city
	 */
	function test_get_shipping_city() {
		$object = new WC_Order();
		$set_to = 'Bedrock';
		$object->set_shipping_city( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_city() );
	}

	/**
	 * Test: get_shipping_state
	 */
	function test_get_shipping_state() {
		$object = new WC_Order();
		$set_to = 'Oregon';
		$object->set_shipping_state( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_state() );
	}

	/**
	 * Test: get_shipping_postcode
	 */
	function test_get_shipping_postcode() {
		$object = new WC_Order();
		$set_to = '00001';
		$object->set_shipping_postcode( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_postcode() );
	}

	/**
	 * Test: get_shipping_country
	 */
	function test_get_shipping_country() {
		$object = new WC_Order();
		$set_to = 'US';
		$object->set_shipping_country( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_country() );
	}

	/**
	 * Test: Setting/getting shipping settings after an order is saved
	 */
	function test_set_shipping_after_save() {
		$object = new WC_Order();
		$country = 'US';
		$object->set_shipping_country( $country );
		$object->save();
		$state = 'Oregon';
		$object->set_shipping_state( $state );

		$this->assertEquals( $country, $object->get_shipping_country() );
		$this->assertEquals( $state, $object->get_shipping_state() );
	}

	/**
	 * Test: get_payment_method
	 */
	function test_get_payment_method() {
		$object = new WC_Order();
		$set_to = 'paypal';
		$object->set_payment_method( $set_to );
		$this->assertEquals( $set_to, $object->get_payment_method() );
	}

	/**
	 * Test: get_payment_method_title
	 */
	function test_get_payment_method_title() {
		$object = new WC_Order();
		$set_to = 'PayPal';
		$object->set_payment_method_title( $set_to );
		$this->assertEquals( $set_to, $object->get_payment_method_title() );
	}

	/**
	 * Test: get_transaction_id
	 */
	function test_get_transaction_id() {
		$object = new WC_Order();
		$set_to = '12345';
		$object->set_transaction_id( $set_to );
		$this->assertEquals( $set_to, $object->get_transaction_id() );
	}

	/**
	 * Test: get_customer_ip_address
	 */
	function test_get_customer_ip_address() {
		$object = new WC_Order();
		$set_to = '192.168.1.1';
		$object->set_customer_ip_address( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_ip_address() );
	}

	/**
	 * Test: get_customer_user_agent
	 */
	function test_get_customer_user_agent() {
		$object = new WC_Order();
		$set_to = 'UAstring';
		$object->set_customer_user_agent( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_user_agent() );
	}

	/**
	 * Test: get_created_via
	 */
	function test_get_created_via() {
		$object = new WC_Order();
		$set_to = 'WooCommerce';
		$object->set_created_via( $set_to );
		$this->assertEquals( $set_to, $object->get_created_via() );
	}

	/**
	 * Test: get_customer_note
	 */
	function test_get_customer_note() {
		$object = new WC_Order();
		$set_to = 'Leave on porch.';
		$object->set_customer_note( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_note() );
	}

	/**
	 * Test: get_date_completed
	 */
	function test_get_date_completed() {
		$object = new WC_Order();
		$object->set_date_completed( '2016-12-12' );
		$this->assertEquals( '1481500800', $object->get_date_completed()->getOffsetTimestamp() );

		$object->set_date_completed( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_completed()->getTimestamp() );
	}

	/**
	 * Test: get_date_paid
	 */
	function test_get_date_paid() {
		$object = new WC_Order();
		$set_to = 'PayPal';
		$object->set_date_paid( '2016-12-12' );
		$this->assertEquals( 1481500800, $object->get_date_paid()->getOffsetTimestamp() );

		$object->set_date_paid( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_paid()->getTimestamp() );
	}

	/**
	 * Test: get_cart_hash
	 */
	function test_get_cart_hash() {
		$object = new WC_Order();
		$set_to = '12345';
		$object->set_cart_hash( $set_to );
		$this->assertEquals( $set_to, $object->get_cart_hash() );
	}

	/**
	 * Test: get_address
	 */
	function test_get_address() {
		$object = new WC_Order();

		$billing = array(
			'first_name' => 'Fred',
			'last_name'  => 'Flintstone',
			'company'    => 'Bedrock Ltd.',
			'address_1'  => '34 Stonepants avenue',
			'address_2'  => 'Rockville',
			'city'       => 'Bedrock',
			'state'      => 'Boulder',
			'postcode'   => '00001',
			'country'    => 'US',
			'email'      => '',
			'phone'      => '',
		);

		$shipping = array(
			'first_name' => 'Barney',
			'last_name'  => 'Rubble',
			'company'    => 'Bedrock Ltd.',
			'address_1'  => '34 Stonepants avenue',
			'address_2'  => 'Rockville',
			'city'       => 'Bedrock',
			'state'      => 'Boulder',
			'postcode'   => '00001',
			'country'    => 'US',
		);

		$object->set_billing_first_name( 'Fred' );
		$object->set_billing_last_name( 'Flintstone' );
		$object->set_billing_company( 'Bedrock Ltd.' );
		$object->set_billing_address_1( '34 Stonepants avenue' );
		$object->set_billing_address_2( 'Rockville' );
		$object->set_billing_city( 'Bedrock' );
		$object->set_billing_state( 'Boulder' );
		$object->set_billing_postcode( '00001' );
		$object->set_billing_country( 'US' );

		$object->set_shipping_first_name( 'Barney' );
		$object->set_shipping_last_name( 'Rubble' );
		$object->set_shipping_company( 'Bedrock Ltd.' );
		$object->set_shipping_address_1( '34 Stonepants avenue' );
		$object->set_shipping_address_2( 'Rockville' );
		$object->set_shipping_city( 'Bedrock' );
		$object->set_shipping_state( 'Boulder' );
		$object->set_shipping_postcode( '00001' );
		$object->set_shipping_country( 'US' );

		$this->assertEquals( $billing, $object->get_address() );
		$this->assertEquals( $shipping, $object->get_address( 'shipping' ) );
	}

	/**
	 * Test: get_shipping_address_map_url
	 */
	function test_get_shipping_address_map_url() {
		$object = new WC_Order();
		$object->set_shipping_first_name( 'Barney' );
		$object->set_shipping_last_name( 'Rubble' );
		$object->set_shipping_company( 'Bedrock Ltd.' );
		$object->set_shipping_address_1( '34 Stonepants avenue' );
		$object->set_shipping_address_2( 'Rockville' );
		$object->set_shipping_city( 'Bedrock' );
		$object->set_shipping_state( 'Boulder' );
		$object->set_shipping_postcode( '00001' );
		$object->set_shipping_country( 'US' );
		$this->assertEquals( 'https://maps.google.com/maps?&q=Barney%2C+Rubble%2C+Bedrock+Ltd.%2C+34+Stonepants+avenue%2C+Rockville%2C+Bedrock%2C+Boulder%2C+00001%2C+US&z=16', $object->get_shipping_address_map_url() );
	}

	/**
	 * Test: get_formatted_billing_full_name
	 */
	function test_get_formatted_billing_full_name() {
		$object = new WC_Order();
		$object->set_billing_first_name( 'Fred' );
		$object->set_billing_last_name( 'Flintstone' );
		$this->assertEquals( 'Fred Flintstone', $object->get_formatted_billing_full_name() );
	}

	/**
	 * Test: get_formatted_shipping_full_name
	 */
	function test_get_formatted_shipping_full_name() {
		$object = new WC_Order();
		$object->set_shipping_first_name( 'Barney' );
		$object->set_shipping_last_name( 'Rubble' );
		$this->assertEquals( 'Barney Rubble', $object->get_formatted_shipping_full_name() );
	}

	/**
	 * Test: get_formatted_billing_address
	 */
	function test_get_formatted_billing_address() {
		$object = new WC_Order();
		$object->set_billing_first_name( 'Fred' );
		$object->set_billing_last_name( 'Flintstone' );
		$object->set_billing_company( 'Bedrock Ltd.' );
		$object->set_billing_address_1( '34 Stonepants avenue' );
		$object->set_billing_address_2( 'Rockville' );
		$object->set_billing_city( 'Bedrock' );
		$object->set_billing_state( 'Boulder' );
		$object->set_billing_postcode( '00001' );
		$object->set_billing_country( 'US' );
		$this->assertEquals( 'Fred Flintstone<br/>Bedrock Ltd.<br/>34 Stonepants avenue<br/>Rockville<br/>Bedrock, BOULDER 00001<br/>United States (US)', $object->get_formatted_billing_address() );
	}

	/**
	 * Test: get_formatted_shipping_address
	 */
	function test_get_formatted_shipping_address() {
		$object = new WC_Order();
		$object->set_shipping_first_name( 'Barney' );
		$object->set_shipping_last_name( 'Rubble' );
		$object->set_shipping_company( 'Bedrock Ltd.' );
		$object->set_shipping_address_1( '34 Stonepants avenue' );
		$object->set_shipping_address_2( 'Rockville' );
		$object->set_shipping_city( 'Bedrock' );
		$object->set_shipping_state( 'Boulder' );
		$object->set_shipping_postcode( '00001' );
		$object->set_shipping_country( 'US' );
		$this->assertEquals( 'Barney Rubble<br/>Bedrock Ltd.<br/>34 Stonepants avenue<br/>Rockville<br/>Bedrock, BOULDER 00001<br/>United States (US)', $object->get_formatted_shipping_address() );
	}

	/**
	 * Test: has_cart_hash
	 */
	function test_has_cart_hash() {
		$object = new WC_Order();
		$this->assertFalse( $object->has_cart_hash( '12345' ) );
		$set_to = '12345';
		$object->set_cart_hash( $set_to );
		$this->assertTrue( $object->has_cart_hash( '12345' ) );
	}

	/**
	 * Test: is_editable
	 */
	function test_is_editable() {
		$object = new WC_Order();
		$object->set_status( 'pending' );
		$this->assertTrue( $object->is_editable() );
		$object->set_status( 'processing' );
		$this->assertFalse( $object->is_editable() );
	}

	/**
	 * Test: is_paid
	 */
	function test_is_paid() {
		$object = new WC_Order();
		$object->set_status( 'pending' );
		$this->assertFalse( $object->is_paid() );
		$object->set_status( 'processing' );
		$this->assertTrue( $object->is_paid() );
	}

	/**
	 * Test: is_download_permitted
	 */
	function test_is_download_permitted() {
		$object = new WC_Order();
		$object->set_status( 'pending' );
		$this->assertFalse( $object->is_download_permitted() );
		$object->set_status( 'completed' );
		$this->assertTrue( $object->is_download_permitted() );
	}

	/**
	 * Test: needs_shipping_address
	 */
	function test_needs_shipping_address() {
		$object = new WC_Order();
		$this->assertFalse( $object->needs_shipping_address() );

		$object = WC_Helper_Order::create_order();
		$this->assertTrue( $object->needs_shipping_address() );
	}

	/**
	 * Test: has_downloadable_item
	 */
	function test_has_downloadable_item() {
		$object = new WC_Order();
		$this->assertFalse( $object->has_downloadable_item() );

		$object = WC_Helper_Order::create_order();
		$this->assertFalse( $object->has_downloadable_item() );
	}

	/**
	 * Test: needs_payment
	 */
	function test_needs_payment() {
		$object = new WC_Order();

		$object->set_status( 'pending' );
		$this->assertFalse( $object->needs_payment() );

		$object->set_total( 100 );
		$this->assertTrue( $object->needs_payment() );

		$object->set_status( 'processing' );
		$this->assertFalse( $object->needs_payment() );
	}

	/**
	 * Test: get_checkout_payment_url
	 */
	function test_get_checkout_payment_url() {
		$object = new WC_Order();
		$id     = $object->save();
		$this->assertEquals( 'http://example.org?order-pay=' . $id . '&pay_for_order=true&key=' . $object->get_order_key(), $object->get_checkout_payment_url() );
	}

	/**
	 * Test: get_checkout_order_received_url
	 */
	function test_get_checkout_order_received_url() {
		$object = new WC_Order();
		$object->set_order_key( 'xxx' );
		$id     = $object->save();
		$this->assertEquals( 'http://example.org?order-received=' . $id . '&key=' . $object->get_order_key(), $object->get_checkout_order_received_url() );
	}

	/**
	 * Test: get_cancel_order_url
	 */
	function test_get_cancel_order_url() {
		$object = new WC_Order();
		$this->assertInternalType( 'string', $object->get_cancel_order_url() );
	}

	/**
	 * Test: get_cancel_order_url_raw
	 */
	function test_get_cancel_order_url_raw() {
		$object = new WC_Order();
		$this->assertInternalType( 'string', $object->get_cancel_order_url_raw() );
	}

	/**
	 * Test: get_cancel_endpoint
	 */
	function test_get_cancel_endpoint() {
		$object = new WC_Order();
		$this->assertEquals( 'http://example.org/', $object->get_cancel_endpoint() );
	}

	/**
	 * Test: get_view_order_url
	 */
	function test_get_view_order_url() {
		$object = new WC_Order();
		$id     = $object->save();
		$this->assertEquals( 'http://example.org?view-order=' . $id, $object->get_view_order_url() );
	}

	/**
	 * Test: add_order_note
	 */
	function test_add_order_note() {
		$object     = new WC_Order();
		$id         = $object->save();
		$comment_id = $object->add_order_note( "Hello, I am a fish" );
		$this->assertTrue( $comment_id > 0 );

		$comment = get_comment( $comment_id );
		$this->assertEquals( "Hello, I am a fish", $comment->comment_content );
	}

	/**
	 * Test: get_customer_order_notes
	 */
	function test_get_customer_order_notes() {
		$object     = new WC_Order();
		$id         = $object->save();

		$this->assertCount( 0, $object->get_customer_order_notes() );

		$object->add_order_note( "Hello, I am a fish", true );
		$object->add_order_note( "Hello, I am a fish", false );
		$object->add_order_note( "Hello, I am a fish", true );

		$this->assertCount( 2, $object->get_customer_order_notes() );
	}

	/**
	 * Test: get_refunds
	 */
	function test_get_refunds() {
		$object     = new WC_Order();
		$object->set_total( 100 );
		$id         = $object->save();

		$this->assertCount( 0, $object->get_refunds() );

		wc_create_refund( array(
			'order_id'   => $id,
			'amount'     => '100',
			'line_items' => array(),
		) );

		$this->assertCount( 1, $object->get_refunds() );
	}

	/**
	 * Test: get_total_refunded
	 */
	function test_get_total_refunded() {
		$object = new WC_Order();
		$object->set_total( 400 );
		$id     = $object->save();
		wc_create_refund( array(
			'order_id'   => $id,
			'amount'     => '100',
			'line_items' => array(),
		) );
		wc_create_refund( array(
			'order_id'   => $id,
			'amount'     => '100',
			'line_items' => array(),
		) );
		$this->assertEquals( 200, $object->get_total_refunded() );
	}

	/**
	 * Test: get_total_tax_refunded
	 */
	function test_get_total_tax_refunded() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_tax_refunded() );
	}

	/**
	 * Test: get_total_shipping_refunded
	 */
	function test_get_total_shipping_refunded() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_shipping_refunded() );
	}

	/**
	 * Test: get_total_shipping_refunded
	 */
	function test_get_total_qty_refunded() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_shipping_refunded() );
	}

	/**
	 * Test: get_qty_refunded_for_item
	 */
	function test_get_qty_refunded_for_item() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_qty_refunded_for_item( 2 ) );
	}

	/**
	 * Test: test_get_total_refunded_for_item
	 */
	function test_get_total_refunded_for_item() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_refunded_for_item( 2 ) );
	}

	/**
	 * Test: get_tax_refunded_for_item
	 */
	function test_get_tax_refunded_for_item() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_tax_refunded_for_item( 1, 1 ) );
	}

	/**
	 * Test: get_total_tax_refunded_by_rate_id
	 */
	function test_get_total_tax_refunded_by_rate_id() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_tax_refunded_by_rate_id( 2 ) );
	}

	/**
	 * Test: get_remaining_refund_amount
	 */
	function test_get_remaining_refund_amount() {
		$object = new WC_Order();
		$object->set_total( 400 );
		$id     = $object->save();
		wc_create_refund( array(
			'order_id'   => $id,
			'amount'     => '100',
			'line_items' => array(),
		) );
		$this->assertEquals( 300, $object->get_remaining_refund_amount() );
	}

	/**
	 * Test: get_total_tax_refunded_by_rate_id
	 */
	function test_get_remaining_refund_items() {
		$object = WC_Helper_Order::create_order();
		$this->assertEquals( 4, $object->get_remaining_refund_items() );
	}
}
