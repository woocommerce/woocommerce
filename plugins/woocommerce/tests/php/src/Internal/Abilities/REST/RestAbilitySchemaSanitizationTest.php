<?php
/**
 * REST Ability Schema Sanitization Test class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\REST;

use Automattic\WooCommerce\Internal\Abilities\REST\RestAbilityFactory;
use ReflectionMethod;

/**
 * Tests for REST Ability Schema sanitization to ensure JSON Schema 2020-12 compliance.
 */
class RestAbilitySchemaSanitizationTest extends \WC_Unit_Test_Case {

	/**
	 * Test that date-time type fields are converted to string with format.
	 */
	public function test_sanitizes_date_time_type_to_string_with_format() {
		$input_args = array(
			'test_date' => array(
				'type'        => 'date-time',
				'description' => 'A date-time field',
			),
		);

		$result = $this->invoke_sanitize_args_to_schema( $input_args );

		$this->assertArrayHasKey( 'properties', $result );
		$this->assertArrayHasKey( 'test_date', $result['properties'] );
		$this->assertEquals( 'string', $result['properties']['test_date']['type'] );
		$this->assertEquals( 'date-time', $result['properties']['test_date']['format'] );
		$this->assertEquals( 'A date-time field', $result['properties']['test_date']['description'] );
	}

	/**
	 * Test that mixed type fields are converted to array of types.
	 */
	public function test_sanitizes_mixed_type_to_array_of_types() {
		$input_args = array(
			'test_mixed' => array(
				'type'        => 'mixed',
				'description' => 'A mixed type field',
			),
		);

		$result = $this->invoke_sanitize_args_to_schema( $input_args );

		$this->assertArrayHasKey( 'properties', $result );
		$this->assertArrayHasKey( 'test_mixed', $result['properties'] );
		$this->assertIsArray( $result['properties']['test_mixed']['type'] );
		$this->assertContains( 'string', $result['properties']['test_mixed']['type'] );
		$this->assertContains( 'number', $result['properties']['test_mixed']['type'] );
		$this->assertContains( 'boolean', $result['properties']['test_mixed']['type'] );
		$this->assertContains( 'object', $result['properties']['test_mixed']['type'] );
		$this->assertContains( 'array', $result['properties']['test_mixed']['type'] );
		$this->assertContains( 'null', $result['properties']['test_mixed']['type'] );
	}

	/**
	 * Test that WordPress-specific context fields are removed.
	 */
	public function test_removes_wordpress_context_fields() {
		$input_args = array(
			'test_field' => array(
				'type'        => 'string',
				'description' => 'A test field',
				'context'     => array( 'view', 'edit' ),
			),
		);

		$result = $this->invoke_sanitize_args_to_schema( $input_args );

		$this->assertArrayHasKey( 'properties', $result );
		$this->assertArrayHasKey( 'test_field', $result['properties'] );
		$this->assertArrayNotHasKey( 'context', $result['properties']['test_field'] );
		$this->assertEquals( 'string', $result['properties']['test_field']['type'] );
		$this->assertEquals( 'A test field', $result['properties']['test_field']['description'] );
	}

	/**
	 * Test that readonly field is converted to readOnly.
	 */
	public function test_converts_readonly_to_read_only() {
		$input_args = array(
			'test_readonly' => array(
				'type'        => 'string',
				'description' => 'A readonly field',
				'readonly'    => true,
			),
		);

		$result = $this->invoke_sanitize_args_to_schema( $input_args );

		$this->assertArrayHasKey( 'properties', $result );
		$this->assertArrayHasKey( 'test_readonly', $result['properties'] );
		$this->assertArrayNotHasKey( 'readonly', $result['properties']['test_readonly'] );
		$this->assertArrayHasKey( 'readOnly', $result['properties']['test_readonly'] );
		$this->assertTrue( $result['properties']['test_readonly']['readOnly'] );
	}

	/**
	 * Test that required fields are collected into required array.
	 */
	public function test_collects_required_fields() {
		$input_args = array(
			'required_field' => array(
				'type'     => 'string',
				'required' => true,
			),
			'optional_field' => array(
				'type' => 'string',
			),
		);

		$result = $this->invoke_sanitize_args_to_schema( $input_args );

		$this->assertArrayHasKey( 'required', $result );
		$this->assertIsArray( $result['required'] );
		$this->assertContains( 'required_field', $result['required'] );
		$this->assertNotContains( 'optional_field', $result['required'] );
	}

