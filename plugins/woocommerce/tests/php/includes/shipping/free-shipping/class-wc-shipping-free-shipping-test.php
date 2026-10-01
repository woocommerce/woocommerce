<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- backcompat nomenclature.

/**
 * Tests for the WC_Shipping_Free_Shipping class.
 */
class WC_Shipping_Free_Shipping_Test extends WC_Unit_Test_Case {

	/**
	 * Use a comma as the decimal separator.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_price_thousand_sep', '.' );
	}

	/**
	 * Restore the separators and empty the cart.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_price_decimal_sep', '.' );
		update_option( 'woocommerce_price_thousand_sep', ',' );
		WC()->cart->empty_cart();
		parent::tearDown();
	}

	/**
	 * @testdox A minimum amount stored in the store's number format still refuses carts below it.
	 *
	 * @testWith [50, false]
	 *           [150, true]
	 *
	 * @param int  $price    Price of the only product in the cart.
	 * @param bool $expected Whether free shipping should be available.
	 */
	public function test_is_available_with_locale_formatted_min_amount( int $price, bool $expected ): void {
		$instance_id = 9001;
		update_option(
			'woocommerce_free_shipping_' . $instance_id . '_settings',
			array(
				'requires'   => 'min_amount',
				'min_amount' => '100,00',
			)
		);
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => $price ) );
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();

		$sut = new WC_Shipping_Free_Shipping( $instance_id );

		$this->assertSame( $expected, $sut->is_available( array() ) );
	}
}
