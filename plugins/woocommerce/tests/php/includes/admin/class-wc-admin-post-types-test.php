<?php
declare( strict_types = 1 );

/**
 * Unit tests for the WC_Admin_Post_Types class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * Class WC_Admin_Post_Types_Test
 */
class WC_Admin_Post_Types_Test extends WC_Unit_Test_Case {

	/**
	 * Get a new SUT (System Under Test) instance and configure the specified fake request data.
	 *
	 * @param array $request_data Fake request data to configure.
	 * @return WC_Admin_Post_Types
	 */
	private function get_sut_with_request_data( $request_data ) {
		$sut = $this
			->getMockBuilder( WC_Admin_Post_Types::class )
			->setMethods( array( 'request_data' ) )
			->getMock();

		$sut->method( 'request_data' )->willReturn( $request_data );

		return $sut;
	}

	/**
	 * @test
	 * @testdox Product attributes should update when requested via quick edit.
	 */
	public function quick_edit_updates_product_attributes() {
		$attribute_data = WC_Helper_Product::create_attribute( 'Quick edit color', array( 'Red', 'Blue' ) );

		try {
			$taxonomy = $attribute_data['attribute_taxonomy'];
			$red_id   = $attribute_data['term_ids'][0];
			$blue_id  = $attribute_data['term_ids'][1];
			$product  = WC_Helper_Product::create_simple_product();

			$attribute = new WC_Product_Attribute();
			$attribute->set_id( $attribute_data['attribute_id'] );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( array( $red_id ) );
			$attribute->set_position( 1 );
			$attribute->set_visible( true );
			$attribute->set_variation( true );
			$product->set_attributes( array( $attribute ) );
			$product->save();

			$this->login_as_administrator();

			$request_data = array(
				'woocommerce_quick_edit'                  => '1',
				'woocommerce_quick_edit_nonce'            => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
				'quick_edit_product_attribute_taxonomies' => array( $taxonomy ),
				'quick_edit_product_attributes'           => array(
					$taxonomy => array( $blue_id ),
				),
			);

			$sut = $this->get_sut_with_request_data( $request_data );

			$sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

			$updated_product    = wc_get_product( $product->get_id() );
			$updated_attributes = $updated_product->get_attributes( 'edit' );

			$this->assertArrayHasKey( $taxonomy, $updated_attributes, 'Updated product should keep the global attribute.' );
			$this->assertSame( array( $blue_id ), array_values( $updated_attributes[ $taxonomy ]->get_options() ), 'Quick edit should save the selected attribute term.' );
			$this->assertTrue( $updated_attributes[ $taxonomy ]->get_visible(), 'Quick edit should preserve attribute visibility.' );
			$this->assertTrue( $updated_attributes[ $taxonomy ]->get_variation(), 'Quick edit should preserve variation usage.' );
			$this->assertSame( array( $blue_id ), wp_list_pluck( wp_get_object_terms( $product->get_id(), $taxonomy ), 'term_id' ), 'Quick edit should update assigned product terms.' );
		} finally {
			$this->delete_test_attribute( $attribute_data );
		}
	}

	/**
	 * @test
	 * @testdox Product attributes should be added to products without existing attributes via quick edit.
	 */
	public function quick_edit_adds_product_attributes_to_product_without_existing_attributes() {
		$attribute_data = WC_Helper_Product::create_attribute( 'Quick edit add size', array( 'Small', 'Medium', 'Large' ) );

		try {
			$taxonomy  = $attribute_data['attribute_taxonomy'];
			$medium_id = $attribute_data['term_ids'][1];
			$large_id  = $attribute_data['term_ids'][2];
			$product   = WC_Helper_Product::create_simple_product();
			$term_ids  = array( $medium_id, $large_id );

			$this->quick_edit_save_product_attributes(
				$product,
				array( $taxonomy ),
				array(
					$taxonomy => $term_ids,
				)
			);

			$updated_product    = wc_get_product( $product->get_id() );
			$updated_attributes = $updated_product->get_attributes( 'edit' );

			$this->assertArrayHasKey( $taxonomy, $updated_attributes, 'Quick edit should add the global attribute.' );
			$this->assertEqualsCanonicalizing( $term_ids, array_values( $updated_attributes[ $taxonomy ]->get_options() ) );
			$this->assertTrue( $updated_attributes[ $taxonomy ]->get_visible() );
			$this->assertFalse( $updated_attributes[ $taxonomy ]->get_variation() );
			$this->assertEqualsCanonicalizing(
				$term_ids,
				wp_list_pluck( wp_get_object_terms( $product->get_id(), $taxonomy ), 'term_id' )
			);
		} finally {
			$this->delete_test_attribute( $attribute_data );
		}
	}

