<?php
/**
 * Tests for the totals class.
 *
 * @package WooCommerce\Tests\Discounts
 */

/**
 * WC_Tests_Totals
 */
class WC_Tests_Totals extends WC_Unit_Test_Case {

	/**
	 * Totals class for getter tests.
	 *
	 * @var object
	 */
	protected $totals;

	/**
	 * ID tracking for cleanup.
	 *
	 * @var array
	 */
	protected $ids = array();

	/**
	 * Setup the cart for totals calculation.
	 */
	public function setUp() {
		$this->ids = array();

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', 1 );
		}

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'base' );

		$product  = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();

		WC_Helper_Shipping::create_simple_flat_rate();
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );

		$coupon = new WC_Coupon;
		$coupon->set_code( 'test-coupon-10' );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'percent' );
		$coupon->save();

		$this->ids['tax_rate_ids'][] = $tax_rate_id;
		$this->ids['products'][]     = $product;
		$this->ids['products'][]     = $product2;
		$this->ids['coupons'][]      = $coupon;

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_to_cart( $product2->get_id(), 2 );
		WC()->cart->add_discount( $coupon->get_code() );

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_cart_fees_callback' ) );

		$this->totals = new WC_Cart_Totals( WC()->cart );
	}

	/**
	 * Add fees when the fees API is called.
	 */
	public function add_cart_fees_callback() {
		WC()->cart->add_fee( 'test fee', 10, true );
		WC()->cart->add_fee( 'test fee 2', 20, true );
		WC()->cart->add_fee( 'test fee non-taxable', 10, false );
	}

	/**
	 * Clean up after test.
	 */
	public function tearDown() {
		WC()->cart->empty_cart();
		WC()->session->set( 'chosen_shipping_methods', array() );
		WC_Helper_Shipping::delete_simple_flat_rate();
		update_option( 'woocommerce_calc_taxes', 'no' );
		remove_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_cart_fees_callback' ) );

		foreach ( $this->ids['products'] as $product ) {
			$product->delete( true );
		}

		foreach ( $this->ids['coupons'] as $coupon ) {
			$coupon->delete( true );
			wp_cache_delete( WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $coupon->get_code(), 'coupons' );
		}

		foreach ( $this->ids['tax_rate_ids'] as $tax_rate_id ) {
			WC_Tax::_delete_tax_rate( $tax_rate_id );
		}

		$this->ids = array();
	}

	/**
	 * Test get and set items.
	 */
	public function test_get_totals() {
		$this->assertEquals( array(
			'fees_total'          => 40.00,
			'fees_total_tax'      => 6.00,
			'items_subtotal'      => 30.00,
			'items_subtotal_tax'  => 6.00,
			'items_total'         => 27.00,
			'items_total_tax'     => 5.40,
			'total'               => 90.40,
			'shipping_total'      => 10,
			'shipping_tax_total'  => 2,
			'discounts_total'     => 3.00,
			'discounts_tax_total' => 0.60,
		), $this->totals->get_totals() );
	}

	/**
	 * Test that cart totals get updated.
	 */
	public function test_cart_totals() {
		$cart = WC()->cart;

		$this->assertEquals( 40.00, $cart->fee_total );
		$this->assertEquals( 27.00, $cart->cart_contents_total );
		$this->assertEquals( 90.40, $cart->total );
		$this->assertEquals( 36.00, $cart->subtotal );
		$this->assertEquals( 30.00, $cart->subtotal_ex_tax );
		$this->assertEquals( 11.40, $cart->tax_total );
		$this->assertEquals( 2.40, $cart->discount_cart );
		$this->assertEquals( 0.60, $cart->discount_cart_tax );
		$this->assertEquals( 40.00, $cart->fee_total );
		$this->assertEquals( 10, $cart->shipping_total );
		$this->assertEquals( 2, $cart->shipping_tax_total );
	}
}
