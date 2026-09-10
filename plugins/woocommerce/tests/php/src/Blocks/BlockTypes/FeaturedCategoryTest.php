<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use WC_Unit_Test_Case;

/**
 * Tests for the Featured Category block type.
 */
class FeaturedCategoryTest extends WC_Unit_Test_Case {

	/**
	 * Attachment IDs created during tests.
	 *
	 * @var int[]
	 */
	private $attachment_ids = array();

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->attachment_ids as $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}
		$this->attachment_ids = array();

		parent::tearDown();
	}

	/**
	 * @testdox Should render the product category inherited from Core term context.
	 */
	public function test_renders_category_from_term_context(): void {
		$category_id = $this->create_category( 'Inherited category' );
		$markup      = <<<'HTML'
<!-- wp:woocommerce/featured-category {"source":"context"} -->
<!-- wp:woocommerce/category-title /-->
<!-- /wp:woocommerce/featured-category -->
HTML;

		$output = $this->render_with_term_context( $markup, $category_id );

		$this->assertStringContainsString( 'Inherited category', $output, 'The inherited category title should be rendered.' );
	}

	/**
	 * @testdox Should replace a bound Cover image with the inherited category thumbnail.
	 */
	public function test_renders_category_thumbnail_in_bound_cover(): void {
		$category_id   = $this->create_category( 'Cover category' );
		$attachment_id = $this->create_attachment();
		update_term_meta( $category_id, 'thumbnail_id', $attachment_id );
		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
		$markup    = <<<'HTML'
<!-- wp:woocommerce/featured-category {"layout":"cover","source":"context","ariaLabel":"Browse Cover category"} -->
<!-- wp:cover {"url":"https://example.com/fallback.jpg","className":"wc-block-featured-category__cover","metadata":{"bindings":{"id":{"source":"woocommerce/term-image"},"url":{"source":"woocommerce/term-image"}}}} -->
<div class="wp-block-cover wc-block-featured-category__cover"><img class="wp-block-cover__image-background wp-image-999" alt="" src="https://example.com/fallback.jpg"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:button {"url":"https://example.com/custom"} --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.com/custom">Custom</a></div><!-- /wp:button --><!-- wp:woocommerce/category-title /--><!-- wp:button {"url":"#","className":"wc-block-featured-category__link","metadata":{"bindings":{"url":{"source":"core/term-data","args":{"field":"link"}}}}} --><div class="wp-block-button wc-block-featured-category__link"><a class="wp-block-button__link wp-element-button" href="#">Shop now</a></div><!-- /wp:button --></div></div>
<!-- /wp:cover -->
<!-- /wp:woocommerce/featured-category -->
HTML;

		$output = $this->render_with_term_context( $markup, $category_id );

		$this->assertStringContainsString( 'wp-block-woocommerce-featured-category', $output, 'The Cover should retain the Featured Category class.' );
		$this->assertStringContainsString( esc_url( (string) $image_url ), $output, 'The category thumbnail should replace the fallback image.' );
		$this->assertStringNotContainsString( 'https://example.com/fallback.jpg', $output, 'The fallback image should not remain in the rendered Cover.' );
		$this->assertStringNotContainsString( 'wp-image-999', $output, 'The old attachment class should be removed.' );
		$this->assertStringContainsString( 'href="https://example.com/custom"', $output, 'Custom button URLs should remain unchanged.' );
		$this->assertStringContainsString( 'aria-label="Browse Cover category"', $output, 'The category CTA should receive the accessible label.' );
		$this->assertStringContainsString( esc_url( get_term_link( $category_id, 'product_cat' ) ), $output, 'The bound button should link to the inherited category.' );
	}

	/**
	 * @testdox Should replace a repeated Cover background with the inherited category thumbnail.
	 */
	public function test_renders_category_thumbnail_in_repeated_cover(): void {
		$category_id   = $this->create_category( 'Repeated Cover category' );
		$attachment_id = $this->create_attachment();
		update_term_meta( $category_id, 'thumbnail_id', $attachment_id );
		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
		$markup    = <<<'HTML'
<!-- wp:woocommerce/featured-category {"layout":"cover","source":"context"} -->
<!-- wp:cover {"url":"https://example.com/stale.jpg","isRepeated":true,"className":"wc-block-featured-category__cover","metadata":{"bindings":{"id":{"source":"woocommerce/term-image"},"url":{"source":"woocommerce/term-image"}}}} -->
<div class="wp-block-cover is-repeated wc-block-featured-category__cover"><span aria-hidden="true" class="wp-block-cover__image-background" style="background-position:50% 50%;background-image:url(https://example.com/stale.jpg)"></span><div class="wp-block-cover__inner-container"></div></div>
<!-- /wp:cover -->
<!-- /wp:woocommerce/featured-category -->
HTML;

		$output = $this->render_with_term_context( $markup, $category_id );

		$this->assertStringContainsString( esc_url( (string) $image_url ), $output );
		$this->assertStringNotContainsString( 'https://example.com/stale.jpg', $output );
		$this->assertStringContainsString( 'background-position:50% 50%', $output );
	}

	/**
	 * @testdox Should clear stale image metadata when a category has no thumbnail.
	 */
	public function test_renders_placeholder_without_stale_image_metadata(): void {
		$category_id = $this->create_category( 'Placeholder category' );
		$markup      = <<<'HTML'
<!-- wp:woocommerce/featured-category {"layout":"cover","source":"context"} -->
<!-- wp:cover {"url":"https://example.com/stale.jpg","id":999,"className":"wc-block-featured-category__cover","metadata":{"bindings":{"id":{"source":"woocommerce/term-image"},"url":{"source":"woocommerce/term-image"}}}} -->
<div class="wp-block-cover wc-block-featured-category__cover"><img class="wp-block-cover__image-background wp-image-999" width="1200" height="800" src="https://example.com/stale.jpg" srcset="stale" sizes="100vw"/><div class="wp-block-cover__inner-container"></div></div>
<!-- /wp:cover -->
<!-- /wp:woocommerce/featured-category -->
HTML;

		$output = $this->render_with_term_context( $markup, $category_id );

		$this->assertStringContainsString( esc_url( wc_placeholder_img_src() ), $output );
		$this->assertStringNotContainsString( 'wp-image-999', $output );
		$this->assertStringNotContainsString( 'width="1200"', $output );
		$this->assertStringNotContainsString( 'srcset=', $output );
	}

	/**
	 * Render block markup with Core term context.
	 *
	 * @param string $markup      Serialized blocks.
	 * @param int    $category_id Product category ID.
	 * @return string
	 */
	private function render_with_term_context( string $markup, int $category_id ): string {
		$parsed_block = parse_blocks( $markup )[0];
		$block        = new \WP_Block(
			$parsed_block,
			array(
				'termId'   => $category_id,
				'taxonomy' => 'product_cat',
			)
		);

		return $block->render();
	}

	/**
	 * Create a product category.
	 *
	 * @param string $name Category name.
	 * @return int
	 */
	private function create_category( string $name ): int {
		$category = wp_insert_term( $name, 'product_cat' );
		$this->assertNotWPError( $category );

		return (int) $category['term_id'];
	}

	/**
	 * Create and track a test attachment.
	 *
	 * @return int Attachment ID.
	 */
	private function create_attachment(): int {
		$attachment_id          = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$this->attachment_ids[] = $attachment_id;

		return $attachment_id;
	}
}
