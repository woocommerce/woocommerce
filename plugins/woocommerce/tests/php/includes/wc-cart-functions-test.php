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
	 * @testdox Taxless shipping labels can render before the customer is initialized.
	 */
	public function test_taxless_shipping_label_without_customer(): void {
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		add_filter( 'woocommerce_cart_display_prices_including_tax', '__return_true' );
		$rate     = new WC_Shipping_Rate( 'flat_rate:1', 'Flat rate', 70, array(), 'flat_rate' );
		$customer = WC()->customer;

		try {
			WC()->customer = null;
			$this->assertSame( 'Flat rate: ' . wc_price( 70 ), wc_cart_totals_shipping_method_label( $rate ) );
		} finally {
			WC()->customer = $customer;
		}
	}

	/**
	 * @testdox Shipping labels use per-rate tax rounding without changing the stored rate.
	 * @testWith ["no", [10.5], 80, false, false]
	 *           ["yes", [10.5], 81, false, false]
	 *           ["no", [5.5, 5.5], 80, false, false]
	 *           ["yes", [5.5, 5.5], 81, false, false]
	 *           ["no", [10.5], 70, true, false]
	 *           ["no", [10.5], 81, false, true]
	 *           ["no", [10.5], 70, false, false, false]
	 *
	 * @param string $round_at_subtotal Whether tax rounds at subtotal.
	 * @param array  $taxes             Raw rate taxes.
	 * @param float  $expected          Displayed gross shipping cost.
	 * @param bool   $exempt            Whether the customer is exempt.
	 * @param bool   $round_up          Whether a tax filter selects half up.
	 * @param bool   $display_tax       Whether prices display tax-inclusive.
	 */
	public function test_shipping_label_rounds_each_tax( string $round_at_subtotal, array $taxes, float $expected, bool $exempt, bool $round_up, bool $display_tax = true ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_display_cart', $display_tax ? 'incl' : 'excl' );
		update_option( 'woocommerce_tax_round_at_subtotal', $round_at_subtotal );
		update_option( 'woocommerce_price_num_decimals', 0 );
		WC()->customer->set_is_vat_exempt( $exempt );
		if ( $round_up ) {
			add_filter(
				'wc_round_tax_total',
				static function ( $rounded, $value, $precision ) {
					return round( $value, $precision, PHP_ROUND_HALF_UP );
				},
				10,
				3
			);
		}
		$rate = new WC_Shipping_Rate( 'flat_rate:1', 'Flat rate', 70, $taxes, 'flat_rate' );

		$this->assertStringStartsWith( 'Flat rate: ' . wc_price( $expected ), wc_cart_totals_shipping_method_label( $rate ) );
		$this->assertSame( $taxes, $rate->get_taxes(), 'Formatting must not change cached rate taxes.' );
	}

	/**
	 * @testdox Shipping tax filters retain their raw input and any overridden amount.
	 * @testWith [10.5, 80]
	 *           [12.5, 83]
	 *
	 * @param float $filtered_tax Tax returned by the shipping filter.
	 * @param float $expected     Displayed gross shipping cost.
	 */
	public function test_shipping_label_preserves_filtered_tax( float $filtered_tax, float $expected ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_display_cart', 'incl' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		update_option( 'woocommerce_price_num_decimals', 0 );
		$rate = new WC_Shipping_Rate( 'flat_rate:1', 'Flat rate', 70, array( 10.5 ), 'flat_rate' );
		add_filter(
			'woocommerce_get_shipping_tax',
			function ( $tax, $filtered_rate ) use ( $rate, $filtered_tax ) {
				$this->assertSame( 10.5, $tax, 'The shipping tax filter must still receive the unrounded amount.' );
				$this->assertSame( $rate, $filtered_rate );
				return $filtered_tax;
			},
			10,
			2
		);

		$this->assertSame( 'Flat rate: ' . wc_price( $expected ), wc_cart_totals_shipping_method_label( $rate ) );
	}

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
