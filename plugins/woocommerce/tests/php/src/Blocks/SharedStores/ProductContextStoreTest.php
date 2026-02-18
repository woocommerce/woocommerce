<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\SharedStores\ProductContextStore;
use Automattic\WooCommerce\Blocks\SharedStores\ProductsStore;
use WP_UnitTestCase;

/**
 * Tests for the ProductContextStore class.
 */
class ProductContextStoreTest extends WP_UnitTestCase {

	/**
	 * The consent statement required to use the experimental API.
	 *
	 * @var string
	 */
	private string $consent = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

	/**
	 * Reset static state between tests.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Reset ProductContextStore static state.
		$ref = new \ReflectionClass( ProductContextStore::class );

		$context_loaded = $ref->getProperty( 'context_loaded' );
		$context_loaded->setAccessible( true );
		$context_loaded->setValue( null, false );

		$context_state = $ref->getProperty( 'context_state' );
		$context_state->setAccessible( true );
		$context_state->setValue( null, array(
			'productId'   => 0,
			'variationId' => null,
		) );

		// Reset ProductsStore static state.
		$products_ref = new \ReflectionClass( ProductsStore::class );

		$products = $products_ref->getProperty( 'products' );
		$products->setAccessible( true );
		$products->setValue( null, array() );

		$variations = $products_ref->getProperty( 'product_variations' );
		$variations->setAccessible( true );
		$variations->setValue( null, array() );
	}

	/**
	 * @testdox get_context_data returns productId and variationId.
	 */
	public function test_get_context_data_returns_product_and_variation_ids(): void {
		$result = ProductContextStore::get_context_data(
			$this->consent,
			123,
			456
		);

		$this->assertArrayHasKey( 'productId', $result );
		$this->assertSame( 123, $result['productId'] );
		$this->assertArrayHasKey( 'variationId', $result );
		$this->assertSame( 456, $result['variationId'] );
	}

	/**
	 * @testdox get_context_data returns null variationId when not provided.
	 */
	public function test_get_context_data_returns_null_variation_id_when_not_provided(): void {
		$result = ProductContextStore::get_context_data(
			$this->consent,
			123
		);

		$this->assertArrayHasKey( 'productId', $result );
		$this->assertSame( 123, $result['productId'] );
		$this->assertArrayHasKey( 'variationId', $result );
		$this->assertNull( $result['variationId'] );
	}

	/**
	 * @testdox get_context_data does not include selectedAttributes.
	 */
	public function test_get_context_data_does_not_include_selected_attributes(): void {
		$result = ProductContextStore::get_context_data(
			$this->consent,
			123,
			456
		);

		$this->assertArrayNotHasKey( 'selectedAttributes', $result );
	}

	/**
	 * @testdox get_context_data throws exception without consent.
	 */
	public function test_get_context_data_throws_without_consent(): void {
		$this->expectException( \InvalidArgumentException::class );

		ProductContextStore::get_context_data(
			'wrong consent',
			123
		);
	}
}
