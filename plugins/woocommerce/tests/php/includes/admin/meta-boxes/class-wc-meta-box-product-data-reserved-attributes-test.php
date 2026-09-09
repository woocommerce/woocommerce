<?php
declare( strict_types = 1 );

/**
 * Tests for reserved attribute name handling in the product meta box save path.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

/**
 * Class WC_Meta_Box_Product_Data_Reserved_Attributes_Test
 */
class WC_Meta_Box_Product_Data_Reserved_Attributes_Test extends WC_Unit_Test_Case {

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		WC_Admin_Meta_Boxes::$meta_box_errors = array();
		$_POST                                = array();
		parent::tearDown();
	}

	/**
	 * Build the $_POST attribute payload for the meta box save, with the given custom
	 * attribute name plus a non-reserved "Material" attribute.
	 *
	 * @param string $name Custom attribute name to include.
	 * @return array
	 */
	private function attribute_post_data( string $name ): array {
		return array(
			'product-type'         => 'variable',
			'attribute_names'      => array( $name, 'Material' ),
			'attribute_values'     => array( 'One | Two', 'Cotton | Wool' ),
			'attribute_position'   => array( 0, 1 ),
			'attribute_visibility' => array( 1, 1 ),
			'attribute_variation'  => array( 1, 1 ),
		);
	}

	/**
	 * Get the sanitized names of the custom attributes stored on a product.
	 *
	 * @param WC_Product $product The product.
	 * @return string[]
	 */
	private function saved_attribute_slugs( WC_Product $product ): array {
		$slugs = array();
		foreach ( $product->get_attributes( 'edit' ) as $attribute ) {
			$slugs[] = sanitize_title( $attribute->get_name() );
		}

		return $slugs;
	}

	/**
	 * @testdox Saving a product via the meta box drops a new reserved custom attribute, keeps the others, and records a notice.
	 */
	public function test_save_drops_reserved_attribute_and_adds_notice(): void {
		WC_Admin_Meta_Boxes::$meta_box_errors = array();
		$product                              = new WC_Product_Variable();
		$product->set_name( 'Meta box reserved attribute' );
		$product->save();

		$_POST = $this->attribute_post_data( 'variation' );
		// save() runs a Point of Sale visibility check that calls a function trunk deprecated
		// after this branch; that unrelated deprecation is not under test here.
		remove_all_actions( 'deprecated_function_run' );
		WC_Meta_Box_Product_Data::save( $product->get_id(), get_post( $product->get_id() ) );

		$slugs = $this->saved_attribute_slugs( wc_get_product( $product->get_id() ) );

		$this->assertNotContains( 'variation', $slugs, 'The reserved attribute should be dropped.' );
		$this->assertContains( 'material', $slugs, 'Non-reserved attributes should be kept.' );
		$this->assertNotEmpty( WC_Admin_Meta_Boxes::$meta_box_errors, 'A reserved-term notice should be recorded.' );

		$product->delete( true );
	}

	/**
	 * @testdox Saving a product via the meta box keeps a grandfathered reserved custom attribute and records no notice.
	 */
	public function test_save_grandfathers_existing_reserved_attribute(): void {
		WC_Admin_Meta_Boxes::$meta_box_errors = array();
		$product                              = new WC_Product_Variable();
		$product->set_name( 'Meta box grandfathered attribute' );
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'variation' );
		$attribute->set_options( array( 'One', 'Two' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$_POST = $this->attribute_post_data( 'variation' );
		// save() runs a Point of Sale visibility check that calls a function trunk deprecated
		// after this branch; that unrelated deprecation is not under test here.
		remove_all_actions( 'deprecated_function_run' );
		WC_Meta_Box_Product_Data::save( $product->get_id(), get_post( $product->get_id() ) );

		$slugs = $this->saved_attribute_slugs( wc_get_product( $product->get_id() ) );

		$this->assertContains( 'variation', $slugs, 'A grandfathered reserved attribute should be kept.' );
		$this->assertEmpty( WC_Admin_Meta_Boxes::$meta_box_errors, 'No reserved-term notice should be recorded for a grandfathered attribute.' );

		$product->delete( true );
	}
}
