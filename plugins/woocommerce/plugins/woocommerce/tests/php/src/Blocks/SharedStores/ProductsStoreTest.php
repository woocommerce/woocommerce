<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\SharedStores\ProductsStore;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductsStore class.
 */
class ProductsStoreTest extends WC_Unit_Test_Case {

	/**
	 * Consent string used by the experimental API.
	 *
	 * @var string
	 */
	private string $consent = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * Reset the static state between tests.
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
	 * @testdox load_product should hydrate product data into interactivity state keyed by product ID.
	 */
	public function test_load_product_hydrates_state(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$result = ProductsStore::load_product( $this->consent, $product->get_id() );

		$state = wp_interactivity_state( 'woocommerce/products' );

		$this->assertArrayHasKey( 'products', $state );
		$this->assertArrayHasKey( $product->get_id(), $state['products'] );
		$this->assertSame( $product->get_name(), $state['products'][ $product->get_id() ]['name'] );
		$this->assertSame( $product->get_name(), $result['name'], 'Return value should contain the product data' );

		$product->delete( true );
	}

	/**
	 * @testdox load_variations should hydrate all variations into interactivity state keyed by variation ID.
	 */
	public function test_load_variations_hydrates_state(): void {
		$product       = \WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();

		$result = ProductsStore::load_variations( $this->consent, $product->get_id() );

		$state = wp_interactivity_state( 'woocommerce/products' );

		$this->assertArrayHasKey( 'productVariations', $state );
		$this->assertNotEmpty( $result, 'Should return loaded variations' );

		foreach ( $variation_ids as $variation_id ) {
			$this->assertArrayHasKey( $variation_id, $state['productVariations'], "Variation $variation_id should be in state" );
		}

		$product->delete( true );
	}
}
