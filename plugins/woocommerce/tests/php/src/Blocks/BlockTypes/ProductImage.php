<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;
use WP_Block;

/**
 * Tests for the ProductImage block type
 */
class ProductImage extends \WP_UnitTestCase {

	/**
	 * Test that the ProductImage block renders correctly for a simple product.
	 */
	public function test_product_image_render_simple_product() {
		$product = WC_Helper_Product::create_simple_product();

		// Create and set a test image.
		$image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Test Product Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$product->set_image_id( $image_id );
		$product->save();

		$block = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(),
			),
			array(
				'postId' => $product->get_id(),
			)
		);

		$markup = $block->render();

		$this->assertStringContainsString( 'wc-block-components-product-image', $markup );
		$this->assertStringContainsString( 'data-testid="product-image"', $markup );
		$this->assertStringContainsString( 'data-image-id="' . $image_id . '"', $markup );

		// Clean up.
		$product->delete( true );
		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Test that the ProductImage block renders correctly for a variable product with variation images.
	 * This is the main test case: if product is variable product and has some images attached to the variation
	 * (but not in the main gallery) and the imageId of variation image is provided via context,
	 * it still recognises the imageId as its own image.
	 */
	public function test_product_image_render_variable_product_with_variation_images() {
		// Create a variable product.
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

		// Create gallery images (separate from variation images).
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

		// Create a variation image that is NOT in the main gallery.
		$variation_image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Variation Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		// Get the variations and set the variation image.
		$variations = $variable_product->get_children();
		$this->assertNotEmpty( $variations, 'Variable product should have variations' );

		$variation = wc_get_product( $variations[0] );
		$variation->set_image_id( $variation_image_id );
		$variation->save();

		// Test that the ProductImage block recognizes the variation image when provided via context.
		$block = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(),
			),
			array(
				'postId'  => $variable_product->get_id(),
				'imageId' => $variation_image_id,
			)
		);

		$markup = $block->render();

		// The block should recognize the variation image as valid and use it.
		$this->assertStringContainsString( 'data-image-id="' . $variation_image_id . '"', $markup );
		$this->assertStringContainsString( 'data-testid="product-image"', $markup );
		$this->assertStringContainsString( 'wc-block-components-product-image', $markup );

		// Test that the block falls back to the main product image when no imageId is provided.
		$block_no_image_id = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(),
			),
			array(
				'postId' => $variable_product->get_id(),
			)
		);

		$markup_no_image_id = $block_no_image_id->render();
		$this->assertStringContainsString( 'data-image-id="' . $main_image_id . '"', $markup_no_image_id );

		// Test that the block rejects invalid image IDs.
		$invalid_image_id = 99999;
		$block_invalid    = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(),
			),
			array(
				'postId'  => $variable_product->get_id(),
				'imageId' => $invalid_image_id,
			)
		);

		$markup_invalid = $block_invalid->render();
		// Should fall back to main product image when invalid image ID is provided.
		$this->assertStringContainsString( 'data-image-id="' . $main_image_id . '"', $markup_invalid );

		// Clean up.
		$variable_product->delete( true );
		wp_delete_attachment( $main_image_id, true );
		wp_delete_attachment( $variation_image_id, true );
		foreach ( $gallery_image_ids as $gallery_image_id ) {
			wp_delete_attachment( $gallery_image_id, true );
		}
	}

	/**
	 * Test that the ProductImage block renders correctly with different image sizing options.
	 */
	public function test_product_image_render_with_different_sizing() {
		$product = WC_Helper_Product::create_simple_product();

		$image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Test Product Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$product->set_image_id( $image_id );
		$product->save();

		// Test with 'single' image sizing.
		$block_single = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(
					'imageSizing' => 'single',
				),
			),
			array(
				'postId' => $product->get_id(),
			)
		);

		$markup_single = $block_single->render();
		$this->assertStringContainsString( 'wc-block-components-product-image', $markup_single );

		// Test with 'thumbnail' image sizing.
		$block_thumbnail = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(
					'imageSizing' => 'thumbnail',
				),
			),
			array(
				'postId' => $product->get_id(),
			)
		);

		$markup_thumbnail = $block_thumbnail->render();
		$this->assertStringContainsString( 'wc-block-components-product-image', $markup_thumbnail );

		// Clean up.
		$product->delete( true );
		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Test that the ProductImage block renders correctly with sale badge.
	 */
	public function test_product_image_render_with_sale_badge() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );

		$image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Test Product Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$product->set_image_id( $image_id );
		$product->save();

		$block = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(
					'showSaleBadge' => true,
				),
			),
			array(
				'postId' => $product->get_id(),
			)
		);

		$markup = $block->render();

		$this->assertStringContainsString( 'wc-block-components-product-image', $markup );
		$this->assertStringContainsString( 'wp-block-woocommerce-product-sale-badge', $markup );

		// Clean up.
		$product->delete( true );
		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Test that the ProductImage block renders correctly with inner blocks content.
	 */
	public function test_product_image_render_with_inner_blocks() {
		$product = WC_Helper_Product::create_simple_product();

		$image_id = wp_insert_attachment(
			array(
				'post_title'     => 'Test Product Image',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		$product->set_image_id( $image_id );
		$product->save();

		$inner_content = '<div class="custom-inner-block">Custom content</div>';

		$block = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(),
			),
			array(
				'postId' => $product->get_id(),
			)
		);

		$markup = $block->render( array(), $inner_content, $block );

		$this->assertStringContainsString( 'wc-block-components-product-image', $markup );
		$this->assertStringContainsString( 'wc-block-components-product-image__inner-container', $markup );
		$this->assertStringContainsString( 'Custom content', $markup );

		// Clean up.
		$product->delete( true );
		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Test that the ProductImage block handles products without images correctly.
	 */
	public function test_product_image_render_without_images() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$block = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(),
			),
			array(
				'postId' => $product->get_id(),
			)
		);

		$markup = $block->render();

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
		$block = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(),
			),
			array(
				'postId' => 99999, // Non-existent product ID.
			)
		);

		$markup = $block->render();

		$this->assertEmpty( $markup );
	}

	/**
	 * Test that the ProductImage block handles missing postId context correctly.
	 */
	public function test_product_image_render_without_post_id() {
		$block = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-image',
				'attrs'     => array(),
			),
			array()
		);

		$markup = $block->render();

		$this->assertEmpty( $markup );
	}
}
