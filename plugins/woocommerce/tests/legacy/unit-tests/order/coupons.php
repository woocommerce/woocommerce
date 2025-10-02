<?php
declare(strict_types=1);

/**
 * Order coupon tests.
 *
 * @package WooCommerce\Tests\Orders
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductTaxStatus;
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
				'status'        => OrderStatus::PENDING,
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
		 * Discount should be based on subtotal unless coupons apply sequentially.
		 *
		 * Coupon will therefore discount 200. Compare the total without tax so we can compare the ex tax price and avoid rounding mishaps.
		 */
		$order->apply_coupon( 'test-coupon-2' );
		$this->assertEquals( 401, NumberUtil::round( $order->get_total_discount( false ), 2 ), 'Total discount should be 401' );
		$this->assertEquals( 598.99, $order->get_total(), 'Order total should be 598.99' );
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
		$this->assertEquals( 401, $order->get_discount_total(), 'Discount total should be 401' );
		$this->assertFloatEquals( ( 1000 - 401 ) * 1.1, $order->get_total(), 0.01, 'Order total should be 658.90' );
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
		$product_3->set_tax_status( ProductTaxStatus::NONE );
		$product_3->save();
		$product_3 = wc_get_product( $product_3->get_id() );

		$coupon = new WC_Coupon();
		$coupon->set_code( 'test-coupon-1' );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		$order = wc_create_order(
			array(
				'status'        => OrderStatus::PENDING,
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

	/**
	 * @testdox No error is thrown when coupons are recalculated if an applied coupon with custom discount type is deleted and the code that defined the discount type has disappeared.
	 */
	public function test_custom_discount_type_removed_and_coupon_trashed() {
		add_filter( 'woocommerce_coupon_discount_types', array( $this, 'handle_woocommerce_coupon_discount_types' ) );
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'handle_woocommerce_coupon_is_valid' ), 999, 3 );
		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'handle_woocommerce_coupon_get_discount_amount' ), 999, 5 );
		add_filter( 'woocommerce_coupon_is_valid_for_cart', array( $this, 'handle_woocommerce_coupon_is_valid_for_cart' ), 999, 2 );

		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );

		$this->init_test();

		$coupon = new WC_Coupon();
		$coupon->set_code( 'test-coupon-3' );
		$coupon->set_amount( 2.00 );
		$coupon->set_discount_type( 'foobar' );
		$coupon->save();

		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		$order->remove_coupon( 'test-coupon-1' );
		$order->remove_coupon( 'this-is-a-virtal-coupon' );

		$order->apply_coupon( 'test-coupon-3' );

		$order->calculate_totals( true );
		$order->save();

		$this->assertEquals( '998.00', $order->get_total() );

		// Remove the custom discount type handler, trash the coupon, recalculate: should not throw.

		$coupon->delete( true );

		remove_filter( 'woocommerce_coupon_discount_types', array( $this, 'handle_woocommerce_coupon_discount_types' ), 999 );
		remove_filter( 'woocommerce_coupon_is_valid', array( $this, 'handle_woocommerce_coupon_is_valid' ), 999 );
		remove_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'handle_woocommerce_coupon_get_discount_amount' ), 999 );
		remove_filter( 'woocommerce_coupon_is_valid_for_cart', array( $this, 'handle_woocommerce_coupon_is_valid_for_cart' ), 999 );

		$order->recalculate_coupons();
		$order->save();

		$this->assertEquals( '1000.00', $order->get_total() );
	}

	/**
	 * Handler for the woocommerce_coupon_discount_types filter.
	 *
	 * @param array $types Discount types.
	 */
	public function handle_woocommerce_coupon_discount_types( $types ) {
		$types['foobar'] = 'Alternative fixed discount';
		return $types;
	}

	/**
	 * Handler for the woocommerce_coupon_is_valid filter.
	 *
	 * @param bool         $valid Whether the coupon is initially considered valid.
	 * @param WC_Coupon    $coupon The coupon to check.
	 * @param WC_Discounts $discounts Discounts object.
	 * @return bool
	 */
	public function handle_woocommerce_coupon_is_valid( $valid, $coupon, $discounts = null ) {
		return $valid || ( $coupon->get_discount_type() === 'foobar' );
	}

	/**
	 * Handler for the woocommerce_coupon_get_discount_amount filter.
	 *
	 * @param float     $discount Initial discount amount.
	 * @param float     $discounting_amount Amount from which the discount is to be substracted.
	 * @param object    $cart_item Cart item.
	 * @param bool      $single Always false.
	 * @param WC_Coupon $coupon The coupon to check.
	 * @return float
	 */
	public function handle_woocommerce_coupon_get_discount_amount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
		return $coupon->get_discount_type() === 'foobar' ? $coupon->get_amount() : $discount;
	}

	/**
	 * Handler for the woocommerce_coupon_is_valid_for_Cart filter.
	 *
	 * @param bool      $valid Whether the coupon is initially considered valid for the cart.
	 * @param WC_Coupon $coupon The coupon to check.
	 * @return bool
	 */
	public function handle_woocommerce_coupon_is_valid_for_cart( $valid, $coupon ) {
		return $valid || ( $coupon->get_discount_type() === 'foobar' );
	}

	/**
	 * Test: test_recalculate_coupons_preserves_amounts_for_finalized_orders
	 */
	public function test_recalculate_coupons_preserves_amounts_for_finalized_orders() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'preserve-test' );
		$coupon->set_amount( 50 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->apply_coupon( 'preserve-test' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		$original_total = $order->get_total();
		$original_discount = $order->get_discount_total();

		// Modify the coupon amount
		$coupon->set_amount( 10 );
		$coupon->save();

		$order->recalculate_coupons();
		$order->save();

		// Order totals should remain unchanged for finalized orders
		$this->assertEquals( $original_total, $order->get_total() );
		$this->assertEquals( $original_discount, $order->get_discount_total() );

		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that when coupon amount is increased and new products are added,
	 * locked products keep original discount and new products get remainder.
	 */
	public function test_coupon_amount_increased_with_new_product() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'increase-test' );
		$coupon->set_amount( 50 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 100 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 60 );
		$product2->save();

		// Create order with one product and $50 coupon
		$order = wc_create_order();
		$order->add_product( $product1, 1 );
		$order->apply_coupon( 'increase-test' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		// Original: product1 = $100 - $50 = $50 total
		$this->assertEquals( 50.00, $order->get_total() );
		$this->assertEquals( 50.00, $order->get_discount_total() );

		// Merchant increases coupon to $120
		$coupon->set_amount( 120 );
		$coupon->save();

		// Add second product
		$order->add_product( $product2, 1 );
		$order->recalculate_coupons();
		$order->calculate_totals();
		$order->save();

		// Expected with preservation:
		// Product1 keeps $50 discount (locked)
		// Product2 gets remainder: $120 - $50 = $70, but product2 costs only $60, so gets $60
		// Total: ($100 - $50) + ($60 - $60) = $50 + $0 = $50
		// Total discount: $50 + $60 = $110
		//
		// Without preservation (incorrect):
		// Both products would get $120 / 2 = $60 each
		// Total: ($100 - $60) + ($60 - $60) = $40 + $0 = $40
		// This test would fail because total would be $40 not $50
		$this->assertEquals( 50.00, $order->get_total() );
		$this->assertEquals( 110.00, $order->get_discount_total() );

		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that preserved coupons still affect order calculations properly.
	 * While we preserve coupon discount amounts, other order calculations should work normally.
	 */
	public function test_recalculate_coupons_preserved_coupons_still_function() {
		// Create a coupon with free shipping
		$coupon = new WC_Coupon();
		$coupon->set_code( 'function-test' );
		$coupon->set_amount( 20 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_free_shipping( true );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		// Enable tax calculations
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		// Create a tax rate
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10',
			'tax_rate_name'     => 'Test Tax',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 1,
			'tax_rate_order'    => 1,
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->apply_coupon( 'function-test' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		$original_total = $order->get_total();
		$original_discount = $order->get_discount_total();
		$original_tax = $order->get_total_tax();

		// Modify the coupon amount - this should NOT affect the finalized order
		$coupon->set_amount( 5 );
		$coupon->save();

		// Recalculate - discount should be preserved but other calculations should work
		$order->recalculate_coupons();
		$order->calculate_totals(); // This should recalculate taxes, etc.
		$order->save();

		// Discount amount should be preserved (still 20, not 5)
		$this->assertEquals( $original_discount, $order->get_discount_total(), 'Coupon discount amount should be preserved' );
		$this->assertEquals( $original_total, $order->get_total(), 'Order total should remain the same' );

		// But the coupon should still function for other aspects like free shipping
		$this->assertTrue( $coupon->get_free_shipping(), 'Coupon should still provide free shipping' );

		// Tax calculations should still work properly based on preserved discount
		$this->assertEquals( $original_tax, $order->get_total_tax(), 'Tax calculations should work with preserved discount' );

		// Clean up
		WC_Tax::_delete_tax_rate( $tax_rate['tax_rate_id'] ?? 1 );
		update_option( 'woocommerce_calc_taxes', 'no' );
		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test: test_recalculate_coupons_updates_amounts_for_draft_orders
	 */
	public function test_recalculate_coupons_updates_amounts_for_draft_orders() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'draft-test' );
		$coupon->set_amount( 50 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->apply_coupon( 'draft-test' );
		$order->calculate_totals();
		$order->set_status( 'pending' );
		$order->save();

		// Modify the coupon amount
		$coupon->set_amount( 10 );
		$coupon->save();

		$order->recalculate_coupons();
		$order->save();

		// Draft order should use the new coupon amount
		$this->assertEquals( '90.00', $order->get_total() );
		$this->assertEquals( 10, $order->get_discount_total() );

		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test: test_recalculate_coupons_ignores_recreated_coupons
	 */
	public function test_recalculate_coupons_ignores_recreated_coupons() {
		$original_coupon = new WC_Coupon();
		$original_coupon->set_code( 'recreate-test' );
		$original_coupon->set_amount( 30 );
		$original_coupon->set_discount_type( 'percent' );
		$original_coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->apply_coupon( 'recreate-test' );
		$order->calculate_totals();
		$order->set_status( 'completed' );
		$order->save();

		$original_total = $order->get_total();
		$original_discount = $order->get_discount_total();

		// Delete original coupon and create new one with same code
		$original_coupon->delete( true );

		$new_coupon = new WC_Coupon();
		$new_coupon->set_code( 'recreate-test' );
		$new_coupon->set_amount( 10 );
		$new_coupon->set_discount_type( 'percent' );
		$new_coupon->save();

		$order->recalculate_coupons();
		$order->save();

		// Order should preserve original discount amounts
		$this->assertEquals( $original_total, $order->get_total() );
		$this->assertEquals( $original_discount, $order->get_discount_total() );

		$new_coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test: test_recalculate_coupon_statuses_filter
	 */
	public function test_recalculate_coupon_statuses_filter() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'filter-test' );
		$coupon->set_amount( 25 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->apply_coupon( 'filter-test' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		$original_total = $order->get_total();

		// Modify coupon and recalculate - custom status should preserve amounts by default
		$coupon->set_amount( 5 );
		$coupon->save();

		$order->recalculate_coupons();
		$order->save();

		// Processing status should preserve coupon amounts by default (safe behavior)
		$this->assertEquals( $original_total, $order->get_total() );

		// Add filter to include processing status in recalculation list
		add_filter( 'woocommerce_order_recalculate_coupon_statuses', function( $statuses, $order ) {
			$statuses[] = 'processing';
			return $statuses;
		}, 10, 2 );

		$order->recalculate_coupons();
		$order->save();

		// Now filter should allow processing status to recalculate coupon amounts
		$this->assertEquals( 95.00, $order->get_total() ); // 100 - 5 (new coupon amount)

		remove_all_filters( 'woocommerce_order_recalculate_coupon_statuses' );
		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test: test_recalculate_coupons_preserves_product_restrictions_percent
	 *
	 * CRITICAL test: When percent coupon product restrictions are changed after order placement,
	 * recalculation should NOT retroactively apply the new restrictions to finalized orders.
	 * Tests individual line item totals to catch discount redistribution.
	 */
	public function test_recalculate_coupons_preserves_product_restrictions_percent() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'percent-restriction-test' );
		$coupon->set_amount( 20 ); // 20% discount
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		// Create two products
		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 50 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 50 );
		$product2->save();

		// Initially restrict coupon to only product1
		$coupon->set_product_ids( array( $product1->get_id() ) );
		$coupon->save();

		$order = wc_create_order();
		$order->add_product( $product1, 1 );
		$order->add_product( $product2, 1 ); // This should NOT get the discount
		$order->apply_coupon( 'percent-restriction-test' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		// Get original line item totals
		$line_items = $order->get_items();
		$product1_item = null;
		$product2_item = null;
		foreach ( $line_items as $item ) {
			if ( $item->get_product_id() == $product1->get_id() ) {
				$product1_item = $item;
			} elseif ( $item->get_product_id() == $product2->get_id() ) {
				$product2_item = $item;
			}
		}

		// Should be: product1 = 40 (50 - 20%), product2 = 50 (no discount)
		$this->assertEquals( 40.00, $product1_item->get_total() );
		$this->assertEquals( 50.00, $product2_item->get_total() );
		$this->assertEquals( 90.00, $order->get_total() );
		$this->assertEquals( 10.00, $order->get_discount_total() );

		// Now change coupon to allow ALL products (simulate admin expanding coupon scope)
		$coupon->set_product_ids( array() ); // Empty = all products
		$coupon->save();

		// Trigger recalculation (e.g., from admin action or plugin)
		$order->recalculate_coupons();
		$order->save();

		// Refresh line items
		$line_items = $order->get_items();
		foreach ( $line_items as $item ) {
			if ( $item->get_product_id() == $product1->get_id() ) {
				$product1_item = $item;
			} elseif ( $item->get_product_id() == $product2->get_id() ) {
				$product2_item = $item;
			}
		}

		// The original restriction should be preserved - product2 should still not have discount
		// Without the fix, this would fail as product2 would now get 20% discount (total = 40)
		$this->assertEquals( 40.00, $product1_item->get_total() );
		$this->assertEquals( 50.00, $product2_item->get_total() );
		$this->assertEquals( 90.00, $order->get_total() );
		$this->assertEquals( 10.00, $order->get_discount_total() );

		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test multiple coupons on finalized orders to ensure no cross-coupon bleeding.
	 */
	public function test_recalculate_coupons_multiple_coupons_no_bleeding() {
		// Create two different coupons
		$coupon1 = new WC_Coupon();
		$coupon1->set_code( 'multi-test-1' );
		$coupon1->set_amount( 30 );
		$coupon1->set_discount_type( 'fixed_cart' );
		$coupon1->save();

		$coupon2 = new WC_Coupon();
		$coupon2->set_code( 'multi-test-2' );
		$coupon2->set_amount( 20 );
		$coupon2->set_discount_type( 'fixed_cart' );
		$coupon2->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->apply_coupon( 'multi-test-1' );
		$order->apply_coupon( 'multi-test-2' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		$original_total = $order->get_total();
		$original_discount = $order->get_discount_total();

		// Modify ONLY the first coupon amount
		$coupon1->set_amount( 5 );
		$coupon1->save();

		$order->recalculate_coupons();
		$order->save();

		// Both coupon discounts should be preserved (no bleeding between coupons)
		$this->assertEquals( $original_total, $order->get_total() );
		$this->assertEquals( $original_discount, $order->get_discount_total() );

		$coupon1->delete( true );
		$coupon2->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that adding products to finalized orders handles coupons properly.
	 * Fixed cart coupons preserve their original discount amount on existing items
	 * and new items receive no discount when the coupon amount is fully consumed.
	 */
	public function test_recalculate_coupons_added_products_after_finalization() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'added-products-test' );
		$coupon->set_amount( 25 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 100 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 50 );
		$product2->save();

		// Create order with one product and coupon
		$order = wc_create_order();
		$order->add_product( $product1, 1 );
		$order->apply_coupon( 'added-products-test' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		$original_total = $order->get_total();
		$original_discount = $order->get_discount_total();

		// Add second product after finalization
		$order->add_product( $product2, 1 );
		$order->recalculate_coupons();
		$order->calculate_totals();
		$order->save();

		// Original coupon discount should be preserved
		// New product receives no discount because the $25 fixed cart coupon
		// is fully consumed by the existing product
		$this->assertEquals( $original_discount, $order->get_discount_total() );

		// Total should be original + new product price (no discount on new item)
		$expected_total = $original_total + $product2->get_regular_price();
		$this->assertEquals( $expected_total, $order->get_total() );

		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test complex coupon stacking scenarios on finalized orders.
	 */
	public function test_recalculate_coupons_complex_stacking_preserved() {
		// Create percentage and fixed coupons
		$percent_coupon = new WC_Coupon();
		$percent_coupon->set_code( 'stack-percent' );
		$percent_coupon->set_amount( 10 ); // 10%
		$percent_coupon->set_discount_type( 'percent' );
		$percent_coupon->save();

		$fixed_coupon = new WC_Coupon();
		$fixed_coupon->set_code( 'stack-fixed' );
		$fixed_coupon->set_amount( 15 );
		$fixed_coupon->set_discount_type( 'fixed_cart' );
		$fixed_coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 2 ); // 2 x $100 = $200
		$order->apply_coupon( 'stack-percent' ); // 10% off $200 = $20 off
		$order->apply_coupon( 'stack-fixed' );   // $15 off
		$order->calculate_totals(); // Should be $200 - $20 - $15 = $165
		$order->set_status( 'processing' );
		$order->save();

		$original_total = $order->get_total();
		$original_discount = $order->get_discount_total();

		// Modify both coupons
		$percent_coupon->set_amount( 25 ); // Change to 25%
		$percent_coupon->save();
		$fixed_coupon->set_amount( 50 ); // Change to $50
		$fixed_coupon->save();

		$order->recalculate_coupons();
		$order->save();

		// All original stacked discounts should be preserved
		$this->assertEquals( $original_total, $order->get_total() );
		$this->assertEquals( $original_discount, $order->get_discount_total() );

		$percent_coupon->delete( true );
		$fixed_coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test fixed cart discount exhaustion - when preserved line items have already
	 * consumed the full discount amount, new items should get NO discount.
	 */
	public function test_recalculate_coupons_fixed_cart_discount_exhausted() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'fixed-exhausted-test' );
		$coupon->set_amount( 20 ); // $20 fixed cart discount
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 50 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 30 );
		$product2->save();

		// Create order with one product and coupon
		$order = wc_create_order();
		$order->add_product( $product1, 1 ); // $50 product
		$order->apply_coupon( 'fixed-exhausted-test' ); // $20 off
		$order->calculate_totals(); // Total should be $30
		$order->set_status( 'processing' );
		$order->save();

		$original_total = $order->get_total(); // Should be $30
		$original_discount = $order->get_discount_total(); // Should be $20

		// Add second product after finalization
		$order->add_product( $product2, 1 ); // $30 product added
		$order->recalculate_coupons();
		$order->calculate_totals();
		$order->save();

		// The $20 discount is already fully consumed by the first product
		// New product should NOT get any discount because the fixed cart coupon is exhausted
		// Expected: $30 (original total) + $30 (new product) = $60
		// Discount should remain $20 (not increase)

		$this->assertEquals( $original_discount, $order->get_discount_total(), 'Fixed cart discount should not exceed original amount' );

		// Total should be $60 ($30 preserved + $30 new product with no discount)
		// The fixed cart coupon amount is exhausted so new items cannot receive discounts
		$expected_total = $original_total + $product2->get_regular_price();
		$this->assertEquals( $expected_total, $order->get_total(), 'New product should not get discount when fixed amount is exhausted' );

		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test fixed cart discount partial consumption - when preserved line items
	 * only partially consume the discount, new items should get the remainder.
	 */
	public function test_recalculate_coupons_fixed_cart_discount_partial() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'fixed-partial-test' );
		$coupon->set_amount( 50 ); // $50 fixed cart discount
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 30 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 40 );
		$product2->save();

		// Create order with one product and coupon
		$order = wc_create_order();
		$item1_id = $order->add_product( $product1, 1 ); // $30 product

		$order->apply_coupon( 'fixed-partial-test' ); // $50 off, but only $30 used

		$order->calculate_totals(); // Total should be $0 (can't go negative)
		$order->set_status( 'processing' );
		$order->save();

		$original_total = $order->get_total(); // Should be $0
		$original_discount = $order->get_discount_total(); // Should be $30 (actual applied)

		// Add second product after finalization
		$item2_id = $order->add_product( $product2, 1 ); // $40 product added

		$order->recalculate_coupons();

		$order->calculate_totals();
		$order->save();

		// Expected behavior:
		// - First product keeps its $30 discount (preserved)
		// - Second product should get $20 discount (remainder: $50 - $30 = $20)
		// - Total discount: $30 + $20 = $50
		// - Total: ($30 + $40) - $50 = $20

		$this->assertEquals( 50.00, $order->get_discount_total(), 'Full discount should be applied' );
		$this->assertEquals( 20.00, $order->get_total(), 'Remainder discount on new product' );

		// Verify the new product got the remaining discount.
		$product2_item = $order->get_item( $item2_id );
		$this->assertEquals( 20.00, $product2_item->get_total(), 'New product should get remainder discount' );

		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that line-item level coupon tracking works properly.
	 */
	public function test_line_item_coupon_tracking() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'line-item-test' );
		$coupon->set_amount( 30 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 100 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 50 );
		$product2->save();

		// Create order with one product and apply coupon.
		$order = wc_create_order();
		$item1 = $order->add_product( $product1, 1 );
		$order->apply_coupon( 'line-item-test' );
		$order->calculate_totals();
		$order->save();

		// Check that coupon_applied_items metadata is stored.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item = reset( $coupon_items );
		$applied_items = $coupon_item->get_meta( 'coupon_applied_items', true );

		$this->assertIsArray( $applied_items, 'Coupon should have applied_items metadata' );
		$this->assertArrayHasKey( $item1, $applied_items, 'Line item ID should be a key in coupon metadata' );
		$this->assertIsArray( $applied_items[ $item1 ], 'Line item should have discount data array' );
		$this->assertArrayHasKey( 'discount', $applied_items[ $item1 ], 'Should store discount amount' );
		$this->assertArrayHasKey( 'discount_tax', $applied_items[ $item1 ], 'Should store discount tax' );

		// Finalize order.
		$order->set_status( 'processing' );
		$order->save();

		$original_item1_total = $order->get_item( $item1 )->get_total();
		$original_discount = $order->get_discount_total();

		// Add new product after finalization.
		$item2 = $order->add_product( $product2, 1 );
		$order->recalculate_coupons();
		$order->calculate_totals();
		$order->save();

		// Check that original item total is preserved (locked).
		$this->assertEquals( $original_item1_total, $order->get_item( $item1 )->get_total(), 'Original item total should be preserved' );

		// Check that new item gets NO discount (coupon was exhausted).
		$this->assertEquals( 50, $order->get_item( $item2 )->get_total(), 'New item should not get discount when coupon is exhausted' );

		// Total discount should remain the same.
		$this->assertEquals( $original_discount, $order->get_discount_total(), 'Total discount should not increase' );

		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that locked items don't get double discounts.
	 */
	public function test_no_double_discount_on_locked_items() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'no-double-test' );
		$coupon->set_amount( 20 );
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		// Create and finalize order.
		$order = wc_create_order();
		$item_id = $order->add_product( $product, 2 ); // 2 items @ $100 = $200.
		$order->apply_coupon( 'no-double-test' ); // 20% off = $40 discount.
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		$original_item_total = $order->get_item( $item_id )->get_total(); // Should be $160.
		$original_discount = $order->get_discount_total(); // Should be $40.

		// Modify coupon to higher percentage.
		$coupon->set_amount( 50 ); // Now 50% off.
		$coupon->save();

		// Recalculate - locked items should NOT get additional discount.
		$order->recalculate_coupons();
		$order->calculate_totals();
		$order->save();

		// Item total should remain the same (no double discount).
		$this->assertEquals( $original_item_total, $order->get_item( $item_id )->get_total(), 'Locked item should not get additional discount' );
		$this->assertEquals( $original_discount, $order->get_discount_total(), 'Total discount should not increase for locked items' );

		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that coupon metadata includes per-item discount and tax amounts.
	 */
	public function test_metadata_storage_format() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'metadata-test' );
		$coupon->set_amount( 30 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		// Enable tax calculations.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		// Create a tax rate.
		$tax_rate_id = WC_Tax::_insert_tax_rate( array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10',
			'tax_rate_name'     => 'Test Tax',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 0,
			'tax_rate_order'    => 1,
			'tax_rate_class'    => '',
		) );

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 100 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 50 );
		$product2->save();

		// Create order and apply coupon.
		$order = wc_create_order();
		$item1_id = $order->add_product( $product1, 1 );
		$item2_id = $order->add_product( $product2, 1 );
		$order->apply_coupon( 'metadata-test' );
		$order->calculate_totals();
		$order->save();

		// Check that coupon_applied_items metadata is stored with discount amounts.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item = reset( $coupon_items );
		$applied_items = $coupon_item->get_meta( 'coupon_applied_items', true );

		$this->assertIsArray( $applied_items, 'Coupon should have applied_items metadata' );
		$this->assertArrayHasKey( $item1_id, $applied_items, 'Item 1 should be in applied items' );
		$this->assertArrayHasKey( $item2_id, $applied_items, 'Item 2 should be in applied items' );

		// Check that discount amounts are stored.
		$this->assertIsArray( $applied_items[$item1_id], 'Item 1 should have discount data' );
		$this->assertArrayHasKey( 'discount', $applied_items[$item1_id], 'Item 1 should have discount amount' );
		$this->assertArrayHasKey( 'discount_tax', $applied_items[$item1_id], 'Item 1 should have discount tax' );

		$this->assertIsArray( $applied_items[$item2_id], 'Item 2 should have discount data' );
		$this->assertArrayHasKey( 'discount', $applied_items[$item2_id], 'Item 2 should have discount amount' );
		$this->assertArrayHasKey( 'discount_tax', $applied_items[$item2_id], 'Item 2 should have discount tax' );

		// Verify discount amounts are numeric and reasonable.
		$item1_discount = (float) $applied_items[$item1_id]['discount'];
		$item2_discount = (float) $applied_items[$item2_id]['discount'];
		$total_discount = $item1_discount + $item2_discount;

		// Total discount should equal coupon amount.
		$this->assertEquals( 30, $total_discount, 'Total stored discount should equal coupon amount' );

		// Fixed cart discounts are distributed equally per item count.
		$expected_item1_discount = 15;
		$expected_item2_discount = 15;
		$this->assertEquals( $expected_item1_discount, $item1_discount, 'Item 1 discount should match' );
		$this->assertEquals( $expected_item2_discount, $item2_discount, 'Item 2 discount should match' );

		// Clean up.
		WC_Tax::_delete_tax_rate( $tax_rate_id );
		update_option( 'woocommerce_calc_taxes', 'no' );
		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test backwards compatibility with orders created before line-item tracking.
	 */
	public function test_backwards_compatibility_no_metadata() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'backwards-compat-test' );
		$coupon->set_amount( 25 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 100 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 50 );
		$product2->save();

		// Simulate an old order without coupon_applied_items metadata.
		$order = wc_create_order();
		$order->add_product( $product1, 1 );
		$order->apply_coupon( 'backwards-compat-test' );
		$order->calculate_totals();

		// Remove the coupon_applied_items metadata to simulate old order.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item = reset( $coupon_items );
		$coupon_item->delete_meta_data( 'coupon_applied_items' );
		$coupon_item->save();

		$order->set_status( 'processing' );
		$order->save();

		// Add new product.
		$order->add_product( $product2, 1 );
		$order->recalculate_coupons();
		$order->calculate_totals();
		$order->save();

		// Without metadata, all items can be recalculated (backwards compatible behavior).
		// New item should get discount if coupon amount allows.
		$this->assertLessThan( 150, $order->get_total(), 'New item should get discount in backwards compatible mode' );

		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test percentage coupon amount preservation.
	 * Verifies that both line totals AND coupon item discount remain at original figures.
	 */
	public function test_percentage_coupon_amount_preservation() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'percent-amount-test' );
		$coupon->set_amount( 20 ); // 20%
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		// Create order with percentage coupon.
		$order = wc_create_order();
		$item_id = $order->add_product( $product, 2 ); // 2 x $100 = $200
		$order->apply_coupon( 'percent-amount-test' ); // 20% off $200 = $40 discount
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		$original_line_total = $order->get_item( $item_id )->get_total(); // Should be $160
		$original_coupon_discount = $order->get_discount_total(); // Should be $40

		// Get original coupon item discount amount.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item = reset( $coupon_items );
		$original_coupon_amount = $coupon_item->get_discount(); // Should be $40

		// Change coupon percentage.
		$coupon->set_amount( 50 ); // Change to 50%
		$coupon->save();

		// Recalculate.
		$order->recalculate_coupons();
		$order->save();

		// Both line totals AND coupon item discount should remain at original figures.
		$this->assertEquals( $original_line_total, $order->get_item( $item_id )->get_total(), 'Line item total should be preserved' );
		$this->assertEquals( $original_coupon_discount, $order->get_discount_total(), 'Order discount total should be preserved' );

		// Refresh coupon item and check its discount amount.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item = reset( $coupon_items );
		$this->assertEquals( $original_coupon_amount, $coupon_item->get_discount(), 'Coupon item discount amount should be preserved' );

		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test multiple coupons on same line items.
	 * One fixed-cart + one percentage coupon on same products.
	 */
	public function test_multiple_coupons_same_line_preservation() {
		$fixed_coupon = new WC_Coupon();
		$fixed_coupon->set_code( 'multi-fixed' );
		$fixed_coupon->set_amount( 30 );
		$fixed_coupon->set_discount_type( 'fixed_cart' );
		$fixed_coupon->save();

		$percent_coupon = new WC_Coupon();
		$percent_coupon->set_code( 'multi-percent' );
		$percent_coupon->set_amount( 10 ); // 10%
		$percent_coupon->set_discount_type( 'percent' );
		$percent_coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		// Create order with both coupons.
		$order = wc_create_order();
		$item_id = $order->add_product( $product, 2 ); // 2 x $100 = $200
		$order->apply_coupon( 'multi-fixed' );   // $30 off
		$order->apply_coupon( 'multi-percent' ); // 10% off remaining = 10% of $170 = $17 off
		$order->calculate_totals(); // Total: $200 - $30 - $17 = $153
		$order->set_status( 'processing' );
		$order->save();

		$original_total = $order->get_total();
		$original_total_discount = $order->get_discount_total();

		// Get individual coupon discount amounts.
		$coupon_items = $order->get_items( 'coupon' );
		$original_coupon_amounts = array();
		foreach ( $coupon_items as $coupon_item ) {
			$original_coupon_amounts[ $coupon_item->get_code() ] = $coupon_item->get_discount();
		}

		// Modify both coupons.
		$fixed_coupon->set_amount( 50 );   // Change to $50
		$percent_coupon->set_amount( 25 ); // Change to 25%
		$fixed_coupon->save();
		$percent_coupon->save();

		// Recalculate.
		$order->recalculate_coupons();
		$order->save();

		// All original amounts should be preserved.
		$this->assertEquals( $original_total, $order->get_total(), 'Order total should be preserved' );
		$this->assertEquals( $original_total_discount, $order->get_discount_total(), 'Total discount should be preserved' );

		// Check each coupon retained its original discount amount.
		$coupon_items = $order->get_items( 'coupon' );
		foreach ( $coupon_items as $coupon_item ) {
			$code = $coupon_item->get_code();
			$this->assertEquals(
				$original_coupon_amounts[ $code ],
				$coupon_item->get_discount(),
				"Coupon {$code} should preserve its original discount amount"
			);
		}

		$fixed_coupon->delete( true );
		$percent_coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test REST API coupon creation gap.
	 * REST API uses calculate_coupons() which bypasses our metadata storage.
	 */
	public function test_rest_api_metadata_gap() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'rest-gap-test' );
		$coupon->set_amount( 15 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50 );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 2 );
		$order->save();

		// Simulate REST API calculate_coupons() path (not apply_coupon).
		$discounts = new WC_Discounts( $order );
		$discounts->apply_coupon( new WC_Coupon( $coupon->get_id() ) );

		// Manually add coupon item like REST API does.
		$coupon_item = new WC_Order_Item_Coupon();
		$coupon_item->set_code( 'rest-gap-test' );
		$coupon_item->set_discount( 15 );
		$coupon_item->set_discount_tax( 0 );

		// REST API does NOT store coupon_applied_items metadata.
		// This is the gap we need to address.

		$order->add_item( $coupon_item );
		$order->set_status( 'processing' );
		$order->save();

		// Verify no metadata was stored (demonstrating the gap).
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item_check = reset( $coupon_items );
		$applied_items = $coupon_item_check->get_meta( 'coupon_applied_items', true );

		$this->assertEmpty( $applied_items, 'REST API path should NOT store metadata (demonstrating the gap)' );

		// Because no metadata exists, recalculation will treat this as unlocked.
		$original_total = $order->get_total();

		// Modify coupon.
		$coupon->set_amount( 5 );
		$coupon->save();

		// Recalculate - without metadata, this will use new coupon amount.
		$order->recalculate_coupons();
		$order->save();

		// Total should change because there's no preservation (backwards compatibility).
		$new_total = $order->get_total();
		$this->assertNotEquals( $original_total, $new_total, 'Without metadata, recalculation should use new coupon amount' );

		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test percentage coupon extension to new items.
	 * Verifies that percentage coupons apply to new items while preserving locked totals.
	 */
	public function test_percentage_coupon_extends_to_new_items() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'percent-extend-test' );
		$coupon->set_amount( 20 ); // 20%
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 100 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 50 );
		$product2->save();

		// Create order with first product and percentage coupon.
		$order = wc_create_order();
		$item1_id = $order->add_product( $product1, 1 ); // $100 product
		$order->apply_coupon( 'percent-extend-test' ); // 20% off = $20 discount
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		$original_item1_total = $order->get_item( $item1_id )->get_total(); // Should be $80
		$original_total_discount = $order->get_discount_total(); // Should be $20

		// Add second product after finalization.
		$item2_id = $order->add_product( $product2, 1 ); // $50 product
		$order->recalculate_coupons();
		$order->calculate_totals();
		$order->save();

		// Item A keeps $20 off, Item B gets $10 off (20% of $50).
		$this->assertEquals( $original_item1_total, $order->get_item( $item1_id )->get_total(), 'Item A should keep $20 off' );
		$this->assertEquals( 40.00, $order->get_item( $item2_id )->get_total(), 'Item B should get $10 off (20% of $50)' );

		// Total discount should be $20 + $10 = $30.
		$this->assertEquals( 30.00, $order->get_discount_total(), 'Total discount should be $20 + $10' );
		$this->assertEquals( 120.00, $order->get_total(), 'Order total should be $150 - $30' );

		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test metadata integrity after recalculation.
	 * Verifies that coupon_applied_items metadata includes new entries with discount data.
	 */
	public function test_metadata_integrity_after_recalculation() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'metadata-integrity-test' );
		$coupon->set_amount( 15 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 60 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 40 );
		$product2->save();

		// Create order with first product.
		$order = wc_create_order();
		$item1_id = $order->add_product( $product1, 1 );
		$order->apply_coupon( 'metadata-integrity-test' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		// Verify initial metadata.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item = reset( $coupon_items );
		$initial_metadata = $coupon_item->get_meta( 'coupon_applied_items', true );

		$this->assertArrayHasKey( $item1_id, $initial_metadata, 'Initial metadata should include first item' );

		// Add second product and recalculate.
		$item2_id = $order->add_product( $product2, 1 );
		$order->recalculate_coupons();
		$order->save();

		// Verify updated metadata includes both items.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item = reset( $coupon_items );
		$updated_metadata = $coupon_item->get_meta( 'coupon_applied_items', true );

		$this->assertArrayHasKey( $item1_id, $updated_metadata, 'Updated metadata should preserve first item' );
		$this->assertArrayHasKey( $item2_id, $updated_metadata, 'Updated metadata should include new item' );

		// Verify discount data structure for both items.
		foreach ( array( $item1_id, $item2_id ) as $item_id ) {
			$this->assertIsArray( $updated_metadata[ $item_id ], "Item {$item_id} should have discount data" );
			$this->assertArrayHasKey( 'discount', $updated_metadata[ $item_id ], "Item {$item_id} should have discount amount" );
			$this->assertArrayHasKey( 'discount_tax', $updated_metadata[ $item_id ], "Item {$item_id} should have discount tax" );
		}

		$coupon->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test draft status recalculation.
	 * Verifies that draft orders fully recalculate to latest coupon data.
	 */
	public function test_draft_status_recalculation() {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'draft-status-test' );
		$coupon->set_amount( 20 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		// Create order in draft status.
		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->apply_coupon( 'draft-status-test' );
		$order->calculate_totals();
		$order->set_status( 'draft' );
		$order->save();

		$original_total = $order->get_total(); // Should be $80

		// Modify coupon amount.
		$coupon->set_amount( 10 );
		$coupon->save();

		// Recalculate - draft orders should use new coupon data.
		$order->recalculate_coupons();
		$order->save();

		// Should use new coupon amount (not preserve old).
		$this->assertEquals( 90.00, $order->get_total(), 'Draft order should use new coupon amount' );
		$this->assertEquals( 10.00, $order->get_discount_total(), 'Draft order should have new discount total' );
		$this->assertNotEquals( $original_total, $order->get_total(), 'Draft order should recalculate fully' );

		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that multiple coupons both apply to newly added items.
	 */
	public function test_multiple_coupons_apply_to_new_items() {
		$coupon1 = new WC_Coupon();
		$coupon1->set_code( 'multi-coupon-1' );
		$coupon1->set_amount( 10 );
		$coupon1->set_discount_type( 'fixed_cart' );
		$coupon1->save();

		$coupon2 = new WC_Coupon();
		$coupon2->set_code( 'multi-coupon-2' );
		$coupon2->set_amount( 5 );
		$coupon2->set_discount_type( 'fixed_cart' );
		$coupon2->save();

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 50 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 30 );
		$product2->save();

		// Create finalized order with two coupons.
		$order = wc_create_order();
		$item1_id = $order->add_product( $product1, 1 );
		$order->apply_coupon( 'multi-coupon-1' );
		$order->apply_coupon( 'multi-coupon-2' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		// Add new item after finalization.
		$item2_id = $order->add_product( $product2, 1 );
		$order->recalculate_coupons();
		$order->calculate_totals();
		$order->save();

		// Both coupons should apply to the new item.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon1_item = null;
		$coupon2_item = null;

		foreach ( $coupon_items as $coupon_item ) {
			if ( 'multi-coupon-1' === $coupon_item->get_code() ) {
				$coupon1_item = $coupon_item;
			}
			if ( 'multi-coupon-2' === $coupon_item->get_code() ) {
				$coupon2_item = $coupon_item;
			}
		}

		$this->assertNotNull( $coupon1_item, 'Coupon 1 should exist' );
		$this->assertNotNull( $coupon2_item, 'Coupon 2 should exist' );

		// Check that both coupons have metadata for the new item.
		$coupon1_applied = $coupon1_item->get_meta( 'coupon_applied_items', true );
		$coupon2_applied = $coupon2_item->get_meta( 'coupon_applied_items', true );

		$this->assertArrayHasKey( $item2_id, $coupon1_applied, 'Coupon 1 should apply to new item' );
		$this->assertArrayHasKey( $item2_id, $coupon2_applied, 'Coupon 2 should apply to new item' );

		// Total discount should be 10 + 5 = 15.
		$this->assertEquals( 15.00, $order->get_discount_total(), 'Both coupons should apply' );

		$coupon1->delete( true );
		$coupon2->delete( true );
		$product1->delete( true );
		$product2->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that removing a coupon properly cleans up metadata and resets totals.
	 */
	public function test_remove_coupon_cleans_metadata() {
		// Create a product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		// Create a fixed cart coupon.
		$coupon = new WC_Coupon();
		$coupon->set_code( 'test-remove-meta' );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		// Create an order with completed status.
		$order = wc_create_order(
			array(
				'status'      => OrderStatus::COMPLETED,
				'customer_id' => 1,
			)
		);

		// Add product to order.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 100,
				'total'    => 100,
			)
		);
		$order->add_item( $item );
		$order->save();
		$order->calculate_totals();

		// Initial total should be 100.
		$this->assertEquals( 100.00, $order->get_total(), 'Initial total should be 100' );

		// Apply the coupon.
		$order->apply_coupon( $coupon );
		$order->calculate_totals();

		// Total should now be 90 (100 - 10).
		$this->assertEquals( 90.00, $order->get_total(), 'Total should be 90 after coupon' );
		$this->assertEquals( 10.00, $order->get_discount_total(), 'Discount should be 10' );

		// Check that coupon metadata exists.
		$coupon_items = $order->get_items( 'coupon' );
		$this->assertCount( 1, $coupon_items, 'Should have one coupon item' );

		$coupon_item = reset( $coupon_items );
		$applied_items_meta = $coupon_item->get_meta( 'coupon_applied_items', true );

		$this->assertIsArray( $applied_items_meta, 'Coupon applied items metadata should exist' );
		$this->assertNotEmpty( $applied_items_meta, 'Coupon applied items metadata should not be empty' );
		$this->assertArrayHasKey( $item->get_id(), $applied_items_meta, 'Metadata should include the line item' );

		// Remove the coupon.
		$order->remove_coupon( 'test-remove-meta' );
		$order->calculate_totals();

		// Total should be back to 100.
		$this->assertEquals( 100.00, $order->get_total(), 'Total should be 100 after coupon removal' );
		$this->assertEquals( 0.00, $order->get_discount_total(), 'Discount should be 0 after coupon removal' );

		// Verify coupon item is removed.
		$coupon_items_after = $order->get_items( 'coupon' );
		$this->assertCount( 0, $coupon_items_after, 'Should have no coupon items after removal' );

		// Clean up.
		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that reapplying a coupon with a changed value gives the new discount, not the old locked discount.
	 */
	public function test_reapply_coupon_with_changed_value() {
		// Create a product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();

		// Create a fixed cart coupon with initial value of 10.
		$coupon = new WC_Coupon();
		$coupon->set_code( 'test-change-value' );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		// Create an order with completed status.
		$order = wc_create_order(
			array(
				'status'      => OrderStatus::COMPLETED,
				'customer_id' => 1,
			)
		);

		// Add product to order.
		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 100,
				'total'    => 100,
			)
		);
		$order->add_item( $item );
		$order->save();
		$order->calculate_totals();

		// Apply the coupon with value 10.
		$order->apply_coupon( $coupon );
		$order->calculate_totals();

		// Total should be 90 (100 - 10).
		$this->assertEquals( 90.00, $order->get_total(), 'Total should be 90 after first coupon application' );
		$this->assertEquals( 10.00, $order->get_discount_total(), 'Discount should be 10' );

		// Get the coupon item and check metadata.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item = reset( $coupon_items );
		$this->assertEquals( 10.00, $coupon_item->get_discount(), 'Coupon discount should be 10' );

		$applied_items_meta = $coupon_item->get_meta( 'coupon_applied_items', true );
		$this->assertArrayHasKey( $item->get_id(), $applied_items_meta, 'Metadata should include the line item' );
		$this->assertEquals( 10.00, $applied_items_meta[ $item->get_id() ]['discount'], 'Metadata discount should be 10' );

		// Remove the coupon.
		$order->remove_coupon( 'test-change-value' );
		$order->calculate_totals();

		// Total should be back to 100.
		$this->assertEquals( 100.00, $order->get_total(), 'Total should be 100 after coupon removal' );

		// Change the coupon value to 20.
		$coupon->set_amount( 20 );
		$coupon->save();

		// Reapply the coupon with new value.
		$order->apply_coupon( $coupon );
		$order->calculate_totals();

		// Total should now be 80 (100 - 20), NOT 90 (which would be if old locked value was used).
		$this->assertEquals( 80.00, $order->get_total(), 'Total should be 80 with new coupon value' );
		$this->assertEquals( 20.00, $order->get_discount_total(), 'Discount should be 20 with new coupon value' );

		// Verify coupon item has the new discount value.
		$coupon_items_after = $order->get_items( 'coupon' );
		$coupon_item_after = reset( $coupon_items_after );
		$this->assertEquals( 20.00, $coupon_item_after->get_discount(), 'Coupon discount should be 20 after reapplication' );

		// Verify metadata reflects the new discount.
		$applied_items_meta_after = $coupon_item_after->get_meta( 'coupon_applied_items', true );
		$this->assertArrayHasKey( $item->get_id(), $applied_items_meta_after, 'Metadata should include the line item' );
		$this->assertEquals( 20.00, $applied_items_meta_after[ $item->get_id() ]['discount'], 'Metadata discount should be 20 after reapplication' );

		// Clean up.
		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test that discount tax is correctly calculated when prices include tax.
	 */
	public function test_coupon_discount_tax_with_prices_including_tax() {
		// Enable tax calculations with prices including tax.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		// Create a 20% tax rate.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 0,
			'tax_rate_order'    => 1,
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create a $20 fixed cart discount coupon.
		$coupon = new WC_Coupon();
		$coupon->set_code( 'tax-incl-test' );
		$coupon->set_amount( 20 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		// Create a product with price including tax: $120 (incl 20% tax = $100 + $20 tax).
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 120 ); // Price includes tax.
		$product->save();

		// Create order and apply coupon.
		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->apply_coupon( 'tax-incl-test' );
		$order->calculate_totals();
		$order->set_status( 'processing' );
		$order->save();

		// Get the stored discount metadata.
		$coupon_items = $order->get_items( 'coupon' );
		$coupon_item = reset( $coupon_items );
		$applied_items = $coupon_item->get_meta( 'coupon_applied_items', true );

		$this->assertIsArray( $applied_items, 'Should have coupon_applied_items metadata' );
		$this->assertNotEmpty( $applied_items, 'Applied items should not be empty' );

		$item_discount_data = reset( $applied_items );
		$discount_amount = $item_discount_data['discount'];
		$discount_tax = $item_discount_data['discount_tax'];

		// Verify discount amounts are stored.
		// Discount amount in metadata is gross (including tax).
		$this->assertGreaterThan( 0, $discount_amount, 'Discount amount should be positive' );
		$this->assertGreaterThan( 0, $discount_tax, 'Discount tax should be positive' );

		// For a $20 discount with 20% tax: tax portion = $20 * (20/120) = $3.33.
		$expected_discount_tax = round( 20 * ( 20 / 120 ), 2 );
		$this->assertEqualsWithDelta( $expected_discount_tax, round( $discount_tax, 2 ), 0.01, 'Discount tax should be $3.33' );

		// Verify tax ratio matches tax rate.
		$actual_tax_ratio = $discount_tax / $discount_amount;
		$expected_tax_ratio = 20 / 120;
		$this->assertEqualsWithDelta( $expected_tax_ratio, $actual_tax_ratio, 0.01, 'Tax ratio should be 0.1667' );

		// Verify order totals.
		// Original: $120 = $100 excl + $20 tax.
		// After $20 discount: $100 = $83.33 excl + $16.67 tax.
		$order_total = (float) $order->get_total();
		$order_discount_total = (float) $order->get_discount_total();
		$order_discount_tax = (float) $order->get_discount_tax();
		$order_tax = (float) $order->get_total_tax();

		$this->assertEqualsWithDelta( 100.00, round( $order_total, 2 ), 0.01, 'Order total should be $100' );

		$expected_discount_excl = round( 20 * ( 100 / 120 ), 2 );
		$this->assertEqualsWithDelta( $expected_discount_excl, round( $order_discount_total, 2 ), 0.01, 'Discount excl tax should be $16.67' );

		$this->assertEqualsWithDelta( $expected_discount_tax, round( $order_discount_tax, 2 ), 0.01, 'Order discount tax should be $3.33' );

		$expected_remaining_tax = round( 20 - $expected_discount_tax, 2 );
		$this->assertEqualsWithDelta( $expected_remaining_tax, round( $order_tax, 2 ), 0.01, 'Remaining tax should be $16.67' );

		// Modify coupon and recalculate to verify preservation.
		$original_total = $order->get_total();
		$original_discount_total = $order->get_discount_total();
		$original_discount_tax = $order->get_discount_tax();
		$original_tax = $order->get_total_tax();

		$coupon->set_amount( 5 );
		$coupon->save();

		$order->recalculate_coupons();
		$order->calculate_totals();
		$order->save();

		$this->assertEquals( $original_total, $order->get_total(), 'Order total preserved' );
		$this->assertEquals( $original_discount_total, $order->get_discount_total(), 'Discount excl tax preserved' );
		$this->assertEquals( $original_discount_tax, $order->get_discount_tax(), 'Discount tax preserved' );
		$this->assertEquals( $original_tax, $order->get_total_tax(), 'Order tax preserved' );

		// Clean up.
		WC_Tax::_delete_tax_rate( $tax_rate['tax_rate_id'] ?? 1 );
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		$coupon->delete( true );
		$product->delete( true );
		$order->delete( true );
	}
}
