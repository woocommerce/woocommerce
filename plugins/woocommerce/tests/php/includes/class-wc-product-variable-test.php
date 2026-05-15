<?php

/**
 * Tests for WC_Product_Variable.
 */
class WC_Product_Variable_Test extends \WC_Unit_Test_Case {
	/**
	 * @testdox 'get_available_variations' returns the variations as arrays if no parameters is passed.
	 */
	public function test_get_available_variations_returns_array_when_no_parameter_is_passed() {
		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations();

		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );
	}

	/**
	 * @testdox 'get_available_variations' returns the variations as arrays if the parameter passed is 'array'.
	 */
	public function test_get_available_variations_returns_array_when_array_parameter_is_passed() {
		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations( 'array' );

		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );
	}

	/**
	 * @testdox 'get_available_variations' returns the variations as objects if the parameter passed is 'objects'.
	 */
	public function test_get_available_variations_returns_object_when_objects_parameter_is_passed() {
		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations( 'objects' );

		$this->assertInstanceOf( WC_Product_Variation::class, $variations[0] );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]->get_sku() );
	}

	/**
	 * @testdox 'has_purchasable_variations' should return true when all variations are purchasable.
	 */
	public function test_has_purchasable_variations_returns_true_when_all_variations_are_purchasable() {

		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations( 'array' );
		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );

		$has_purchasable_variations = $product->has_purchasable_variations();
		$this->assertIsBool( $has_purchasable_variations );
		$this->assertTrue( $has_purchasable_variations );
	}

	/**
	 * @testdox 'has_purchasable_variations' returns true when some variations are purchasable.
	 */
	public function test_has_purchasable_variations_returns_true_when_some_variations_are_purchasable() {

		$product = new WC_Product_Variable();

		$product->set_props(
			array(
				'name' => 'Dummy Variable Product',
				'sku'  => 'DUMMY VARIABLE SKU' . microtime(),
			)
		);

		$attributes = array();

		$attributes[] = WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large', 'huge' ) );

		$product->set_attributes( $attributes );
		$product->save();

		$variations = array();

		$variations[] = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE SMALL',
			10,
			array( 'pa_size' => 'small' )
		);

		$variations[] = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE LARGE',
			'', // Variation is not available.
			array( 'pa_size' => 'large' )
		);

		$variation_ids = array_map(
			function ( $variation ) {
				return $variation->get_id();
			},
			$variations
		);
		$product->set_children( $variation_ids );

		$variations = $product->get_available_variations( 'array' );
		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );

		$has_purchasable_variations = $product->has_purchasable_variations();
		$this->assertIsBool( $has_purchasable_variations );
		$this->assertTrue( $has_purchasable_variations );
	}

	/**
	 * @testdox 'has_purchasable_variations' returns false when all variations are not purchasable.
	 */
	public function test_has_purchasable_variations_returns_false_when_all_variations_are_not_purchasable() {

		$product = new WC_Product_Variable();

		$product->set_props(
			array(
				'name' => 'Dummy Variable Product',
				'sku'  => 'DUMMY VARIABLE SKU' . microtime(),
			)
		);

		$attributes = array();

		$attributes[] = WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large', 'huge' ) );

		$product->set_attributes( $attributes );
		$product->save();

		$variations = array();

		$variations[] = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE SMALL',
			'', // Variation is not available.
			array( 'pa_size' => 'small' )
		);

		$variations[] = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE LARGE',
			'', // Variation is not available.
			array( 'pa_size' => 'large' )
		);

		$variation_ids = array_map(
			function ( $variation ) {
				return $variation->get_id();
			},
			$variations
		);
		$product->set_children( $variation_ids );

		$variations = $product->get_available_variations( 'array' );
		$this->assertTrue( empty( $variations ) );

		$has_purchasable_variations = $product->has_purchasable_variations();
		$this->assertIsBool( $has_purchasable_variations );
		$this->assertFalse( $has_purchasable_variations );
	}

	/**
	 * @testdox 'get_price_html' renders a dynamic price display suffix when all variation prices match.
	 *
	 * Regression for woocommerce#54016: when every variation shared the same price, the
	 * `{price_including_tax}` placeholder suffix was suppressed alongside the price range case.
	 */
	public function test_get_price_html_renders_dynamic_suffix_when_variation_prices_match() {
		$original_suffix             = get_option( 'woocommerce_price_display_suffix' );
		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes' );
		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax' );
		$original_tax_display_shop   = get_option( 'woocommerce_tax_display_shop' );

		update_option( 'woocommerce_price_display_suffix', '{price_including_tax} (inc vat)' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_tax_display_shop', 'excl' );

		$tax_rate    = array(
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

		$product = new WC_Product_Variable();
		$product->set_props(
			array(
				'name' => 'Same-price variable product',
				'sku'  => 'SAME-PRICE-VAR-' . microtime( true ),
			)
		);

		$attribute = WC_Helper_Product::create_product_attribute_object( 'color', array( 'black', 'white' ) );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'SAME-PRICE-VAR-BLACK',
			20,
			array( 'pa_color' => 'black' )
		);
		WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'SAME-PRICE-VAR-WHITE',
			20,
			array( 'pa_color' => 'white' )
		);

		// Reload to pick up synced min/max prices.
		$product = wc_get_product( $product->get_id() );

		$price_html = $product->get_price_html();

		// Suffix label and resolved tax-inclusive price (20 * 1.20 = 24) should both be present.
		$this->assertStringContainsString( '(inc vat)', $price_html, 'Expected the price display suffix label to appear when variations share a price.' );
		$this->assertStringContainsString( 'woocommerce-price-suffix', $price_html, 'Expected the suffix wrapper markup to be present.' );

		// Sanity: the placeholder itself should have been replaced.
		$this->assertStringNotContainsString( '{price_including_tax}', $price_html );

		// Now flip one variation's price so the variations form a range; suffix with dynamic placeholders should be suppressed.
		$variations = $product->get_children();
		$variation  = wc_get_product( end( $variations ) );
		$variation->set_regular_price( 30 );
		$variation->save();

		$product    = wc_get_product( $product->get_id() );
		$range_html = $product->get_price_html();

		$this->assertStringNotContainsString( '(inc vat)', $range_html, 'Dynamic placeholder suffix should remain suppressed when variation prices form a range.' );

		// Test clean up.
		WC_Tax::_delete_tax_rate( $tax_rate_id );
		WC_Helper_Product::delete_product( $product->get_id() );
		update_option( 'woocommerce_price_display_suffix', $original_suffix );
		update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
		update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
		update_option( 'woocommerce_tax_display_shop', $original_tax_display_shop );
	}
}
