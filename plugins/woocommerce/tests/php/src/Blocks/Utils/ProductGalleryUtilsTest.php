<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;
use WP_UnitTestCase;

/**
 * Tests for the ProductGalleryUtils class.
 */
class ProductGalleryUtilsTest extends \WP_UnitTestCase {
	/**
	 * Reset feature-flag options leaked by individual tests.
	 */
	public function tearDown(): void {
		delete_option( ProductMediaGallery::ENABLE_OPTION_NAME );
		delete_option( \Automattic\WooCommerce\Internal\VariationGallery\Package::ENABLE_OPTION_NAME );
		parent::tearDown();
	}

	/**
	 * Test get_product_gallery_image_data method.
	 */
	public function test_get_product_gallery_image_data() {
		update_option( \Automattic\WooCommerce\Internal\VariationGallery\Package::ENABLE_OPTION_NAME, 'yes' );

		// Create the variable product.
		$variable_product = \WC_Helper_Product::create_variation_product();

		// Create and set the main product image.
		$image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Test Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$variable_product->set_image_id( $image_id );
		update_post_meta( $image_id, '_wp_attached_file', 'product-featured.jpg' );

		// Create a variation image but don't add it to the gallery.
		$variation_image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Variation Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		update_post_meta( $variation_image_id, '_wp_attached_file', 'variation-featured.jpg' );

		// Get the variations.
		$variations = $variable_product->get_children();
		if ( ! empty( $variations ) ) {
			$variation = wc_get_product( $variations[0] );
			$variation->set_image_id( $variation_image_id );
			$variation->save();
		}

		// Create and set gallery images (separate from the variation image).
		$gallery_image_ids = array(
			wp_insert_attachment(
				array(
					'post_title'     => 'Gallery Image 1',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			),
			wp_insert_attachment(
				array(
					'post_title'     => 'Gallery Image 2',
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			),
		);
		$variable_product->set_gallery_image_ids( $gallery_image_ids );
		$variable_product->save();
		foreach ( $gallery_image_ids as $i => $gallery_image_id ) {
			update_post_meta( $gallery_image_id, '_wp_attached_file', 'product-gallery-' . ( $i + 1 ) . '.jpg' );
		}

		$variation_gallery_image_ids = array(
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
		foreach ( $variation_gallery_image_ids as $i => $variation_gallery_image_id ) {
			update_post_meta( $variation_gallery_image_id, '_wp_attached_file', 'variation-gallery-' . ( $i + 1 ) . '.jpg' );
		}

		if ( isset( $variation ) ) {
			$variation->set_gallery_image_ids( $variation_gallery_image_ids );
			$variation->save();
		}

		$image_data = ProductGalleryUtils::get_product_gallery_image_data( $variable_product, 'woocommerce_thumbnail' );

		// Assert that $image_data is a non-empty array.
		$this->assertIsArray( $image_data );
		$this->assertNotEmpty( $image_data );

		// Assert that each item in $image_data has required keys and correct types.
		foreach ( $image_data as $image ) {
			$this->assertIsArray( $image );
			$this->assertArrayHasKey( 'id', $image );
			$this->assertArrayHasKey( 'sizes', $image );
			$this->assertArrayHasKey( 'srcset', $image );
			$this->assertArrayHasKey( 'src', $image );
		}

		// Assert that the child product image is included in the image data array.
		$ids = array_column( $image_data, 'id' );
		$this->assertContains( $variation_image_id, $ids );
		foreach ( $variation_gallery_image_ids as $variation_gallery_image_id ) {
			$this->assertContains( $variation_gallery_image_id, $ids );
		}

		// Clean up.
		$variable_product->delete( true );
		wp_delete_attachment( $image_id, true );
		wp_delete_attachment( $variation_image_id, true );
		foreach ( $gallery_image_ids as $gallery_image_id ) {
			wp_delete_attachment( $gallery_image_id, true );
		}
		foreach ( $variation_gallery_image_ids as $variation_gallery_image_id ) {
			wp_delete_attachment( $variation_gallery_image_id, true );
		}
	}

	/**
	 * Test that get_product_variation_gallery_data returns the single-image
	 * shape when the variation gallery feature flag is disabled, even when
	 * the variation has multiple gallery images saved.
	 */
	public function test_get_product_variation_gallery_data_returns_single_image_when_feature_flag_disabled() {
		update_option( \Automattic\WooCommerce\Internal\VariationGallery\Package::ENABLE_OPTION_NAME, 'no' );

		$variable_product = \WC_Helper_Product::create_variation_product();

		$variation_image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Variation Featured Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		update_post_meta( $variation_image_id, '_wp_attached_file', 'variation-featured.jpg' );

		$variation_gallery_image_ids = array(
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

		$variation = wc_get_product( $variable_product->get_children()[0] );
		$variation->set_image_id( $variation_image_id );
		$variation->set_gallery_image_ids( $variation_gallery_image_ids );
		$variation->save();

		$variation_entry = ProductGalleryUtils::get_product_variation_gallery_data( $variable_product )[ $variation->get_id() ];

		$this->assertSame( $variation_image_id, $variation_entry['image_id'] );
		$this->assertSame( array( $variation_image_id ), $variation_entry['image_ids'] );
	}

	/**
	 * Test that variation gallery data falls back to the variation's own gallery when the variation featured image is stale.
	 */
	public function test_get_product_variation_gallery_data_falls_back_to_variation_gallery_when_featured_is_stale() {
		update_option( \Automattic\WooCommerce\Internal\VariationGallery\Package::ENABLE_OPTION_NAME, 'yes' );

		$variable_product     = \WC_Helper_Product::create_variation_product();
		$parent_featured_id   = $this->create_image_attachment( 'Parent Featured Image', 'parent-featured.jpg' );
		$stale_featured_id    = $this->create_image_attachment( 'Stale Variation Image', 'stale-featured.jpg' );
		$variation_gallery_id = $this->create_image_attachment( 'Variation Gallery Image', 'variation-gallery.jpg' );

		$variable_product->set_image_id( $parent_featured_id );
		$variable_product->save();

		$variation = wc_get_product( $variable_product->get_children()[0] );

		// Delete-then-assign: leaves the variation referencing a deleted
		// attachment (the bug we're testing). Reversing the order would let
		// wp_delete_attachment clear _thumbnail_id automatically.
		wp_delete_attachment( $stale_featured_id, true );

		$variation->set_image_id( $stale_featured_id );
		$variation->set_gallery_image_ids( array( $variation_gallery_id ) );
		$variation->save();

		$variation_gallery_data = ProductGalleryUtils::get_product_variation_gallery_data( $variable_product );

		$this->assertSame( $variation_gallery_id, $variation_gallery_data[ $variation->get_id() ]['image_id'] );
		$this->assertSame(
			array( $variation_gallery_id ),
			$variation_gallery_data[ $variation->get_id() ]['image_ids']
		);
	}

	/**
	 * Variation has only its own featured image (no gallery) → the
	 * variation featured replaces the parent's hero, parent gallery extras
	 * stay. Applies whether the feature flag is on or off.
	 */
	public function test_get_product_variation_gallery_data_case_3_single_image_appends_parent_gallery_extras() {
		$parent_featured_id     = $this->create_image_attachment( 'Parent Featured', 'parent-featured.jpg' );
		$parent_gallery_extra_a = $this->create_image_attachment( 'Parent Gallery A', 'parent-gallery-a.jpg' );
		$parent_gallery_extra_b = $this->create_image_attachment( 'Parent Gallery B', 'parent-gallery-b.jpg' );
		$variation_featured_id  = $this->create_image_attachment( 'Variation Featured', 'variation-featured.jpg' );

		$entry = $this->create_variation_gallery_entry(
			$parent_featured_id,
			array( $parent_gallery_extra_a, $parent_gallery_extra_b ),
			$variation_featured_id
		);

		$this->assertSame( $variation_featured_id, $entry['image_id'] );
		$this->assertSame(
			array( $variation_featured_id, $parent_gallery_extra_a, $parent_gallery_extra_b ),
			$entry['image_ids']
		);
	}

	/**
	 * Variation has its own featured plus gallery images (feature flag
	 * on) → the variation's images replace the parent's entirely.
	 */
	public function test_get_product_variation_gallery_data_case_4_multiple_images_replaces_parent_set() {
		$parent_featured_id     = $this->create_image_attachment( 'Parent Featured', 'parent-featured.jpg' );
		$parent_gallery_extra   = $this->create_image_attachment( 'Parent Gallery', 'parent-gallery.jpg' );
		$variation_featured_id  = $this->create_image_attachment( 'Variation Featured', 'variation-featured.jpg' );
		$variation_gallery_id_a = $this->create_image_attachment( 'Variation Gallery A', 'variation-gallery-a.jpg' );
		$variation_gallery_id_b = $this->create_image_attachment( 'Variation Gallery B', 'variation-gallery-b.jpg' );

		$entry = $this->create_variation_gallery_entry(
			$parent_featured_id,
			array( $parent_gallery_extra ),
			$variation_featured_id,
			array( $variation_gallery_id_a, $variation_gallery_id_b )
		);

		$this->assertSame( $variation_featured_id, $entry['image_id'] );
		$this->assertSame(
			array( $variation_featured_id, $variation_gallery_id_a, $variation_gallery_id_b ),
			$entry['image_ids']
		);
	}

	/**
	 * (Edge case - legacy AVI shape): variation has gallery images but no own featured →
	 * parent featured anchors the set, variation gallery follows. No parent
	 * gallery extras appear — the variation owns the rest of the lineup.
	 */
	public function test_get_product_variation_gallery_data_case_5_avi_shape_uses_parent_featured_plus_variation_gallery() {
		$parent_featured_id     = $this->create_image_attachment( 'Parent Featured', 'parent-featured.jpg' );
		$parent_gallery_extra   = $this->create_image_attachment( 'Parent Gallery Extra', 'parent-gallery-extra.jpg' );
		$variation_gallery_id_a = $this->create_image_attachment( 'Variation Gallery A', 'variation-gallery-a.jpg' );
		$variation_gallery_id_b = $this->create_image_attachment( 'Variation Gallery B', 'variation-gallery-b.jpg' );

		$entry = $this->create_variation_gallery_entry(
			$parent_featured_id,
			array( $parent_gallery_extra ),
			0,
			array( $variation_gallery_id_a, $variation_gallery_id_b )
		);

		$this->assertSame( $parent_featured_id, $entry['image_id'] );
		$this->assertSame(
			array( $parent_featured_id, $variation_gallery_id_a, $variation_gallery_id_b ),
			$entry['image_ids']
		);
	}

	/**
	 * Variation has no images of its own → full parent gallery
	 * (featured + extras) is used as a fallback.
	 */
	public function test_get_product_variation_gallery_data_case_2_no_variation_images_uses_full_parent_gallery() {
		$parent_featured_id   = $this->create_image_attachment( 'Parent Featured', 'parent-featured.jpg' );
		$parent_gallery_extra = $this->create_image_attachment( 'Parent Gallery Extra', 'parent-gallery-extra.jpg' );

		$entry = $this->create_variation_gallery_entry(
			$parent_featured_id,
			array( $parent_gallery_extra )
		);

		$this->assertSame( $parent_featured_id, $entry['image_id'] );
		$this->assertSame(
			array( $parent_featured_id, $parent_gallery_extra ),
			$entry['image_ids']
		);
	}

	/**
	 * Feature flag off: Variation gallery is treated as empty even if rows exist in postmeta,
	 * so the single-image rule applies (variation featured + parent gallery extras).
	 */
	public function test_get_product_variation_gallery_data_case_3_applies_with_feature_flag_off() {
		$parent_featured_id     = $this->create_image_attachment( 'Parent Featured', 'parent-featured.jpg' );
		$parent_gallery_extra   = $this->create_image_attachment( 'Parent Gallery Extra', 'parent-gallery-extra.jpg' );
		$variation_featured_id  = $this->create_image_attachment( 'Variation Featured', 'variation-featured.jpg' );
		$variation_gallery_id_a = $this->create_image_attachment( 'Variation Gallery A (ignored)', 'variation-gallery-a.jpg' );

		$entry = $this->create_variation_gallery_entry(
			$parent_featured_id,
			array( $parent_gallery_extra ),
			$variation_featured_id,
			array( $variation_gallery_id_a ),
			'no'
		);

		$this->assertSame( $variation_featured_id, $entry['image_id'] );
		$this->assertSame(
			array( $variation_featured_id, $parent_gallery_extra ),
			$entry['image_ids']
		);
	}

	/**
	 * The variation featured is also present in the parent gallery — output
	 * must dedup so the image doesn't render twice in a row.
	 */
	public function test_get_product_variation_gallery_data_dedups_variation_featured_overlapping_parent_gallery() {
		$parent_featured_id   = $this->create_image_attachment( 'Parent Featured', 'parent-featured.jpg' );
		$shared_id            = $this->create_image_attachment( 'Shared Image', 'shared.jpg' );
		$parent_gallery_extra = $this->create_image_attachment( 'Parent Gallery Extra', 'parent-gallery-extra.jpg' );

		$entry = $this->create_variation_gallery_entry(
			$parent_featured_id,
			array( $shared_id, $parent_gallery_extra ),
			$shared_id
		);

		$this->assertSame( $shared_id, $entry['image_id'] );
		$this->assertSame(
			array( $shared_id, $parent_gallery_extra ),
			$entry['image_ids']
		);
	}

	/**
	 * Create a variation gallery fixture and return the selected variation entry.
	 *
	 * @param int    $parent_featured_id    Parent product featured image ID.
	 * @param int[]  $parent_gallery_ids    Parent product gallery image IDs.
	 * @param int    $variation_featured_id Variation featured image ID.
	 * @param int[]  $variation_gallery_ids Variation gallery image IDs.
	 * @param string $feature_flag          Variation gallery feature flag value.
	 * @return array<string, mixed>
	 */
	private function create_variation_gallery_entry(
		int $parent_featured_id,
		array $parent_gallery_ids = array(),
		int $variation_featured_id = 0,
		array $variation_gallery_ids = array(),
		string $feature_flag = 'yes'
	): array {
		update_option( \Automattic\WooCommerce\Internal\VariationGallery\Package::ENABLE_OPTION_NAME, $feature_flag );

		$variable_product = \WC_Helper_Product::create_variation_product();
		$variable_product->set_image_id( $parent_featured_id );
		$variable_product->set_gallery_image_ids( $parent_gallery_ids );
		$variable_product->save();

		$variation = wc_get_product( $variable_product->get_children()[0] );
		$variation->set_image_id( $variation_featured_id );
		$variation->set_gallery_image_ids( $variation_gallery_ids );
		$variation->save();

		return ProductGalleryUtils::get_product_variation_gallery_data( $variable_product )[ $variation->get_id() ];
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

	/**
	 * Test get_product_gallery_media_data method with legacy gallery images and no featured image.
	 *
	 * @testdox Should preserve legacy gallery images when no featured image is set.
	 */
	public function test_get_product_gallery_media_data_preserves_legacy_gallery_without_featured_image() {
		$product  = \WC_Helper_Product::create_simple_product();
		$image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Gallery Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		$product->set_image_id( 0 );
		$product->set_gallery_image_ids( array( $image_id ) );
		$product->save();

		$media_data = ProductGalleryUtils::get_product_gallery_media_data( $product, 'woocommerce_thumbnail' );

		$this->assertCount( 1, $media_data );
		$this->assertSame( $image_id, $media_data[0]['id'] );
		$this->assertSame( 'image', $media_data[0]['media_type'] );

		$product->delete( true );
		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Test get_product_gallery_media_data method with composed image and video items.
	 *
	 * @testdox Should include positioned video items in product gallery media data.
	 */
	public function test_get_product_gallery_media_data_supports_videos() {
		update_option( ProductMediaGallery::ENABLE_OPTION_NAME, 'yes' );

		$product = \WC_Helper_Product::create_simple_product();

		$image_id  = wp_insert_attachment(
			array(
				'post_title'     => 'Gallery Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$poster_id = wp_insert_attachment(
			array(
				'post_title'     => 'Video Poster',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$video_id  = wp_insert_attachment(
			array(
				'post_title'     => 'Product Video',
				'post_type'      => 'attachment',
				'post_mime_type' => 'video/mp4',
				'guid'           => 'https://example.com/product-video.mp4',
			)
		);

		update_post_meta( $video_id, '_thumbnail_id', $poster_id );

		$product->set_gallery_image_ids( array( $image_id ) );
		$product->save();

		ProductMediaGallery::set_stored_video_gallery_items(
			$product,
			array(
				array(
					'source_type' => 'attachment',
					'id'          => $video_id,
					'position'    => 0,
				),
			)
		);

		$media_data = ProductGalleryUtils::get_product_gallery_media_data( $product, 'woocommerce_thumbnail' );

		$this->assertCount( 2, $media_data );
		$this->assertSame( $video_id, $media_data[0]['id'] );
		$this->assertSame( 'video', $media_data[0]['media_type'] );
		$this->assertSame( $poster_id, $media_data[0]['poster_id'] );
		$this->assertSame( 'https://example.com/product-video.mp4', $media_data[0]['video_src'] );
		$this->assertSame( array(), $media_data[0]['settings'] );
		$this->assertSame( $image_id, $media_data[1]['id'] );
		$this->assertSame( 'image', $media_data[1]['media_type'] );

		$product->delete( true );
		wp_delete_attachment( $image_id, true );
		wp_delete_attachment( $poster_id, true );
		wp_delete_attachment( $video_id, true );
	}

	/**
	 * Test get_product_gallery_media_data method with stored gallery videos.
	 *
	 * @testdox Should keep the featured image before positioned gallery videos.
	 */
	public function test_get_product_gallery_media_data_prepends_featured_image_to_gallery_only_media() {
		update_option( ProductMediaGallery::ENABLE_OPTION_NAME, 'yes' );

		$product = \WC_Helper_Product::create_simple_product();

		$featured_image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Featured Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$video_id          = wp_insert_attachment(
			array(
				'post_title'     => 'Product Video',
				'post_type'      => 'attachment',
				'post_mime_type' => 'video/mp4',
				'guid'           => 'https://example.com/product-video.mp4',
			)
		);

		$product->set_image_id( $featured_image_id );
		ProductMediaGallery::set_stored_video_gallery_items(
			$product,
			array(
				array(
					'source_type' => 'attachment',
					'id'          => $video_id,
					'position'    => 0,
					'poster_id'   => $featured_image_id,
				),
			)
		);

		$media_data = ProductGalleryUtils::get_product_gallery_media_data( $product, 'woocommerce_thumbnail' );

		$this->assertCount( 2, $media_data );
		$this->assertSame( $featured_image_id, $media_data[0]['id'] );
		$this->assertSame( 'image', $media_data[0]['media_type'] );
		$this->assertSame( $video_id, $media_data[1]['id'] );
		$this->assertSame( 'video', $media_data[1]['media_type'] );

		$product->delete( true );
		wp_delete_attachment( $featured_image_id, true );
		wp_delete_attachment( $video_id, true );
	}

	/**
	 * Test get_product_gallery_media_count method with stored videos while the feature is disabled.
	 *
	 * @testdox Should ignore stored video media when product gallery videos are disabled.
	 */
	public function test_get_product_gallery_media_count_ignores_stored_videos_when_feature_disabled() {
		$product = \WC_Helper_Product::create_simple_product();

		$featured_image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Featured Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$video_id          = wp_insert_attachment(
			array(
				'post_title'     => 'Product Video',
				'post_type'      => 'attachment',
				'post_mime_type' => 'video/mp4',
				'guid'           => 'https://example.com/product-video.mp4',
			)
		);

		$product->set_image_id( $featured_image_id );
		ProductMediaGallery::set_stored_video_gallery_items(
			$product,
			array(
				array(
					'source_type' => 'attachment',
					'id'          => $video_id,
					'position'    => 0,
				),
			)
		);

		$media_data = ProductGalleryUtils::get_product_gallery_media_data( $product, 'woocommerce_thumbnail' );

		$this->assertSame( 1, ProductGalleryUtils::get_product_gallery_media_count( $product ) );
		$this->assertCount( 1, $media_data );
		$this->assertSame( $featured_image_id, $media_data[0]['id'] );
		$this->assertSame( 'image', $media_data[0]['media_type'] );

		$product->delete( true );
		wp_delete_attachment( $featured_image_id, true );
		wp_delete_attachment( $video_id, true );
	}
}
