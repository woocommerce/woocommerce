<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\REST;

use Automattic\WooCommerce\Internal\Abilities\REST\RestAbilityFactory;
use WC_Unit_Test_Case;

/**
 * Tests for the RestAbilityFactory class.
 *
 * Focuses on schema sanitization logic in sanitize_args_to_schema().
 */
class RestAbilityFactoryTest extends WC_Unit_Test_Case {

	/**
	 * Valid JSON Schema types per the spec.
	 */
	private const VALID_JSON_SCHEMA_TYPES = array( 'string', 'number', 'integer', 'boolean', 'object', 'array', 'null' );

	/**
	 * Helper to invoke the private sanitize_args_to_schema method.
	 *
	 * @param array $args WordPress REST API arguments array.
	 * @return array Sanitized JSON Schema.
	 */
	private function invoke_sanitize_args_to_schema( array $args ): array {
		$reflection = new \ReflectionClass( RestAbilityFactory::class );
		$method     = $reflection->getMethod( 'sanitize_args_to_schema' );
		$method->setAccessible( true );

		return $method->invoke( null, $args );
	}

	/**
	 * Helper to invoke the private get_output_schema method.
	 *
	 * @param object $controller REST controller instance.
	 * @param string $operation  Operation type.
	 * @return array Output schema.
	 */
	private function invoke_get_output_schema( $controller, string $operation ): array {
		$reflection = new \ReflectionClass( RestAbilityFactory::class );
		$method     = $reflection->getMethod( 'get_output_schema' );
		$method->setAccessible( true );

		return $method->invoke( null, $controller, $operation );
	}

