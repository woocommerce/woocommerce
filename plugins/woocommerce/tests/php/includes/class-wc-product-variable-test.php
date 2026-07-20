<?php

use Automattic\WooCommerce\Internal\VariationGallery\Package;

/**
 * Tests for WC_Product_Variable.
 */
class WC_Product_Variable_Test extends \WC_Unit_Test_Case {
	/**
	 * Reset variation gallery feature-flag option leaked by individual tests.
	 */
	public function tearDown(): void {
		delete_option( Package::ENABLE_OPTION_NAME );
		parent::tearDown();
	}

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
	 * @testdox 'get_available_variations' with 'array' return includes image and gallery data for variations that have images set.
	 */
	public function test_get_available_variations_array_includes_image_data_when_variation_has_images(): void {
		update_option( Package::ENABLE_OPTION_NAME, 'yes' );

		$image_id   = $this->create_image_attachment( 'Variation Image', 'variation-image.jpg' );
		$gallery_id = $this->create_image_attachment( 'Variation Gallery Image', 'variation-gallery.jpg' );

		$product   = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );
		$variation->set_image_id( $image_id );
		$variation->set_gallery_image_ids( array( $gallery_id ) );
		$variation->save();

		$variations = $product->get_available_variations( 'array' );

		$this->assertSame( $image_id, $variations[0]['image_id'] );
		$this->assertSame( array( $gallery_id ), $variations[0]['gallery_image_ids'] );

		$product->delete( true );
	}

	/**
	 * @testdox 'get_available_variation' exposes typed variation gallery image IDs.
	 */
	public function test_get_available_variation_includes_gallery_image_ids() {
		update_option( Package::ENABLE_OPTION_NAME, 'yes' );

		$product   = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );
		$image_id  = wp_insert_attachment(
			array(
				'post_title'     => 'Variation Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$image_ids = array(
			wp_insert_attachment(
				array(
					'post_title'     => 'Variation Gallery Image 1',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			),
			wp_insert_attachment(
				array(
					'post_title'     => 'Variation Gallery Image 2',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			),
		);

		update_post_meta( $image_id, '_wp_attached_file', 'variation-featured.jpg' );
		foreach ( $image_ids as $i => $gallery_image_id ) {
			update_post_meta( $gallery_image_id, '_wp_attached_file', 'variation-gallery-' . ( $i + 1 ) . '.jpg' );
		}

		$variation->set_image_id( $image_id );
		$variation->set_gallery_image_ids( $image_ids );
		$variation->save();

		$available_variation = $product->get_available_variation( $variation );

		$this->assertSame( $image_ids, $available_variation['gallery_image_ids'] );
		$this->assertNotEmpty( $available_variation['gallery_images_html'] );
	}

	/**
	 * @testdox 'get_available_variation' omits multi-image gallery data when the variation gallery feature flag is disabled.
	 */
	public function test_get_available_variation_returns_single_image_shape_when_feature_flag_disabled() {
		update_option( Package::ENABLE_OPTION_NAME, 'no' );

		$product   = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );
		$image_id  = wp_insert_attachment(
			array(
				'post_title'     => 'Variation Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$image_ids = array(
			wp_insert_attachment(
				array(
					'post_title'     => 'Variation Gallery Image 1',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			),
			wp_insert_attachment(
				array(
					'post_title'     => 'Variation Gallery Image 2',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			),
		);

		update_post_meta( $image_id, '_wp_attached_file', 'variation-disabled.jpg' );

		$variation->set_image_id( $image_id );
		$variation->set_gallery_image_ids( $image_ids );
		$variation->save();

		$available_variation = $product->get_available_variation( $variation );

		$this->assertSame( array(), $available_variation['gallery_image_ids'] );
		$this->assertSame( '', $available_variation['gallery_images_html'] );
		$this->assertSame( $image_id, $available_variation['image_id'] );
	}

	/**
	 * @testdox 'get_available_variation' falls back to the variation's own gallery when the variation featured image is stale.
	 */
	public function test_get_available_variation_falls_back_to_variation_gallery_when_featured_is_stale() {
		update_option( Package::ENABLE_OPTION_NAME, 'yes' );

		$product              = WC_Helper_Product::create_variation_product();
		$variation            = wc_get_product( $product->get_children()[0] );
		$parent_featured_id   = $this->create_image_attachment( 'Parent Featured Image', 'parent-featured.jpg' );
		$stale_featured_id    = $this->create_image_attachment( 'Stale Variation Image', 'stale-featured.jpg' );
		$variation_gallery_id = $this->create_image_attachment( 'Variation Gallery Image', 'variation-gallery.jpg' );

		// Delete-then-assign: set_image_id() doesn't validate the attachment,
		// but wp_delete_attachment() would clear _thumbnail_id on any post
		// pointing at it. Doing it in this order leaves the variation
		// referencing a deleted attachment, which is the bug we're testing.

		$product->set_image_id( $parent_featured_id );
		$product->save();

		wp_delete_attachment( $stale_featured_id, true );

		$variation->set_image_id( $stale_featured_id );
		$variation->set_gallery_image_ids( array( $variation_gallery_id ) );
		$variation->save();

		$available_variation = $product->get_available_variation( $variation );

		$this->assertSame( $variation_gallery_id, $available_variation['image_id'] );
		$this->assertStringContainsString( 'variation-gallery.jpg', $available_variation['gallery_images_html'] );
		$this->assertStringNotContainsString( 'parent-featured.jpg', $available_variation['gallery_images_html'] );
	}

	/**
	 * @testdox 'get_available_variation' falls back to the parent featured image when both the variation featured image and gallery are absent.
	 */
	public function test_get_available_variation_falls_back_to_parent_featured_when_variation_has_no_images() {
		update_option( Package::ENABLE_OPTION_NAME, 'yes' );

		$product            = WC_Helper_Product::create_variation_product();
		$variation          = wc_get_product( $product->get_children()[0] );
		$parent_featured_id = $this->create_image_attachment( 'Parent Featured Image', 'parent-featured.jpg' );
		$stale_featured_id  = $this->create_image_attachment( 'Stale Variation Image', 'stale-featured.jpg' );

		$product->set_image_id( $parent_featured_id );
		$product->save();

		wp_delete_attachment( $stale_featured_id, true );

		$variation->set_image_id( $stale_featured_id );
		$variation->set_gallery_image_ids( array() );
		$variation->save();

		$available_variation = $product->get_available_variation( $variation );

		$this->assertSame( $parent_featured_id, $available_variation['image_id'] );
		$this->assertSame( '', $available_variation['gallery_images_html'] );
	}

	/**
	 * Create a real image attachment that passes `wp_attachment_is_image()`.
	 *
	 * @param string $title         Post title.
	 * @param string $attached_file Synthetic file path.
	 * @return int
	 */
	private function create_image_attachment( string $title, string $attached_file ): int {
		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => $title,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		update_post_meta( $attachment_id, '_wp_attached_file', $attached_file );

		return $attachment_id;
	}
}