	/**
	 * Product types that should accept global attribute updates from quick edit.
	 *
	 * @return array
	 */
	public function data_provider_quick_edit_adds_attributes_for_product_types() {
		return array(
			'simple product'   => array( 'simple' ),
			'external product' => array( 'external' ),
			'grouped product'  => array( 'grouped' ),
			'variable product' => array( 'variable' ),
		);
	}

	/**
	 * @test
	 * @testdox Product attributes should be added to supported product types via quick edit.
	 * @dataProvider data_provider_quick_edit_adds_attributes_for_product_types
	 *
	 * @param string $product_type Product type to create.
	 */
	public function quick_edit_adds_product_attributes_for_product_types( $product_type ) {
		$attribute_data = WC_Helper_Product::create_attribute( 'Quick edit product type', array( 'Standard' ) );

		try {
			$taxonomy = $attribute_data['attribute_taxonomy'];
			$term_id  = $attribute_data['term_ids'][0];
			$product  = $this->create_product_by_type( $product_type );

			$this->quick_edit_save_product_attributes(
				$product,
				array( $taxonomy ),
				array(
					$taxonomy => array( $term_id ),
				)
			);

			$updated_product    = wc_get_product( $product->get_id() );
			$updated_attributes = $updated_product->get_attributes( 'edit' );

			$this->assertArrayHasKey( $taxonomy, $updated_attributes );
			$this->assertSame( array( $term_id ), array_values( $updated_attributes[ $taxonomy ]->get_options() ) );
		} finally {
			$this->delete_test_attribute( $attribute_data );
		}
	}

	/**
	 * @test
	 * @testdox Updating one product attribute via quick edit should not remove another submitted attribute.
	 */
	public function quick_edit_updates_one_product_attribute_without_removing_another() {
		$size_data  = WC_Helper_Product::create_attribute( 'Quick edit update size', array( 'Small', 'Large' ) );
		$style_data = WC_Helper_Product::create_attribute( 'Quick edit update style', array( 'Logo', 'Regular' ) );

		try {
			$size_taxonomy  = $size_data['attribute_taxonomy'];
			$style_taxonomy = $style_data['attribute_taxonomy'];
			$small_id       = $size_data['term_ids'][0];
			$large_id       = $size_data['term_ids'][1];
			$regular_id     = $style_data['term_ids'][1];
			$product        = WC_Helper_Product::create_simple_product();

			$product->set_attributes(
				array(
					$this->create_product_attribute( $size_data, array( $small_id ) ),
					$this->create_product_attribute( $style_data, array( $regular_id ) ),
				)
			);
			$product->save();

			$this->quick_edit_save_product_attributes(
				$product,
				array( $size_taxonomy, $style_taxonomy ),
				array(
					$size_taxonomy  => array( $large_id ),
					$style_taxonomy => array( $regular_id ),
				)
			);

			$updated_product    = wc_get_product( $product->get_id() );
			$updated_attributes = $updated_product->get_attributes( 'edit' );

			$this->assertSame( array( $large_id ), array_values( $updated_attributes[ $size_taxonomy ]->get_options() ) );
			$this->assertSame( array( $regular_id ), array_values( $updated_attributes[ $style_taxonomy ]->get_options() ) );
		} finally {
			$this->delete_test_attribute( $size_data );
			$this->delete_test_attribute( $style_data );
		}
	}

