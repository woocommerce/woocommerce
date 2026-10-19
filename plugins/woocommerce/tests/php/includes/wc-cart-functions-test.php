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
	 * @testdox A password-protected variable product cannot be added by passing its variation ID to cart validation.
	 */
	public function test_protected_variation_fails_add_to_cart_validation(): void {
		$product      = WC_Helper_Product::create_variation_product();
		$variation_id = $product->get_children()[0];
		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'secret',
			)
		);

		$this->assertFalse( apply_filters( 'woocommerce_add_to_cart_validation', true, $variation_id, 1 ), 'A variation must inherit its parent password protection.' );
		$this->assertSame( 1, wc_notice_count( 'error' ), 'The rejected variation should display the protected-product notice.' );
	}

	/**
	 * @testdox The legacy add-to-cart form rejects a protected product submitted by variation ID.
	 */
	public function test_form_rejects_protected_variation_id(): void {
		$product      = WC_Helper_Product::create_variation_product();
		$variation_id = $product->get_children()[0];
		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'secret',
			)
		);

		$request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preserve the request so the form test can restore it.
		try {
			$_REQUEST['add-to-cart'] = (string) $variation_id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The legacy add-to-cart form accepts requests without a nonce.
			WC_Form_Handler::add_to_cart_action();

			$this->assertCount( 0, WC()->cart->get_cart(), 'The protected variation should not be added to the cart.' );
			$this->assertSame( 1, wc_notice_count( 'error' ), 'The form should display the protected-product notice.' );
		} finally {
			$_REQUEST = $request;
		}
	}

	/**
	 * @testdox An unprotected variation passes add-to-cart validation.
	 */
	public function test_unprotected_variation_passes_add_to_cart_validation(): void {
		$product      = WC_Helper_Product::create_variation_product();
		$variation_id = $product->get_children()[0];

		$this->assertTrue( apply_filters( 'woocommerce_add_to_cart_validation', true, $variation_id, 1 ), 'An unprotected variation should remain purchasable.' );
	}

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
