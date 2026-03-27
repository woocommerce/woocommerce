<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use WC_Unit_Test_Case;

/**
 * Tests for the AbstractSchema class.
 */
class AbstractSchemaTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var AbstractSchema
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );
		$extend     = new ExtendSchema( $formatters );
		$controller = new SchemaController( $extend );

		$this->sut = new class( $extend, $controller ) extends AbstractSchema {
			public function get_properties() {
				return array();
			}

			public function expose_remove_arg_options( $properties ) {
				return $this->remove_arg_options( $properties );
			}
		};
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @testdox Should handle array properties with arg_options correctly.
	 */
	public function test_remove_arg_options_with_array_properties(): void {
		$properties = array(
			'field1' => array(
				'type'        => 'string',
				'arg_options' => array(
					'default' => 'test',
				),
			),
			'field2' => array(
				'type'        => 'integer',
				'arg_options' => array(
					'default' => 42,
				),
			),
		);

		$result = $this->sut->expose_remove_arg_options( $properties );

		$this->assertArrayHasKey( 'field1', $result );
		$this->assertArrayHasKey( 'field2', $result );
		$this->assertArrayNotHasKey( 'arg_options', $result['field1'] );
		$this->assertArrayNotHasKey( 'arg_options', $result['field2'] );
		$this->assertSame( 'string', $result['field1']['type'] );
		$this->assertSame( 'integer', $result['field2']['type'] );
	}

	/**
	 * @testdox Should handle non-array properties without errors in PHP 8.5.
	 */
	public function test_remove_arg_options_with_non_array_properties(): void {
		$properties = array(
			'field1' => 'string',
			'field2' => array(
				'type'        => 'integer',
				'arg_options' => array(
					'default' => 42,
				),
			),
		);

		$result = $this->sut->expose_remove_arg_options( $properties );

		$this->assertArrayHasKey( 'field1', $result );
		$this->assertArrayHasKey( 'field2', $result );
		$this->assertSame( 'string', $result['field1'] );
		$this->assertArrayNotHasKey( 'arg_options', $result['field2'] );
		$this->assertSame( 'integer', $result['field2']['type'] );
	}

	/**
	 * @testdox Should recursively remove arg_options from nested properties.
	 */
	public function test_remove_arg_options_with_nested_properties(): void {
		$properties = array(
			'field1' => array(
				'type'        => 'object',
				'arg_options' => array(
					'default' => array(),
				),
				'properties'  => array(
					'nested1' => array(
						'type'        => 'string',
						'arg_options' => array(
							'default' => 'nested',
						),
					),
				),
			),
		);

		$result = $this->sut->expose_remove_arg_options( $properties );

		$this->assertArrayHasKey( 'field1', $result );
		$this->assertArrayNotHasKey( 'arg_options', $result['field1'] );
		$this->assertArrayHasKey( 'properties', $result['field1'] );
		$this->assertArrayHasKey( 'nested1', $result['field1']['properties'] );
		$this->assertArrayNotHasKey( 'arg_options', $result['field1']['properties']['nested1'] );
		$this->assertSame( 'string', $result['field1']['properties']['nested1']['type'] );
	}

	/**
	 * @testdox Should recursively remove arg_options from array item properties.
	 */
	public function test_remove_arg_options_with_array_items(): void {
		$properties = array(
			'field1' => array(
				'type'        => 'array',
				'arg_options' => array(
					'default' => array(),
				),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'item_field' => array(
							'type'        => 'string',
							'arg_options' => array(
								'default' => 'item',
							),
						),
					),
				),
			),
		);

		$result = $this->sut->expose_remove_arg_options( $properties );

		$this->assertArrayHasKey( 'field1', $result );
		$this->assertArrayNotHasKey( 'arg_options', $result['field1'] );
		$this->assertArrayHasKey( 'items', $result['field1'] );
		$this->assertArrayHasKey( 'properties', $result['field1']['items'] );
		$this->assertArrayHasKey( 'item_field', $result['field1']['items']['properties'] );
		$this->assertArrayNotHasKey( 'arg_options', $result['field1']['items']['properties']['item_field'] );
		$this->assertSame( 'string', $result['field1']['items']['properties']['item_field']['type'] );
	}

	/**
	 * @testdox Should handle mixed array and non-array properties.
	 */
	public function test_remove_arg_options_with_mixed_properties(): void {
		$properties = array(
			'string_field' => 'string',
			'array_field'  => array(
				'type'        => 'object',
				'arg_options' => array(
					'default' => array(),
				),
			),
			'another_string' => 'integer',
		);

		$result = $this->sut->expose_remove_arg_options( $properties );

		$this->assertArrayHasKey( 'string_field', $result );
		$this->assertArrayHasKey( 'array_field', $result );
		$this->assertArrayHasKey( 'another_string', $result );
		$this->assertSame( 'string', $result['string_field'] );
		$this->assertSame( 'integer', $result['another_string'] );
		$this->assertArrayNotHasKey( 'arg_options', $result['array_field'] );
		$this->assertSame( 'object', $result['array_field']['type'] );
	}
}
