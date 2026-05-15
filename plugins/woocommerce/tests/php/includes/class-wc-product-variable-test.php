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
	 * @testdox 'get_available_variations' exposes the variation's stock-derived max_qty when stock is managed at the variation level only.
	 *
	 * Regression: when the parent variable product does not manage stock but a variation does, the product page's
	 * quantity input must receive the variation's stock quantity as `max_qty` so it matches the cart page behaviour.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/37141
	 */
	public function test_get_available_variation_exposes_variation_stock_as_max_qty() {
		$product = new WC_Product_Variable();
		$product->set_props(
			array(
				'name'         => 'Variation Stock Parent',
				'sku'          => 'VARSTOCK-PARENT-' . microtime( true ),
				'manage_stock' => false,
			)
		);

		$attributes   = array();
		$attributes[] = WC_Helper_Product::create_product_attribute_object( 'color', array( 'red' ) );
		$product->set_attributes( $attributes );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_props(
			array(
				'parent_id'      => $product->get_id(),
				'attributes'     => array( 'pa_color' => 'red' ),
				'regular_price'  => 20,
				'manage_stock'   => true,
				'stock_quantity' => 5,
				'stock_status'   => 'instock',
			)
		);
		$variation->save();

		$product->set_children( array( $variation->get_id() ) );
		WC_Product_Variable::sync( $product->get_id() );

		// Refresh from the DB so parent_data is hydrated as it would be for a frontend request.
		$variation = wc_get_product( $variation->get_id() );

		$this->assertTrue( $variation->managing_stock(), 'Variation should be stock-managed.' );
		$this->assertSame( 5, $variation->get_max_purchase_quantity(), 'Variation max purchase quantity must equal its stock.' );

		$variable_product = wc_get_product( $product->get_id() );
		$available        = $variable_product->get_available_variation( $variation );

		$this->assertIsArray( $available );
		$this->assertArrayHasKey( 'max_qty', $available );
		$this->assertSame(
			5,
			$available['max_qty'],
			'max_qty in the available-variation payload should match the variation stock so the product page quantity input can enforce it.'
		);
		$this->assertNotSame(
			'',
			$available['max_qty'],
			'max_qty should not be empty when the variation manages stock.'
		);
	}
}
