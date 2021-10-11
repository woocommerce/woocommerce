<?php
/**
 * Order coupon tests.
 *
 * @package WooCommerce\Tests\Orders
 */

use Automattic\WooCommerce\Utilities\NumberUtil;

/**
 * Order coupon tests.
 */
class WC_Tests_Order_Coupons extends WC_Unit_Test_Case {

	/**
	 * Track ids.
	 *
	 * @var array
	 */
	protected $objects = array();

	/**
	 * Setup an order.
	 */
	protected function init_test() {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'base' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 1000 );
		$product->save();
		$product = wc_get_product( $product->get_id() );

		$coupon = new WC_Coupon();
		$coupon->set_code( 'test-coupon-1' );
		$coupon->set_amount( 1.00 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$coupon2 = new WC_Coupon();
		$coupon2->set_code( 'test-coupon-2' );
		$coupon2->set_amount( 20 );
		$coupon2->set_discount_type( 'percent' );
		$coupon2->save();

		$order = wc_create_order(
			array(
				'status'        => 'pending',
				'customer_id'   => 1,
				'customer_note' => '',
				'total'         => '',
			)
		);

		// Add order products.
		$product_item  = new WC_Order_Item_Product();
		$coupon_item_1 = new WC_Order_Item_Coupon();
		$coupon_item_2 = new WC_Order_Item_Coupon();

		if ( get_option( 'woocommerce_prices_include_tax', 'no' ) === 'yes' && get_option( 'woocommerce_calc_taxes', 'no' ) === 'yes' ) {
			$product_item->set_props(
				array(
					'product'  => $product,
					'quantity' => 1,
					'subtotal' => 909.09, // Ex tax 10%.
					'total'    => 726.36,
				)
			);
			$coupon_item_1->set_props(
				array(
					'code'         => 'test-coupon-1',
					'discount'     => 0.91,
					'discount_tax' => 0.09,
				)
			);
			$coupon_item_2->set_props(
				array(
					'code'         => 'this-is-a-virtal-coupon',
					'discount'     => 181.82,
					'discount_tax' => 18.18,
				)
			);
		} else {
			$product_item->set_props(
				array(
					'product'  => $product,
					'quantity' => 1,
					'subtotal' => 1000, // Ex tax.
					'total'    => 799,
				)
			);
			$coupon_item_1->set_props(
				array(
					'code'         => 'test-coupon-1',
					'discount'     => 1,
					'discount_tax' => get_option( 'woocommerce_calc_taxes', 'no' ) === 'yes' ? 0.1 : 0,
				)
			);
			$coupon_item_2->set_props(
				array(
					'code'         => 'this-is-a-virtal-coupon',
					'discount'     => 200,
					'discount_tax' => get_option( 'woocommerce_calc_taxes', 'no' ) === 'yes' ? 20 : 0,
				)
			);
		}

		$product_item->save();
		$coupon_item_1->save();
		$coupon_item_2->save();

		$order->add_item( $product_item );
		$order->add_item( $coupon_item_1 );
		$order->add_item( $coupon_item_2 );

		$this->objects['coupons'][]      = $coupon;
		$this->objects['coupons'][]      = $coupon2;
		$this->objects['products'][]     = $product;
		$this->objects['order']          = $order;
		$this->objects['tax_rate_ids'][] = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$order->calculate_totals( true );
		$order->save();
	}

	/**
	 * Test: test_remove_coupon_from_order
	 */
	public function test_remove_coupon_from_order() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		// Check it's expected.
		$this->assertEquals( 'shop_order', $order->get_type() );
		$this->assertEquals( '799.00', $order->get_total(), $order->get_total() );

