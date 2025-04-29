<?php

namespace Automattic\WooCommerce\Tests\Blocks\Utils;

use Automattic\WooCommerce\Blocks\Utils\ProductGalleryUtils;
use WP_UnitTestCase;

/**
 * Tests for the ProductGalleryUtils class.
 */
class ProductGalleryUtilsTest extends \WP_UnitTestCase {
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
}
