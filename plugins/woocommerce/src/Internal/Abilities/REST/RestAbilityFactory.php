<?php
/**
 * REST Ability Factory class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\REST;

use Automattic\WooCommerce\Internal\MCP\Transport\WooCommerceRestTransport;

defined( 'ABSPATH' ) || exit;

/**
 * Factory class for creating abilities from REST controllers.
 *
 * Handles the conversion of WooCommerce REST API endpoints into WordPress abilities
 * that can be consumed by MCP or other systems.
 */
class RestAbilityFactory {

	/**
	 * Register abilities for a REST controller based on configuration.
	 *
	 * @param array $config Controller configuration containing controller class and abilities array.
	 */
	public static function register_controller_abilities( array $config ): void {
		$controller_class = $config['controller'];

		if ( ! class_exists( $controller_class ) ) {
			return;
		}

		$controller = new $controller_class();

		foreach ( $config['abilities'] as $ability_config ) {
			self::register_single_ability( $controller, $ability_config, $config['route'] );
		}
	}

	/**
	 * Register a single ability.
	 *
	 * @param object $controller REST controller instance.
	 * @param array  $ability_config Ability configuration array.
	 * @param string $route REST route for this controller.
	 */
	private static function register_single_ability( $controller, array $ability_config, string $route ): void {
		// Only proceed if wp_register_ability function exists.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		try {
			$ability_args = array(
				'label'               => $ability_config['label'],
				'description'         => $ability_config['description'],
				'category'            => 'woocommerce-rest',
				'input_schema'        => self::get_schema_for_operation( $controller, $ability_config['operation'] ),
				'output_schema'       => self::get_output_schema( $controller, $ability_config['operation'] ),
				'execute_callback'    => function ( $input ) use ( $controller, $ability_config, $route ) {
					return self::execute_operation( $controller, $ability_config['operation'], $input, $route );
				},
				'permission_callback' => function () use ( $controller, $ability_config ) {
					return self::check_permission( $controller, $ability_config['operation'] );
				},
				'ability_class'       => RestAbility::class,
				'meta'                => array(
					'show_in_rest' => true,
				),
			);

			// Add readonly annotation for GET operations (list and get).
			if ( in_array( $ability_config['operation'], array( 'list', 'get' ), true ) ) {
				$ability_args['meta']['annotations'] = array(
					'readonly' => true,
				);
			}

			wp_register_ability( $ability_config['id'], $ability_args );
		} catch ( \Throwable $e ) {
			// Log the error for debugging but don't break the registration of other abilities.
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error(
					"Failed to register ability {$ability_config['id']}: " . $e->getMessage(),
					array( 'source' => 'woocommerce-rest-abilities' )
				);
			}
		}
	}

	/**
	 * Get input schema based on operation type.
	 *
	 * @param object $controller REST controller instance.
	 * @param string $operation Operation type (list, get, create, update, delete).
	 * @return array Input schema array.
	 */
	private static function get_schema_for_operation( $controller, string $operation ): array {
		switch ( $operation ) {
			case 'list':
				// Use controller's collection parameters.
				if ( method_exists( $controller, 'get_collection_params' ) ) {
					return self::sanitize_args_to_schema( $controller->get_collection_params() );
				}
				break;

			case 'create':
				// Use controller's creatable schema.
				if ( method_exists( $controller, 'get_endpoint_args_for_item_schema' ) ) {
					$args = $controller->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE );
					return self::sanitize_args_to_schema( $args );
				}
				break;

			case 'update':
				// Use controller's editable schema + ID.
				if ( method_exists( $controller, 'get_endpoint_args_for_item_schema' ) ) {
					$args   = $controller->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE );
					$schema = self::sanitize_args_to_schema( $args );

					// Add ID field for update operations.
					$schema['properties']['id'] = array(
						'type'        => 'integer',
						'description' => __( 'Unique identifier for the resource', 'woocommerce' ),
					);

					// Ensure ID is required.
					if ( ! isset( $schema['required'] ) ) {
						$schema['required'] = array();
					}
					if ( ! in_array( 'id', $schema['required'], true ) ) {
						$schema['required'][] = 'id';
					}

					return $schema;
				}
				break;

			case 'get':
			case 'delete':
				// Only need ID.
				return array(
					'type'       => 'object',
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => __( 'Unique identifier for the resource', 'woocommerce' ),
						),
					),
					'required'   => array( 'id' ),
				);
		}

		// Fallback.
		return array( 'type' => 'object' );
	}

	/**
	 * Valid JSON Schema types.
	 *
	 * @var array
	 */
	private static $valid_types = array( 'string', 'number', 'integer', 'boolean', 'object', 'array', 'null' );

	/**
	 * Sanitize WordPress REST args to valid JSON Schema format.
	 *
	 * Converts WordPress REST API argument arrays to JSON Schema by:
	 * - Removing PHP callbacks (sanitize_callback, validate_callback)
	 * - Converting 'required' from boolean-per-field to array-of-names
	 * - Removing WordPress-specific non-schema fields
	 * - Preserving valid JSON Schema properties
	 * - Converting invalid types (date-time, mixed, action) to valid JSON Schema
	 * - Recursively sanitizing nested properties and items
	 * - Deduplicating enum values
	 *
	 * @param array $args WordPress REST API arguments array.
	 * @return array Valid JSON Schema object.
	 */
	private static function sanitize_args_to_schema( array $args ): array {
		$properties = array();
		$required   = array();

		foreach ( $args as $key => $arg ) {
			$property = array();

			// Copy valid JSON Schema fields, normalizing types.
			if ( isset( $arg['type'] ) ) {
				$property = self::normalize_type( $property, $arg['type'] );
			}
			if ( isset( $arg['description'] ) ) {
				$property['description'] = $arg['description'];
			}
			if ( isset( $arg['default'] ) ) {
				$property['default'] = $arg['default'];
			}
			if ( isset( $arg['enum'] ) ) {
				$property['enum'] = self::dedupe_enum( $arg['enum'] );
			}
			if ( isset( $arg['items'] ) ) {
				$property['items'] = self::sanitize_schema( $arg['items'] );
			}
			if ( isset( $arg['minimum'] ) ) {
				$property['minimum'] = $arg['minimum'];
			}
			if ( isset( $arg['maximum'] ) ) {
				$property['maximum'] = $arg['maximum'];
			}
			if ( isset( $arg['format'] ) && ! isset( $property['format'] ) ) {
				$property['format'] = $arg['format'];
			}
			if ( isset( $arg['properties'] ) ) {
				$property['properties'] = self::sanitize_schema_properties( $arg['properties'] );
			}

			// Convert readonly to readOnly (JSON Schema format).
			if ( isset( $arg['readonly'] ) && $arg['readonly'] ) {
				$property['readOnly'] = true;
			}

			// Collect required fields.
			if ( isset( $arg['required'] ) && true === $arg['required'] ) {
				$required[] = $key;
			}

			$properties[ $key ] = $property;
		}

		$schema = array(
			'type'       => 'object',
			'properties' => $properties,
		);

		if ( ! empty( $required ) ) {
			$schema['required'] = array_unique( $required );
		}

		return $schema;
	}

	/**
	 * Recursively sanitize a JSON Schema node.
	 *
	 * Fixes invalid types, deduplicates enums, and recurses into
	 * nested properties and items.
	 *
	 * @param array $schema A JSON Schema node.
	 * @return array Sanitized schema node.
	 */
	private static function sanitize_schema( array $schema ): array {
		if ( isset( $schema['type'] ) ) {
			$schema = self::normalize_type( $schema, $schema['type'] );
		}

		if ( isset( $schema['enum'] ) ) {
			$schema['enum'] = self::dedupe_enum( $schema['enum'] );
		}

		// Remove WordPress-style boolean 'required' — JSON Schema requires an array.
		if ( isset( $schema['required'] ) && is_bool( $schema['required'] ) ) {
			unset( $schema['required'] );
		}

		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			// Collect required fields from nested boolean 'required' before sanitizing.
			$required = array();
			foreach ( $schema['properties'] as $key => $property ) {
				if ( is_array( $property ) && isset( $property['required'] ) && true === $property['required'] ) {
					$required[] = $key;
				}
			}
			if ( ! empty( $required ) ) {
				$schema['required'] = isset( $schema['required'] ) && is_array( $schema['required'] )
					? array_values( array_unique( array_merge( $schema['required'], $required ) ) )
					: $required;
			}

			$schema['properties'] = self::sanitize_schema_properties( $schema['properties'] );
		}

		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			$schema['items'] = self::sanitize_schema( $schema['items'] );
		}

		return $schema;
	}

	/**
	 * Sanitize a map of JSON Schema properties.
	 *
	 * @param array $properties Map of property name to schema.
	 * @return array Sanitized properties map.
	 */
	private static function sanitize_schema_properties( array $properties ): array {
		foreach ( $properties as $key => $property ) {
			if ( is_array( $property ) ) {
				$properties[ $key ] = self::sanitize_schema( $property );
			}
		}
		return $properties;
	}

	/**
	 * Normalize a schema type value.
	 *
	 * Handles both string types ('string', 'date-time', etc.) and
	 * array types (['string', 'null']) used for nullable fields.
	 *
	 * @param array        $schema The schema node being built.
	 * @param string|array $type   The type value to normalize.
	 * @return array Schema with normalized type (or type removed if all invalid).
	 */
	private static function normalize_type( array $schema, $type ): array {
		if ( is_string( $type ) ) {
			if ( 'date-time' === $type ) {
				$schema['type'] = 'string';
				if ( ! isset( $schema['format'] ) ) {
					$schema['format'] = 'date-time';
				}
			} elseif ( 'action' === $type ) {
				$schema['type'] = 'object';
			} elseif ( in_array( $type, self::$valid_types, true ) ) {
				$schema['type'] = $type;
			} else {
				unset( $schema['type'] );
			}
			return $schema;
		}

		if ( is_array( $type ) ) {
			$normalized = array();
			foreach ( $type as $single ) {
				if ( ! is_string( $single ) ) {
					continue;
				}
				if ( 'date-time' === $single ) {
					$single = 'string';
					if ( ! isset( $schema['format'] ) ) {
						$schema['format'] = 'date-time';
					}
				} elseif ( 'action' === $single ) {
					$single = 'object';
				} elseif ( ! in_array( $single, self::$valid_types, true ) ) {
					continue;
				}
				$normalized[] = $single;
			}
			$normalized = array_values( array_unique( $normalized ) );
			if ( empty( $normalized ) ) {
				unset( $schema['type'] );
			} elseif ( 1 === count( $normalized ) ) {
				$schema['type'] = $normalized[0];
			} else {
				$schema['type'] = $normalized;
			}
			return $schema;
		}

		// Non-string, non-array type — remove it.
		unset( $schema['type'] );
		return $schema;
	}

	/**
	 * Remove duplicate enum values while preserving order.
	 *
	 * Uses JSON encoding for fingerprinting to correctly handle
	 * mixed scalar types (1 vs '1'), nulls, and complex values (arrays).
	 *
	 * @param array $values Enum values.
	 * @return array Deduplicated enum values.
	 */
	private static function dedupe_enum( array $values ): array {
		$seen   = array();
		$unique = array();
		foreach ( $values as $value ) {
			$fingerprint = wp_json_encode( $value );
			if ( isset( $seen[ $fingerprint ] ) ) {
				continue;
			}
			$seen[ $fingerprint ] = true;
			$unique[]             = $value;
		}
		return $unique;
	}

	/**
	 * Get output schema for operation.
	 *
	 * @param object $controller REST controller instance.
	 * @param string $operation Operation type.
	 * @return array Output schema array.
	 */
	private static function get_output_schema( $controller, string $operation ): array {
		if ( method_exists( $controller, 'get_item_schema' ) ) {
			$schema = self::sanitize_schema( $controller->get_item_schema() );

			if ( 'list' === $operation ) {
				// For list operations, return object wrapping array of items.
				// This ensures MCP compatibility while maintaining REST structure.
				return array(
					'type'       => 'object',
					'properties' => array(
						'data' => array(
							'type'  => 'array',
							'items' => $schema,
						),
					),
				);
			} elseif ( 'delete' === $operation ) {
				// For delete operations, return simple confirmation.
				return array(
					'type'       => 'object',
					'properties' => array(
						'deleted'  => array( 'type' => 'boolean' ),
						'previous' => $schema,
					),
				);
			}

			// For get, create, update operations.
			return $schema;
		}

		return array( 'type' => 'object' );
	}

	/**
	 * Execute the REST operation.
	 *
	 * @param object $controller REST controller instance.
	 * @param string $operation Operation type.
	 * @param array  $input Input parameters.
	 * @param string $route REST route for this controller.
	 * @return mixed Operation result.
	 */
	private static function execute_operation( $controller, string $operation, array $input, string $route ) {
		$method = self::get_http_method_for_operation( $operation );

		// Build final route - add ID for single item operations.
		$request_route = $route;
		if ( isset( $input['id'] ) && in_array( $operation, array( 'get', 'update', 'delete' ), true ) ) {
			$request_route .= '/' . intval( $input['id'] );
			unset( $input['id'] );
		}

		// Create REST request.
		$request = new \WP_REST_Request( $method, $request_route );
		foreach ( $input as $key => $value ) {
			$request->set_param( $key, $value );
		}

		// Dispatch through REST API for proper validation and permissions.
		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response instanceof \WP_REST_Response ? $response->get_data() : $response;

		// For list operations, wrap in data object to match schema.
		if ( 'list' === $operation ) {
			return array( 'data' => $data );
		}

		return $data;
	}

	/**
	 * Get HTTP method for a given operation type.
	 *
	 * @param string $operation Operation type (list, get, create, update, delete).
	 * @return string HTTP method (GET, POST, PUT, DELETE).
	 */
	private static function get_http_method_for_operation( string $operation ): string {
		$method_map = array(
			'list'   => 'GET',
			'get'    => 'GET',
			'create' => 'POST',
			'update' => 'PUT',
			'delete' => 'DELETE',
		);
		return $method_map[ $operation ] ?? 'GET';
	}

	/**
	 * Check permissions for MCP operations.
	 *
	 * @param object $controller REST controller instance.
	 * @param string $operation Operation type.
	 * @return bool Whether permission is granted.
	 */
	private static function check_permission( $controller, string $operation ): bool {
		// Get HTTP method for the operation.
		$method = self::get_http_method_for_operation( $operation );

		/**
		 * Filter to check REST ability permissions for HTTP method.
		 *
		 * @since 10.3.0
		 * @param bool   $allowed    Whether the operation is allowed. Default false.
		 * @param string $method     HTTP method (GET, POST, PUT, DELETE).
		 * @param object $controller REST controller instance.
		 */
		return apply_filters( 'woocommerce_check_rest_ability_permissions_for_method', false, $method, $controller );
	}
}
