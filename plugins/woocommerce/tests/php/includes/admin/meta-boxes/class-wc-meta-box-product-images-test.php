<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Meta_Box_Product_Images class.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

/**
 * Class WC_Meta_Box_Product_Images_Test
 */
class WC_Meta_Box_Product_Images_Test extends WC_Unit_Test_Case {

	/**
	 * Original POST data.
	 *
	 * @var array
	 */
	private $original_post;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_POST = $this->original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::tearDown();
	}

	/**
	 * @testdox Should save posted product image IDs.
	 */
	public function test_save_saves_posted_product_image_ids(): void {
		$product = WC_Helper_Product::create_variation_product();

		$saved_product = $this->save_product_images(
			$product,
			array(
				'product-type'         => 'variable',
				'wc_product_image_ids' => '12,34,56',
			)
		);

		$this->assertEquals( 12, $saved_product->get_image_id( 'edit' ), 'The first posted image ID should be saved as the featured image.' );
		$this->assertSame( array( 34, 56 ), $saved_product->get_gallery_image_ids( 'edit' ), 'Remaining posted image IDs should be saved as gallery images.' );
		$this->assertTrue( $saved_product->is_type( 'variable' ), 'Posted product type should be used while saving.' );
	}

	/**
	 * @testdox Should clear product image IDs when posted image IDs are empty.
	 */
	public function test_save_clears_product_image_ids_when_posted_image_ids_are_empty(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( 12 );
		$product->set_gallery_image_ids( array( 34 ) );
		$product->save();

		$saved_product = $this->save_product_images(
			$product,
			array(
				'product-type'         => 'simple',
				'wc_product_image_ids' => '',
			)
		);

		$this->assertEquals( 0, $saved_product->get_image_id( 'edit' ), 'Empty posted image IDs should clear the featured image.' );
		$this->assertSame( array(), $saved_product->get_gallery_image_ids( 'edit' ), 'Empty posted image IDs should clear gallery images.' );
	}

	/**
	 * @testdox Should filter sparse posted product image IDs.
	 */
	public function test_save_filters_sparse_posted_product_image_ids(): void {
		$product = WC_Helper_Product::create_simple_product();

		$saved_product = $this->save_product_images(
			$product,
			array(
				'product-type'         => 'simple',
				'wc_product_image_ids' => '12,,34, ,56',
			)
		);

		$this->assertEquals( 12, $saved_product->get_image_id( 'edit' ), 'The first sparse posted image ID should be saved as the featured image.' );
		$this->assertSame( array( 34, 56 ), $saved_product->get_gallery_image_ids( 'edit' ), 'Sparse posted gallery image IDs should be filtered before saving.' );
	}

	/**
	 * @testdox Should normalize array-shaped posted image fields.
	 */
	public function test_save_normalizes_array_shaped_posted_image_fields(): void {
		$product = WC_Helper_Product::create_simple_product();

		$saved_product = $this->save_product_images(
			$product,
			array(
				'product-type'         => array( 'simple' ),
				'wc_product_image_ids' => array( '12', '34,56', array( '78' ) ),
			)
		);

		$this->assertEquals( 12, $saved_product->get_image_id( 'edit' ), 'The first normalized image ID should be saved as the featured image.' );
		$this->assertSame( array( 34, 56 ), $saved_product->get_gallery_image_ids( 'edit' ), 'Array-shaped image ID fields should be normalized before saving.' );
		$this->assertTrue( $saved_product->is_type( 'simple' ), 'Invalid product type input should fall back to the stored product type.' );
	}

	/**
	 * @testdox Should fall back to the stored product type when none is posted.
	 */
	public function test_save_falls_back_to_stored_product_type_when_none_is_posted(): void {
		$product = WC_Helper_Product::create_simple_product();

		$saved_product = $this->save_product_images(
			$product,
			array(
				'wc_product_image_ids' => '12,34',
			)
		);

		$this->assertEquals( 12, $saved_product->get_image_id( 'edit' ), 'The first image ID should be saved when product type falls back to the stored value.' );
		$this->assertSame( array( 34 ), $saved_product->get_gallery_image_ids( 'edit' ), 'Gallery image IDs should be saved when product type falls back to the stored value.' );
		$this->assertTrue( $saved_product->is_type( 'simple' ), 'Stored product type should be used when no product type is posted.' );
	}

	/**
	 * @testdox Should prefer the legacy gallery field when both image fields are posted.
	 */
	public function test_save_prefers_legacy_gallery_field_when_both_fields_are_posted(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( 99 );
		$product->save();

		$saved_product = $this->save_product_images(
			$product,
			array(
				'product-type'          => 'simple',
				'_thumbnail_id'         => '12',
				'wc_product_image_ids'  => '12,34,56',
				'product_image_gallery' => '34,56',
			)
		);

		$this->assertEquals( 99, $saved_product->get_image_id( 'edit' ), 'The meta box save handler should leave featured image persistence to WordPress core.' );
		$this->assertSame( array( 34, 56 ), $saved_product->get_gallery_image_ids( 'edit' ), 'The legacy gallery field should remain the canonical gallery value.' );
	}

	/**
	 * Save product images from POST data.
	 *
	 * @param WC_Product $product Product to save.
	 * @param array      $post_data POST data for the save handler.
	 * @return WC_Product
	 */
	private function save_product_images( WC_Product $product, array $post_data ): WC_Product {
		$_POST = $post_data; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		WC_Meta_Box_Product_Images::save( $product->get_id(), get_post( $product->get_id() ) );

		return wc_get_product( $product->get_id() );
	}
}
