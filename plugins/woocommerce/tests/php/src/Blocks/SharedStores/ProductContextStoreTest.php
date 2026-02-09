<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\SharedStores\ProductContextStore;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductContextStore class.
 */
class ProductContextStoreTest extends WC_Unit_Test_Case {

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
}
