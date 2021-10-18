<?php

/**
 * Class WC_Product_Variable_Data_Store_CPT_Test
 */
class WC_Product_Variable_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * Helper filter to force prices inclusice of tax.
	 */
	public function __return_incl() {
		return 'incl';
	}

	/**
	 * @testdox Variation price cache accounts for Customer VAT exemption.
	 */
	public function test_variation_price_cache_vat_exempt() {
		// Set store to include tax in price display.
		add_filter( 'wc_tax_enabled', '__return_true' );
		add_filter( 'woocommerce_prices_include_tax', '__return_true' );
		add_filter( 'pre_option_woocommerce_tax_display_shop', array( $this, '__return_incl' ) );
		add_filter( 'pre_option_woocommerce_tax_display_cart', array( $this, '__return_incl' ) );

		// Create tax rate.
		$tax_id = WC_Tax::_insert_tax_rate(
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

		// Create our variable product.
		$product = WC_Helper_Product::create_variation_product();

		// Verify that a VAT exempt customer gets prices with tax removed.
		WC()->customer->set_is_vat_exempt( true );

		$prices_no_tax    = array( '9.09', '13.64', '14.55', '15.45', '16.36', '17.27' );
		$variation_prices = $product->get_variation_prices( true );

		$this->assertEquals( $prices_no_tax, array_values( $variation_prices['price'] ) );

		// Verify that a normal customer gets prices with tax included.
		// This indirectly proves that the customer's VAT exemption influences the cache key.
		WC()->customer->set_is_vat_exempt( false );

		$prices_with_tax  = array( '10.00', '15.00', '16.00', '17.00', '18.00', '19.00' );
		$variation_prices = $product->get_variation_prices( true );

		$this->assertEquals( $prices_with_tax, array_values( $variation_prices['price'] ) );

		// Clean up.
		WC_Tax::_delete_tax_rate( $tax_id );

		remove_filter( 'wc_tax_enabled', '__return_true' );
		remove_filter( 'woocommerce_prices_include_tax', '__return_true' );
		remove_filter( 'pre_option_woocommerce_tax_display_shop', array( $this, '__return_incl' ) );
		remove_filter( 'pre_option_woocommerce_tax_display_cart', array( $this, '__return_incl' ) );
	}
}
