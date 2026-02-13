<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MarkdownProductFeed;

use Automattic\WooCommerce\Internal\MarkdownProductFeed\HtmlToMarkdown;
use Automattic\WooCommerce\Internal\MarkdownProductFeed\MarkdownRenderer;
use WC_Unit_Test_Case;

/**
 * Tests for the MarkdownRenderer class.
 */
class MarkdownRendererTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var MarkdownRenderer
	 */
	private MarkdownRenderer $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new MarkdownRenderer();
		$this->sut->init( new HtmlToMarkdown() );
	}

	/**
	 * @testdox Should render a simple product with front matter, title, price, stock status, and buy link.
	 */
	public function test_render_simple_product(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Test Widget' );
		$product->set_regular_price( '29.99' );
		$product->save();

		$result = $this->sut->render( $product );

		$this->assertStringContainsString( '---', $result, 'Output should contain front matter delimiters' );
		$this->assertStringContainsString( '# Test Widget', $result, 'Output should contain product name as H1 heading' );
		$this->assertStringContainsString( '29.99', $result, 'Output should contain the product price' );
		$this->assertStringContainsString( 'In stock', $result, 'Output should contain stock status' );
		$this->assertStringContainsString( '/checkout-link', $result, 'Output should contain a checkout link' );
		$this->assertStringContainsString( 'Add to cart', $result, 'Output should contain add-to-cart text' );

		$product->delete( true );
	}

	/**
	 * @testdox Should include SKU in the rendered output when product has one.
	 */
	public function test_render_includes_sku(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'SKU Product' );
		$product->set_sku( 'TEST-001' );
		$product->save();

		$result = $this->sut->render( $product );

		$this->assertStringContainsString( '**SKU:** TEST-001', $result, 'Output should contain the product SKU' );

		$product->delete( true );
	}

	/**
	 * @testdox Should include categories in the rendered output when product has them.
	 */
	public function test_render_includes_categories(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Categorized Product' );

		$term = wp_insert_term( 'Electronics', 'product_cat' );
		$product->set_category_ids( array( $term['term_id'] ) );
		$product->save();

		$result = $this->sut->render( $product );

		$this->assertStringContainsString( '**Categories:** Electronics', $result, 'Output should contain the product category' );

		$product->delete( true );
		wp_delete_term( $term['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Should render a compact summary with H2 heading and pipe-separated meta.
	 */
	public function test_render_summary(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_name( 'Summary Product' );
		$product->set_regular_price( '15.00' );
		$product->set_sku( 'SUM-001' );
		$product->save();

		$result = $this->sut->render_summary( $product );

		$this->assertStringContainsString( '## Summary Product', $result, 'Summary should use H2 heading' );
		$this->assertStringContainsString( '|', $result, 'Summary meta should be pipe-separated' );
		$this->assertStringContainsString( '**SKU:** SUM-001', $result, 'Summary should contain SKU' );
		$this->assertStringContainsString( '15.00', $result, 'Summary should contain price' );
		$this->assertStringContainsString( 'View product', $result, 'Summary should contain a details link' );

		$product->delete( true );
	}

	/**
	 * @testdox Should return true for a published product with default visibility.
	 */
	public function test_is_feed_visible_returns_true_for_published_product(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->save();

		$result = $this->sut->is_feed_visible( $product );

		$this->assertTrue( $result, 'Published product with visible catalog visibility should be feed-visible' );

		$product->delete( true );
	}

	/**
	 * @testdox Should return false for a draft product.
	 */
	public function test_is_feed_visible_returns_false_for_draft_product(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_status( 'draft' );
		$product->save();

		$result = $this->sut->is_feed_visible( $product );

		$this->assertFalse( $result, 'Draft product should not be feed-visible' );

		$product->delete( true );
	}

	/**
	 * @testdox Should return false for a product with hidden catalog visibility.
	 */
	public function test_is_feed_visible_returns_false_for_hidden_product(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->save();

		$result = $this->sut->is_feed_visible( $product );

		$this->assertFalse( $result, 'Hidden product should not be feed-visible' );

		$product->delete( true );
	}

	/**
	 * @testdox Should return empty string when product has no images.
	 */
	public function test_render_images_with_no_images(): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_image_id( 0 );
		$product->set_gallery_image_ids( array() );
		$product->save();

		$result = $this->sut->render_images( $product );

		$this->assertSame( '', $result, 'Product with no images should return empty string' );

		$product->delete( true );
	}

	/**
	 * @testdox Should return a checkout link URL containing /checkout-link and products= parameter.
	 */
	public function test_get_checkout_link_format(): void {
		$result = $this->sut->get_checkout_link( 42 );

		$this->assertStringContainsString( '/checkout-link', $result, 'Checkout link should contain /checkout-link path' );
		$this->assertStringContainsString( 'products=', $result, 'Checkout link should contain products query parameter' );
		$this->assertStringContainsString( '42', $result, 'Checkout link should contain the product ID' );
	}

	/**
	 * @testdox Should include a Variations section when rendering a variable product.
	 */
	public function test_render_variable_product_includes_variations(): void {
		$product = \WC_Helper_Product::create_variation_product();

		$result = $this->sut->render( $product );

		$this->assertStringContainsString( '## Variations', $result, 'Variable product output should contain a Variations section' );

		$product->delete( true );
	}
}
