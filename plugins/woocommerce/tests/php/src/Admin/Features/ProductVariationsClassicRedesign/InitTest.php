<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\ProductVariationsClassicRedesign;

use Automattic\WooCommerce\Admin\Features\ProductVariationsClassicRedesign\Init;
use WC_Helper_Product;
use WC_Product_Attribute;
use WC_Unit_Test_Case;

/**
 * Tests for the Init class's preserve_variation_attributes handler.
 */
class InitTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Init
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Init();
	}

	/**
	 * @testdox Should merge DB variation attributes back into the in-flight product when form POST save runs.
	 */
	public function test_preserve_variation_attributes_merges_db_variation_attributes(): void {
		$product = WC_Helper_Product::create_variation_product();

		$variation_attrs = array_filter(
			$product->get_attributes(),
			array( 'WC_Meta_Box_Product_Data', 'filter_variation_attributes' )
		);
		$this->assertNotEmpty( $variation_attrs, 'Test requires a product with variation attributes.' );

		// Simulate form POST: product in-flight has only non-variation attributes.
		// set_attributes fills existing keys with null (WC "to be deleted" marker) before
		// setting provided values, so the in-flight product retains null entries for every
		// variation attribute that was filtered out of the rendered tab.
		$non_variation_attrs = array_filter(
			$product->get_attributes(),
			array( 'WC_Meta_Box_Product_Data', 'filter_non_variation_attributes' )
		);
		$product->set_attributes( $non_variation_attrs );

		// The DB was NOT modified — the handler must read variation attrs from there.
		$this->sut->preserve_variation_attributes( $product );

		$result_attributes = $product->get_attributes();

		foreach ( array_keys( $variation_attrs ) as $key ) {
			$this->assertArrayHasKey(
				$key,
				$result_attributes,
				"Variation attribute '{$key}' should be preserved after form POST save."
			);
			$this->assertInstanceOf(
				WC_Product_Attribute::class,
				$result_attributes[ $key ],
				"Preserved attribute '{$key}' should be a WC_Product_Attribute instance."
			);
			$this->assertTrue(
				$result_attributes[ $key ]->get_variation(),
				"Preserved attribute '{$key}' should still have variation=true."
			);
		}
	}

	/**
	 * @testdox Should not modify a simple product (non-variable) when handler runs.
	 */
	public function test_preserve_variation_attributes_is_no_op_for_simple_product(): void {
		$product = WC_Helper_Product::create_simple_product();

		$before_attributes = $product->get_attributes();

		$this->sut->preserve_variation_attributes( $product );

		$this->assertSame(
			$before_attributes,
			$product->get_attributes(),
			'Simple product attributes should be unchanged after handler runs.'
		);
	}

	/**
	 * @testdox Should preserve variation attributes when product has only variation attributes (empty form POST).
	 */
	public function test_preserve_variation_attributes_handles_variation_only_product(): void {
		$product = WC_Helper_Product::create_variation_product();

		$variation_attrs = array_filter(
			$product->get_attributes(),
			array( 'WC_Meta_Box_Product_Data', 'filter_variation_attributes' )
		);
		$this->assertNotEmpty( $variation_attrs, 'Test requires a product with at least one variation attribute.' );

		// Simulate form POST with no attributes at all (all were variation=true, filtered from the tab).
		$product->set_attributes( array() );

		// The DB was NOT modified — variation attributes are still in the database.
		$this->sut->preserve_variation_attributes( $product );

		$result_attributes = $product->get_attributes();

		foreach ( array_keys( $variation_attrs ) as $key ) {
			$this->assertArrayHasKey(
				$key,
				$result_attributes,
				"Variation-only product: attribute '{$key}' should be re-merged after preservation handler runs."
			);
		}
	}

	/**
	 * @testdox Should preserve both variation and non-variation attributes when a mixed product is updated.
	 */
	public function test_preserve_variation_attributes_keeps_non_variation_attributes(): void {
		$product = WC_Helper_Product::create_variation_product();

		// Add a non-variation attribute so we have a mixed set to work with.
		$custom_attr = new WC_Product_Attribute();
		$custom_attr->set_id( 0 );
		$custom_attr->set_name( 'Material' );
		$custom_attr->set_options( array( 'Cotton', 'Polyester' ) );
		$custom_attr->set_position( 10 );
		$custom_attr->set_visible( true );
		$custom_attr->set_variation( false );

		$existing_attributes             = $product->get_attributes();
		$existing_attributes['material'] = $custom_attr;
		$product->set_attributes( $existing_attributes );
		$product->save();

		$variation_attrs = array_filter(
			$product->get_attributes(),
			array( 'WC_Meta_Box_Product_Data', 'filter_variation_attributes' )
		);
		$non_variation_attrs = array_filter(
			array_filter( $product->get_attributes() ),
			array( 'WC_Meta_Box_Product_Data', 'filter_non_variation_attributes' )
		);

		$this->assertNotEmpty( $non_variation_attrs, 'Test requires at least one non-variation attribute.' );

		// Simulate form POST: only the non-variation attributes are in the submitted form.
		$product->set_attributes( $non_variation_attrs );

		// The DB was NOT modified after the last save — variation attributes are still persisted.
		$this->sut->preserve_variation_attributes( $product );

		$result_attributes = $product->get_attributes();

		foreach ( array_keys( $variation_attrs ) as $key ) {
			$this->assertArrayHasKey( $key, $result_attributes, "Variation attribute '{$key}' should be present after preservation." );
		}
		foreach ( array_keys( $non_variation_attrs ) as $key ) {
			$this->assertArrayHasKey( $key, $result_attributes, "Non-variation attribute '{$key}' should still be present after preservation." );
		}
	}
}