	/**
	 * @test
	 * @testdox Clearing one product attribute via quick edit should not remove another submitted attribute.
	 */
	public function quick_edit_clears_one_product_attribute_without_removing_another() {
		$size_data  = WC_Helper_Product::create_attribute( 'Quick edit clear size', array( 'Small', 'Large' ) );
		$style_data = WC_Helper_Product::create_attribute( 'Quick edit clear style', array( 'Logo', 'Regular' ) );

		try {
			$size_taxonomy  = $size_data['attribute_taxonomy'];
			$style_taxonomy = $style_data['attribute_taxonomy'];
			$small_id       = $size_data['term_ids'][0];
			$regular_id     = $style_data['term_ids'][1];
			$product        = WC_Helper_Product::create_simple_product();

			$product->set_attributes(
				array(
					$this->create_product_attribute( $size_data, array( $small_id ) ),
					$this->create_product_attribute( $style_data, array( $regular_id ) ),
				)
			);
			$product->save();

			$this->quick_edit_save_product_attributes(
				$product,
				array( $size_taxonomy, $style_taxonomy ),
				array(
					$style_taxonomy => array( $regular_id ),
				)
			);

			$updated_product    = wc_get_product( $product->get_id() );
			$updated_attributes = $updated_product->get_attributes( 'edit' );

			$this->assertArrayNotHasKey( $size_taxonomy, $updated_attributes );
			$this->assertArrayHasKey( $style_taxonomy, $updated_attributes );
			$this->assertSame( array( $regular_id ), array_values( $updated_attributes[ $style_taxonomy ]->get_options() ) );
			$this->assertSame( array(), wp_get_object_terms( $product->get_id(), $size_taxonomy ) );
		} finally {
			$this->delete_test_attribute( $size_data );
			$this->delete_test_attribute( $style_data );
		}
	}

	/**
	 * @test
	 * @testdox Product attributes should be removed when no terms are selected via quick edit.
	 */
	public function quick_edit_removes_product_attributes_when_no_terms_are_selected() {
		$attribute_data = WC_Helper_Product::create_attribute( 'Quick edit size', array( 'Small', 'Large' ) );

		try {
			$taxonomy         = $attribute_data['attribute_taxonomy'];
			$small_id         = $attribute_data['term_ids'][0];
			$product          = WC_Helper_Product::create_simple_product();
			$global_attribute = new WC_Product_Attribute();
			$custom_attribute = new WC_Product_Attribute();

			$global_attribute->set_id( $attribute_data['attribute_id'] );
			$global_attribute->set_name( $taxonomy );
			$global_attribute->set_options( array( $small_id ) );
			$global_attribute->set_visible( true );

			$custom_attribute->set_name( 'Material' );
			$custom_attribute->set_options( array( 'Cotton' ) );
			$custom_attribute->set_visible( true );

			$product->set_attributes( array( $global_attribute, $custom_attribute ) );
			$product->save();

			$this->login_as_administrator();

			$request_data = array(
				'woocommerce_quick_edit'                  => '1',
				'woocommerce_quick_edit_nonce'            => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
				'quick_edit_product_attribute_taxonomies' => array( $taxonomy ),
			);

			$sut = $this->get_sut_with_request_data( $request_data );

			$sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

			$updated_product    = wc_get_product( $product->get_id() );
			$updated_attributes = $updated_product->get_attributes( 'edit' );

			$this->assertArrayNotHasKey( $taxonomy, $updated_attributes, 'Quick edit should remove cleared global attributes.' );
			$this->assertArrayHasKey( 'material', $updated_attributes, 'Quick edit should preserve custom product attributes.' );
			$this->assertSame( array(), wp_get_object_terms( $product->get_id(), $taxonomy ), 'Quick edit should clear assigned product terms.' );
		} finally {
			$this->delete_test_attribute( $attribute_data );
		}
	}

