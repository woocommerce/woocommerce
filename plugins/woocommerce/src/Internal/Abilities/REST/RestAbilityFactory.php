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
			self::register_single_ability( $controller, $ability_config );
		}
	}

	/**
	 * Register a single ability.
	 *
	 * @param object $controller REST controller instance.
	 * @param array  $ability_config Ability configuration array.
	 */
	private static function register_single_ability( $controller, array $ability_config ): void {
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
					'execute_callback'    => function( $input ) use ( $controller, $ability_config ) {
						return self::execute_operation( $controller, $ability_config['operation'], $input );
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
	 * @return mixed Operation result.
	 */
	private static function execute_operation( $controller, string $operation, array $input ) {
		$request = new \WP_REST_Request();

		// Map operation to REST method
		$method_map = array(
			'list'   => 'GET',
			'get'    => 'GET',
			'create' => 'POST',
			'update' => 'PUT',
			'delete' => 'DELETE',
		);

		$request->set_method( $method_map[ $operation ] );

		// Set parameters
		if ( isset( $input['id'] ) ) {
			$request->set_param( 'id', $input['id'] );
			unset( $input['id'] );
		}

		// Set remaining parameters
		foreach ( $input as $key => $value ) {
			$request->set_param( $key, $value );
		}

		// Execute controller method
		$controller_method_map = array(
			'list'   => 'get_items',
			'get'    => 'get_item',
			'create' => 'create_item',
			'update' => 'update_item',
			'delete' => 'delete_item',
		);

		$method = $controller_method_map[ $operation ];

		if ( ! method_exists( $controller, $method ) ) {
			return new \WP_Error( 'method_not_found', "Method $method not found in controller" );
		}

		// Execute the operation - controller handles permissions automatically
		$response = $controller->$method( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response->get_data();

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