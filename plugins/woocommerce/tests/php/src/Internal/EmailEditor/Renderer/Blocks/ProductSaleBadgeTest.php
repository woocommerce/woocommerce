<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Product_Sale_Badge;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product_Sale_Badge email block renderer.
 */
class ProductSaleBadgeTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Product_Sale_Badge
	 */
	private $sut;

	/**
	 * Product on sale.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut     = new Product_Sale_Badge();
		$this->product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => 12,
				'sale_price'    => 9,
			)
		);
	}

	/**
	 * @testdox Shows the sale badge for a product on sale.
	 */
	public function test_product_on_sale_shows_badge(): void {
		$this->assertStringContainsString( '>Sale</span>', $this->render( $this->product->get_id() ) );
	}

	/**
	 * @testdox Shows no badge for a product that is not on sale.
	 */
	public function test_product_not_on_sale_shows_no_badge(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertStringNotContainsString( 'sale-badge__text', $this->render( $product->get_id() ) );
	}

	/**
	 * @testdox Uses the badge text from the woocommerce_sale_badge_text filter, escaped.
	 */
	public function test_badge_text_comes_from_filter_and_is_escaped(): void {
		add_filter(
			'woocommerce_sale_badge_text',
			function () {
				return '<b>Deal</b>';
			}
		);

		$html = $this->render( $this->product->get_id() );

		$this->assertStringContainsString( '&lt;b&gt;Deal&lt;/b&gt;', $html );
		$this->assertStringNotContainsString( '<b>Deal</b>', $html );
	}

	/**
	 * @testdox Places the badge and its email cell according to the alignment, and on the default side for any other value.
	 * @testWith ["left", "text-align:left;", "margin-right:auto;", "left"]
	 *           ["center", "text-align:center;", "margin-left:auto;margin-right:auto;", "center"]
	 *           ["right", "text-align:right;", "margin-left:auto;", "right"]
	 *           ["justify", "text-align:left;", "margin-right:auto;", "left"]
	 *
	 * @param string $align      Block alignment.
	 * @param string $text_align Text alignment of the badge.
	 * @param string $margins    Margins that position the badge.
	 * @param string $cell_align Alignment of the email table cell around the badge.
	 */
	public function test_badge_follows_alignment( string $align, string $text_align, string $margins, string $cell_align ): void {
		$html = $this->render( $this->product->get_id(), array( 'align' => $align ) );

		$this->assertSame( 1, preg_match( '/<span class="wc-block-components-product-sale-badge__text" style="([^"]*)"/', $html, $matches ) );
		$this->assertStringContainsString( $text_align, $matches[1] );
		$this->assertStringEndsWith( 'position:static;' . $margins, $matches[1] );
		$this->assertMatchesRegularExpression( '/class="email-product-sale-badge-cell"[^>]*align="' . $cell_align . '"/', $html );
	}

	/**
	 * Renders the block for a product.
	 *
	 * @param int   $product_id Product ID in the block context.
	 * @param array $attrs      Block attributes.
	 */
	private function render( int $product_id, array $attrs = array() ): string {
		$parsed_block = array(
			'blockName' => 'woocommerce/product-sale-badge',
			'attrs'     => $attrs,
			'context'   => array( 'postId' => $product_id ),
		);
		return $this->sut->render( '', $parsed_block, new Rendering_Context( new \WP_Theme_JSON() ) );
	}
}