	/**
	 * @test
	 * @testdox Variation attributes should not be removed from variable products when no terms are selected via quick edit.
	 */
	public function quick_edit_preserves_variable_product_variation_attributes_when_no_terms_are_selected() {
		$product          = WC_Helper_Product::create_variation_product();
		$taxonomy         = 'pa_size';
		$attributes       = $product->get_attributes( 'edit' );
		$original_options = array_values( $attributes[ $taxonomy ]->get_options() );

		$this->login_as_administrator();

		$request_data = array(
			'woocommerce_quick_edit'                  => '1',
			'woocommerce_quick_edit_nonce'            => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'quick_edit_product_attribute_taxonomies' => array( $taxonomy ),
		);

		$sut = $this->get_sut_with_request_data( $request_data );

		$sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );

		$updated_product    = wc_get_product( $product->get_id() );
		$updated_attributes = $updated_product->get_attributes( 'edit' );

		$this->assertArrayHasKey( $taxonomy, $updated_attributes, 'Quick edit should preserve variation attributes on variable products.' );
		$this->assertTrue( $updated_attributes[ $taxonomy ]->get_variation(), 'Quick edit should keep the attribute available for variations.' );
		$this->assertEqualsCanonicalizing( $original_options, array_values( $updated_attributes[ $taxonomy ]->get_options() ), 'Quick edit should keep the variation attribute terms.' );
		$this->assertEqualsCanonicalizing( $original_options, wp_list_pluck( wp_get_object_terms( $product->get_id(), $taxonomy ), 'term_id' ), 'Quick edit should keep the assigned variation attribute terms.' );
	}

	/**
	 * Save global attribute request data through the Quick Edit save handler.
	 *
	 * @param WC_Product $product Product to save.
	 * @param array      $taxonomies Submitted attribute taxonomies.
	 * @param array      $selected_terms Submitted term IDs keyed by taxonomy.
	 */
	private function quick_edit_save_product_attributes( $product, $taxonomies, $selected_terms = array() ) {
		$this->login_as_administrator();

		$request_data = array(
			'woocommerce_quick_edit'                  => '1',
			'woocommerce_quick_edit_nonce'            => wp_create_nonce( 'woocommerce_quick_edit_nonce' ),
			'quick_edit_product_attribute_taxonomies' => $taxonomies,
		);

		if ( ! empty( $selected_terms ) ) {
			$request_data['quick_edit_product_attributes'] = $selected_terms;
		}

		$sut = $this->get_sut_with_request_data( $request_data );
		$sut->bulk_and_quick_edit_save_post( $product->get_id(), get_post( $product->get_id() ) );
	}

	/**
	 * Create a product for quick edit product type coverage.
	 *
	 * @param string $product_type Product type.
	 * @return WC_Product
	 */
	private function create_product_by_type( $product_type ) {
		switch ( $product_type ) {
			case 'external':
				return WC_Helper_Product::create_external_product();
			case 'grouped':
				return WC_Helper_Product::create_grouped_product();
			case 'variable':
				return WC_Helper_Product::create_variation_product();
			case 'simple':
			default:
				return WC_Helper_Product::create_simple_product();
		}
	}

	/**
	 * Create a product attribute object from test attribute data.
	 *
	 * @param array $attribute_data Attribute data from WC_Helper_Product::create_attribute().
	 * @param array $term_ids Term IDs to set.
	 * @return WC_Product_Attribute
	 */
	private function create_product_attribute( $attribute_data, $term_ids ) {
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( $attribute_data['attribute_id'] );
		$attribute->set_name( $attribute_data['attribute_taxonomy'] );
		$attribute->set_options( $term_ids );
		$attribute->set_visible( true );

		return $attribute;
	}

	/**
	 * Delete a test attribute and clear its taxonomy caches.
	 *
	 * @param array $attribute_data Attribute data from WC_Helper_Product::create_attribute().
	 */
	private function delete_test_attribute( $attribute_data ) {
		global $wc_product_attributes;

		WC_Helper_Product::delete_attribute( $attribute_data['attribute_id'] );
		unregister_taxonomy( $attribute_data['attribute_taxonomy'] );
		unset( $wc_product_attributes[ $attribute_data['attribute_taxonomy'] ] );
		delete_transient( 'wc_attribute_taxonomies' );
		WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
	}
}
