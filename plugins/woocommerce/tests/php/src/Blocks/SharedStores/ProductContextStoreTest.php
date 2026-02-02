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
	 * @testdox Attribute formatting converts key-value pairs to object format.
	 */
	public function test_formats_key_value_attributes_to_objects(): void {
		$result = ProductContextStore::get_context_data(
			$this->consent,
			123,
			null,
			array(
				'pa_color' => 'red',
				'pa_size'  => 'large',
			)
		);

		$expected = array(
			array(
				'attribute' => 'pa_color',
				'value'     => 'red',
			),
			array(
				'attribute' => 'pa_size',
				'value'     => 'large',
			),
		);

		$this->assertSame( $expected, $result['_selectedAttributes'] );
	}

	/**
	 * @testdox Attribute formatting passes through already-formatted attributes unchanged.
	 */
	public function test_passes_through_formatted_attributes(): void {
		$attributes = array(
			array(
				'attribute' => 'pa_color',
				'value'     => 'red',
			),
		);

		$result = ProductContextStore::get_context_data( $this->consent, 123, null, $attributes );

		$this->assertSame( $attributes, $result['_selectedAttributes'] );
	}

	/**
	 * @testdox Attribute formatting handles mixed format attributes.
	 */
	public function test_handles_mixed_attribute_formats(): void {
		$result = ProductContextStore::get_context_data(
			$this->consent,
			123,
			null,
			array(
				'pa_color' => 'red',
				array(
					'attribute' => 'pa_size',
					'value'     => 'large',
				),
			)
		);

		$expected = array(
			array(
				'attribute' => 'pa_color',
				'value'     => 'red',
			),
			array(
				'attribute' => 'pa_size',
				'value'     => 'large',
			),
		);

		$this->assertSame( $expected, $result['_selectedAttributes'] );
	}
}
