<?php

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use WP_UnitTestCase;

/**
 * Tests for the ProductGalleryUtils class.
 */
class ProductGalleryUtilsTest extends \WP_UnitTestCase {
	/**
	 * Tear down test case.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_product_gallery_videos_enabled' );
		parent::tearDown();
	}

	/**
	 * Test get_product_gallery_image_data method.
	 */
	public function test_get_product_gallery_image_data() {
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

		// Create a variation image but don't add it to the gallery.
		$variation_image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Variation Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

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

		// Clean up.
		$variable_product->delete( true );
		wp_delete_attachment( $image_id, true );
		wp_delete_attachment( $variation_image_id, true );
		foreach ( $gallery_image_ids as $gallery_image_id ) {
			wp_delete_attachment( $gallery_image_id, true );
		}
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
	 * Test get_product_gallery_media_data method with mixed image and video items.
	 *
	 * @testdox Should include unique video items in product gallery media data.
	 */
	public function test_get_product_gallery_media_data_supports_videos() {
		update_option( 'woocommerce_feature_product_gallery_videos_enabled', 'yes' );

		$product = \WC_Helper_Product::create_simple_product();

		$image_id = wp_insert_attachment(
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

		$product->set_media_gallery(
			array(
				array(
					'media_type'  => 'video',
					'source_type' => 'attachment',
					'id'          => $video_id,
					'settings'    => array(
						'controls' => true,
						'preload'  => 'metadata',
					),
				),
				array(
					'media_type'  => 'image',
					'source_type' => 'attachment',
					'id'          => $image_id,
				),
				array(
					'media_type'  => 'video',
					'source_type' => 'attachment',
					'id'          => $video_id,
				),
				array(
					'media_type'  => 'image',
					'source_type' => 'attachment',
					'id'          => $image_id,
				),
			)
		);
		$product->save();

		$media_data = ProductGalleryUtils::get_product_gallery_media_data( $product, 'woocommerce_thumbnail' );

		$this->assertCount( 2, $media_data );
		$this->assertSame( $video_id, $media_data[0]['id'] );
		$this->assertSame( 'video', $media_data[0]['media_type'] );
		$this->assertSame( $poster_id, $media_data[0]['poster_id'] );
		$this->assertSame( 'https://example.com/product-video.mp4', $media_data[0]['video_src'] );
		$this->assertSame( $image_id, $media_data[1]['id'] );
		$this->assertSame( 'image', $media_data[1]['media_type'] );

		$product->delete( true );
		wp_delete_attachment( $image_id, true );
		wp_delete_attachment( $poster_id, true );
		wp_delete_attachment( $video_id, true );
	}

	/**
	 * Test get_product_gallery_media_data method with gallery-only media gallery items.
	 *
	 * @testdox Should prepend the featured image to gallery-only media gallery data.
	 */
	public function test_get_product_gallery_media_data_prepends_featured_image_to_gallery_only_media() {
		update_option( 'woocommerce_feature_product_gallery_videos_enabled', 'yes' );

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
		$product->set_media_gallery(
			array(
				array(
					'media_type'  => 'video',
					'source_type' => 'attachment',
					'id'          => $video_id,
					'poster_id'   => $featured_image_id,
				),
			)
		);
		$product->save();

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
		$product->set_media_gallery(
			array(
				array(
					'media_type'  => 'video',
					'source_type' => 'attachment',
					'id'          => $video_id,
				),
			)
		);
		$product->save();

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
