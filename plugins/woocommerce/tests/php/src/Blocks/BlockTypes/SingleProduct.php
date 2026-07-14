<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\SingleProduct as SingleProductBlockType;
use Automattic\WooCommerce\Blocks\SharedStores\CartStore;
use WC_Helper_Product;

/**
 * Tests for the SingleProduct block type.
 */
class SingleProduct extends \WP_UnitTestCase {

	/**
	 * Consent string required by the CartStore API.
	 *
	 * @var string
	 */
	protected $consent = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * Reset static state between tests so scope-stack and occurrence-counter
	 * state does not bleed from one test into the next.
	 */
	public function tearDown(): void {
		$this->reset_cart_store_static_state();
		$this->reset_single_product_occurrence_counts();
		parent::tearDown();
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

	/**
	 * Builds minimal Single Product block markup wrapping a single inner block.
	 *
	 * @param int $product_id Product ID.
	 * @return string Single Product block markup.
	 */
	private function get_minimal_single_product_markup( $product_id ) {
		return sprintf(
			'<!-- wp:woocommerce/single-product {"productId":%1$d} -->
<div class="wp-block-woocommerce-single-product woocommerce">
<!-- wp:woocommerce/product-price {"isDescendentOfSingleProductBlock":true} /-->
</div>
<!-- /wp:woocommerce/single-product -->',
			$product_id
		);
	}

	/**
	 * @testdox The wrapper carries both the default woocommerce/products context and a hand-rolled woocommerce:: scope context shaped single-product/<productId>/<n>.
	 */
	public function test_wrapper_emits_scope_context_alongside_products_context() {
		$product = WC_Helper_Product::create_simple_product();

		try {
			$markup = do_blocks( $this->get_minimal_single_product_markup( $product->get_id() ) );

			// `set_attribute()` always serializes double-quoted, entity-escaped
			// attributes, so parse and decode via the same Tag Processor API
			// rather than matching a raw string, per the resilient-assertion
			// convention (targeted, decoded reads over brittle string matches).
			$tags = new \WP_HTML_Tag_Processor( $markup );
			$this->assertTrue( $tags->next_tag( array( 'tag_name' => 'div' ) ), 'The wrapper div should be present.' );

			$this->assertSame(
				'woocommerce/products::' . wp_json_encode(
					array(
						'productId'   => $product->get_id(),
						'variationId' => null,
					)
				),
				$tags->get_attribute( 'data-wp-context' ),
				'The default woocommerce/products context should still be present, unaffected by the second context bag.'
			);
			$this->assertSame(
				'woocommerce::' . wp_json_encode( array( 'scope' => 'single-product/' . $product->get_id() . '/1' ) ),
				$tags->get_attribute( 'data-wp-context---scope' ),
				'A second, hand-rolled woocommerce:: scope context should be present.'
			);
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
	}

	/**
	 * @testdox get_current_scope() returns the block's scope while its inner blocks render, and the page scope is restored once rendering completes.
	 */
	public function test_pushes_scope_during_inner_block_render_and_restores_afterward() {
		$product = WC_Helper_Product::create_simple_product();

		$page_scope_before   = CartStore::get_current_scope( $this->consent );
		$inner_block_scope   = null;
		$capture_inner_scope = function ( $block_content, $parsed_block ) use ( &$inner_block_scope ) {
			if ( 'woocommerce/product-price' === ( $parsed_block['blockName'] ?? null ) ) {
				$inner_block_scope = CartStore::get_current_scope( $this->consent );
			}
			return $block_content;
		};
		add_filter( 'render_block', $capture_inner_scope, 10, 2 );

		try {
			do_blocks( $this->get_minimal_single_product_markup( $product->get_id() ) );
		} finally {
			remove_filter( 'render_block', $capture_inner_scope, 10 );
			WC_Helper_Product::delete_product( $product->get_id() );
		}

		$page_scope_after = CartStore::get_current_scope( $this->consent );

		$this->assertSame(
			'single-product/' . $product->get_id() . '/1',
			$inner_block_scope,
			'get_current_scope() should return the container scope while its inner blocks render.'
		);
		$this->assertSame( $page_scope_before, $page_scope_after, 'The page scope should be restored once the block has finished rendering.' );
	}

	/**
	 * @testdox Two Single Product blocks of the same product on one page get distinct, reproducible <n> occurrence values.
	 */
	public function test_two_instances_of_same_product_get_distinct_reproducible_occurrence_counters() {
		$product       = WC_Helper_Product::create_simple_product();
		$markup_of_two = $this->get_minimal_single_product_markup( $product->get_id() ) . $this->get_minimal_single_product_markup( $product->get_id() );

		try {
			$first_render = do_blocks( $markup_of_two );

			// Simulate a fresh render of the same page structure (e.g. a
			// router-region re-render), which is a new PHP request in production.
			$this->reset_single_product_occurrence_counts();

			$second_render = do_blocks( $markup_of_two );
		} finally {
			WC_Helper_Product::delete_product( $product->get_id() );
		}

		$expected_scopes = array(
			'single-product/' . $product->get_id() . '/1',
			'single-product/' . $product->get_id() . '/2',
		);

		$this->assertSame( $expected_scopes, $this->extract_scope_contexts( $first_render ), 'The first render should mint occurrences 1 and 2, in document order.' );
		$this->assertSame( $expected_scopes, $this->extract_scope_contexts( $second_render ), 'A fresh render of the same page structure should reproduce the same occurrences.' );
	}

	/**
	 * Extracts every wrapper div's decoded `data-wp-context---scope` `scope`
	 * value from a rendered markup string, in document order.
	 *
	 * @param string $markup Rendered HTML.
	 * @return array<int, string|null> The decoded scope ids, in document order.
	 */
	private function extract_scope_contexts( $markup ) {
		$tags   = new \WP_HTML_Tag_Processor( $markup );
		$scopes = array();

		while ( $tags->next_tag( array( 'tag_name' => 'div' ) ) ) {
			$scope_context = $tags->get_attribute( 'data-wp-context---scope' );

			if ( null === $scope_context ) {
				continue;
			}

			$decoded  = json_decode( substr( $scope_context, strlen( 'woocommerce::' ) ), true );
			$scopes[] = $decoded['scope'] ?? null;
		}

		return $scopes;
	}

	/**
	 * Reset the CartStore's private static properties between tests.
	 */
	private function reset_cart_store_static_state(): void {
		$reflection = new \ReflectionClass( CartStore::class );

		$page_scope = $reflection->getProperty( 'page_scope' );
		$page_scope->setAccessible( true );
		$page_scope->setValue( null, null );

		$scope_stack = $reflection->getProperty( 'scope_stack' );
		$scope_stack->setAccessible( true );
		$scope_stack->setValue( null, array() );
	}

	/**
	 * Reset the SingleProduct block type's per-product occurrence counters
	 * between tests (and to simulate a fresh render of the same page
	 * structure within a single test).
	 */
	private function reset_single_product_occurrence_counts(): void {
		$reflection = new \ReflectionClass( SingleProductBlockType::class );

		$occurrence_counts = $reflection->getProperty( 'occurrence_counts' );
		$occurrence_counts->setAccessible( true );
		$occurrence_counts->setValue( null, array() );
	}
}
