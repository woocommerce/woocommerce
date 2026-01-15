<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;

/**
 * Tests for the ProductGallery block type
 */
class ProductGallery extends \WP_UnitTestCase {

	/**
	 * Helper method to create a product with multiple images.
	 *
	 * @param int $gallery_count Number of gallery images to create.
	 * @return array Array containing 'product', 'main_image_id', and 'gallery_image_ids'.
	 */
	private function create_product_with_gallery( $gallery_count = 3 ) {
		$product = WC_Helper_Product::create_simple_product();

		// Create and set the main product image.
		$main_image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Main Product Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$product->set_image_id( $main_image_id );

		// Create gallery images.
		$gallery_image_ids = array();
		for ( $i = 0; $i < $gallery_count; $i++ ) {
			$gallery_image_ids[] = wp_insert_attachment(
				array(
					'post_title'     => 'Gallery Image ' . ( $i + 1 ),
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			);
		}
		$product->set_gallery_image_ids( $gallery_image_ids );
		$product->save();

		return array(
			'product'           => $product,
			'main_image_id'     => $main_image_id,
			'gallery_image_ids' => $gallery_image_ids,
		);
	}

	/**
	 * Helper method to render the product gallery block.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $gallery_attributes Optional gallery attributes.
	 * @return string The rendered markup.
	 */
	private function render_product_gallery( $product_id, $gallery_attributes = '' ) {
		return do_blocks(
			sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%d} -->
				<div class="wp-block-woocommerce-single-product woocommerce">
					<!-- wp:woocommerce/product-gallery %s -->
					<div class="wp-block-woocommerce-product-gallery wc-block-product-gallery">
						<!-- wp:woocommerce/product-gallery-thumbnails /-->

						<!-- wp:woocommerce/product-gallery-large-image -->
						<div class="wp-block-woocommerce-product-gallery-large-image wc-block-product-gallery-large-image__inner-blocks">
							<!-- wp:woocommerce/product-image {"showProductLink":false,"showSaleBadge":false,"isDescendentOfSingleProductBlock":true} /-->

							<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->

							<!-- wp:woocommerce/product-gallery-large-image-next-previous -->
							<div class="wp-block-woocommerce-product-gallery-large-image-next-previous"></div>
							<!-- /wp:woocommerce/product-gallery-large-image-next-previous -->
						</div>
						<!-- /wp:woocommerce/product-gallery-large-image -->
					</div>
					<!-- /wp:woocommerce/product-gallery -->
				</div>
				<!-- /wp:woocommerce/single-product -->',
				$product_id,
				$gallery_attributes
			)
		);
	}

	/**
	 * Helper method to clean up product and image data.
	 *
	 * @param array|WC_Product $data Product data array or WC_Product instance.
	 */
	private function cleanup_product_data( $data ) {
		if ( is_array( $data ) ) {
			$data['product']->delete( true );
			wp_delete_attachment( $data['main_image_id'], true );
			foreach ( $data['gallery_image_ids'] as $gallery_image_id ) {
				wp_delete_attachment( $gallery_image_id, true );
			}
		} elseif ( is_object( $data ) && is_a( $data, 'WC_Product' ) ) {
			$data->delete( true );
		}
	}

	/**
	 * Test that the ProductGallery block renders correctly with multiple images.
	 */
	public function test_product_gallery_render_with_multiple_images() {
		$data = $this->create_product_with_gallery( 3 );

		$markup = $this->render_product_gallery( $data['product']->get_id() );

		// Check that the gallery wrapper is rendered.
		$this->assertStringContainsString( 'wc-block-product-gallery', $markup );

		// Check that the viewer block is rendered.
		$this->assertStringContainsString( 'wc-block-product-gallery-large-image', $markup );

		// Check that the thumbnails block is rendered.
		$this->assertStringContainsString( 'wc-block-product-gallery-thumbnails', $markup );

		// Check that all images are rendered (main image + gallery images).
		$this->assertStringContainsString( 'data-image-id="' . $data['main_image_id'] . '"', $markup );
		foreach ( $data['gallery_image_ids'] as $gallery_image_id ) {
			$this->assertStringContainsString( 'data-image-id="' . $gallery_image_id . '"', $markup );
		}

		// Check that the aspect ratio class is applied.
		$this->assertStringContainsString( 'wc-block-components-product-image--aspect-ratio-auto', $markup );

		$this->cleanup_product_data( $data );
	}

	/**
	 * Test that the ProductGallery block renders correctly with hover zoom enabled.
	 */
	public function test_product_gallery_render_with_hover_zoom() {
		$data = $this->create_product_with_gallery( 1 );

		$markup = $this->render_product_gallery(
			$data['product']->get_id(),
			'{"hoverZoom":true}'
		);

		// Check that hover zoom is enabled in the context.
		$this->assertStringContainsString( 'data-hover-zoom="true"', $markup );

		$this->cleanup_product_data( $data );
	}

	/**
	 * Test that the ProductGallery block renders correctly with fullscreen on click enabled.
	 */
	public function test_product_gallery_render_with_fullscreen_on_click() {
		$data = $this->create_product_with_gallery( 1 );

		$markup = $this->render_product_gallery(
			$data['product']->get_id(),
			'{"fullScreenOnClick":true}'
		);

		// Check that fullscreen is enabled in the context.
		$this->assertStringContainsString( 'data-full-screen-on-click="true"', $markup );

		$this->cleanup_product_data( $data );
	}

	/**
	 * Test that the ProductGallery block handles products without images correctly.
	 */
	public function test_product_gallery_render_without_images() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$markup = $this->render_product_gallery( $product->get_id() );

		// Should contain placeholder image.
		$this->assertStringContainsString( 'woocommerce-placeholder', $markup );

		$this->cleanup_product_data( $product );
	}

	/**
	 * Test that the ProductGallery block handles invalid product IDs correctly.
	 */
	public function test_product_gallery_render_with_invalid_product() {
		$markup = $this->render_product_gallery( 99999 );

		$this->assertEmpty( $markup );
	}
}
