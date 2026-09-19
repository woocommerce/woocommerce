<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\SharedStores\ProductScopes;
use WC_Helper_Product;

/**
 * Tests for the SingleProduct block type.
 */
class SingleProduct extends \WP_UnitTestCase {

	/**
	 * Reset the scope helper's per-request state so occurrence counts and
	 * open places do not bleed between tests.
	 */
	public function setUp(): void {
		parent::setUp();
		ProductScopes::reset();
	}

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
<!-- wp:woocommerce/product-image {"showProductLink":false,"showSaleBadge":false} /-->

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

<!-- wp:woocommerce/product-rating /-->

<!-- wp:woocommerce/product-price /-->

<!-- wp:woocommerce/product-summary /-->

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

	/**
	 * @testdox Password-protected products render the password form instead of product content.
	 */
	public function test_password_protected_product_renders_password_form() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_post_password( 'secret' );
		$product->save();

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%d} -->
<div class="wp-block-woocommerce-single-product"><p>VISIBLE_PRODUCT_CONTENT</p></div>
<!-- /wp:woocommerce/single-product -->',
					$product->get_id()
				)
			);

			$this->assertStringContainsString(
				'This content is password-protected',
				$markup,
				'Password-protected products should render the password form.'
			);
			$this->assertStringNotContainsString(
				'VISIBLE_PRODUCT_CONTENT',
				$markup,
				'Password-protected products should not render block content.'
			);
			$this->assertStringNotContainsString(
				'?product=' . $product->get_slug(),
				$markup,
				'Password form should redirect to the current page instead of the product page.'
			);
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox Draft products that are not viewable render no content.
	 */
	public function test_draft_product_renders_nothing() {
		wp_set_current_user( 0 );

		$product = WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%d} -->
<div class="wp-block-woocommerce-single-product"><p>VISIBLE_PRODUCT_CONTENT</p></div>
<!-- /wp:woocommerce/single-product -->',
					$product->get_id()
				)
			);

			$this->assertSame(
				'',
				$markup,
				'Draft products should not render any Single Product block output.'
			);
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * Extract the `woocommerce`-namespaced `data-wp-context` declared on the
	 * Single Product block's wrapper `div`.
	 *
	 * @param string $markup Rendered markup.
	 * @return array{namespace: string, context: array} The namespace and decoded context of the first `data-wp-context` found.
	 */
	private function get_wrapper_scope_context( string $markup ): array {
		$processor = new \WP_HTML_Tag_Processor( $markup );
		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wp-block-woocommerce-single-product' ) ) );

		$context = $processor->get_attribute( 'data-wp-context' );
		$this->assertIsString( $context );

		list( $namespace, $json_context ) = explode( '::', $context, 2 );

		return array(
			'namespace' => $namespace,
			'context'   => json_decode( $json_context, true ),
		);
	}

	/**
	 * @testdox The wrapper declares productId, variation and scopeName together in the woocommerce namespace.
	 */
	public function test_wrapper_declares_woocommerce_scope_context(): void {
		$product = WC_Helper_Product::create_simple_product();

		try {
			$markup = do_blocks(
				sprintf(
					'<!-- wp:woocommerce/single-product {"productId":%d} --><div class="wp-block-woocommerce-single-product"></div><!-- /wp:woocommerce/single-product -->',
					$product->get_id()
				)
			);

			$scope = $this->get_wrapper_scope_context( $markup );

			$this->assertSame( 'woocommerce', $scope['namespace'] );
			$this->assertSame( $product->get_id(), $scope['context']['productId'] );
			$this->assertSame( array(), $scope['context']['variation'] );
			$this->assertIsString( $scope['context']['scopeName'] );
			$this->assertNotSame( '', $scope['context']['scopeName'] );
			$this->assertStringNotContainsString( 'woocommerce/products', $markup, 'No context should be declared in the retired woocommerce/products namespace.' );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox Two Single Product blocks for the same product receive different scopeName values and neither leaves a place open.
	 */
	public function test_two_single_product_blocks_for_same_product_get_different_scope_names(): void {
		$product = WC_Helper_Product::create_simple_product();

		try {
			$block = sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%d} --><div class="wp-block-woocommerce-single-product"></div><!-- /wp:woocommerce/single-product -->',
				$product->get_id()
			);

			$markup = do_blocks( $block . $block );

			$processor   = new \WP_HTML_Tag_Processor( $markup );
			$scope_names = array();
			while ( $processor->next_tag( array( 'class_name' => 'wp-block-woocommerce-single-product' ) ) ) {
				list( , $json_context ) = explode( '::', $processor->get_attribute( 'data-wp-context' ), 2 );
				$scope_names[]          = json_decode( $json_context, true )['scopeName'];
			}

			$this->assertCount( 2, $scope_names );
			$this->assertNotSame( $scope_names[0], $scope_names[1], 'Two Single Product blocks for the same product should get different scopeName values.' );
			$this->assertSame( '', ProductScopes::current_place(), 'No place should be left open after both blocks render.' );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox A Single Product block saved with no productId attribute still renders its wrapper's scopeName and leaves no place open.
	 */
	public function test_single_product_without_product_id_attribute_still_declares_scope(): void {
		$product = WC_Helper_Product::create_simple_product();

		try {
			$this->go_to( get_permalink( $product->get_id() ) );

			$markup = do_blocks( '<!-- wp:woocommerce/single-product --><div class="wp-block-woocommerce-single-product"></div><!-- /wp:woocommerce/single-product -->' );

			$scope = $this->get_wrapper_scope_context( $markup );

			$this->assertSame( $product->get_id(), $scope['context']['productId'] );
			$this->assertIsString( $scope['context']['scopeName'] );
			$this->assertNotSame( '', $scope['context']['scopeName'] );
			$this->assertSame( '', ProductScopes::current_place(), 'No place should be left open behind a productId-less block.' );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}
}
