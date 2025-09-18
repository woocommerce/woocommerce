<?php
/**
 * REST Ability Factory class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\REST;

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
		// Only proceed if wp_register_ability function exists
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
					'execute_callback'    => function( $input ) use ( $controller, $ability_config, $route ) {
						return self::execute_operation( $controller, $ability_config['operation'], $input, $route );
					},
					'permission_callback' => function( $input ) use ( $controller, $ability_config ) {
						return self::check_permission( $controller, $ability_config['operation'], $input );
					},
					'ability_class'       => RestAbility::class,
				)
			);
		} catch ( \Throwable $e ) {
			// Log the error for debugging but don't break the registration of other abilities
			error_log( "Failed to register ability {$ability_config['id']}: " . $e->getMessage() );
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
				// Use controller's collection parameters
				if ( method_exists( $controller, 'get_collection_params' ) ) {
					return array(
						'type'       => 'object',
						'properties' => $controller->get_collection_params(),
					);
				}
				break;

			case 'create':
				// Use controller's creatable schema
				if ( method_exists( $controller, 'get_endpoint_args_for_item_schema' ) ) {
					$args = $controller->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE );
					return array(
						'type'       => 'object',
						'properties' => $args,
					);
				}
				break;

			case 'update':
				// Use controller's editable schema + ID
				if ( method_exists( $controller, 'get_endpoint_args_for_item_schema' ) ) {
					$args       = $controller->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE );
					$args['id'] = array(
						'type'        => 'integer',
						'description' => __( 'Unique identifier for the resource', 'woocommerce' ),
					);
					return array(
						'type'       => 'object',
						'properties' => $args,
						'required'   => array( 'id' ),
					);
				}
				break;

			case 'get':
			case 'delete':
				// Only need ID
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

		// Fallback
		return array( 'type' => 'object' );
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
				// For list operations, return object wrapping array of items
				// This ensures MCP compatibility while maintaining REST structure
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
				// For delete operations, return simple confirmation
				return array(
					'type'       => 'object',
					'properties' => array(
						'deleted' => array( 'type' => 'boolean' ),
						'previous' => $schema,
					),
				);
			}

			// For get, create, update operations
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
		// Map operation to REST method
		$method_map = array(
			'list'   => 'GET',
			'get'    => 'GET',
			'create' => 'POST',
			'update' => 'PUT',
			'delete' => 'DELETE',
		);
		$method = $method_map[ $operation ] ?? 'GET';

		// Build final route - add ID for single item operations
		$request_route = $route;
		if ( isset( $input['id'] ) && in_array( $operation, array( 'get', 'update', 'delete' ), true ) ) {
			$request_route .= '/' . intval( $input['id'] );
			unset( $input['id'] );
		}

		// Create REST request
		$request = new \WP_REST_Request( $method, $request_route );
		foreach ( $input as $key => $value ) {
			$request->set_param( $key, $value );
		}

		// Dispatch through REST API for proper validation and permissions
		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response instanceof \WP_REST_Response ? $response->get_data() : $response;

		// For list operations, wrap in data object to match schema
		if ( 'list' === $operation ) {
			return array( 'data' => $data );
		}

		return $data;
	}


	/**
	 * Check permissions - delegate to REST controller.
	 *
	 * @param object $controller REST controller instance.
	 * @param string $operation Operation type.
	 * @param array  $input Input parameters.
	 * @return bool Whether permission is granted.
	 */
	private static function check_permission( $controller, string $operation, array $input ): bool {
		// We return true here and let the controller handle permissions
		// The REST controller will check permissions when its methods are called
		return true;
	}
}