<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;

/**
 * Tests for the SingleProduct block type.
 */
class SingleProduct extends \WP_UnitTestCase {
	/**
	 * Creates a simple product with a featured image and gallery images.
	 *
	 * @param int   $gallery_count Number of gallery-only attachments (in addition to the featured image).
	 * @param array $product_props Optional props merged into {@see WC_Helper_Product::create_simple_product()} (e.g. `name`).
	 * @return array{product: \WC_Product, main_image_id: int, gallery_image_ids: int[]}
	 */
	private function create_product_with_gallery( $gallery_count = 2, array $product_props = array() ) {
		$product = WC_Helper_Product::create_simple_product( true, $product_props );

		$main_image_id = $this->create_test_jpeg_attachment( 'Main Product Image' );
		$product->set_image_id( $main_image_id );

		$gallery_image_ids = array();
		for ( $i = 0; $i < $gallery_count; $i++ ) {
			$gallery_image_ids[] = $this->create_test_jpeg_attachment( 'Gallery Image ' . ( $i + 1 ) );
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
	 * Creates a JPEG attachment on disk so `wp_get_attachment_image` returns markup (required for the gallery viewer).
	 *
	 * @param string $title Attachment title.
	 * @return int Attachment ID.
	 */
	private function create_test_jpeg_attachment( $title ) {
		$file = wp_tempnam( 'wc-test-gallery-' . sanitize_title( $title ) . '.jpg' );

		$attachment_id = wp_insert_attachment(
			array(
				'post_title'     => $title,
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			),
			$file
		);

		return $attachment_id;
	}

	/**
	 * Deletes a product created by {@see create_product_with_gallery()} and its image attachments.
	 *
	 * @param array{product: \WC_Product, main_image_id: int, gallery_image_ids: int[]} $data Product data from create_product_with_gallery().
	 */
	private function delete_product_with_gallery_attachments( array $data ) {
		WC_Helper_Product::delete_product( $data['product']->get_id() );
		wp_delete_attachment( $data['main_image_id'], true );
		foreach ( $data['gallery_image_ids'] as $gallery_image_id ) {
			wp_delete_attachment( $gallery_image_id, true );
		}
	}

	/**
	 * Renders the Single Product block with the default-style layout.
	 *
	 * @param int $product_id Product ID.
	 * @return string Rendered HTML.
	 */
	private function render_single_product_with_gallery_columns_and_title( $product_id ) {
		return do_blocks(
			sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%d} -->
<div class="wp-block-woocommerce-single-product woocommerce">
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:woocommerce/product-gallery -->
<div class="wp-block-woocommerce-product-gallery wc-block-product-gallery">
<!-- wp:woocommerce/product-gallery-thumbnails /-->

<!-- wp:woocommerce/product-gallery-large-image -->
<div class="wp-block-woocommerce-product-gallery-large-image wc-block-product-gallery-large-image__inner-blocks">
<!-- wp:woocommerce/product-image {"showProductLink":false,"showSaleBadge":false,"isDescendentOfSingleProductBlock":true} /-->

<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->

<!-- wp:woocommerce/product-gallery-large-image-next-previous -->
<div class="wp-block-woocommerce-product-gallery-large-image-next-previous"></div>
<!-- /wp:woocommerce/product-gallery-large-image-next-previous --></div>
<!-- /wp:woocommerce/product-gallery-large-image --></div>
<!-- /wp:woocommerce/product-gallery --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:post-title {"isLink":true,"__woocommerceNamespace":"woocommerce/product-query/product-title"} /-->

<!-- wp:woocommerce/product-rating {"isDescendentOfSingleProductBlock":true} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductBlock":true} /-->

<!-- wp:woocommerce/product-summary {"isDescendentOfSingleProductBlock":true} /-->

<!-- wp:woocommerce/product-meta -->
<div class="wp-block-woocommerce-product-meta"></div>
<!-- /wp:woocommerce/product-meta --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:woocommerce/single-product -->',
				$product_id
			)
		);
	}

	/**
	 * @testdox Post title outputs the product name when Product Gallery and viewer blocks are present in an earlier column.
	 */
	public function test_post_title_renders_correct_product_title_with_product_gallery_layout() {
		$product_title = 'Product 123';

		$data       = $this->create_product_with_gallery(
			2,
			array(
				'name' => $product_title,
			)
		);
		$product_id = $data['product']->get_id();

		try {
			$markup = $this->render_single_product_with_gallery_columns_and_title( $product_id );

			$this->assertStringContainsString( 'wp-block-post-title', $markup, 'The core Post Title block should render inside the Single Product block.' );
			$this->assertStringContainsString( $product_title, $markup, 'The visible product title should match the product post title, not the global post.' );
		} finally {
			$this->delete_product_with_gallery_attachments( $data );
		}
	}
}
