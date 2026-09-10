<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Helper_Product;

/**
 * Tests for the SingleProduct block type.
 */
class SingleProduct extends \WP_UnitTestCase {
	/**
	 * @testdox Query results and the following Single Product title retain their own products.
	 * @testWith ["woocommerce/product-collection", "woocommerce/product-template"]
	 *           ["core/query", "core/post-template"]
	 * @param string $query_block Query block name.
	 * @param string $template_block Template block name.
	 */
	public function test_nested_query_context( string $query_block, string $template_block ): void {
		$selected = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Selected product' ) );
		$first    = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Query first' ) );
		$second   = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Query second' ) );
		$page_id  = self::factory()->post->create( array( 'post_title' => 'Containing page' ) );
		$this->go_to( get_permalink( $page_id ) );
		$previous_post = $GLOBALS['post'];
		$location      = null;
		add_filter(
			'render_block_woocommerce/product-template',
			static function ( $content, $parsed_block, $instance ) use ( &$location ) {
				$location = $instance->context['productCollectionLocation'] ?? null;
				return $content;
			},
			10,
			3
		);
		$query = wp_json_encode(
			array(
				'query' => array(
					'isProductCollectionBlock'      => 'woocommerce/product-collection' === $query_block,
					'woocommerceHandPickedProducts' => array( $first->get_id(), $second->get_id() ),
					'postType'                      => 'product',
					'perPage'                       => 2,
					'pages'                         => 0,
					'offset'                        => 0,
					'order'                         => 'asc',
					'orderBy'                       => 'id',
					'inherit'                       => false,
					'include'                       => array( $first->get_id(), $second->get_id() ),
					'exclude'                       => array( $selected->get_id() ),
				),
			)
		);
		$html  = do_blocks(
			sprintf(
				'<!-- wp:group --><div><!-- wp:woocommerce/single-product {"productId":%1$d} --><div>
				<!-- wp:%2$s %3$s --><div><!-- wp:%4$s --><!-- wp:post-title {"isLink":true} /--><!-- /wp:%4$s --></div><!-- /wp:%2$s -->
				<!-- wp:post-title /--></div><!-- /wp:woocommerce/single-product --></div><!-- /wp:group --><!-- wp:post-title /-->',
				$selected->get_id(),
				$query_block,
				$query,
				$template_block
			)
		);
		$this->assertSame( array( 'Query first', 'Query second', 'Selected product', 'Containing page' ), $this->get_element_texts( $html, array( 'tag_name' => 'H2' ) ), 'Each title should use the nearest product/query context.' );
		$this->assertSame( $previous_post, $GLOBALS['post'], 'Rendering must restore the containing post.' );
		if ( 'woocommerce/product-collection' === $query_block ) {
			$this->assertSame( 'product', $location['type'] ?? null, 'The collection must recognize its enclosing Single Product.' );
			$this->assertSame( $selected->get_id(), $location['sourceData']['productId'] ?? null, 'Collection filters must use the selected product as their source.' );
		}
	}

	/**
	 * @testdox Nested and consecutive Single Product blocks restore their enclosing context.
	 */
	public function test_nested_single_product_context(): void {
		$outer = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Outer product' ) );
		$inner = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Inner product' ) );
		$page  = self::factory()->post->create( array( 'post_title' => 'Containing page' ) );
		$this->go_to( get_permalink( $page ) );
		$previous_post = $GLOBALS['post'];
		$html          = do_blocks(
			sprintf(
				'<!-- wp:group --><div><!-- wp:woocommerce/single-product {"productId":%1$d} --><div>
				<!-- wp:post-title /--><!-- wp:woocommerce/single-product {"productId":%2$d} --><div><!-- wp:post-title /--></div><!-- /wp:woocommerce/single-product -->
				<!-- wp:post-title /--></div><!-- /wp:woocommerce/single-product -->
				<!-- wp:woocommerce/single-product {"productId":%2$d} --><div><!-- wp:post-title /--></div><!-- /wp:woocommerce/single-product -->
				<!-- wp:post-title /--></div><!-- /wp:group -->',
				$outer->get_id(),
				$inner->get_id()
			)
		);
		$this->assertSame( array( 'Outer product', 'Inner product', 'Outer product', 'Inner product', 'Containing page' ), $this->get_element_texts( $html, array( 'tag_name' => 'H2' ) ), 'Nested selections must not replace their enclosing product.' );
		$this->assertSame( $previous_post, $GLOBALS['post'], 'Rendering must restore the containing post.' );
	}

