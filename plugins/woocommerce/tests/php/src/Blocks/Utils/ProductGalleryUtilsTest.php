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

		$image_data = ProductGalleryUtils::get_product_gallery_image_data( $variable_product );

		$this->assertArrayHasKey( 'image_ids', $image_data );
		$this->assertArrayHasKey( 'images', $image_data );
		$this->assertNotEmpty( $image_data['image_ids'] );
		$this->assertNotEmpty( $image_data['images'] );

		// Assert that $image_data['image_ids'] is a flat array of numbers.
		$this->assertIsArray( $image_data['image_ids'] );
		$this->assertContainsOnly( 'integer', $image_data['image_ids'] );
		$this->assertEquals( array_values( $image_data['image_ids'] ), $image_data['image_ids'], 'Array should have no keys' );

		// Assert that the keys of $image_data['images'] are IDs.
		foreach ( $image_data['images'] as $key => $image ) {
			$this->assertIsInt( $key );
		}

		// Assert that each item in $image_data['images'] has required keys.
		foreach ( $image_data['images'] as $image ) {
			$this->assertArrayHasKey( 'id', $image );
			$this->assertArrayHasKey( 'sizes', $image );
			$this->assertArrayHasKey( 'src_set', $image );
			$this->assertArrayHasKey( 'src', $image );
		}

		// Assert that the child product image is included in the image_ids array.
		$this->assertContains( $variation_image_id, $image_data['image_ids'] );

		// Clean up.
		$variable_product->delete( true );
		wp_delete_attachment( $image_id, true );
		wp_delete_attachment( $variation_image_id, true );
		foreach ( $gallery_image_ids as $gallery_image_id ) {
			wp_delete_attachment( $gallery_image_id, true );
		}
	}
}
