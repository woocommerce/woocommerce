<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Product_Price;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the Product_Price email block renderer.
 */
class ProductPriceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Product_Price
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Product_Price();
	}

	/**
	 * @testdox Shows the regular price of a simple product that is not on sale.
	 */
	public function test_simple_product_shows_regular_price(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 12 ) );

		$html = $this->render( $product->get_id() );

		$this->assertSame( '$12.00', $this->text( $html ) );
		$this->assertStringNotContainsString( '<del', $html );
	}

	/**
	 * @testdox Shows the regular price struck through, followed by the sale price.
	 */
	public function test_product_on_sale_shows_both_prices(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => 12,
				'sale_price'    => 9,
			)
		);

		$html = $this->render( $product->get_id() );

		$this->assertMatchesRegularExpression( '/<del[^>]*>.*12\.00.*<\/del><span>.*9\.00.*<\/span>/s', $html );
	}

	/**
	 * @testdox Shows no price for a product without a regular price.
	 */
	public function test_product_without_regular_price_renders_nothing(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '',
				'price'         => '',
			)
		);

		$this->assertSame( '', $this->text( $this->render( $product->get_id() ) ) );
	}

	/**
	 * @testdox Shows no price when there is no product in the block context.
	 */
	public function test_without_product_renders_nothing(): void {
		$this->assertSame( '', $this->text( $this->render( 0 ) ) );
	}

	/**
	 * @testdox Shows the price range of a variable product.
	 */
	public function test_variable_product_shows_price_range(): void {
		$product = $this->create_variable_product( array( 10, 20 ) );

		$this->assertSame( '$10.00 — $20.00', $this->text( $this->render( $product->get_id() ) ) );
	}

	/**
	 * @testdox Shows one price for a variable product whose variations all cost the same.
	 */
	public function test_variable_product_with_one_price_shows_it_once(): void {
		$product = $this->create_variable_product( array( 15, 15 ) );

		$this->assertSame( '$15.00', $this->text( $this->render( $product->get_id() ) ) );
	}

	/**
	 * @testdox Shows the regular price struck through for a variable product on sale at one price.
	 */
	public function test_variable_product_on_sale_at_one_price_shows_both_prices(): void {
		$product = $this->create_variable_product( array( 20, 20 ), 15 );

		$this->assertMatchesRegularExpression( '/<del[^>]*>.*20\.00.*<\/del><span>.*15\.00.*<\/span>/s', $this->render( $product->get_id() ) );
	}

	/**
	 * @testdox Still shows the struck-through price when a filter returns equal prices of different types.
	 */
	public function test_variable_product_on_sale_with_mixed_price_types(): void {
		$product = $this->create_variable_product( array( 20, 20 ), 15 );
		add_filter(
			'woocommerce_variation_prices',
			function ( $prices ) {
				foreach ( array( 'price', 'regular_price', 'sale_price' ) as $key ) {
					$ids                       = array_keys( $prices[ $key ] );
					$prices[ $key ][ $ids[0] ] = (float) $prices[ $key ][ $ids[0] ];
				}
				return $prices;
			}
		);

		$this->assertMatchesRegularExpression( '/<del[^>]*>.*20\.00.*<\/del><span>.*15\.00.*<\/span>/s', $this->render( $product->get_id() ) );
	}

	/**
	 * @testdox Shows no price for a variable product whose variations have no price.
	 */
	public function test_variable_product_without_prices_shows_no_price(): void {
		$product = $this->create_variable_product( array( '', '' ) );

		$this->assertSame( '', $this->text( $this->render( $product->get_id() ) ) );
	}

	/**
	 * @testdox Shows one price for a grouped product whose items all cost the same.
	 */
	public function test_grouped_product_with_one_price_shows_it_once(): void {
		$grouped = new \WC_Product_Grouped();
		$grouped->set_children(
			array(
				WC_Helper_Product::create_simple_product()->get_id(),
				WC_Helper_Product::create_simple_product()->get_id(),
			)
		);
		$grouped->save();

		$this->assertSame( '$10.00', $this->text( $this->render( $grouped->get_id() ) ) );
	}

	/**
	 * @testdox Shows the range of child prices for a grouped product.
	 */
	public function test_grouped_product_shows_child_price_range(): void {
		$product = WC_Helper_Product::create_grouped_product();

		$this->assertSame( '$1.00 — $10.00', $this->text( $this->render( $product->get_id() ) ) );
	}

	/**
	 * @testdox Shows no price for a grouped product with no children, or none with a price.
	 */
	public function test_grouped_product_without_priced_children_renders_nothing(): void {
		$grouped = new \WC_Product_Grouped();
		$grouped->save();
		$this->assertSame( '', $this->text( $this->render( $grouped->get_id() ) ), 'No children' );

		$child = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => '',
				'price'         => '',
			)
		);
		$grouped->set_children( array( $child->get_id() ) );
		$grouped->save();
		$this->assertSame( '', $this->text( $this->render( $grouped->get_id() ) ), 'Children without a price' );
	}

	/**
	 * @testdox Leaves items that are not published out of a grouped product's price range.
	 */
	public function test_grouped_product_ignores_unpublished_items(): void {
		$draft   = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price' => 1,
				'price'         => 1,
				'status'        => 'draft',
			)
		);
		$grouped = new \WC_Product_Grouped();
		$grouped->set_children( array( $draft->get_id(), WC_Helper_Product::create_simple_product()->get_id() ) );
		$grouped->save();

		$this->assertSame( '$10.00', $this->text( $this->render( $grouped->get_id() ) ) );
	}

	/**
	 * @testdox Counts grouped items that are hidden from the catalog, as the shop does.
	 */
	public function test_grouped_product_counts_catalog_hidden_items(): void {
		$cheap   = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price'      => 5,
				'price'              => 5,
				'catalog_visibility' => 'hidden',
			)
		);
		$dear    = WC_Helper_Product::create_simple_product( true, array( 'catalog_visibility' => 'hidden' ) );
		$grouped = new \WC_Product_Grouped();
		$grouped->set_children( array( $cheap->get_id(), $dear->get_id() ) );
		$grouped->save();

		$this->assertSame( '$5.00 — $10.00', $this->text( $this->render( $grouped->get_id() ) ) );
	}

	/**
	 * Renders the block for a product.
	 *
	 * @param int $product_id Product ID in the block context.
	 */
	private function render( int $product_id ): string {
		$parsed_block = array(
			'blockName' => 'woocommerce/product-price',
			'attrs'     => array(),
			'context'   => array( 'postId' => $product_id ),
		);
		return $this->sut->render( '', $parsed_block, new Rendering_Context( new \WP_Theme_JSON() ) );
	}

	/**
	 * The text a reader sees in the rendered HTML.
	 *
	 * @param string $html Rendered HTML.
	 */
	private function text( string $html ): string {
		return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES ) );
	}

	/**
	 * Creates a variable product with one variation per price.
	 *
	 * @param array    $prices     Variation regular prices.
	 * @param int|null $sale_price Sale price for every variation.
	 */
	private function create_variable_product( array $prices, ?int $sale_price = null ): \WC_Product_Variable {
		$attribute = WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large' ) );
		$product   = new \WC_Product_Variable();
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$sizes = array( 'small', 'large' );
		foreach ( $prices as $index => $price ) {
			$variation = WC_Helper_Product::create_product_variation_object( $product->get_id(), 'SKU-' . $product->get_id() . '-' . $index, $price, array( 'pa_size' => $sizes[ $index ] ) );
			if ( null !== $sale_price ) {
				$variation->set_sale_price( (string) $sale_price );
				$variation->save();
			}
		}
		\WC_Product_Variable::sync( $product->get_id() );

		return wc_get_product( $product->get_id() );
	}
}