	/**
	 * Recursively collect all 'type' values from a schema.
	 *
	 * @param array $schema JSON Schema array.
	 * @return array All type values found.
	 */
	private function collect_all_types( array $schema ): array {
		$types = array();

		if ( isset( $schema['type'] ) ) {
			if ( is_array( $schema['type'] ) ) {
				$types = array_merge( $types, $schema['type'] );
			} else {
				$types[] = $schema['type'];
			}
		}

		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $property ) {
				if ( is_array( $property ) ) {
					$types = array_merge( $types, $this->collect_all_types( $property ) );
				}
			}
		}

		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			$types = array_merge( $types, $this->collect_all_types( $schema['items'] ) );
		}

		return $types;
	}

	/**
	 * Create a mock controller with a given item schema.
	 *
	 * @param array $item_schema The schema to return from get_item_schema.
	 * @return object Mock controller.
	 */
	private function create_mock_controller_with_item_schema( array $item_schema ): object {
		return new class( $item_schema ) {
			/**
			 * The schema.
			 *
			 * @var array
			 */
			private array $schema;

			/**
			 * Constructor.
			 *
			 * @param array $schema The schema.
			 */
			public function __construct( array $schema ) {
				$this->schema = $schema;
			}

			/**
			 * Get item schema.
			 *
			 * @return array
			 */
			public function get_item_schema(): array {
				return $this->schema;
			}
		};
	}

	// ── Bug 1: date-time type conversion (issue #62764) ──

	/**
	 * @testdox Should convert date-time type to string with date-time format.
	 */
	public function test_converts_date_time_type_to_string_with_format(): void {
		$args = array(
			'date_created' => array(
				'type'        => 'date-time',
				'description' => 'The date the resource was created.',
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( 'string', $schema['properties']['date_created']['type'], 'date-time should be converted to string type' );
		$this->assertSame( 'date-time', $schema['properties']['date_created']['format'], 'date-time format should be set' );
	}

	/**
	 * @testdox Should preserve explicit format when converting date-time type.
	 */
	public function test_date_time_conversion_preserves_explicit_format(): void {
		$args = array(
			'date_field' => array(
				'type'   => 'date-time',
				'format' => 'date-time',
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( 'string', $schema['properties']['date_field']['type'] );
		$this->assertSame( 'date-time', $schema['properties']['date_field']['format'] );
	}

	// ── Bug 2: duplicate enum values (issue #62034) ──

	/**
	 * @testdox Should deduplicate enum values.
	 */
	public function test_deduplicates_enum_values(): void {
		$args = array(
			'orderby' => array(
				'type' => 'string',
				'enum' => array( 'date', 'id', 'title', 'price', 'popularity', 'rating', 'price', 'popularity', 'rating' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$enum = $schema['properties']['orderby']['enum'];
		$this->assertSame( array_values( array_unique( $enum ) ), $enum, 'Enum should not contain duplicate values' );
		$this->assertCount( 6, $enum );
	}

	/**
	 * @testdox Should reindex enum values after deduplication.
	 */
	public function test_enum_values_are_reindexed(): void {
		$args = array(
			'status' => array(
				'type' => 'string',
				'enum' => array( 'draft', 'published', 'draft' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( array( 'draft', 'published' ), $schema['properties']['status']['enum'] );
	}

	// ── Gap 1: invalid types like 'mixed' and 'action' ──

	/**
	 * @testdox Should handle type mixed by removing the type key.
	 */
	public function test_handles_mixed_type(): void {
		$args = array(
			'value' => array(
				'type'        => 'mixed',
				'description' => 'Meta value.',
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertArrayNotHasKey( 'type', $schema['properties']['value'], 'mixed type should be removed' );
		$this->assertSame( 'Meta value.', $schema['properties']['value']['description'] );
	}

	/**
	 * @testdox Should handle type action by converting to object.
	 */
	public function test_handles_action_type(): void {
		$args = array(
			'line_items' => array(
				'type'        => 'action',
				'description' => 'Line items.',
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( 'object', $schema['properties']['line_items']['type'], 'action type should be converted to object' );
	}

	/**
	 * @testdox Should remove any unrecognized type value.
	 */
	public function test_handles_unrecognized_type(): void {
		$args = array(
			'field' => array(
				'type'        => 'foobar',
				'description' => 'Unknown type field.',
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertArrayNotHasKey( 'type', $schema['properties']['field'], 'Unrecognized type should be removed' );
	}

	/**
	 * @testdox Should preserve all valid JSON Schema types.
	 */
	public function test_preserves_valid_types(): void {
		$args = array();
		foreach ( self::VALID_JSON_SCHEMA_TYPES as $type ) {
			$args[ $type . '_field' ] = array( 'type' => $type );
		}

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		foreach ( self::VALID_JSON_SCHEMA_TYPES as $type ) {
			$this->assertSame( $type, $schema['properties'][ $type . '_field' ]['type'], "Valid type '$type' should be preserved" );
		}
	}

	/**
	 * @testdox Should collect required fields correctly.
	 */
	public function test_collects_required_fields(): void {
		$args = array(
			'name'  => array(
				'type'     => 'string',
				'required' => true,
			),
			'price' => array(
				'type'     => 'string',
				'required' => true,
			),
			'sku'   => array(
				'type'     => 'string',
				'required' => false,
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertArrayHasKey( 'required', $schema );
		$this->assertContains( 'name', $schema['required'] );
		$this->assertContains( 'price', $schema['required'] );
		$this->assertNotContains( 'sku', $schema['required'] );
	}

	// ── Gap 3: recursive sanitization of nested properties/items ──

	/**
	 * @testdox Should recursively sanitize nested properties with invalid types.
	 */
	public function test_sanitizes_nested_properties(): void {
		$args = array(
			'meta_data' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'key'   => array( 'type' => 'string' ),
						'value' => array( 'type' => 'mixed' ),
					),
				),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$all_types = $this->collect_all_types( $schema );
		$this->assertNotContains( 'mixed', $all_types, 'Nested mixed type should be sanitized' );
	}

	/**
	 * @testdox Should recursively sanitize date-time in nested items.
	 */
	public function test_sanitizes_nested_date_time(): void {
		$args = array(
			'dates' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'created_at' => array( 'type' => 'date-time' ),
						'updated_at' => array( 'type' => 'date-time' ),
					),
				),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$all_types = $this->collect_all_types( $schema );
		$this->assertNotContains( 'date-time', $all_types, 'Nested date-time type should be converted' );

		$created = $schema['properties']['dates']['items']['properties']['created_at'];
		$this->assertSame( 'string', $created['type'] );
		$this->assertSame( 'date-time', $created['format'] );
	}

	/**
	 * @testdox Should recursively deduplicate nested enums.
	 */
	public function test_sanitizes_nested_enums(): void {
		$args = array(
			'filter' => array(
				'type'       => 'object',
				'properties' => array(
					'status' => array(
						'type' => 'string',
						'enum' => array( 'active', 'inactive', 'active' ),
					),
				),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$enum = $schema['properties']['filter']['properties']['status']['enum'];
		$this->assertCount( 2, $enum, 'Nested enum should be deduplicated' );
		$this->assertSame( array( 'active', 'inactive' ), $enum );
	}

	// ── Gap 2: output schema sanitization ──

	/**
	 * @testdox Should sanitize output schema types for get operations.
	 */
	public function test_sanitizes_output_schema_types(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'id'           => array( 'type' => 'integer' ),
					'date_created' => array( 'type' => 'date-time' ),
					'meta_data'    => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'value' => array( 'type' => 'mixed' ),
							),
						),
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$all_types = $this->collect_all_types( $schema );
		$this->assertNotContains( 'date-time', $all_types, 'Output schema should not contain date-time type' );
		$this->assertNotContains( 'mixed', $all_types, 'Output schema should not contain mixed type' );
	}

	/**
	 * @testdox Should sanitize output schema types for list operations.
	 */
	public function test_sanitizes_output_schema_for_list_operations(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'id'           => array( 'type' => 'integer' ),
					'date_created' => array( 'type' => 'date-time' ),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'list' );

		$all_types = $this->collect_all_types( $schema );
		$this->assertNotContains( 'date-time', $all_types, 'Output schema for list should not contain date-time type' );
	}

	// ── Array types (nullable fields) ──

	/**
	 * @testdox Should normalize array type with valid types preserved.
	 */
	public function test_normalizes_array_type_with_valid_types(): void {
		$args = array(
			'name' => array(
				'type' => array( 'string', 'null' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( array( 'string', 'null' ), $schema['properties']['name']['type'], 'Valid array types should be preserved' );
	}

	/**
	 * @testdox Should filter invalid types from array type and keep valid ones.
	 */
	public function test_filters_invalid_types_from_array_type(): void {
		$args = array(
			'value' => array(
				'type' => array( 'mixed', 'string', 'null' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( array( 'string', 'null' ), $schema['properties']['value']['type'], 'Invalid types should be filtered from array' );
	}

	/**
	 * @testdox Should convert date-time in array type to string and set format.
	 */
	public function test_converts_date_time_in_array_type(): void {
		$args = array(
			'created' => array(
				'type' => array( 'date-time', 'null' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( array( 'string', 'null' ), $schema['properties']['created']['type'] );
		$this->assertSame( 'date-time', $schema['properties']['created']['format'] );
	}

	/**
	 * @testdox Should convert action in array type to object.
	 */
	public function test_converts_action_in_array_type(): void {
		$args = array(
			'field' => array(
				'type' => array( 'action', 'null' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( array( 'object', 'null' ), $schema['properties']['field']['type'] );
	}

	/**
	 * @testdox Should deduplicate array types after normalization.
	 */
	public function test_deduplicates_array_types_after_normalization(): void {
		$args = array(
			'field' => array(
				'type' => array( 'date-time', 'string' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( 'string', $schema['properties']['field']['type'], 'Should collapse to single type after dedup' );
	}

	/**
	 * @testdox Should remove type key when all array types are invalid.
	 */
	public function test_removes_type_when_all_array_types_invalid(): void {
		$args = array(
			'field' => array(
				'type'        => array( 'mixed', 'foobar' ),
				'description' => 'All bad types.',
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertArrayNotHasKey( 'type', $schema['properties']['field'], 'Should remove type when all array types are invalid' );
		$this->assertSame( 'All bad types.', $schema['properties']['field']['description'] );
	}

	/**
	 * @testdox Should handle non-string values in array type.
	 */
	public function test_skips_non_string_values_in_array_type(): void {
		$args = array(
			'field' => array(
				'type' => array( 'string', 123, null, 'integer' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( array( 'string', 'integer' ), $schema['properties']['field']['type'], 'Non-string values in type array should be skipped' );
	}

	// ── Robust enum deduplication ──

	/**
	 * @testdox Should deduplicate enum with mixed scalar and complex values.
	 */
	public function test_deduplicates_enum_with_mixed_value_types(): void {
		$args = array(
			'value' => array(
				'type' => 'string',
				'enum' => array(
					1,
					'1',
					null,
					null,
					array( 'a' => 1 ),
					array( 'a' => 1 ),
					array( 'a' => 2 ),
				),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$enum = $schema['properties']['value']['enum'];
		$this->assertCount( 5, $enum, 'Should have 5 unique values: 1, "1", null, {a:1}, {a:2}' );
		$this->assertSame(
			array( 1, '1', null, array( 'a' => 1 ), array( 'a' => 2 ) ),
			$enum
		);
	}

	// ── Nested required boolean conversion ──

	/**
	 * @testdox Should lift boolean required from nested properties to parent required array.
	 */
	public function test_lifts_nested_boolean_required_to_parent_array(): void {
		$args = array(
			'gift_cards' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'code'   => array(
							'type'     => 'string',
							'required' => true,
						),
						'amount' => array(
							'type'     => 'number',
							'required' => false,
						),
					),
				),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$items = $schema['properties']['gift_cards']['items'];

		$this->assertArrayNotHasKey( 'required', $items['properties']['code'], 'Boolean required should be removed from property' );
		$this->assertArrayNotHasKey( 'required', $items['properties']['amount'], 'Boolean required should be removed from property' );

		$this->assertArrayHasKey( 'required', $items, 'Parent object should have required array' );
		$this->assertContains( 'code', $items['required'], 'code should be in parent required array' );
		$this->assertNotContains( 'amount', $items['required'], 'amount should not be in parent required array' );
	}

	// ── Realistic scenario ──

	/**
	 * @testdox Should sanitize realistic collection params with multiple issues.
	 */
	public function test_sanitizes_realistic_collection_params(): void {
		$args = array(
			'after'    => array(
				'type'        => 'date-time',
				'description' => 'Limit response to resources published after a given date.',
			),
			'before'   => array(
				'type'        => 'date-time',
				'description' => 'Limit response to resources published before a given date.',
			),
			'per_page' => array(
				'type'    => 'integer',
				'default' => 10,
				'minimum' => 1,
				'maximum' => 100,
			),
			'orderby'  => array(
				'type'    => 'string',
				'default' => 'date',
				'enum'    => array( 'date', 'id', 'title', 'slug', 'price', 'popularity', 'rating', 'menu_order', 'price', 'popularity', 'rating' ),
			),
			'status'   => array(
				'type'    => 'string',
				'default' => 'any',
				'enum'    => array( 'any', 'draft', 'pending', 'private', 'publish' ),
			),
		);

		$schema = $this->invoke_sanitize_args_to_schema( $args );

		$this->assertSame( 'string', $schema['properties']['after']['type'] );
		$this->assertSame( 'date-time', $schema['properties']['after']['format'] );
		$this->assertSame( 'string', $schema['properties']['before']['type'] );
		$this->assertSame( 'date-time', $schema['properties']['before']['format'] );
		$this->assertSame( 'integer', $schema['properties']['per_page']['type'] );
		$this->assertArrayNotHasKey( 'format', $schema['properties']['per_page'] );

		$orderby_enum = $schema['properties']['orderby']['enum'];
		$this->assertCount( count( array_unique( $orderby_enum ) ), $orderby_enum, 'orderby enum should have no duplicates' );
		$this->assertCount( 8, $orderby_enum );
		$this->assertCount( 5, $schema['properties']['status']['enum'] );
	}
}
