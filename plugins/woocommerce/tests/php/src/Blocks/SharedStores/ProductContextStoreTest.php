<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\SharedStores;

use Automattic\WooCommerce\Blocks\SharedStores\ProductContextStore;
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
