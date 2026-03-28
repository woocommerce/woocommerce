<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Tests for the SingleProduct block type.
 */
class SingleProductTest extends \WP_UnitTestCase {

	/**
	 * Tracks whether blocks have been registered.
	 *
	 * @var bool
	 */
	protected static $are_blocks_registered = false;

	/**
	 * Test that the product title block renders the product title, not the page title.
	 *
	 * This addresses WOOPLUG-6454 where the Product Block's title renders the current
	 * page/post title on the frontend instead of the product title when used in regular
	 * post content.
	 */
	public function test_product_title_renders_product_not_page_title(): void {
		$product = FixtureData::get_sample_simple_product();
		$product_id = $product->get_id();
		$product_title = $product->get_name();

		$page_id = $this->factory->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Test Page Title',
			)
		);

		global $post;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( $page_id );
		setup_postdata( $post );

		$markup = do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} -->' .
			'<!-- wp:core/post-title /-->' .
			'<!-- /wp:woocommerce/single-product -->'
		);

		wp_reset_postdata();

		$this->assertStringContainsString( $product_title, $markup, 'Product title should be rendered' );
		$this->assertStringNotContainsString( 'Test Page Title', $markup, 'Page title should not be rendered' );
	}

	/**
	 * Test that the product title block renders correctly with multiple inner blocks.
	 */
	public function test_product_title_with_multiple_inner_blocks(): void {
		$product = FixtureData::get_sample_simple_product();
		$product_id = $product->get_id();
		$product_title = $product->get_name();

		$page_id = $this->factory->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Another Page Title',
			)
		);

		global $post;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( $page_id );
		setup_postdata( $post );

		$markup = do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} -->' .
			'<!-- wp:woocommerce/product-image /-->' .
			'<!-- wp:core/post-title /-->' .
			'<!-- wp:woocommerce/product-price /-->' .
			'<!-- /wp:woocommerce/single-product -->'
		);

		wp_reset_postdata();

		$this->assertStringContainsString( $product_title, $markup, 'Product title should be rendered with multiple blocks' );
		$this->assertStringNotContainsString( 'Another Page Title', $markup, 'Page title should not be rendered with multiple blocks' );
	}

	/**
	 * Test that the product excerpt block renders the product excerpt, not the page excerpt.
	 */
	public function test_product_excerpt_renders_product_not_page_excerpt(): void {
		$product = FixtureData::get_sample_simple_product();
		$product_id = $product->get_id();
		$product_description = $product->get_short_description();

		$page_id = $this->factory->post->create(
			array(
				'post_type'    => 'page',
				'post_title'   => 'Test Page',
				'post_excerpt' => 'This is the page excerpt',
			)
		);

		global $post;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$post = get_post( $page_id );
		setup_postdata( $post );

		$markup = do_blocks(
			'<!-- wp:woocommerce/single-product {"productId":' . $product_id . '} -->' .
			'<!-- wp:core/post-excerpt /-->' .
			'<!-- /wp:woocommerce/single-product -->'
		);

		wp_reset_postdata();

		if ( ! empty( $product_description ) ) {
			$this->assertStringContainsString( $product_description, $markup, 'Product description should be rendered' );
		}
		$this->assertStringNotContainsString( 'This is the page excerpt', $markup, 'Page excerpt should not be rendered' );
	}
}
