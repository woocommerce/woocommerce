<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;

/**
 * Tests for the ProductImage block type
 */
class ProductImage extends \WP_UnitTestCase {

	/**
	 * Previous thumbnail cropping option values to restore after tests.
	 *
	 * @var array<string, mixed>
	 */
	private $prev_thumbnail_cropping_options = array();

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$option_names = array(
			'woocommerce_thumbnail_cropping',
			'woocommerce_thumbnail_cropping_custom_width',
			'woocommerce_thumbnail_cropping_custom_height',
		);

		foreach ( $option_names as $option_name ) {
			$this->prev_thumbnail_cropping_options[ $option_name ] = get_option( $option_name, null );
		}
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		foreach ( $this->prev_thumbnail_cropping_options as $option_name => $value ) {
			if ( null === $value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $value );
			}
		}

		parent::tearDown();
	}

	/**
	 * Helper method to create a simple product with an image.
	 *
	 * @param string $image_title Optional title for the image.
	 * @return array Array containing 'product' and 'image_id'.
	 */
	private function create_product_with_image( $image_title = 'Test Product Image' ) {
		$product = WC_Helper_Product::create_simple_product();

		$image_id = wp_insert_attachment(
			array(
				'post_title'     => $image_title,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$product->set_image_id( $image_id );
		$product->save();

		return array(
			'product'  => $product,
			'image_id' => $image_id,
		);
	}

	/**
	 * Helper method to create a variable product with main image, gallery images, and variation images.
	 *
	 * @param int $gallery_count Number of gallery images to create.
	 * @param int $variation_count Number of variations to create images for.
	 * @return array Array containing 'product', 'main_image_id', 'gallery_image_ids', and 'variation_image_ids'.
	 */
	private function create_variable_product_with_images( $gallery_count = 2, $variation_count = 1 ) {
		$variable_product = WC_Helper_Product::create_variation_product();

		// Create and set the main product image.
		$main_image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Main Product Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$variable_product->set_image_id( $main_image_id );

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
		$variable_product->set_gallery_image_ids( $gallery_image_ids );
		$variable_product->save();

		// Create variation images.
		$variation_image_ids = array();
		$variations          = $variable_product->get_children();
		$variations_count    = min( $variation_count, count( $variations ) );

		for ( $i = 0; $i < $variations_count; $i++ ) {
			$variation_image_id = wp_insert_attachment(
				array(
					'post_title'     => 'Variation Image ' . ( $i + 1 ),
					'post_type'      => 'attachment',
					'post_mime_type' => 'image/jpeg',
				)
			);

			$variation = wc_get_product( $variations[ $i ] );
			$variation->set_image_id( $variation_image_id );
			$variation->save();

			$variation_image_ids[] = $variation_image_id;
		}

		return array(
			'product'             => $variable_product,
			'main_image_id'       => $main_image_id,
			'gallery_image_ids'   => $gallery_image_ids,
			'variation_image_ids' => $variation_image_ids,
		);
	}

	/**
	 * Helper to render a product image block for a product.
	 *
	 * @param \WC_Product $product Product object.
	 * @param string      $block_attributes Optional JSON attributes for the block.
	 * @return string Rendered block markup.
	 */
	private function render_product_image_block( $product, $block_attributes = '' ) {
		$attrs = '' !== $block_attributes ? ' ' . $block_attributes : '';

		return do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product->get_id() . '} -->' .
			'<!-- wp:woocommerce/product-image' . $attrs . ' /-->' .
			'<!-- /wp:woocommerce/single-product -->'
		);
	}

	/**
	 * Test that the ProductImage block renders correctly for a simple product.
	 */
	public function test_product_image_render_simple_product() {
		$data = $this->create_product_with_image();

		$markup = $this->render_product_image_block( $data['product'] );

		$this->assertStringContainsString( 'wc-block-components-product-image', $markup );
		$this->assertStringContainsString( 'data-testid="product-image"', $markup );
		$this->assertStringContainsString( 'data-image-id="' . $data['image_id'] . '"', $markup );

		// Clean up.
		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}

	/**
	 * Test that the ProductImage block renders a link by default.
	 */
	public function test_product_image_render_includes_product_link_by_default() {
		$data = $this->create_product_with_image();

		$markup = $this->render_product_image_block( $data['product'] );

		$this->assertStringContainsString( '<a href="' . $data['product']->get_permalink() . '"', $markup );
		$this->assertStringContainsString( 'data-wp-on--click="woocommerce/product-collection::actions.viewProduct"', $markup );

		// Clean up.
		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}

	/**
	 * Test that the ProductImage block does not render a disabled link when product links are hidden.
	 */
	public function test_product_image_render_omits_anchor_when_product_link_is_hidden() {
		$data = $this->create_product_with_image();

		$markup = $this->render_product_image_block( $data['product'], '{"showProductLink":false}' );

		$this->assertStringContainsString( 'data-testid="product-image"', $markup );
		$this->assertStringNotContainsString( '<a ', $markup );
		$this->assertStringNotContainsString( 'href="#"', $markup );
		$this->assertStringNotContainsString( 'onclick="return false;"', $markup );
		$this->assertStringNotContainsString( 'data-wp-on--click="woocommerce/product-collection::actions.viewProduct"', $markup );

		// Clean up.
		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}

	/**
	 * Test that the ProductImage block renders correctly for a variable product with variation images.
	 * This is the main test case: if product is variable product and has some images attached to the variation
	 * (but not in the main gallery) and the imageId of variation image is provided via context,
	 * it still recognises the imageId as its own image.
	 */
	public function test_product_image_render_variable_product_with_variation_images() {
		$data               = $this->create_variable_product_with_images( 2, 1 );
		$variation_image_id = $data['variation_image_ids'][0];

		// Test that the ProductImage block recognizes the variation image when provided via context.
		$markup = $this->render_product_image_block( $data['product'], '{"imageId":' . $variation_image_id . '}' );

		// The block should recognize the variation image as valid and use it.
		$this->assertStringContainsString( 'data-image-id="' . $variation_image_id . '"', $markup );
		$this->assertStringContainsString( 'wc-block-components-product-image', $markup );

		// Test that the block falls back to the main product image when no imageId is provided.
		$markup_no_image_id = $this->render_product_image_block( $data['product'] );
		$this->assertStringContainsString( 'data-image-id="' . $data['main_image_id'] . '"', $markup_no_image_id );

		// Test that the block rejects invalid image IDs.
		$invalid_image_id = 99999;
		$markup_invalid   = $this->render_product_image_block( $data['product'], '{"imageId":' . $invalid_image_id . '}' );
		// Should fall back to main product image when invalid image ID is provided.
		$this->assertStringContainsString( 'data-image-id="' . $data['main_image_id'] . '"', $markup_invalid );

		// Clean up.
		$data['product']->delete( true );
		wp_delete_attachment( $data['main_image_id'], true );
		wp_delete_attachment( $variation_image_id, true );
		foreach ( $data['gallery_image_ids'] as $gallery_image_id ) {
			wp_delete_attachment( $gallery_image_id, true );
		}
	}

	/**
	 * Test that the ProductImage block uses store thumbnail cropping aspect ratio.
	 *
	 * @testdox Should honor the store thumbnail cropping aspect ratio when imageSizing is thumbnail.
	 */
	public function test_product_image_render_with_store_aspect_ratio() {
		$data = $this->create_product_with_image();

		update_option( 'woocommerce_thumbnail_cropping', '1:1' );
		$markup_single    = $this->render_product_image_block( $data['product'], '{"imageSizing":"single"}' );
		$markup_thumbnail = $this->render_product_image_block( $data['product'], '{"imageSizing":"thumbnail"}' );
		$this->assertStringNotContainsString( 'aspect-ratio:1/1', $markup_single );
		$this->assertStringNotContainsString( 'wc-block-components-product-image--aspect-ratio-1-1', $markup_single );
		$this->assertStringContainsString( 'aspect-ratio:1/1', $markup_thumbnail );
		$this->assertStringContainsString( 'wc-block-components-product-image--aspect-ratio-1-1', $markup_thumbnail );

		update_option( 'woocommerce_thumbnail_cropping', 'custom' );
		update_option( 'woocommerce_thumbnail_cropping_custom_width', '4' );
		update_option( 'woocommerce_thumbnail_cropping_custom_height', '3' );
		$markup = $this->render_product_image_block( $data['product'], '{"imageSizing":"thumbnail"}' );
		$this->assertStringContainsString( 'aspect-ratio:4/3', $markup );
		$this->assertStringContainsString( 'wc-block-components-product-image--aspect-ratio-4-3', $markup );

		update_option( 'woocommerce_thumbnail_cropping', 'uncropped' );
		$markup = $this->render_product_image_block( $data['product'], '{"imageSizing":"thumbnail"}' );
		$this->assertStringNotContainsString( 'aspect-ratio:', $markup );
		$this->assertStringContainsString( 'wc-block-components-product-image--aspect-ratio-auto', $markup );

		// Clean up.
		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}

	/**
	 * Test that block aspect ratio overrides store thumbnail cropping.
	 *
	 * @testdox Should prioritize the block aspect ratio over the store thumbnail cropping aspect ratio.
	 */
	public function test_product_image_render_with_block_aspect_ratio_override() {
		$data = $this->create_product_with_image();

		update_option( 'woocommerce_thumbnail_cropping', '1:1' );
		$markup = $this->render_product_image_block( $data['product'], '{"aspectRatio":"3/5","imageSizing":"thumbnail"}' );
		$this->assertStringContainsString( 'aspect-ratio:3/5', $markup );
		$this->assertStringContainsString( 'wc-block-components-product-image--aspect-ratio-3-5', $markup );
		$this->assertStringNotContainsString( 'aspect-ratio:1/1', $markup );

		// Clean up.
		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}

	/**
	 * Test that the ProductImage block renders correctly with sale badge.
	 */
	public function test_product_image_render_with_sale_badge() {
		$data = $this->create_product_with_image();
		$data['product']->set_regular_price( 10 );
		$data['product']->set_sale_price( 5 );
		$data['product']->save();

		$markup = $this->render_product_image_block( $data['product'], '{"showSaleBadge":true}' );

		$this->assertStringContainsString( 'wc-block-components-product-image', $markup );
		$this->assertStringContainsString( 'wp-block-woocommerce-product-sale-badge', $markup );

		// Clean up.
		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}

	/**
	 * Test that the ProductImage block renders correctly with inner blocks content.
	 */
	public function test_product_image_render_with_inner_blocks() {
		$data = $this->create_product_with_image();

		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":' . $data['product']->get_id() . '} --><!-- wp:woocommerce/product-image --><div class="custom-inner-block">Custom content</div><!-- /wp:woocommerce/product-image --><!-- /wp:woocommerce/single-product -->' );

		$this->assertStringContainsString( 'wc-block-components-product-image', $markup );
		$this->assertStringContainsString( 'wc-block-components-product-image__inner-container', $markup );
		$this->assertStringContainsString( 'custom-inner-block', $markup );
		$this->assertStringContainsString( 'Custom content', $markup );

		// Clean up.
		$data['product']->delete( true );
		wp_delete_attachment( $data['image_id'], true );
	}

	/**
	 * Test that the ProductImage block handles products without images correctly.
	 */
	public function test_product_image_render_without_images() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$markup = $this->render_product_image_block( $product );

		$this->assertStringContainsString( 'wc-block-components-product-image', $markup );
		// Should contain placeholder image.
		$this->assertStringContainsString( 'woocommerce-placeholder', $markup );

		// Clean up.
		$product->delete( true );
	}

	/**
	 * Test that the ProductImage block handles invalid product IDs correctly.
	 */
	public function test_product_image_render_with_invalid_product() {
		$markup = do_blocks( '<!-- wp:woocommerce/single-product {"productId":99999} --><!-- wp:woocommerce/product-image /--><!-- /wp:woocommerce/single-product -->' );

		$this->assertEmpty( $markup );
	}

	/**
	 * Test that the ProductImage block handles missing postId context correctly.
	 */
	public function test_product_image_render_without_post_id() {
		$markup = do_blocks( '<!-- wp:woocommerce/product-image --><!-- /wp:woocommerce/product-image -->' );

		$this->assertEmpty( $markup );
	}
}
