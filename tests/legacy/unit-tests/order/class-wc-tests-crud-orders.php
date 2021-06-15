<?php
/**
 * Class WC_Tests_CRUD_Orders file.
 *
 * @package WooCommerce\Tests\CRUD
 */

/**
 * Meta
 *
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_CRUD_Orders extends WC_Unit_Test_Case {

	/**
	 * Test: get_type
	 */
	public function test_get_type() {
		$object = new WC_Order();
		$this->assertEquals( 'shop_order', $object->get_type() );
	}

	/**
	 * Test: get_data
	 */
	public function test_get_data() {
		$object = new WC_Order();
		$this->assertInternalType( 'array', $object->get_data() );
	}

	/**
	 * Test: get_id
	 */
	public function test_get_id() {
		$object = new WC_Order();
		$id     = $object->save();
		$this->assertEquals( $id, $object->get_id() );
	}

	/**
	 * Test: get_parent_id
	 */
	public function test_get_parent_id() {
		$object1   = new WC_Order();
		$parent_id = $object1->save();
		$object    = new WC_Order();
		$object->set_parent_id( $parent_id );
		$this->assertEquals( $parent_id, $object->get_parent_id() );
	}

	/**
	 * Test: get_order_number
	 */
	public function test_get_order_number() {
		$object = new WC_Order();
		$id     = $object->save();
		$this->assertEquals( $id, $object->get_order_number() );
	}

	/**
	 * Test: get_order_key
	 */
	public function test_get_order_key() {
		$object = new WC_Order();
		$set_to = 'some_key';
		$object->set_order_key( $set_to );
		$this->assertEquals( $set_to, $object->get_order_key() );
	}

	/**
	 * Test: get_currency
	 */
	public function test_get_currency() {
		$object = new WC_Order();
		$set_to = 'USD';
		$object->set_currency( $set_to );
		$this->assertEquals( $set_to, $object->get_currency() );
	}

	/**
	 * Test: get_version
	 */
	public function test_get_version() {
		$object = new WC_Order();
		$set_to = '3.0.0';
		$object->set_version( $set_to );
		$this->assertEquals( $set_to, $object->get_version() );
	}

	/**
	 * Test: get_prices_include_tax
	 */
	public function test_get_prices_include_tax() {
		$object = new WC_Order();
		$object->set_prices_include_tax( 1 );
		$this->assertTrue( $object->get_prices_include_tax() );
	}

	/**
	 * Test: get_date_created
	 */
	public function test_get_date_created() {
		$object = new WC_Order();
		$object->set_date_created( '2016-12-12' );
		$this->assertEquals( '1481500800', $object->get_date_created()->getOffsetTimestamp() );

		$object->set_date_created( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_created()->getTimestamp() );
	}

	/**
	 * Test: get_date_modified
	 */
	public function test_get_date_modified() {
		$object = new WC_Order();
		$object->set_date_modified( '2016-12-12' );
		$this->assertEquals( '1481500800', $object->get_date_modified()->getOffsetTimestamp() );

		$object->set_date_modified( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_modified()->getTimestamp() );
	}

	/**
	 * Test: get_customer_id
	 */
	public function test_get_customer_id() {
		$object = new WC_Order();
		$set_to = 10;
		$object->set_customer_id( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_id() );
	}

	/**
	 * Test: get_user
	 */
	public function test_get_user() {
		$object = new WC_Order();
		$this->assertFalse( $object->get_user() );
		$set_to = '1';
		$object->set_customer_id( $set_to );
		$this->assertInstanceOf( 'WP_User', $object->get_user() );
	}

	/**
	 * Test: get_discount_total
	 */
	public function test_get_discount_total() {
		$object = new WC_Order();
		$object->set_discount_total( 50 );
		$this->assertEquals( 50, $object->get_discount_total() );
	}

	/**
	 * Test: get_discount_tax
	 */
	public function test_get_discount_tax() {
		$object = new WC_Order();
		$object->set_discount_tax( 5 );
		$this->assertEquals( 5, $object->get_discount_tax() );
	}

	/**
	 * Test: get_shipping_total
	 */
	public function test_get_shipping_total() {
		$object = new WC_Order();
		$object->set_shipping_total( 5 );
		$this->assertEquals( 5, $object->get_shipping_total() );
	}

	/**
	 * Test: get_shipping_tax
	 */
	public function test_get_shipping_tax() {
		$object = new WC_Order();
		$object->set_shipping_tax( 5 );
		$this->assertEquals( 5, $object->get_shipping_tax() );
	}

	/**
	 * Test: get_cart_tax
	 */
	public function test_get_cart_tax() {
		$object = new WC_Order();
		$object->set_cart_tax( 5 );
		$this->assertEquals( 5, $object->get_cart_tax() );
	}

	/**
	 * Test: get_total
	 */
	public function test_get_total() {
		$object = new WC_Order();
		$object->set_total( 5 );
		$this->assertEquals( 5, $object->get_total() );
	}

	/**
	 * Test: get_total_tax
	 */
	public function test_get_total_tax() {
		$object = new WC_Order();
		$object->set_cart_tax( 5 );
		$object->set_shipping_tax( 5 );
		$this->assertEquals( 10, $object->get_total_tax() );
	}

	/**
	 * Test: get_total_discount
	 */
	public function test_get_total_discount() {
		$object = new WC_Order();
		$object->set_discount_total( 50 );
		$object->set_discount_tax( 5 );
		$this->assertEquals( 50, $object->get_total_discount() );
		$this->assertEquals( 55, $object->get_total_discount( false ) );
	}

	/**
	 * Test: get_subtotal
	 */
	public function test_get_subtotal() {
		$object = WC_Helper_Order::create_order();
		$this->assertEquals( 40, $object->get_subtotal() );
	}

	/**
	 * Test: get_tax_totals
	 */
	public function test_get_tax_totals() {
		$object = WC_Helper_Order::create_order();
		$this->assertEquals( array(), $object->get_tax_totals() );
	}

	/**
	 * Test: remove_order_items
	 */
	public function test_remove_order_items() {
		$product = WC_Helper_Product::create_simple_product();
		$object  = new WC_Order();
		$item_1  = new WC_Order_Item_Product();
		$item_1->set_props(
			array(
				'product'  => $product,
				'quantity' => 4,
			)
		);
		$item_2 = new WC_Order_Item_Product();
		$item_2->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
			)
		);
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
	public function test_get_items() {
		$object = new WC_Order();
		$item_1 = new WC_Order_Item_Product();
		$item_1->set_props(
			array(
				'product'  => WC_Helper_Product::create_simple_product(),
				'quantity' => 4,
			)
		);
		$item_2 = new WC_Order_Item_Product();
		$item_2->set_props(
			array(
				'product'  => WC_Helper_Product::create_simple_product(),
				'quantity' => 2,
			)
		);

		$object->add_item( $item_1 );
		$object->add_item( $item_2 );
		$object->save();
		$this->assertCount( 2, $object->get_items() );
	}

	/**
	 * Test: get_different_items
	 */
	public function test_get_different_items() {
		$object = new WC_Order();
		$item_1 = new WC_Order_Item_Product();
		$item_1->set_props(
			array(
				'product'  => WC_Helper_Product::create_simple_product(),
				'quantity' => 4,
			)
		);
		$item_2 = new WC_Order_Item_Fee();
		$item_2->set_props(
			array(
				'name'       => 'Some Fee',
				'tax_status' => 'taxable',
				'total'      => '100',
				'tax_class'  => '',
			)
		);
		$object->add_item( $item_1 );
		$object->add_item( $item_2 );
		$this->assertCount( 2, $object->get_items( array( 'line_item', 'fee' ) ) );
	}

	/**
	 * Test: get_coupons
	 */
	public function test_get_coupons() {
		$object = new WC_Order();
		$item   = new WC_Order_Item_Coupon();
		$item->set_props(
			array(
				'code'         => '12345',
				'discount'     => 10,
				'discount_tax' => 5,
			)
		);
		$object->add_item( $item );
		$object->save();
		$this->assertCount( 1, $object->get_coupons() );
	}

	/**
	 * Test: get_fees
	 */
	public function test_get_fees() {
		$object = new WC_Order();
		$item   = new WC_Order_Item_Fee();
		$item->set_props(
			array(
				'name'       => 'Some Fee',
				'tax_status' => 'taxable',
				'total'      => '100',
				'tax_class'  => '',
			)
		);
		$object->add_item( $item );
		$this->assertCount( 1, $object->get_fees() );
	}

	/**
	 * Test: get_taxes
	 */
	public function test_get_taxes() {
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
		$item_1 = new WC_Order_Item_Product();
		$item_1->set_props(
			array(
				'product'  => WC_Helper_Product::create_simple_product(),
				'quantity' => 4,
			)
		);
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
	}

	/**
	 * Test mapping from old tax array keys to CRUD functions.
	 */
	public function test_tax_legacy_arrayaccess() {
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
	public function test_get_shipping_methods() {
		$object = new WC_Order();
		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item   = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
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
	public function test_get_shipping_method() {
		$object = new WC_Order();
		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item   = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );
		$object->save();
		$this->assertEquals( 'Flat rate shipping', $object->get_shipping_method() );

		$rate = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping 2', '10', array(), 'flat_rate' );
		$item = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );
		$object->save();
		$this->assertEquals( 'Flat rate shipping, Flat rate shipping 2', $object->get_shipping_method() );
	}

	/**
	 * Test: get_coupon_codes
	 */
	public function test_get_coupon_codes() {
		$object = new WC_Order();
		$item   = new WC_Order_Item_Coupon();
		$item->set_props(
			array(
				'code'         => '12345',
				'discount'     => 10,
				'discount_tax' => 5,
			)
		);
		$object->add_item( $item );
		$object->save();
		$this->assertCount( 1, $object->get_coupon_codes() );
	}

	/**
	 * Test: get_item_count
	 */
	public function test_get_item_count() {
		$object = new WC_Order();
		$item_1 = new WC_Order_Item_Product();
		$item_1->set_props(
			array(
				'product'  => WC_Helper_Product::create_simple_product(),
				'quantity' => 4,
			)
		);
		$item_2 = new WC_Order_Item_Product();
		$item_2->set_props(
			array(
				'product'  => WC_Helper_Product::create_simple_product(),
				'quantity' => 2,
			)
		);
		$object->add_item( $item_1 );
		$object->add_item( $item_2 );
		$object->save();
		$this->assertEquals( 6, $object->get_item_count() );
	}

	/**
	 * Test: get_item
	 */
	public function test_get_item() {
		$object = new WC_Order();
		$item   = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => WC_Helper_Product::create_simple_product(),
				'quantity' => 4,
			)
		);
		$item->save();
		$object->add_item( $item->get_id() );
		$object->save();
		$this->assertTrue( $object->get_item( $item->get_id() ) instanceof WC_Order_Item_Product );

		$object = new WC_Order();
		$item   = new WC_Order_Item_Coupon();
		$item->set_props(
			array(
				'code'         => '12345',
				'discount'     => 10,
				'discount_tax' => 5,
			)
		);
		$item_id = $item->save();
		$object->add_item( $item );
		$object->save();
		$this->assertTrue( $object->get_item( $item_id ) instanceof WC_Order_Item_Coupon );
	}

	/**
	 * Test: add_payment_token
	 */
	public function test_add_payment_token() {
		$object = new WC_Order();
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
	public function test_get_payment_tokens() {
		$object = new WC_Order();
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
	public function test_calculate_shipping() {
		$object = new WC_Order();
		$rate   = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item_1 = new WC_Order_Item_Shipping();
		$item_1->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item_1->add_meta_data( $key, $value, true );
		}
		$item_2 = new WC_Order_Item_Shipping();
		$item_2->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
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
	public function test_calculate_taxes() {
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

		$rate = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );

		$object->calculate_taxes();
		$this->assertEquals( 5, $object->get_total_tax() );
	}

	/**
	 * Test: calculate_taxes_is_vat_excempt
	 */
	public function test_calculate_taxes_is_vat_excempt() {
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
		$rate = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );

		$object->calculate_taxes();
		$this->assertEquals( 5, $object->get_total_tax() );

		// Add VAT except meta.
		$object->add_meta_data( 'is_vat_exempt', 'yes', true );
		$object->save();
		$object->calculate_taxes();
		$this->assertEquals( 0, $object->get_total_tax() );
	}

	/**
	 * Test: calculate_taxes_issue_with_addresses
	 */
	public function test_calculate_taxes_issue_with_addresses() {
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$taxes = array();

		$taxes[] = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'TAX',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);
		$taxes[] = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'PY',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'TAX',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		update_option( 'woocommerce_default_country', 'PY:Central' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		$order = new WC_Order();
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CA' );
		$order->add_product( WC_Helper_Product::create_simple_product(), 4 );
		$order->calculate_taxes();

		$tax = $order->get_taxes();
		$this->assertEquals( 1, count( $tax ) );
		$this->assertEquals( 'US-TAX-1', current( $tax )->get_name() );
	}

	/**
	 * Test: calculate_totals
	 */
	public function test_calculate_totals() {
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

		$rate = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}
		$object->add_item( $item );

		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'total' => 10,
			)
		);
		$object->add_item( $fee );

		$object->calculate_totals();
		$this->assertEquals( 66, $object->get_total() );
	}

	/**
	 * Test: calculate_totals negative fees should not make order total negative.
	 *
	 * See: https://github.com/woocommerce/woocommerce/commit/804feb93333a8f00d0f93a163c6de58204f31f14
	 */
	public function test_calculate_totals_negative_fees_should_not_make_order_total_negative() {
		$order = WC_Helper_Order::create_order();

		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'total' => -60,
			)
		);
		$order->add_item( $fee );

		$order->calculate_totals();
		$this->assertEquals( 0, $order->get_total() );
	}

	/**
	 * Test: has_status
	 */
	public function test_has_status() {
		$object = new WC_Order();
		$this->assertFalse( $object->has_status( 'completed' ) );
		$this->assertFalse( $object->has_status( array( 'processing', 'completed' ) ) );
		$this->assertTrue( $object->has_status( 'pending' ) );
		$this->assertTrue( $object->has_status( array( 'processing', 'pending' ) ) );
	}

	/**
	 * Test: has_shipping_method
	 */
	public function test_has_shipping_method() {
		$object = new WC_Order();
		$object->save();

		$this->assertFalse( $object->has_shipping_method( 'flat_rate_shipping' ) );

		$rate = new WC_Shipping_Rate( 'flat_rate_shipping:1', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
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
	public function test_key_is_valid() {
		$object = new WC_Order();
		$object->save();
		$this->assertFalse( $object->key_is_valid( '1234' ) );
		$object->set_order_key( '1234' );
		$this->assertTrue( $object->key_is_valid( '1234' ) );
	}

	/**
	 * Test: has_free_item
	 */
	public function test_has_free_item() {
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
	public function test_CRUD() {
		$object = new WC_Order();

		// Save + create.
		$save_id = $object->save();
		$post    = get_post( $save_id );
		$this->assertEquals( 'shop_order', $post->post_type );
		$this->assertEquals( 'shop_order', $post->post_type );

		// Update.
		$update_id = $object->save();
		$this->assertEquals( $update_id, $save_id );

		// Delete.
		$object->delete( true );
		$post = get_post( $save_id );
		$this->assertNull( $post );
	}

	/**
	 * Test: payment_complete
	 */
	public function test_payment_complete() {
		$object = new WC_Order();
		$this->assertFalse( $object->payment_complete() );
		$object->save();
		$this->assertTrue( $object->payment_complete( '12345' ) );
		$this->assertEquals( 'completed', $object->get_status() );
		$this->assertEquals( '12345', $object->get_transaction_id() );
	}

	/**
	 * Test that exceptions thrown during payment_complete are handled.
	 * Note: This can't actually test the transaction rollbacks since WC transactions are disabled in unit tests.
	 *
	 * @since 3.3.0
	 */
	public function test_payment_complete_error() {
		$object = new WC_Order();
		$object->save();

		add_action( 'woocommerce_payment_complete', array( $this, 'throwAnException' ) );

		$this->assertFalse( $object->payment_complete( '12345' ) );
		$note = current(
			wc_get_order_notes(
				array(
					'order_id' => $object->get_id(),
				)
			)
		);
		$this->assertContains( 'Payment complete event failed', $note->content );

		remove_action( 'woocommerce_payment_complete', array( $this, 'throwAnException' ) );
	}

	/**
	 * Test: get_formatted_order_total
	 */
	public function test_get_formatted_order_total() {
		$object = new WC_Order();
		$object->set_total( 100 );
		$object->set_currency( 'USD' );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">&#36;</span>100.00</bdi></span>', $object->get_formatted_order_total() );
	}

	/**
	 * Test: set_status
	 */
	public function test_set_status() {
		$object = new WC_Order();
		$object->set_status( 'on-hold' );
		$this->assertEquals( 'on-hold', $object->get_status() );
	}

	/**
	 * Test: update_status
	 */
	public function test_update_status() {
		$object = new WC_Order();
		$this->assertFalse( $object->update_status( 'on-hold' ) );
		$object->save();
		$this->assertTrue( $object->update_status( 'on-hold' ) );
		$this->assertEquals( 'on-hold', $object->get_status() );
	}

	/**
	 * Test that exceptions thrown during update_status are handled.
	 * Note: This can't actually test the transaction rollbacks since WC transactions are disabled in unit tests.
	 *
	 * @since 3.3.0
	 */
	public function test_update_status_error() {
		$object = new WC_Order();
		$object->save();

		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'throwAnException' ) );

		$this->assertFalse( $object->update_status( 'on-hold' ) );
		$note = current(
			wc_get_order_notes(
				array(
					'order_id' => $object->get_id(),
				)
			)
		);
		$this->assertContains( 'Update status event failed', $note->content );

		remove_filter( 'woocommerce_payment_complete_order_status', array( $this, 'throwAnException' ) );
	}

	/**
	 * Test: status_transition
	 */
	public function test_status_transition_handles_transition_errors() {
		$object = new WC_Order();
		$object->save();

		add_filter( 'woocommerce_order_status_on-hold', array( $this, 'throwAnException' ) );
		$object->update_status( 'on-hold' );
		remove_filter( 'woocommerce_order_status_on-hold', array( $this, 'throwAnException' ) );

		$note = current(
			wc_get_order_notes(
				array(
					'order_id' => $object->get_id(),
				)
			)
		);

		$this->assertContains( __( 'Error during status transition.', 'woocommerce' ), $note->content );
	}

	/**
	 * Test: get_billing_first_name
	 */
	public function test_get_billing_first_name() {
		$object = new WC_Order();
		$set_to = 'Fred';
		$object->set_billing_first_name( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_first_name() );
	}

	/**
	 * Test: get_billing_last_name
	 */
	public function test_get_billing_last_name() {
		$object = new WC_Order();
		$set_to = 'Flintstone';
		$object->set_billing_last_name( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_last_name() );
	}

	/**
	 * Test: get_billing_company
	 */
	public function test_get_billing_company() {
		$object = new WC_Order();
		$set_to = 'Bedrock Ltd.';
		$object->set_billing_company( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_company() );
	}

	/**
	 * Test: get_billing_address_1
	 */
	public function test_get_billing_address_1() {
		$object = new WC_Order();
		$set_to = '34 Stonepants avenue';
		$object->set_billing_address_1( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_address_1() );
	}

	/**
	 * Test: get_billing_address_2
	 */
	public function test_get_billing_address_2() {
		$object = new WC_Order();
		$set_to = 'Rockville';
		$object->set_billing_address_2( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_address_2() );
	}

	/**
	 * Test: get_billing_city
	 */
	public function test_get_billing_city() {
		$object = new WC_Order();
		$set_to = 'Bedrock';
		$object->set_billing_city( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_city() );
	}

	/**
	 * Test: get_billing_state
	 */
	public function test_get_billing_state() {
		$object = new WC_Order();
		$set_to = 'Oregon';
		$object->set_billing_state( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_state() );
	}

	/**
	 * Test: get_billing_postcode
	 */
	public function test_get_billing_postcode() {
		$object = new WC_Order();
		$set_to = '00001';
		$object->set_billing_postcode( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_postcode() );
	}

	/**
	 * Test: get_billing_country
	 */
	public function test_get_billing_country() {
		$object = new WC_Order();
		$set_to = 'US';
		$object->set_billing_country( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_country() );
	}

	/**
	 * Test: get_billing_email
	 */
	public function test_get_billing_email() {
		$object = new WC_Order();
		$set_to = 'test@test.com';
		$object->set_billing_email( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_email() );

		$set_to = 'not an email';
		$this->setExpectedException( 'WC_Data_Exception', 'Invalid billing email address' );
		$object->set_billing_email( $set_to );
		$this->assertEquals( 'test@test.com', $object->get_billing_email() );
	}

	/**
	 * Test: get_billing_phone
	 */
	public function test_get_billing_phone() {
		$object = new WC_Order();
		$set_to = '123456678';
		$object->set_billing_phone( $set_to );
		$this->assertEquals( $set_to, $object->get_billing_phone() );
	}

	/**
	 * Test: Setting/getting billing settings after an order is saved
	 */
	public function test_set_billing_after_save() {
		$object = new WC_Order();
		$phone  = '123456678';
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
	public function test_get_shipping_first_name() {
		$object = new WC_Order();
		$set_to = 'Fred';
		$object->set_shipping_first_name( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_first_name() );
	}

	/**
	 * Test: get_shipping_last_name
	 */
	public function test_get_shipping_last_name() {
		$object = new WC_Order();
		$set_to = 'Flintstone';
		$object->set_shipping_last_name( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_last_name() );
	}

	/**
	 * Test: get_shipping_company
	 */
	public function test_get_shipping_company() {
		$object = new WC_Order();
		$set_to = 'Bedrock Ltd.';
		$object->set_shipping_company( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_company() );
	}

	/**
	 * Test: get_shipping_address_1
	 */
	public function test_get_shipping_address_1() {
		$object = new WC_Order();
		$set_to = '34 Stonepants avenue';
		$object->set_shipping_address_1( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_address_1() );
	}

	/**
	 * Test: get_shipping_address_2
	 */
	public function test_get_shipping_address_2() {
		$object = new WC_Order();
		$set_to = 'Rockville';
		$object->set_shipping_address_2( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_address_2() );
	}

	/**
	 * Test: get_shipping_city
	 */
	public function test_get_shipping_city() {
		$object = new WC_Order();
		$set_to = 'Bedrock';
		$object->set_shipping_city( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_city() );
	}

	/**
	 * Test: get_shipping_state
	 */
	public function test_get_shipping_state() {
		$object = new WC_Order();
		$set_to = 'Oregon';
		$object->set_shipping_state( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_state() );
	}

	/**
	 * Test: get_shipping_postcode
	 */
	public function test_get_shipping_postcode() {
		$object = new WC_Order();
		$set_to = '00001';
		$object->set_shipping_postcode( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_postcode() );
	}

	/**
	 * Test: get_shipping_country
	 */
	public function test_get_shipping_country() {
		$object = new WC_Order();
		$set_to = 'US';
		$object->set_shipping_country( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_country() );
	}

	/**
	 * Test: get_shipping_phone
	 */
	public function test_get_shipping_phone() {
		$object = new WC_Order();
		$set_to = '123456678';
		$object->set_shipping_phone( $set_to );
		$this->assertEquals( $set_to, $object->get_shipping_phone() );
	}

	/**
	 * Test get_formatted_billing_address and has_billing_address.
	 *
	 * @since 3.3
	 */
	public function test_get_has_formatted_billing_address() {
		$order = new WC_Order();

		$this->assertEquals( 'none', $order->get_formatted_billing_address( 'none' ) );

		$order->set_billing_address_1( '123 Test St.' );
		$order->set_billing_country( 'US' );
		$order->set_billing_city( 'Portland' );
		$order->set_billing_postcode( '97266' );
		$this->assertEquals( '123 Test St.<br/>Portland, 97266', $order->get_formatted_billing_address( 'none' ) );

		$this->assertTrue( $order->has_billing_address() );
		$this->assertFalse( $order->has_shipping_address() );
	}

	/**
	 * Test get_formatted_shipping_address and has_shipping_address.
	 *
	 * @since 3.3
	 */
	public function test_get_has_formatted_shipping_address() {
		$order = new WC_Order();

		$this->assertEquals( 'none', $order->get_formatted_shipping_address( 'none' ) );

		$order->set_shipping_address_1( '123 Test St.' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_city( 'Portland' );
		$order->set_shipping_postcode( '97266' );
		$this->assertEquals( '123 Test St.<br/>Portland, 97266', $order->get_formatted_shipping_address( 'none' ) );

		$this->assertFalse( $order->has_billing_address() );
		$this->assertTrue( $order->has_shipping_address() );
	}

	/**
	 * Test: Setting/getting shipping settings after an order is saved
	 */
	public function test_set_shipping_after_save() {
		$object  = new WC_Order();
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
	public function test_get_payment_method() {
		$object = new WC_Order();
		$set_to = 'paypal';
		$object->set_payment_method( $set_to );
		$this->assertEquals( $set_to, $object->get_payment_method() );
	}

	/**
	 * Test: get_payment_method_title
	 */
	public function test_get_payment_method_title() {
		$object = new WC_Order();
		$set_to = 'PayPal';
		$object->set_payment_method_title( $set_to );
		$this->assertEquals( $set_to, $object->get_payment_method_title() );
	}

	/**
	 * Test: get_transaction_id
	 */
	public function test_get_transaction_id() {
		$object = new WC_Order();
		$set_to = '12345';
		$object->set_transaction_id( $set_to );
		$this->assertEquals( $set_to, $object->get_transaction_id() );
	}

	/**
	 * Test: get_customer_ip_address
	 */
	public function test_get_customer_ip_address() {
		$object = new WC_Order();
		$set_to = '192.168.1.1';
		$object->set_customer_ip_address( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_ip_address() );
	}

	/**
	 * Test: get_customer_user_agent
	 */
	public function test_get_customer_user_agent() {
		$object = new WC_Order();
		$set_to = 'UAstring';
		$object->set_customer_user_agent( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_user_agent() );
	}

	/**
	 * Test: get_created_via
	 */
	public function test_get_created_via() {
		$object = new WC_Order();
		$set_to = 'WooCommerce';
		$object->set_created_via( $set_to );
		$this->assertEquals( $set_to, $object->get_created_via() );
	}

	/**
	 * Test: get_customer_note
	 */
	public function test_get_customer_note() {
		$object = new WC_Order();
		$set_to = 'Leave on porch.';
		$object->set_customer_note( $set_to );
		$this->assertEquals( $set_to, $object->get_customer_note() );
	}

	/**
	 * Test: get_date_completed
	 */
	public function test_get_date_completed() {
		$object = new WC_Order();
		$object->set_date_completed( '2016-12-12' );
		$this->assertEquals( '1481500800', $object->get_date_completed()->getOffsetTimestamp() );

		$object->set_date_completed( '1481500800' );
		$this->assertEquals( 1481500800, $object->get_date_completed()->getTimestamp() );
	}

	/**
	 * Test: get_date_paid
	 */
	public function test_get_date_paid() {
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
	public function test_get_cart_hash() {
		$object = new WC_Order();
		$set_to = '12345';
		$object->set_cart_hash( $set_to );
		$this->assertEquals( $set_to, $object->get_cart_hash() );
	}

	/**
	 * Test: get_address
	 */
	public function test_get_address() {
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
			'phone'      => '',
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
	public function test_get_shipping_address_map_url() {
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
		$this->assertEquals( 'https://maps.google.com/maps?&q=34%20Stonepants%20avenue%2C%20Rockville%2C%20Bedrock%2C%20Boulder%2C%2000001%2C%20US&z=16', $object->get_shipping_address_map_url() );
	}

	/**
	 * Test: get_formatted_billing_full_name
	 */
	public function test_get_formatted_billing_full_name() {
		$object = new WC_Order();
		$object->set_billing_first_name( 'Fred' );
		$object->set_billing_last_name( 'Flintstone' );
		$this->assertEquals( 'Fred Flintstone', $object->get_formatted_billing_full_name() );
	}

	/**
	 * Test: get_formatted_shipping_full_name
	 */
	public function test_get_formatted_shipping_full_name() {
		$object = new WC_Order();
		$object->set_shipping_first_name( 'Barney' );
		$object->set_shipping_last_name( 'Rubble' );
		$this->assertEquals( 'Barney Rubble', $object->get_formatted_shipping_full_name() );
	}

	/**
	 * Test: get_formatted_billing_address
	 */
	public function test_get_formatted_billing_address() {
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
		$this->assertEquals( 'Fred Flintstone<br/>Bedrock Ltd.<br/>34 Stonepants avenue<br/>Rockville<br/>Bedrock, BOULDER 00001', $object->get_formatted_billing_address() );
	}

	/**
	 * Test: get_formatted_shipping_address
	 */
	public function test_get_formatted_shipping_address() {
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
		$this->assertEquals( 'Barney Rubble<br/>Bedrock Ltd.<br/>34 Stonepants avenue<br/>Rockville<br/>Bedrock, BOULDER 00001', $object->get_formatted_shipping_address() );
	}

	/**
	 * Test: has_cart_hash
	 */
	public function test_has_cart_hash() {
		$object = new WC_Order();
		$this->assertFalse( $object->has_cart_hash( '12345' ) );
		$set_to = '12345';
		$object->set_cart_hash( $set_to );
		$this->assertTrue( $object->has_cart_hash( '12345' ) );
	}

	/**
	 * Test: is_editable
	 */
	public function test_is_editable() {
		$object = new WC_Order();
		$object->set_status( 'pending' );
		$this->assertTrue( $object->is_editable() );
		$object->set_status( 'processing' );
		$this->assertFalse( $object->is_editable() );
	}

	/**
	 * Test: is_paid
	 */
	public function test_is_paid() {
		$object = new WC_Order();
		$object->set_status( 'pending' );
		$this->assertFalse( $object->is_paid() );
		$object->set_status( 'processing' );
		$this->assertTrue( $object->is_paid() );
	}

	/**
	 * Test: is_download_permitted
	 */
	public function test_is_download_permitted() {
		$object = new WC_Order();
		$object->set_status( 'pending' );
		$this->assertFalse( $object->is_download_permitted() );
		$object->set_status( 'completed' );
		$this->assertTrue( $object->is_download_permitted() );
	}

	/**
	 * Test: needs_shipping_address
	 */
	public function test_needs_shipping_address() {
		$object = new WC_Order();
		$this->assertFalse( $object->needs_shipping_address() );

		$object = WC_Helper_Order::create_order();
		$this->assertTrue( $object->needs_shipping_address() );
	}

	/**
	 * Test: has_downloadable_item
	 */
	public function test_has_downloadable_item() {
		$object = new WC_Order();
		$this->assertFalse( $object->has_downloadable_item() );

		$object = WC_Helper_Order::create_order();
		$this->assertFalse( $object->has_downloadable_item() );
	}

	/**
	 * Test: needs_payment
	 */
	public function test_needs_payment() {
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
	public function test_get_checkout_payment_url() {
		$object = new WC_Order();
		$id     = $object->save();
		$this->assertEquals( 'http://example.org?order-pay=' . $id . '&pay_for_order=true&key=' . $object->get_order_key(), $object->get_checkout_payment_url() );
	}

	/**
	 * Test: get_checkout_order_received_url
	 */
	public function test_get_checkout_order_received_url() {
		$object = new WC_Order();
		$object->set_order_key( 'xxx' );
		$id = $object->save();
		$this->assertEquals( 'http://example.org?order-received=' . $id . '&key=' . $object->get_order_key(), $object->get_checkout_order_received_url() );
	}

	/**
	 * Test: get_cancel_order_url
	 */
	public function test_get_cancel_order_url() {
		$object = new WC_Order();
		$this->assertInternalType( 'string', $object->get_cancel_order_url() );
	}

	/**
	 * Test: get_cancel_order_url_raw
	 */
	public function test_get_cancel_order_url_raw() {
		$object = new WC_Order();
		$this->assertInternalType( 'string', $object->get_cancel_order_url_raw() );
	}

	/**
	 * Test: get_cancel_endpoint
	 */
	public function test_get_cancel_endpoint() {
		$object = new WC_Order();
		$this->assertEquals( 'http://example.org/', $object->get_cancel_endpoint() );
	}

	/**
	 * Test: get_view_order_url
	 */
	public function test_get_view_order_url() {
		$object = new WC_Order();
		$id     = $object->save();
		$this->assertEquals( 'http://example.org?view-order=' . $id, $object->get_view_order_url() );
	}

	/**
	 * Test: add_order_note
	 */
	public function test_add_order_note() {
		$object     = new WC_Order();
		$id         = $object->save();
		$comment_id = $object->add_order_note( 'Hello, I am a fish' );
		$this->assertTrue( $comment_id > 0 );

		$comment = get_comment( $comment_id );
		$this->assertEquals( 'Hello, I am a fish', $comment->comment_content );
	}

	/**
	 * Test: get_customer_order_notes
	 */
	public function test_get_customer_order_notes() {
		$object = new WC_Order();
		$id     = $object->save();

		$this->assertCount( 0, $object->get_customer_order_notes() );

		$object->add_order_note( 'Hello, I am a fish', true );
		$object->add_order_note( 'Hello, I am a fish', false );
		$object->add_order_note( 'Hello, I am a fish', true );

		$this->assertCount( 2, $object->get_customer_order_notes() );
	}

	/**
	 * Test: get_refunds
	 */
	public function test_get_refunds() {
		$object = new WC_Order();
		$object->set_total( 100 );
		$id = $object->save();

		$this->assertCount( 0, $object->get_refunds() );

		wc_create_refund(
			array(
				'order_id'   => $id,
				'amount'     => '100',
				'line_items' => array(),
			)
		);

		$this->assertCount( 1, $object->get_refunds() );
	}

	/**
	 * Test: get_total_refunded
	 */
	public function test_get_total_refunded() {
		$object = new WC_Order();
		$object->set_total( 400 );
		$id = $object->save();
		wc_create_refund(
			array(
				'order_id'   => $id,
				'amount'     => '100',
				'line_items' => array(),
			)
		);
		wc_create_refund(
			array(
				'order_id'   => $id,
				'amount'     => '100',
				'line_items' => array(),
			)
		);
		$this->assertEquals( 200, $object->get_total_refunded() );
	}

	/**
	 * Test: get_total_tax_refunded
	 */
	public function test_get_total_tax_refunded() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_tax_refunded() );
	}

	/**
	 * Test: get_total_shipping_refunded
	 */
	public function test_get_total_shipping_refunded() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_shipping_refunded() );
	}

	/**
	 * Test: get_total_shipping_refunded
	 */
	public function test_get_total_qty_refunded() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_shipping_refunded() );
	}

	/**
	 * Test: get_qty_refunded_for_item
	 */
	public function test_get_qty_refunded_for_item() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_qty_refunded_for_item( 2 ) );
	}

	/**
	 * Test: test_get_total_refunded_for_item
	 */
	public function test_get_total_refunded_for_item() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_refunded_for_item( 2 ) );
	}

	/**
	 * Test: get_tax_refunded_for_item
	 */
	public function test_get_tax_refunded_for_item() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_tax_refunded_for_item( 1, 1 ) );
	}

	/**
	 * Test: get_total_tax_refunded_by_rate_id
	 */
	public function test_get_total_tax_refunded_by_rate_id() {
		$object = new WC_Order();
		$this->assertEquals( 0, $object->get_total_tax_refunded_by_rate_id( 2 ) );
	}

	/**
	 * Test: get_remaining_refund_amount
	 */
	public function test_get_remaining_refund_amount() {
		$object = new WC_Order();
		$object->set_total( 400 );
		$id = $object->save();
		wc_create_refund(
			array(
				'order_id'   => $id,
				'amount'     => '100',
				'line_items' => array(),
			)
		);
		$this->assertEquals( 300, $object->get_remaining_refund_amount() );
	}

	/**
	 * Test: get_total_tax_refunded_by_rate_id
	 */
	public function test_get_remaining_refund_items() {
		$object = WC_Helper_Order::create_order();
		$this->assertEquals( 4, $object->get_remaining_refund_items() );
	}

	/**
	 * Test that if an exception is thrown when creating a refund, the refund is deleted from database.
	 */
	public function test_refund_exception() {
		$order = WC_Helper_Order::create_order();
		add_action( 'woocommerce_create_refund', array( $this, 'throwAnException' ) );
		$refund = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $order->get_total(),
				'line_items' => array(),
			)
		);
		remove_action( 'woocommerce_create_refund', array( $this, 'throwAnException' ) );
		$this->assertEmpty( $order->get_refunds() );
	}

	/**
	 * Test apply_coupon and remove_coupon with a fixed discount coupon.
	 *
	 * @since 3.2.0
	 */
	public function test_add_remove_coupon_fixed() {
		$order = WC_Helper_Order::create_order();

		$coupon = new WC_Coupon();
		$coupon->set_code( 'test' );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 10 );
		$coupon->save();

		$order->apply_coupon( 'test' );
		$this->assertEquals( 40, $order->get_total() );

		$order->remove_coupon( 'test' );
		$this->assertEquals( 50, $order->get_total() );
	}

	/**
	 * Test apply_coupon and remove_coupon with a percent discount coupon.
	 *
	 * @since 3.2.0
	 */
	public function test_add_remove_coupon_percent() {
		$order = WC_Helper_Order::create_order();

		$coupon = new WC_Coupon();
		$coupon->set_code( 'test' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 50 );
		$coupon->save();

		$order->apply_coupon( 'test' );
		$this->assertEquals( 30, $order->get_total() );

		$order->remove_coupon( 'test' );
		$this->assertEquals( 50, $order->get_total() );
	}

	/**
	 * Test the coupon usage limit based on guest orders with emails only.
	 *
	 * @return void
	 */
	public function test_coupon_email_usage_limit() {
		// Orders.
		$order1 = WC_Helper_Order::create_order();
		$order2 = WC_Helper_Order::create_order();

		// Setup coupon.
		$coupon = new WC_Coupon();
		$coupon->set_code( 'usage-limit-coupon' );
		$coupon->set_amount( 100 );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_usage_limit_per_user( 1 );
		$coupon->save();

		// Set as guest users with the same email.
		$order1->set_customer_id( 0 );
		$order1->set_billing_email( 'coupontest@example.com' );
		$order2->set_customer_id( 0 );
		$order2->set_billing_email( 'coupontest@example.com' );

		$order1->apply_coupon( 'usage-limit-coupon' );
		$this->assertEquals( 1, count( $order1->get_coupons() ) );

		$order2->apply_coupon( 'usage-limit-coupon' );
		$this->assertEquals( 0, count( $order2->get_coupons() ) );
	}

	/**
	 * Test removing and adding items + recalculation.
	 *
	 * @since 3.2.0
	 */
	public function test_add_remove_items() {
		$product = WC_Helper_Product::create_simple_product();
		$object  = new WC_Order();
		$item_1  = new WC_Order_Item_Product();
		$item_1->set_props(
			array(
				'product'  => $product,
				'quantity' => 4,
				'total'    => 100,
			)
		);
		$item_1_id = $item_1->save();
		$item_2    = new WC_Order_Item_Product();
		$item_2->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'total'    => 100,
			)
		);
		$item_2_id = $item_2->save();
		$object->add_item( $item_1 );
		$object->add_item( $item_2 );
		$object->save();
		$object->calculate_totals();

		$this->assertEquals( 200, $object->get_total() );

		// remove an item and add an item, then compare totals.
		$object->remove_item( $item_1_id );
		$item_3 = new WC_Order_Item_Product();
		$item_3->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'total'    => 100,
			)
		);
		$object->add_item( $item_3 );
		$object->save();
		$object->calculate_totals();

		$this->assertEquals( 200, $object->get_total() );
	}

	/**
	 * Test that exceptions thrown during save are handled.
	 *
	 * @since 3.3.0
	 */
	public function test_save_exception() {
		$object = new WC_Order();
		$object->save();

		add_action( 'woocommerce_before_order_object_save', array( $this, 'throwAnException' ) );

		$object->save();
		$note = current(
			wc_get_order_notes(
				array(
					'order_id' => $object->get_id(),
				)
			)
		);
		$this->assertContains( 'Error saving order', $note->content );

		remove_action( 'woocommerce_before_order_object_save', array( $this, 'throwAnException' ) );
	}

	/**
	 * Basic test for WC_Order::get_total_fees().
	 */
	public function test_get_total_fees_should_return_total_fees() {
		$order      = WC_Helper_Order::create_order();
		$fee_totals = array( 25, 50.50, 34.56 );

		foreach ( $fee_totals as $total ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_props(
				array(
					'name'       => 'Some Fee',
					'tax_status' => 'taxable',
					'total'      => $total,
					'tax_class'  => '',
				)
			);
			$order->add_item( $fee );
		}

		$this->assertEquals( 110.06, $order->get_total_fees() );
	}
}
