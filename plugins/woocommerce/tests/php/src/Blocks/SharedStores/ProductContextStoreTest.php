<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\SharedStores\ProductContextStore;
use Automattic\WooCommerce\Blocks\SharedStores\ProductsStore;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductContextStore class.
 */
class ProductContextStoreTest extends WC_Unit_Test_Case {

	/**
	 * Reset static state between tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$reflection = new \ReflectionClass( ProductsStore::class );

		$products = $reflection->getProperty( 'products' );
		$products->setAccessible( true );
		$products->setValue( null, array() );

		$variations = $reflection->getProperty( 'product_variations' );
		$variations->setAccessible( true );
		$variations->setValue( null, array() );

		parent::tearDown();
	}

	/**
	 * @testdox load_context should throw when consent statement is incorrect.
	 */
	public function test_load_context_throws_for_invalid_consent(): void {
		$this->expectException( InvalidArgumentException::class );

		ProductContextStore::load_context( 'wrong consent', 42 );
	}

	/**
	 * @testdox load_context should register interactivity state with product ID and null variation ID.
	 */
	public function test_load_context_registers_state_with_product_id(): void {
		ProductContextStore::load_context(
			'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
			42
		);

		$state = wp_interactivity_state( 'woocommerce/product-context' );

		$this->assertArrayHasKey( 'productId', $state, 'State should contain productId' );
		$this->assertSame( 42, $state['productId'], 'productId should match the provided value' );
		$this->assertArrayHasKey( 'variationId', $state, 'State should contain variationId' );
		$this->assertNull( $state['variationId'], 'variationId should default to null' );
	}

	/**
	 * @testdox load_context should register interactivity state with both product ID and variation ID.
	 */
	public function test_load_context_registers_state_with_variation_id(): void {
		ProductContextStore::load_context(
			'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
			42,
			99
		);

		$state = wp_interactivity_state( 'woocommerce/product-context' );

		$this->assertArrayHasKey( 'productId', $state, 'State should contain productId' );
		$this->assertSame( 42, $state['productId'], 'productId should match the provided value' );
		$this->assertArrayHasKey( 'variationId', $state, 'State should contain variationId' );
		$this->assertSame( 99, $state['variationId'], 'variationId should match the provided value' );
	}

	/**
	 * @testdox load_context should load the product into the products store.
	 */
	public function test_load_context_loads_product_into_products_store(): void {
		$product = \WC_Helper_Product::create_simple_product();

		ProductContextStore::load_context(
			'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
			$product->get_id()
		);

		$products_state = wp_interactivity_state( 'woocommerce/products' );

		$this->assertArrayHasKey( 'products', $products_state, 'Products store should contain products' );
		$this->assertArrayHasKey( $product->get_id(), $products_state['products'], 'Products store should contain the loaded product' );

		$product->delete( true );
	}

	/**
	 * @testdox load_context should load variations into the products store when variation ID is provided.
	 */
	public function test_load_context_loads_variations_when_variation_id_provided(): void {
		$product    = \WC_Helper_Product::create_variation_product();
		$variations = $product->get_children();

		ProductContextStore::load_context(
			'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
			$product->get_id(),
			$variations[0]
		);

		$products_state = wp_interactivity_state( 'woocommerce/products' );

		$this->assertArrayHasKey( 'productVariations', $products_state, 'Products store should contain productVariations' );
		$this->assertNotEmpty( $products_state['productVariations'], 'Product variations should not be empty' );

		$product->delete( true );
	}
}
