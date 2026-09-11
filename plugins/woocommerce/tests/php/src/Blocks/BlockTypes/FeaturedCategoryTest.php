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
	 * @testdox Should render untouched legacy content as Cover without changing the stored post.
	 * @testWith [true]
	 *           [false]
	 * @param bool $oldest Whether to use the original attribute-only format.
	 */
	public function test_legacy_content_uses_cover( bool $oldest ): void {
		$category_id = $this->create_category( 'Legacy category' );
		$attributes  = array(
			'categoryId'   => $category_id,
			'contentAlign' => 'left',
			'minHeight'    => 650,
			'imageFit'     => 'none',
		);
		if ( $oldest ) {
			$attributes['editMode'] = false;
			$attributes['showDesc'] = false;
		}
		$markup  = '<!-- wp:woocommerce/featured-category ' . wp_json_encode( $attributes ) . ' --><!-- wp:paragraph --><p>Custom content</p><!-- /wp:paragraph --><!-- /wp:woocommerce/featured-category -->';
		$post_id = $this->factory->post->create( array( 'post_content' => $markup ) );
		$output  = do_blocks( get_post_field( 'post_content', $post_id ) );
		$this->assertStringContainsString( 'wp-block-cover', $output );
		$this->assertStringContainsString( 'is-position-center-left', $output );
		$this->assertStringContainsString( 'natural-image', $output );
		$this->assertStringContainsString( 'min-height:650px', $output );
		$this->assertStringContainsString( 'Custom content', $output );
		$this->assertStringNotContainsString( 'woocommerce-placeholder', $output );
		$this->assertSame( $markup, get_post_field( 'post_content', $post_id ) );
		$processor = new \WP_HTML_Tag_Processor( $output );
		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wp-block-cover' ) ) );
		$this->assertFalse( $processor->has_class( 'is-layout-flow' ), 'Layout spacing must not affect the background layers.' );
		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'wp-block-cover__inner-container' ) ) );
		$this->assertTrue( $processor->has_class( 'is-layout-flow' ), 'Layout spacing belongs on the inner content container.' );
		if ( $oldest ) {
			$this->assertStringContainsString( 'Legacy category', $output );
			$this->assertStringNotContainsString( 'wc-block-featured-category__description', $output );
		}
	}

	/**
	 * @testdox Should respect a custom URL after its category binding is disconnected.
	 */
	public function test_disconnected_category_button_keeps_custom_url(): void {
		$category_id = $this->create_category( 'Custom link category' );
		$markup      = '<!-- wp:woocommerce/featured-category {"layout":"cover"} --><!-- wp:cover {"className":"wc-block-featured-category__cover"} --><div class="wp-block-cover wc-block-featured-category__cover"><div class="wp-block-cover__inner-container"><!-- wp:button {"className":"wc-block-featured-category__link"} --><div class="wp-block-button wc-block-featured-category__link"><a class="wp-block-button__link" href="https://example.com/custom">Custom</a></div><!-- /wp:button --></div></div><!-- /wp:cover --><!-- /wp:woocommerce/featured-category -->';
		$output      = $this->render_selected_category( $markup, $category_id );
		$this->assertStringContainsString( 'href="https://example.com/custom"', $output );
	}

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
	 * @testdox Should replace only the managed Cover image with the selected category thumbnail.
	 * @testWith [true]
	 *           [false]
	 * @param bool $binding_preview Whether the block was saved by the binding-based preview.
	 */
	public function test_renders_category_thumbnail_in_managed_cover( bool $binding_preview ): void {
		$category_id   = $this->create_category( 'Cover category' );
		$attachment_id = $this->create_attachment();
		update_term_meta( $category_id, 'thumbnail_id', $attachment_id );
		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
		$markup    = <<<'HTML'
<!-- wp:woocommerce/featured-category {"layout":"cover","ariaLabel":"Browse Cover category"} -->
<!-- wp:cover {"url":"https://example.com/fallback.jpg","className":"wc-block-featured-category__cover","metadata":{"bindings":{"id":{"source":"woocommerce/term-image"},"url":{"source":"woocommerce/term-image"}}}} -->
<div class="wp-block-cover wc-block-featured-category__cover"><img class="wp-block-cover__image-background wp-image-999" alt="" src="https://example.com/fallback.jpg"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:button {"url":"https://example.com/custom"} --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.com/custom">Custom</a></div><!-- /wp:button --><!-- wp:woocommerce/category-title /--></div></div>
<!-- /wp:cover -->
<!-- /wp:woocommerce/featured-category -->
HTML;
		if ( ! $binding_preview ) {
			$markup = str_replace( '"bindings":{"id":{"source":"woocommerce/term-image"},"url":{"source":"woocommerce/term-image"}}', '"woocommerce/featured-category-image":{"id":0,"url":"https://example.com/fallback.jpg"}', $markup );
		}

		$output = $this->render_selected_category( $markup, $category_id );

		$this->assertStringContainsString( 'wp-block-woocommerce-featured-category', $output, 'The Cover should retain the Featured Category class.' );
		$this->assertStringContainsString( esc_url( (string) $image_url ), $output, 'The category thumbnail should replace the fallback image.' );
		$this->assertStringNotContainsString( 'https://example.com/fallback.jpg', $output, 'The fallback image should not remain in the rendered Cover.' );
		$this->assertStringNotContainsString( 'wp-image-999', $output, 'The old attachment class should be removed.' );
		$this->assertStringContainsString( 'href="https://example.com/custom"', $output, 'Custom button URLs should remain unchanged.' );
		$this->assertStringContainsString( 'aria-label="Browse Cover category"', $output, 'The category CTA should receive the accessible label.' );
		$this->assertStringContainsString( 'Cover category', $output, 'The category title should receive context through Cover.' );
	}

	/**
	 * @testdox Should replace a repeated Cover background with the selected category thumbnail.
	 */
	public function test_renders_category_thumbnail_in_repeated_cover(): void {
		$category_id   = $this->create_category( 'Repeated Cover category' );
		$attachment_id = $this->create_attachment();
		update_term_meta( $category_id, 'thumbnail_id', $attachment_id );
		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
		$markup    = <<<'HTML'
<!-- wp:woocommerce/featured-category {"layout":"cover"} -->
<!-- wp:cover {"url":"https://example.com/stale.jpg","isRepeated":true,"className":"wc-block-featured-category__cover","metadata":{"woocommerce/featured-category-image":{"id":0,"url":"https://example.com/stale.jpg"}}} -->
<div class="wp-block-cover is-repeated wc-block-featured-category__cover"><span aria-hidden="true" class="wp-block-cover__image-background" style="background-position:50% 50%;background-image:url(https://example.com/stale.jpg)"></span><div class="wp-block-cover__inner-container"></div></div>
<!-- /wp:cover -->
<!-- /wp:woocommerce/featured-category -->
HTML;

		$output = $this->render_selected_category( $markup, $category_id );

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
<!-- wp:woocommerce/featured-category {"layout":"cover"} -->
<!-- wp:cover {"url":"https://example.com/stale.jpg","id":999,"className":"wc-block-featured-category__cover","metadata":{"woocommerce/featured-category-image":{"id":999,"url":"https://example.com/stale.jpg"}}} -->
<div class="wp-block-cover wc-block-featured-category__cover"><img class="wp-block-cover__image-background wp-image-999" width="1200" height="800" src="https://example.com/stale.jpg" srcset="stale" sizes="100vw"/><div class="wp-block-cover__inner-container"></div></div>
<!-- /wp:cover -->
<!-- /wp:woocommerce/featured-category -->
HTML;

		$output = $this->render_selected_category( $markup, $category_id );

		$this->assertStringContainsString( esc_url( wc_placeholder_img_src() ), $output );
		$this->assertStringNotContainsString( 'wp-image-999', $output );
		$this->assertStringNotContainsString( 'width="1200"', $output );
		$this->assertStringNotContainsString( 'srcset=', $output );
	}

	/**
	 * @testdox Should leave custom images and third-party image bindings untouched.
	 * @testWith ["custom"]
	 *           ["binding"]
	 *           ["unmarked"]
	 * @param string $owner Image owner.
	 */
	public function test_does_not_replace_unmanaged_image( string $owner ): void {
		$category_id = $this->create_category( 'Other image' );
		update_term_meta( $category_id, 'thumbnail_id', $this->create_attachment() );
		$attributes = array(
			'url'       => 'https://example.com/custom.jpg',
			'className' => 'wc-block-featured-category__cover',
			'metadata'  => array(
				'woocommerce/featured-category-image' => array(
					'id'  => 0,
					'url' => 'https://example.com/stale.jpg',
				),
			),
		);
		if ( 'binding' === $owner ) {
			$attributes['metadata']['woocommerce/featured-category-image']['url'] = $attributes['url'];
			$attributes['metadata']['bindings']['url']                            = array( 'source' => 'test/other-image' );
		} elseif ( 'unmarked' === $owner ) {
			unset( $attributes['metadata'] );
		}
		$cover  = '<!-- wp:cover ' . wp_json_encode( $attributes ) . ' --><div class="wp-block-cover wc-block-featured-category__cover"><img class="wp-block-cover__image-background" src="https://example.com/custom.jpg"/><div class="wp-block-cover__inner-container"></div></div><!-- /wp:cover -->';
		$output = $this->render_selected_category( '<!-- wp:woocommerce/featured-category {"layout":"cover"} -->' . $cover . '<!-- /wp:woocommerce/featured-category -->', $category_id );
		$this->assertStringContainsString( 'src="https://example.com/custom.jpg"', $output );
	}

	/**
	 * Render block markup with an explicitly selected product category.
	 *
	 * @param string $markup      Serialized blocks.
	 * @param int    $category_id Product category ID.
	 * @return string
	 */
	private function render_selected_category( string $markup, int $category_id ): string {
		$parsed_block                          = parse_blocks( $markup )[0];
		$parsed_block['attrs']['categoryId']   = $category_id;
		$parsed_block['attrs']['termTaxonomy'] = 'product_cat';

		return render_block( $parsed_block );
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
