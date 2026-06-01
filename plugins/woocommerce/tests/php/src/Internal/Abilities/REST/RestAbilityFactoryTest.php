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
	 * Ability IDs registered by these tests.
	 *
	 * @var array
	 */
	private $registered_ability_ids = array();

	/**
	 * Ability category IDs registered by these tests.
	 *
	 * @var array
	 */
	private $registered_ability_category_ids = array();

	/**
	 * Original value of $wp_actions['wp_abilities_api_init'] to restore in tearDown.
	 *
	 * @var int|null
	 */
	private $original_wp_abilities_api_init_action_count;

	/**
	 * Original value of $wp_actions['wp_abilities_api_categories_init'] to restore in tearDown.
	 *
	 * @var int|null
	 */
	private $original_wp_abilities_api_categories_init_action_count;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		$this->original_wp_abilities_api_init_action_count            = $wp_actions['wp_abilities_api_init'] ?? null;
		$this->original_wp_abilities_api_categories_init_action_count = $wp_actions['wp_abilities_api_categories_init'] ?? null;

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$abilities_bootstrap = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $abilities_bootstrap ) ) {
				require_once $abilities_bootstrap;
			}
		}
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		global $wp_actions;

		foreach ( $this->registered_ability_ids as $ability_id ) {
			if ( function_exists( 'wp_unregister_ability' ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
		$this->registered_ability_ids = array();

		foreach ( $this->registered_ability_category_ids as $category_id ) {
			if ( function_exists( 'wp_unregister_ability_category' ) ) {
				wp_unregister_ability_category( $category_id );
			}
		}
		$this->registered_ability_category_ids = array();

		if ( null !== $this->original_wp_abilities_api_init_action_count ) {
			$wp_actions['wp_abilities_api_init'] = $this->original_wp_abilities_api_init_action_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( isset( $wp_actions['wp_abilities_api_init'] ) ) {
			unset( $wp_actions['wp_abilities_api_init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( null !== $this->original_wp_abilities_api_categories_init_action_count ) {
			$wp_actions['wp_abilities_api_categories_init'] = $this->original_wp_abilities_api_categories_init_action_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( isset( $wp_actions['wp_abilities_api_categories_init'] ) ) {
			unset( $wp_actions['wp_abilities_api_categories_init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		parent::tearDown();
	}

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
	 * Register the test ability category if the suite has not already registered it.
	 *
	 * @param string $category_id Ability category ID.
	 */
	private function ensure_test_ability_category( string $category_id ): void {
		if ( ! function_exists( 'wp_register_ability_category' ) || ! function_exists( 'wp_has_ability_category' ) ) {
			return;
		}

		if ( wp_has_ability_category( $category_id ) ) {
			return;
		}

		$category = null;
		$callback = null;
		$callback = function () use ( &$category, $category_id, &$callback ) {
			remove_action( 'wp_abilities_api_categories_init', $callback );

			if ( wp_has_ability_category( $category_id ) ) {
				return;
			}

			$category = wp_register_ability_category(
				$category_id,
				array(
					'label'       => 'WooCommerce REST API',
					'description' => 'REST API operations for WooCommerce resources.',
				)
			);
		};

		add_action( 'wp_abilities_api_categories_init', $callback );
		do_action( 'wp_abilities_api_categories_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		remove_action( 'wp_abilities_api_categories_init', $callback );

		if ( null !== $category ) {
			$this->assertNotWPError( $category, 'Test ability category should register successfully.' );
			$this->assertNotNull( $category, 'Test ability category should register successfully.' );
			$this->registered_ability_category_ids[] = $category_id;
		}

		$this->assertTrue( wp_has_ability_category( $category_id ), 'Test ability category should be available.' );
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

	/**
	 * @testdox Should mark REST-derived abilities for the deprecated WooCommerce MCP endpoint.
	 */
	public function test_register_controller_abilities_marks_rest_abilities_for_deprecated_mcp(): void {
		$this->assertTrue( function_exists( 'wp_register_ability' ), 'Abilities API should be available.' );
		$this->assertTrue( class_exists( \WC_REST_Products_Controller::class ), 'Products REST controller should be available.' );
		$this->ensure_test_ability_category( 'woocommerce-rest' );

		$ability_id = 'woocommerce/rest-factory-metadata-test';
		$config     = array(
			'controller' => \WC_REST_Products_Controller::class,
			'route'      => '/wc/v3/products',
			'abilities'  => array(
				array(
					'id'          => $ability_id,
					'operation'   => 'list',
					'label'       => 'List REST factory test products',
					'description' => 'Retrieve REST factory test products.',
				),
			),
		);

		$callback = null;
		$callback = static function () use ( $config, &$callback ) {
			remove_action( 'wp_abilities_api_init', $callback );

			RestAbilityFactory::register_controller_abilities( $config );
		};

		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		remove_action( 'wp_abilities_api_init', $callback );

		$ability = wp_get_ability( $ability_id );

		$this->assertNotNull( $ability, 'REST-derived test ability should register successfully.' );
		$this->registered_ability_ids[] = $ability_id;
		$this->assertTrue( $ability->get_meta_item( 'show_in_rest', false ), 'REST-derived abilities should remain exposed through the Abilities REST API.' );
		$this->assertTrue( $ability->get_meta_item( RestAbilityFactory::EXPOSE_IN_DEPRECATED_MCP_META_KEY, false ), 'REST-derived abilities should opt in to the deprecated WooCommerce MCP endpoint.' );
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

	// ── Issue #64195: relax output schema for WC response quirks ──

	/**
	 * @testdox Should strip format date-time from output schema properties.
	 */
	public function test_output_schema_strips_format_date_time(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'date_created' => array( 'type' => 'date-time' ),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertArrayNotHasKey( 'format', $schema['properties']['date_created'], 'date-time format should be stripped from output schema (WC dates omit timezone)' );
	}

	/**
	 * @testdox Should strip format uri from output schema properties.
	 */
	public function test_output_schema_strips_format_uri(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'external_url' => array(
						'type'   => 'string',
						'format' => 'uri',
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertArrayNotHasKey( 'format', $schema['properties']['external_url'], 'uri format should be stripped from output schema (WC returns empty strings)' );
	}

	/**
	 * @testdox Should preserve formats other than date-time and uri in output schema.
	 */
	public function test_output_schema_preserves_other_formats(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'email'    => array(
						'type'   => 'string',
						'format' => 'email',
					),
					'hostname' => array(
						'type'   => 'string',
						'format' => 'hostname',
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertSame( 'email', $schema['properties']['email']['format'] );
		$this->assertSame( 'hostname', $schema['properties']['hostname']['format'] );
	}

	/**
	 * Expected widened scalar union: {@see RestAbilityFactory::OUTPUT_SCALAR_UNION}.
	 */
	private const SCALAR_UNION_WITH_NULL = array( 'string', 'integer', 'number', 'boolean', 'array', 'object', 'null' );

	/**
	 * @testdox Should widen any single scalar type to the full scalar union plus null in output schema.
	 */
	public function test_output_schema_widens_scalar_types_to_full_union(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'low_stock_amount' => array( 'type' => 'integer' ),
					'price'            => array( 'type' => 'number' ),
					'name'             => array( 'type' => 'string' ),
					'on_sale'          => array( 'type' => 'boolean' ),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $schema['properties']['low_stock_amount']['type'] );
		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $schema['properties']['price']['type'] );
		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $schema['properties']['name']['type'] );
		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $schema['properties']['on_sale']['type'] );
	}

	/**
	 * @testdox Should admit cross-scalar values for fields whose declared type disagrees with the actual response.
	 *
	 * Documents the motivating bug: several WooCommerce REST controllers declare a
	 * scalar type that does not match what they return — `shipping_class_id` is
	 * declared `string` but returned as `int`, `meta_data[].display_value` is
	 * declared `string` but can be array/object, etc. The widened union admits
	 * every scalar so structuredContent validation passes regardless of which
	 * scalar the controller actually emits.
	 */
	public function test_output_schema_admits_cross_scalar_values_for_mismatched_declarations(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'shipping_class_id' => array( 'type' => 'string' ),
					'image_id'          => array( 'type' => 'integer' ),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		foreach ( array( 'shipping_class_id', 'image_id' ) as $field ) {
			$this->assertSame( self::SCALAR_UNION_WITH_NULL, $schema['properties'][ $field ]['type'] );
			$this->assertContains( 'string', $schema['properties'][ $field ]['type'] );
			$this->assertContains( 'integer', $schema['properties'][ $field ]['type'] );
			$this->assertContains( 'array', $schema['properties'][ $field ]['type'] );
			$this->assertContains( 'object', $schema['properties'][ $field ]['type'] );
			$this->assertContains( 'null', $schema['properties'][ $field ]['type'] );
		}
	}

	/**
	 * @testdox Should admit array and object values for fields declared as a scalar (meta_data display_value case).
	 *
	 * `meta_data[].display_value` is declared as `string` in the orders schema
	 * but the REST controller returns whatever shape the underlying meta value
	 * has — including arrays for variation attributes and serialized custom
	 * data. Widening the output union to include `array` and `object` keeps
	 * structuredContent validation passing for those rows.
	 */
	public function test_output_schema_admits_array_and_object_for_declared_scalar_fields(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'display_value' => array( 'type' => 'string' ),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertContains( 'array', $schema['properties']['display_value']['type'] );
		$this->assertContains( 'object', $schema['properties']['display_value']['type'] );
	}

	/**
	 * @testdox Should not union null into object or array types in output schema.
	 */
	public function test_output_schema_does_not_union_null_into_compound_types(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'meta_data'  => array(
						'type'  => 'array',
						'items' => array( 'type' => 'object' ),
					),
					'attributes' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertSame( 'array', $schema['properties']['meta_data']['type'], 'array type should remain a single string' );
		$this->assertSame( 'object', $schema['properties']['attributes']['type'], 'object type should remain a single string' );
	}

	/**
	 * @testdox Should widen pre-existing scalar-plus-null unions to the full output union.
	 *
	 * `low_stock_amount` is declared as `[integer, null]` in the products
	 * schema but the controller returns an empty string when unset (via
	 * `set_low_stock_amount('')`), which neither member of the declared union
	 * admits. Widening scalar-only unions covers that case.
	 */
	public function test_output_schema_widens_pre_existing_scalar_unions(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'low_stock_amount' => array( 'type' => array( 'integer', 'null' ) ),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $schema['properties']['low_stock_amount']['type'] );
	}

	/**
	 * @testdox Should leave unions containing compound types untouched in output schema.
	 *
	 * If the schema author declared a union that includes `object` or `array`,
	 * trust that — widening would lose information without a known WC quirk to
	 * justify it.
	 */
	public function test_output_schema_leaves_compound_unions_untouched(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'payload' => array( 'type' => array( 'object', 'null' ) ),
					'mixed'   => array( 'type' => array( 'string', 'array' ) ),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertSame( array( 'object', 'null' ), $schema['properties']['payload']['type'] );
		$this->assertSame( array( 'string', 'array' ), $schema['properties']['mixed']['type'] );
	}

	/**
	 * @testdox Should relax nested properties and items in output schema.
	 */
	public function test_output_schema_relaxes_nested_properties_and_items(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'images' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'src'          => array(
									'type'   => 'string',
									'format' => 'uri',
								),
								'date_created' => array( 'type' => 'date-time' ),
								'id'           => array( 'type' => 'integer' ),
							),
						),
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$image = $schema['properties']['images']['items']['properties'];
		$this->assertArrayNotHasKey( 'format', $image['src'], 'Nested uri format should be stripped' );
		$this->assertArrayNotHasKey( 'format', $image['date_created'], 'Nested date-time format should be stripped' );
		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $image['id']['type'], 'Nested scalar should be widened to the full scalar union with null' );
	}

	/**
	 * @testdox Should strip format date-time set directly on a property (post-sanitization shape).
	 */
	public function test_output_schema_strips_format_date_time_when_set_directly(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'date_created' => array(
						'type'   => 'string',
						'format' => 'date-time',
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertArrayNotHasKey( 'format', $schema['properties']['date_created'], 'format: date-time should be stripped when declared directly, not only when arrived via the date-time pseudo-type' );
	}

	/**
	 * @testdox Should strip format date-time and uri even when the property has no type key.
	 */
	public function test_output_schema_strips_format_when_no_type_key(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'untyped_date' => array( 'format' => 'date-time' ),
					'untyped_uri'  => array( 'format' => 'uri' ),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertArrayNotHasKey( 'format', $schema['properties']['untyped_date'] );
		$this->assertArrayNotHasKey( 'format', $schema['properties']['untyped_uri'] );
	}

	/**
	 * @testdox Should relax the inner schema embedded in the delete operation wrapper.
	 */
	public function test_output_schema_delete_operation_relaxes_previous_schema(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'id'           => array( 'type' => 'integer' ),
					'date_created' => array(
						'type'   => 'string',
						'format' => 'date-time',
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'delete' );

		$this->assertSame( 'object', $schema['type'], 'delete wrapper outer type should remain object' );
		$this->assertSame( 'boolean', $schema['properties']['deleted']['type'], 'delete wrapper deleted flag should remain a single boolean' );

		$previous = $schema['properties']['previous'];
		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $previous['properties']['id']['type'], 'previous schema should have scalar widened to the full scalar union with null' );
		$this->assertArrayNotHasKey( 'format', $previous['properties']['date_created'], 'previous schema should have date-time format stripped' );
	}

	/**
	 * @testdox Should strip formats inside combiner sub-schemas but skip null-union there.
	 */
	public function test_output_schema_relaxes_combiner_sub_schemas(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'either' => array(
						'anyOf' => array(
							array(
								'type'   => 'string',
								'format' => 'date-time',
							),
							array( 'type' => 'integer' ),
						),
					),
					'one_of' => array(
						'oneOf' => array(
							array(
								'type'   => 'string',
								'format' => 'uri',
							),
						),
					),
					'all_of' => array(
						'allOf' => array(
							array( 'type' => 'number' ),
						),
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertArrayNotHasKey( 'format', $schema['properties']['either']['anyOf'][0], 'anyOf branch should have format: date-time stripped' );
		$this->assertSame( 'string', $schema['properties']['either']['anyOf'][0]['type'], 'null should not be unioned inside combiner branches' );
		$this->assertSame( 'integer', $schema['properties']['either']['anyOf'][1]['type'] );
		$this->assertArrayNotHasKey( 'format', $schema['properties']['one_of']['oneOf'][0], 'oneOf branch should have format: uri stripped' );
		$this->assertSame( 'string', $schema['properties']['one_of']['oneOf'][0]['type'] );
		$this->assertSame( 'number', $schema['properties']['all_of']['allOf'][0]['type'] );
	}

	/**
	 * @testdox Should preserve oneOf semantics by not adding null to every branch.
	 */
	public function test_output_schema_preserves_oneof_exactly_one_semantics(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'value' => array(
						'oneOf' => array(
							array( 'type' => 'string' ),
							array( 'type' => 'integer' ),
						),
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$branches = $schema['properties']['value']['oneOf'];
		$this->assertSame( 'string', $branches[0]['type'], 'oneOf branches must remain non-nullable so null does not match every branch and break the "exactly one" rule' );
		$this->assertSame( 'integer', $branches[1]['type'] );
	}

	/**
	 * @testdox Should not null-union scalars nested deep inside a oneOf branch.
	 */
	public function test_output_schema_propagates_no_null_union_into_nested_oneof_descendants(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'shape' => array(
						'oneOf' => array(
							array(
								'type'       => 'object',
								'properties' => array(
									'value' => array( 'type' => 'string' ),
								),
							),
							array(
								'type'       => 'object',
								'properties' => array(
									'value' => array( 'type' => 'integer' ),
								),
							),
						),
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$branches = $schema['properties']['shape']['oneOf'];
		$this->assertSame( 'string', $branches[0]['properties']['value']['type'], 'Nested property inside oneOf branch must not be unioned with null; otherwise {"value": null} matches both branches and breaks oneOf semantics' );
		$this->assertSame( 'integer', $branches[1]['properties']['value']['type'] );
	}

	/**
	 * @testdox Should normalize date-time pseudo-type inside tuple-form items.
	 */
	public function test_sanitize_schema_normalizes_tuple_form_items(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'pair' => array(
						'type'  => 'array',
						'items' => array(
							array( 'type' => 'date-time' ),
							array( 'type' => 'integer' ),
						),
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$entries = $schema['properties']['pair']['items'];
		$this->assertSame(
			self::SCALAR_UNION_WITH_NULL,
			$entries[0]['type'],
			'date-time pseudo-type must be normalized to string by sanitize_schema, then widened by relax'
		);
		$this->assertArrayNotHasKey( 'format', $entries[0], 'format: date-time emitted by sanitize_schema must then be stripped by relax' );
	}

	/**
	 * @testdox Should relax each entry of a tuple-form items array.
	 */
	public function test_output_schema_relaxes_tuple_form_items(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'       => 'object',
				'properties' => array(
					'pair' => array(
						'type'  => 'array',
						'items' => array(
							array(
								'type'   => 'string',
								'format' => 'date-time',
							),
							array( 'type' => 'integer' ),
						),
					),
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$items = $schema['properties']['pair']['items'];
		$this->assertArrayNotHasKey( 'format', $items[0], 'first tuple entry should have format stripped' );
		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $items[0]['type'], 'tuple entries are positional and not combiner branches, so scalar widening applies' );
		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $items[1]['type'] );
	}

	/**
	 * @testdox Should relax sub-schemas declared on additionalProperties.
	 */
	public function test_output_schema_relaxes_additional_properties(): void {
		$controller = $this->create_mock_controller_with_item_schema(
			array(
				'type'                 => 'object',
				'additionalProperties' => array(
					'type'   => 'string',
					'format' => 'uri',
				),
			)
		);

		$schema = $this->invoke_get_output_schema( $controller, 'get' );

		$this->assertArrayNotHasKey( 'format', $schema['additionalProperties'], 'additionalProperties schema should have format: uri stripped' );
		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $schema['additionalProperties']['type'] );
	}

	/**
	 * @testdox Should relax inner items for list operation output schema.
	 */
	public function test_output_schema_list_operation_relaxes_inner_items(): void {
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

		$this->assertSame( 'object', $schema['type'], 'list wrapper outer type should remain object' );
		$this->assertSame( 'array', $schema['properties']['data']['type'], 'list wrapper data property should remain array' );

		$item = $schema['properties']['data']['items'];
		$this->assertSame( self::SCALAR_UNION_WITH_NULL, $item['properties']['id']['type'] );
		$this->assertArrayNotHasKey( 'format', $item['properties']['date_created'] );
	}
}
