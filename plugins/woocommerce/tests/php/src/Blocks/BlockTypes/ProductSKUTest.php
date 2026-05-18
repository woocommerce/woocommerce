<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\Blocks\BlockTypes\ProductSKU;
use WC_Helper_Product;
use WP_Block;

/**
 * Tests for the ProductSKU block type.
 */
class ProductSKUTest extends \WP_UnitTestCase {

	/**
	 * System Under Test.
	 *
	 * @var \Automattic\WooCommerce\Blocks\BlockTypes\ProductSKU
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( \WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/product-sku' ) ) {
			unregister_block_type( 'woocommerce/product-sku' );
		}

		$asset_api            = Package::container()->get( AssetApi::class );
		$asset_data_registry  = Package::container()->get( AssetDataRegistry::class );
		$integration_registry = new IntegrationRegistry();

		$this->sut = new ProductSKU( $asset_api, $asset_data_registry, $integration_registry );
	}

	/**
	 * @testdox Should render parent SKU statically with no interactive attributes inside a query loop.
	 */
	public function test_renders_statically_inside_query_loop(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_sku( 'VAR-PARENT-SKU' );
		$product->save();

		// Simulate inside a query loop by setting block context directly.
		$block_instance = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-sku',
				'attrs'     => array(),
			)
		);
		$block_instance->context = array(
			'postId' => $product->get_id(),
			'query'  => array(),
		);

		$markup = $this->sut->render_callback( array(), '', $block_instance );

		$this->assertStringContainsString( 'VAR-PARENT-SKU', $markup, 'Static SKU should be rendered.' );
		$this->assertStringNotContainsString( 'data-wp-interactive', $markup, 'Should not contain interactive attributes.' );
		$this->assertStringNotContainsString( 'data-wp-text', $markup, 'Should not contain data-wp-text directive.' );

		$product->delete( true );
	}

	/**
	 * @testdox Should render with interactive attributes outside a query loop.
	 */
	public function test_renders_interactively_outside_query_loop(): void {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_sku( 'VAR-PARENT-SKU' );
		$product->save();

		// Simulate outside a query loop by setting block context directly.
		$block_instance = new WP_Block(
			array(
				'blockName' => 'woocommerce/product-sku',
				'attrs'     => array(),
			)
		);
		$block_instance->context = array(
			'postId' => $product->get_id(),
		);

		$markup = $this->sut->render_callback( array(), '', $block_instance );

		$this->assertStringContainsString( 'VAR-PARENT-SKU', $markup, 'Parent SKU should be rendered.' );
		$this->assertStringContainsString( 'data-wp-interactive="woocommerce/products"', $markup, 'Should contain interactive namespace.' );
		$this->assertStringContainsString( 'data-wp-text="state.productInContext.sku"', $markup, 'Should contain data-wp-text directive targeting SKU.' );

		$product->delete( true );
	}
}
