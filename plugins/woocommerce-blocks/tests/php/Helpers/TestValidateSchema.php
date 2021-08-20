<?php
/**
 * Ensures the helper works.
 */

namespace Automattic\WooCommerce\Blocks\Tests\Helpers;

require_once 'ValidateSchema.php';

/**
 * Test Validate schema.
 */
class TestValidateSchema extends \WP_UnitTestCase {
	/**
	 * ValidateSchema instance.
	 *
	 * @var ValidateSchema
	 */
	protected $validate;

	/**
	 * Setup schema.
	 */
	public function setUp() {
		$this->validate = new ValidateSchema(
			[
				'properties' => [
					'test_number'          => [
						'type' => 'number',
					],
					'test_string'          => [
						'type' => 'string',
					],
					'test_integer'         => [
						'type' => 'integer',
					],
					'test_object'          => [
						'type'       => 'object',
						'properties' => [
							'property_1' => [
								'type' => 'string',
							],
							'property_2' => [
								'type' => 'string',
							],
						],
					],
					'test_array'           => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'property_1' => [
									'type' => 'string',
								],
								'property_2' => [
									'type' => 'string',
								],
							],
						],
					],
					'test_integer_or_null' => [
						'type' => [ 'null', 'integer' ],
					],
				],
			]
		);
	}

	/**
	 * Validate an object.
	 */
	public function test_get_diff_from_valid_object() {
		$test_object = (object) [
			'test_number'          => 1.2,
			'test_string'          => 'Hello',
			'test_integer'         => 1,
			'test_object'          => (object) [
				'property_1' => 'Prop 1',
				'property_2' => 'Prop 2',
			],
			'test_array'           => [
				(object) [
					'property_1' => 'Prop 1',
					'property_2' => 'Prop 2',
				],
			],
			'test_integer_or_null' => null,
		];

		$diff = $this->validate->get_diff_from_object( $test_object );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}

	/**
	 * Validate an object.
	 */
	public function test_get_diff_from_invalid_object() {
		$test_object = (object) [
			'test_number'          => 'Invalid',
			'test_string'          => 666,
			'test_integer'         => 'Nope',
			'test_object'          => (object) [
				'property_1' => 1,
				'property_2' => 2,
			],
			'test_array'           => [
				(object) [
					'property_1'  => 1,
					'invalid_key' => 2,
				],
			],
			'test_integer_or_null' => 'string',
		];

		$diff = $this->validate->get_diff_from_object( $test_object );

		$this->assertContains( 'test_array:property_2', $diff['missing'], print_r( $diff['missing'], true ) );
		$this->assertContains( 'test_array:invalid_key', $diff['no_schema'], print_r( $diff['no_schema'], true ) );

		$this->assertContains( 'test_number (string, expected number)', $diff['invalid_type'], print_r( $diff['invalid_type'], true ) );
		$this->assertContains( 'test_string (integer, expected string)', $diff['invalid_type'], print_r( $diff['invalid_type'], true ) );
		$this->assertContains( 'test_integer (string, expected integer)', $diff['invalid_type'], print_r( $diff['invalid_type'], true ) );
		$this->assertContains( 'test_number (string, expected number)', $diff['invalid_type'], print_r( $diff['invalid_type'], true ) );
		$this->assertContains( 'test_array:property_1 (integer, expected string)', $diff['invalid_type'], print_r( $diff['invalid_type'], true ) );
		$this->assertContains( 'test_integer_or_null (string, expected null, integer)', $diff['invalid_type'], print_r( $diff['invalid_type'], true ) );
	}
}