	/**
	 * Test that nested properties are recursively sanitized.
	 */
	public function test_recursively_sanitizes_nested_properties() {
		$input_args = array(
			'parent_field' => array(
				'type'       => 'object',
				'properties' => array(
					'nested_date' => array(
						'type'        => 'date-time',
						'description' => 'Nested date',
					),
					'nested_mixed' => array(
						'type'    => 'mixed',
						'context' => array( 'view' ),
					),
				),
			),
		);

		$result = $this->invoke_sanitize_args_to_schema( $input_args );

		$nested = $result['properties']['parent_field']['properties'];
		$this->assertEquals( 'string', $nested['nested_date']['type'] );
		$this->assertEquals( 'date-time', $nested['nested_date']['format'] );
		$this->assertIsArray( $nested['nested_mixed']['type'] );
		$this->assertArrayNotHasKey( 'context', $nested['nested_mixed'] );
	}

	/**
	 * Test that array items are recursively sanitized.
	 */
	public function test_recursively_sanitizes_array_items() {
		$input_args = array(
			'array_field' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'item_date' => array(
							'type' => 'date-time',
						),
					),
				),
			),
		);

		$result = $this->invoke_sanitize_args_to_schema( $input_args );

		$items = $result['properties']['array_field']['items'];
		$this->assertEquals( 'string', $items['properties']['item_date']['type'] );
		$this->assertEquals( 'date-time', $items['properties']['item_date']['format'] );
	}

	/**
	 * Test real-world product schema that was failing.
	 */
	public function test_handles_real_product_schema() {
		// Simulate problematic fields from the actual product schema.
		$input_args = array(
			'date_created'     => array(
				'type'        => 'date-time',
				'description' => 'The date the product was created, in the site\'s timezone.',
			),
			'date_created_gmt' => array(
				'type'        => 'date-time',
				'description' => 'The date the product was created, as GMT.',
			),
			'meta_data'        => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'    => array(
							'type'     => 'integer',
							'context'  => array( 'view', 'edit' ),
							'readonly' => true,
						),
						'key'   => array(
							'type'    => 'string',
							'context' => array( 'view', 'edit' ),
						),
						'value' => array(
							'type'    => 'mixed',
							'context' => array( 'view', 'edit' ),
						),
					),
				),
			),
			'id'               => array(
				'type'        => 'integer',
				'description' => 'Product ID',
				'required'    => true,
			),
		);

		$result = $this->invoke_sanitize_args_to_schema( $input_args );

		// Check date-time conversions.
		$this->assertEquals( 'string', $result['properties']['date_created']['type'] );
		$this->assertEquals( 'date-time', $result['properties']['date_created']['format'] );
		$this->assertEquals( 'string', $result['properties']['date_created_gmt']['type'] );
		$this->assertEquals( 'date-time', $result['properties']['date_created_gmt']['format'] );

		// Check nested meta_data array items.
		$meta_data_items = $result['properties']['meta_data']['items'];
		$this->assertArrayNotHasKey( 'context', $meta_data_items['properties']['id'] );
		$this->assertArrayHasKey( 'readOnly', $meta_data_items['properties']['id'] );
		$this->assertTrue( $meta_data_items['properties']['id']['readOnly'] );
		$this->assertIsArray( $meta_data_items['properties']['value']['type'] );
		$this->assertArrayNotHasKey( 'context', $meta_data_items['properties']['value'] );

		// Check required field collection.
		$this->assertContains( 'id', $result['required'] );
	}

	/**
	 * Helper method to invoke the private sanitize_args_to_schema method.
	 *
	 * @param array $args Input arguments to sanitize.
	 * @return array Sanitized schema.
	 */
	private function invoke_sanitize_args_to_schema( array $args ): array {
		$reflection = new ReflectionMethod( RestAbilityFactory::class, 'sanitize_args_to_schema' );
		$reflection->setAccessible( true );
		return $reflection->invoke( null, $args );
	}
}