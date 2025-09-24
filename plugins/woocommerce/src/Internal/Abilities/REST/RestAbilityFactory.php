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
			wp_register_ability(
				$ability_config['id'],
				array(
					'label'               => $ability_config['label'],
					'description'         => $ability_config['description'],
					'input_schema'        => self::get_schema_for_operation( $controller, $ability_config['operation'] ),
					'output_schema'       => self::get_output_schema( $controller, $ability_config['operation'] ),
					'execute_callback'    => function ( $input ) use ( $controller, $ability_config, $route ) {
						return self::execute_operation( $controller, $ability_config['operation'], $input, $route );
					},
					'permission_callback' => function () use ( $controller, $ability_config ) {
						return self::check_permission( $controller, $ability_config['operation'] );
					},
					'ability_class'       => RestAbility::class,
				)
			);
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
	 * Sanitize WordPress REST args to valid JSON Schema format.
	 *
	 * Converts WordPress REST API argument arrays to JSON Schema by:
	 * - Converting 'date-time' type to string with format
	 * - Handling 'mixed' types appropriately
	 * - Removing PHP callbacks (sanitize_callback, validate_callback)
	 * - Converting 'required' from boolean-per-field to array-of-names
	 * - Removing WordPress-specific non-schema fields (context)
	 * - Converting 'readonly' to 'readOnly'
	 * - Recursively sanitizing nested properties and items
	 * - Preserving valid JSON Schema properties
	 *
	 * @param array $args WordPress REST API arguments array.
	 * @return array Valid JSON Schema object.
	 */
	private static function sanitize_args_to_schema( array $args ): array {
		$properties = array();
		$required   = array();

		foreach ( $args as $key => $arg ) {
			$property = self::sanitize_single_property( $arg );

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
	 * Sanitize a single property to JSON Schema 2020-12 format.
	 *
	 * @param array $arg Single property definition from WordPress REST API.
	 * @return array Sanitized property for JSON Schema.
	 */
	private static function sanitize_single_property( array $arg ): array {
		$property = array();

		// Handle type field with JSON Schema 2020-12 compliance.
		if ( isset( $arg['type'] ) ) {
			if ( 'date-time' === $arg['type'] ) {
				// Convert date-time type to string with format.
				$property['type'] = 'string';
				$property['format'] = 'date-time';
			} elseif ( 'mixed' === $arg['type'] ) {
				// Convert mixed type to array of all possible types.
				$property['type'] = array( 'string', 'number', 'boolean', 'object', 'array', 'null' );
			} else {
				$property['type'] = $arg['type'];
			}
		}

		// Copy valid JSON Schema fields.
		$valid_fields = array( 'description', 'enum', 'minimum', 'maximum', 'format' );
		foreach ( $valid_fields as $field ) {
			if ( isset( $arg[ $field ] ) ) {
				if ( 'enum' === $field ) {
					$property['enum'] = array_values( $arg['enum'] );
				} else {
					$property[ $field ] = $arg[ $field ];
				}
			}
		}

		// Handle items with recursive sanitization.
		if ( isset( $arg['items'] ) ) {
			$property['items'] = self::sanitize_nested_schema( $arg['items'] );
		}

		// Handle nested properties with recursive sanitization.
		if ( isset( $arg['properties'] ) ) {
			$property['properties'] = array();
			foreach ( $arg['properties'] as $nested_key => $nested_prop ) {
				$property['properties'][ $nested_key ] = self::sanitize_single_property( $nested_prop );
			}
		}

		// Convert readonly to readOnly (JSON Schema format).
		$is_readonly = false;
		if ( isset( $arg['readonly'] ) && $arg['readonly'] ) {
			$property['readOnly'] = true;
			$is_readonly = true;
		}

		// Add default value only if not readonly (avoid contradiction).
		if ( isset( $arg['default'] ) && ! $is_readonly ) {
			$property['default'] = $arg['default'];
		}

		// Skip WordPress-specific fields like 'context' - they're not copied.

		return $property;
	}

	/**
	 * Recursively sanitize nested schema objects.
	 *
	 * @param array $schema Nested schema to sanitize.
	 * @return array Sanitized nested schema.
	 */
	private static function sanitize_nested_schema( array $schema ): array {
		$sanitized = array();

		foreach ( $schema as $key => $value ) {
			if ( 'properties' === $key && is_array( $value ) ) {
				// Recursively sanitize nested properties.
				$sanitized['properties'] = array();
				foreach ( $value as $prop_key => $prop_value ) {
					$sanitized['properties'][ $prop_key ] = self::sanitize_single_property( $prop_value );
				}
			} elseif ( 'items' === $key && is_array( $value ) ) {
				// Recursively sanitize items.
				$sanitized['items'] = self::sanitize_nested_schema( $value );
			} elseif ( in_array( $key, array( 'type', 'description', 'enum', 'minimum', 'maximum', 'format' ), true ) ) {
				// Copy valid JSON Schema fields.
				$sanitized[ $key ] = $value;
			}
			// Skip invalid fields like 'context'.
		}

		return $sanitized;
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
			$schema = $controller->get_item_schema();

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
