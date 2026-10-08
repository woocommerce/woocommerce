<?php
declare( strict_types = 1 );

/**
 * Tests for cart functions.
 *
 * @package WooCommerce\Tests\Cart
 */

/**
 * Tests for coupon discount formatting in cart totals.
 */
class WC_Cart_Functions_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Coupon totals pass the discount to wc_price as a negative amount.
	 */
	public function test_wc_cart_totals_coupon_html_passes_negative_amount_to_wc_price(): void {
		$price_filter = static function ( $price_html, $formatted_price, $args, $unformatted_price, $original_price ) {
			return 'signed-price:' . $original_price . ':' . $unformatted_price;
		};
		add_filter( 'wc_price', $price_filter, 10, 5 );

		$coupon_html = $this->get_coupon_html_for_cart();

		$this->assertStringStartsWith( 'signed-price:-1:-1 ', $coupon_html, 'The wc_price filter should receive the coupon discount as a negative amount.' );
	}

	/**
	 * @testdox Coupon totals render the minus sign inside the price markup.
	 */
	public function test_wc_cart_totals_coupon_html_renders_sign_inside_price_markup(): void {
		$coupon_html = $this->get_coupon_html_for_cart();

		$this->assertStringStartsWith( '<span class="woocommerce-Price-amount amount">-', $coupon_html, 'The minus sign should be part of the price markup, not prepended outside it.' );
	}

	/**
	 * Creates a cart with a coupon and renders its discount HTML.
	 *
	 * @return string
	 */
	private function get_coupon_html_for_cart(): string {
		$product = WC_Helper_Product::create_simple_product();
		$coupon  = WC_Helper_Coupon::create_coupon();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->apply_coupon( $coupon->get_code() );
		WC()->cart->calculate_totals();

		ob_start();
		wc_cart_totals_coupon_html( $coupon );
		return ob_get_clean();
	}
}