	/**
	 * @testdox A short-circuited title leaves the enclosing post and product unchanged.
	 */
	public function test_short_circuited_title_preserves_globals(): void {
		$selected = WC_Helper_Product::create_simple_product();
		$page_id  = self::factory()->post->create();
		$this->go_to( get_permalink( $page_id ) );
		$previous_post    = $GLOBALS['post'];
		$previous_product = $GLOBALS['product'] ?? null;
		add_filter(
			'pre_render_block',
			static function ( $content, $block ) {
				return 'core/post-title' === $block['blockName'] ? '<h2>Cached title</h2>' : $content;
			},
			10,
			2
		);
		$html = do_blocks( sprintf( '<!-- wp:woocommerce/single-product {"productId":%d} --><div><!-- wp:post-title /--></div><!-- /wp:woocommerce/single-product -->', $selected->get_id() ) );
		$this->assertStringContainsString( '<h2>Cached title</h2>', $html );
		$this->assertSame( $previous_post, $GLOBALS['post'] );
		$this->assertSame( $previous_product, $GLOBALS['product'] ?? null );
	}

	/**
	 * @testdox Product excerpts use their own context and preserve the enclosing globals.
	 */
	public function test_post_excerpt_preserves_globals(): void {
		$selected = WC_Helper_Product::create_simple_product( true, array( 'short_description' => 'Selected excerpt' ) );
		$outer    = WC_Helper_Product::create_simple_product( true, array( 'short_description' => 'Outer excerpt' ) );
		$this->go_to( get_permalink( $outer->get_id() ) );
		$GLOBALS['wp_query']->the_post();
		$previous_post    = $GLOBALS['post'];
		$previous_product = $GLOBALS['product'];

		$html = do_blocks(
			sprintf(
				'<!-- wp:woocommerce/single-product {"productId":%d} --><div><!-- wp:post-excerpt /--></div><!-- /wp:woocommerce/single-product --><!-- wp:post-excerpt /-->',
				$selected->get_id()
			)
		);

		$excerpts = $this->get_element_texts(
			$html,
			array(
				'tag_name'   => 'P',
				'class_name' => 'wp-block-post-excerpt__excerpt',
			)
		);
		$this->assertSame( array( 'Selected excerpt', 'Outer excerpt' ), $excerpts );
		$this->assertSame( $previous_post, $GLOBALS['post'] );
		$this->assertSame( $previous_product, $GLOBALS['product'] );
	}

	/**
	 * Collect text from matching headings or excerpt paragraphs, including inline markup.
	 *
	 * @param string $html Rendered blocks.
	 * @param array  $query Tag processor query.
	 * @return string[] Element texts in document order.
	 */
	private function get_element_texts( string $html, array $query ): array {
		$processor = new \WP_HTML_Tag_Processor( $html );
		$texts     = array();
		while ( $processor->next_tag( $query ) ) {
			$tag  = $processor->get_tag();
			$text = '';
			while ( $processor->next_token() ) {
				if ( $processor->is_tag_closer() && $tag === $processor->get_tag() ) {
					break;
				}
				if ( '#text' === $processor->get_token_type() ) {
					$text .= $processor->get_modifiable_text();
				}
			}
			$texts[] = trim( $text );
		}
		return $texts;
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
}
