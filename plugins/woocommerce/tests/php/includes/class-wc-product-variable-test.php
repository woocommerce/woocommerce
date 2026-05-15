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
	 * @testdox The variable add-to-cart template should not show the "out of stock and unavailable" message when no variations have a price (mirrors simple product behaviour).
	 *
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/27192
	 */
	public function test_variable_add_to_cart_template_suppresses_out_of_stock_message_when_no_variations_have_a_price() {
		$product = new WC_Product_Variable();
		$product->set_props(
			array(
				'name' => 'Dummy Variable Product No Price',
				'sku'  => 'DUMMY VARIABLE NO PRICE ' . microtime(),
			)
		);

		$attributes   = array();
		$attributes[] = WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large' ) );
		$product->set_attributes( $attributes );
		$product->save();

		// Create two variations with no price set, so the parent is not purchasable.
		$variations    = array();
		$variations[]  = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE NO PRICE SMALL',
			'',
			array( 'pa_size' => 'small' )
		);
		$variations[]  = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE NO PRICE LARGE',
			'',
			array( 'pa_size' => 'large' )
		);
		$variation_ids = array_map(
			function ( $variation ) {
				return $variation->get_id();
			},
			$variations
		);
		$product->set_children( $variation_ids );
		WC_Product_Variable::sync( $product->get_id() );

		// Sanity: parent is not purchasable when no variation has a price.
		$reloaded = wc_get_product( $product->get_id() );
		$this->assertFalse( $reloaded->is_purchasable(), 'Variable product with no priced variations should not be purchasable.' );
		$this->assertEmpty( $reloaded->get_available_variations(), 'No variations should be available when none have a price.' );

		// Render the variable add-to-cart template.
		$GLOBALS['product'] = $reloaded;
		ob_start();
		wc_get_template(
			'single-product/add-to-cart/variable.php',
			array(
				'available_variations' => $reloaded->get_available_variations(),
				'attributes'           => $reloaded->get_variation_attributes(),
				'selected_attributes'  => $reloaded->get_default_attributes(),
			)
		);
		$output = ob_get_clean();

		$this->assertStringNotContainsString(
			'This product is currently out of stock and unavailable.',
			$output,
			'Variable products with no priced variations should not show the misleading out-of-stock message.'
		);
		$this->assertStringNotContainsString(
			'<form',
			$output,
			'No add-to-cart form should be rendered when the variable product has no priced variations.'
		);
	}

	/**
	 * @testdox The variable add-to-cart template should still show the out-of-stock message when variations are priced but none are available (e.g. all hidden out-of-stock).
	 */
	public function test_variable_add_to_cart_template_shows_out_of_stock_message_when_priced_variations_are_unavailable() {
		$product = new WC_Product_Variable();
		$product->set_props(
			array(
				'name' => 'Dummy Variable Product Priced Hidden',
				'sku'  => 'DUMMY VARIABLE PRICED HIDDEN ' . microtime(),
			)
		);

		$attributes   = array();
		$attributes[] = WC_Helper_Product::create_product_attribute_object( 'size', array( 'small' ) );
		$product->set_attributes( $attributes );
		$product->save();

		$variations    = array();
		$variations[]  = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'DUMMY SKU VARIABLE PRICED SMALL',
			10,
			array( 'pa_size' => 'small' )
		);
		$variation_ids = array_map(
			function ( $variation ) {
				return $variation->get_id();
			},
			$variations
		);
		$product->set_children( $variation_ids );
		WC_Product_Variable::sync( $product->get_id() );

		$reloaded = wc_get_product( $product->get_id() );
		$this->assertTrue( $reloaded->is_purchasable(), 'Variable product with at least one priced variation should be purchasable.' );

		// Force the available variations to be empty to simulate the "all unavailable" case
		// (e.g. all variations out of stock with hide-out-of-stock enabled) while the parent is still
		// purchasable. This is the scenario where the out-of-stock message is appropriate.
		$GLOBALS['product'] = $reloaded;
		ob_start();
		wc_get_template(
			'single-product/add-to-cart/variable.php',
			array(
				'available_variations' => array(),
				'attributes'           => $reloaded->get_variation_attributes(),
				'selected_attributes'  => $reloaded->get_default_attributes(),
			)
		);
		$output = ob_get_clean();

		$this->assertStringContainsString(
			'This product is currently out of stock and unavailable.',
			$output,
			'Variable products with priced but unavailable variations should still show the out-of-stock message.'
		);
	}
}
