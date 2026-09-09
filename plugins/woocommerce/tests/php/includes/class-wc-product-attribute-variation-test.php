<?php
/**
 * Tests for the read_attributes regression: WC_Product_Attribute::get_variation()
 * must return false for non-variable product types even when the stored meta has
 * is_variation = 1.
 */

/**
 * @covers \WC_Product_Data_Store_CPT::read_attributes
 */
class WC_Product_Attribute_Variation_Test extends \WC_Unit_Test_Case {

	/**
	 * Clean up products created during tests.
	 */
	public function tearDown(): void {
		parent::tearDown();
		// Ensure no lingering filters.
		remove_all_filters( 'woocommerce_product_read_attribute' );
	}

	/**
	 * Helper: store raw product attributes meta with is_variation = 1 and
	 * return the reloaded product so read_attributes runs.
	 *
	 * @param string $product_type 'simple' or 'variable'.
	 * @return WC_Product The reloaded product.
	 */
	private function create_product_with_variation_meta( string $product_type ) {
		if ( 'variable' === $product_type ) {
			$product = new WC_Product_Variable();
		} else {
			$product = new WC_Product_Simple();
		}

		$product->set_name( 'Test Product ' . $product_type );
		$product->set_status( 'publish' );
		$product->save();

		// Store raw meta with is_variation = 1, simulating corrupted or legacy data.
		$meta_attributes = array(
			'pa_test_attr' => array(
				'name'         => 'pa_test_attr',
				'value'        => '',
				'position'     => 0,
				'is_visible'   => 1,
				'is_variation' => 1,
				'is_taxonomy'  => 0,
			),
		);

		update_post_meta( $product->get_id(), '_product_attributes', $meta_attributes );

		// Reload the product from the data store so read_attributes() fires.
		return wc_get_product( $product->get_id() );
	}

	/**
	 * @testdox get_variation() returns false for attributes on a simple product even when stored meta has is_variation = 1.
	 */
	public function test_simple_product_attribute_variation_is_false() {
		$product = $this->create_product_with_variation_meta( 'simple' );

		$this->assertInstanceOf( WC_Product_Simple::class, $product );

		$attributes = $product->get_attributes();

		$this->assertNotEmpty( $attributes, 'Product should have at least one attribute.' );

		foreach ( $attributes as $attribute ) {
			if ( $attribute instanceof WC_Product_Attribute ) {
				$this->assertFalse(
					$attribute->get_variation(),
					'Attributes on a simple product must not be marked for variation.'
				);
			}
		}
	}

	/**
	 * @testdox get_variation() returns true for attributes on a variable product when stored meta has is_variation = 1.
	 */
	public function test_variable_product_attribute_variation_is_true() {
		$product = $this->create_product_with_variation_meta( 'variable' );

		$this->assertInstanceOf( WC_Product_Variable::class, $product );

		$attributes = $product->get_attributes();

		$this->assertNotEmpty( $attributes, 'Product should have at least one attribute.' );

		$found_variation_attribute = false;
		foreach ( $attributes as $attribute ) {
			if ( $attribute instanceof WC_Product_Attribute ) {
				if ( $attribute->get_variation() ) {
					$found_variation_attribute = true;
					break;
				}
			}
		}
		$this->assertTrue( $found_variation_attribute, 'Variable product attributes with is_variation=1 should have variation enabled.' );
	}

	/**
	 * @testdox The woocommerce_product_read_attribute filter still receives the raw is_variation value before gating.
	 */
	public function test_filter_receives_raw_attribute_object() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Filter Test Product' );
		$product->set_status( 'publish' );
		$product->save();

		$meta_attributes = array(
			'pa_filter_test' => array(
				'name'         => 'pa_filter_test',
				'value'        => '',
				'position'     => 0,
				'is_visible'   => 1,
				'is_variation' => 1,
				'is_taxonomy'  => 0,
			),
		);

		update_post_meta( $product->get_id(), '_product_attributes', $meta_attributes );

		$captured_variation = null;
		add_filter(
			'woocommerce_product_read_attribute',
			function ( $attribute, $meta_value, $product ) use ( &$captured_variation ) {
				if ( $attribute instanceof WC_Product_Attribute ) {
					$captured_variation = $attribute->get_variation();
				}
				return $attribute;
			},
			10,
			3
		);

		wc_get_product( $product->get_id() );

		$this->assertFalse( $captured_variation, 'Filter should see variation as false because gating happens before the filter.' );
	}
}
