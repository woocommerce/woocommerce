<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Formatters;
use WC_Unit_Test_Case;
use ReflectionClass;

/**
 * Tests for the AbstractSchema class.
 *
 * @since 10.8.0
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
		$extend     = new ExtendSchema( $formatters );
		$controller = $this->createMock( SchemaController::class );

		$this->sut = $this->getMockForAbstractClass(
			AbstractSchema::class,
			array( $extend, $controller )
		);
	}

	/**
	 * @testdox Should remove arg_options from properties array.
	 */
	public function test_removes_arg_options_from_properties(): void {
		$properties = array(
			'name' => array(
				'type'        => 'string',
				'arg_options' => array( 'default' => 'test' ),
			),
		);

		$result = $this->invoke_remove_arg_options( $properties );

		$this->assertArrayNotHasKey( 'arg_options', $result['name'], 'arg_options should be removed' );
		$this->assertSame( 'string', $result['name']['type'], 'type property should remain' );
	}

	/**
	 * @testdox Should handle nested properties recursively.
	 */
	public function test_handles_nested_properties_recursively(): void {
		$properties = array(
			'address' => array(
				'type'       => 'object',
				'properties' => array(
					'city' => array(
						'type'        => 'string',
						'arg_options' => array( 'default' => 'Los Angeles' ),
					),
				),
			),
		);

		$result = $this->invoke_remove_arg_options( $properties );

		$this->assertArrayNotHasKey( 'arg_options', $result['address']['properties']['city'], 'nested arg_options should be removed' );
		$this->assertSame( 'string', $result['address']['properties']['city']['type'], 'nested type property should remain' );
	}

	/**
	 * @testdox Should handle items with properties recursively.
	 */
	public function test_handles_items_properties_recursively(): void {
		$properties = array(
			'tags' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'name' => array(
							'type'        => 'string',
							'arg_options' => array( 'default' => 'tag' ),
						),
					),
				),
			),
		);

		$result = $this->invoke_remove_arg_options( $properties );

		$this->assertArrayNotHasKey( 'arg_options', $result['tags']['items']['properties']['name'], 'items properties arg_options should be removed' );
		$this->assertSame( 'string', $result['tags']['items']['properties']['name']['type'], 'items properties type should remain' );
	}

	/**
	 * @testdox Should handle non-array property values without errors in PHP 8.4+.
	 */
	public function test_handles_non_array_property_values(): void {
		$properties = array(
			'title'       => 'Product Schema',
			'description' => array(
				'type'        => 'string',
				'arg_options' => array( 'default' => 'test' ),
			),
		);

		$result = $this->invoke_remove_arg_options( $properties );

		$this->assertSame( 'Product Schema', $result['title'], 'string property should remain unchanged' );
		$this->assertArrayNotHasKey( 'arg_options', $result['description'], 'arg_options should be removed from array property' );
	}

	/**
	 * @testdox Should handle empty properties array.
	 */
	public function test_handles_empty_properties_array(): void {
		$properties = array();

		$result = $this->invoke_remove_arg_options( $properties );

		$this->assertIsArray( $result, 'result should be an array' );
		$this->assertEmpty( $result, 'result should be empty' );
	}

	/**
	 * @testdox Should handle properties without arg_options.
	 */
	public function test_handles_properties_without_arg_options(): void {
		$properties = array(
			'id' => array(
				'type'     => 'integer',
				'readonly' => true,
			),
		);

		$result = $this->invoke_remove_arg_options( $properties );

		$this->assertSame( 'integer', $result['id']['type'], 'type property should remain' );
		$this->assertTrue( $result['id']['readonly'], 'readonly property should remain' );
	}

	/**
	 * Invoke the protected remove_arg_options method.
	 *
	 * @param array $properties Properties array.
	 * @return array Result from remove_arg_options.
	 */
	private function invoke_remove_arg_options( array $properties ): array {
		$reflection = new ReflectionClass( $this->sut );
		$method     = $reflection->getMethod( 'remove_arg_options' );
		$method->setAccessible( true );

		return $method->invoke( $this->sut, $properties );
	}
}