		// Remove the virtual coupon. Total should be 999.
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '999.00', $order->get_total(), $order->get_total() );

		// Remove the other coupon. Total should be 1000.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '1000.00', $order->get_total(), $order->get_total() );

		// Reset.
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		// Check it's expected.
		$this->assertEquals( 'shop_order', $order->get_type() );
		$this->assertEquals( '799.00', $order->get_total(), $order->get_total() );

		// Do the above tests in reverse.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '800.00', $order->get_total(), $order->get_total() );
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '1000.00', $order->get_total(), $order->get_total() );
	}

	/**
	 * Test: test_add_coupon_to_order
	 */
	public function test_add_coupon_to_order() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		$this->assertEquals( '799.00', $order->get_total(), $order->get_total() );

		/**
		 * Discount should be based on subtotal unless coupons apply sequencially.
		 *
		 * Coupon will therefore discount 200. Compare the total without tax so we can compare the ex tax price and avoid rounding mishaps.
		 */
		$order->apply_coupon( 'test-coupon-2' );
		$this->assertEquals( 401, NumberUtil::round( $order->get_total_discount( false ), 2 ), $order->get_total_discount( false ) );
		$this->assertEquals( 598.99, $order->get_total(), $order->get_total() );
	}

	/**
	 * Test: test_remove_coupon_from_order_ex_tax
	 */
	public function test_remove_coupon_from_order_ex_tax() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		// Check it's expected.
		$this->assertEquals( 'shop_order', $order->get_type() );
		$this->assertEquals( '878.90', $order->get_total(), $order->get_total() );

		// Remove the virtual coupon. Total should be 999.
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '1098.90', $order->get_total(), $order->get_total() );

		// Remove the other coupon. Total should be 1000.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '1100.00', $order->get_total(), $order->get_total() );

		// Reset.
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		// Check it's expected.
		$this->assertEquals( 'shop_order', $order->get_type() );
		$this->assertEquals( '878.90', $order->get_total(), $order->get_total() );

		// Do the above tests in reverse.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '880.00', $order->get_total(), $order->get_total() );
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '1100.00', $order->get_total(), $order->get_total() );
	}

	/**
	 * Test: test_add_coupon_to_order_ex_tax
	 */
	public function test_add_coupon_to_order_ex_tax() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		$order->apply_coupon( 'test-coupon-2' );
		$this->assertEquals( 401, $order->get_discount_total(), $order->get_discount_total() );
		$this->assertEquals( ( 1000 - 401 ) * 1.1, $order->get_total(), $order->get_total() );
	}

	/**
	 * Test: test_remove_coupon_from_order_no_tax
	 */
	public function test_remove_coupon_from_order_no_tax() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		// Check it's expected.
		$this->assertEquals( 'shop_order', $order->get_type() );
		$this->assertEquals( '799.00', $order->get_total(), $order->get_total() );

		// Remove the virtual coupon. Total should be 999.
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '999.00', $order->get_total(), $order->get_total() );

		// Remove the other coupon. Total should be 1000.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '1000.00', $order->get_total(), $order->get_total() );

		// Reset.
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		// Check it's expected.
		$this->assertEquals( 'shop_order', $order->get_type() );
		$this->assertEquals( '799.00', $order->get_total(), $order->get_total() );

		// Do the above tests in reverse.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '800.00', $order->get_total(), $order->get_total() );
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '1000.00', $order->get_total(), $order->get_total() );
	}

	/**
	 * Test: test_add_coupon_to_order_no_tax
	 */
	public function test_add_coupon_to_order_no_tax() {
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		$order->apply_coupon( 'test-coupon-2' );
		$this->assertEquals( '599.00', $order->get_total(), $order->get_total() );
	}

	/**
	 * Test: test_remove_coupon_from_order_no_tax
	 */
	public function test_remove_coupon_from_order_no_tax_inc_prices_on() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		// Check it's expected.
		$this->assertEquals( 'shop_order', $order->get_type() );
		$this->assertEquals( '799.00', $order->get_total(), $order->get_total() );

		// Remove the virtual coupon. Total should be 999.
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '999.00', $order->get_total(), $order->get_total() );

		// Remove the other coupon. Total should be 1000.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '1000.00', $order->get_total(), $order->get_total() );

		// Reset.
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		// Check it's expected.
		$this->assertEquals( 'shop_order', $order->get_type() );
		$this->assertEquals( '799.00', $order->get_total(), $order->get_total() );

		// Do the above tests in reverse.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '800.00', $order->get_total(), $order->get_total() );
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '1000.00', $order->get_total(), $order->get_total() );
	}

	/**
	 * Test: test_add_coupon_to_order_no_tax
	 */
	public function test_add_coupon_to_order_no_tax_inc_prices_on() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		$this->init_test();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		$order->apply_coupon( 'test-coupon-2' );
		$this->assertEquals( '599.00', $order->get_total(), $order->get_total() );
	}

	/**
	 * Test a rounding issue on order totals when the order includes a percentage coupon and taxable and non-taxable items
	 * See: #25091.
	 */
	public function test_inclusive_tax_rounding_on_totals() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '5.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '2',
				'tax_rate_compound' => '1',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		$product_1 = WC_Helper_Product::create_simple_product();
		$product_1->set_regular_price( 3.17 );
		$product_1->save();
		$product_1 = wc_get_product( $product_1->get_id() );

		$product_2 = WC_Helper_Product::create_simple_product();
		$product_2->set_regular_price( 6.13 );
		$product_2->save();
		$product_2 = wc_get_product( $product_2->get_id() );

		$product_3 = WC_Helper_Product::create_simple_product();
		$product_3->set_regular_price( 9.53 );
		$product_3->set_tax_status( 'none' );
		$product_3->save();
		$product_3 = wc_get_product( $product_3->get_id() );

		$coupon = new WC_Coupon();
		$coupon->set_code( 'test-coupon-1' );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		$order = wc_create_order(
			array(
				'status'        => 'pending',
				'customer_id'   => 1,
				'customer_note' => '',
				'total'         => '',
			)
		);

		$order->add_product( $product_1 );
		$order->add_product( $product_2 );
		$order->add_product( $product_3 );

		$order->calculate_totals( true );

		$order->apply_coupon( $coupon->get_code() );

		$applied_coupons = $order->get_items( 'coupon' );

		$this->assertEquals( '16.95', $order->get_total() );
		$this->assertEquals( '1.73', $order->get_total_tax() );
		$this->assertEquals( '1.69', $order->get_discount_total() );
	}
}
