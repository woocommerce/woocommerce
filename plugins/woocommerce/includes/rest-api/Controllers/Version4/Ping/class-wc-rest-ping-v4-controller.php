<?php
/**
 * REST API Ping controller
 *
 * Handles requests to the /ping endpoint for API v4.
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * REST API Ping controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_V4_Controller
 */
class WC_REST_Ping_V4_Controller extends WC_REST_V4_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ping';

	/**
	 * Register the routes for ping.
	 *
	 * @since 4.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_ping' ),
					'permission_callback' => array( $this, 'get_ping_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to read ping.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_ping_permissions_check( $request ) {
		// Allow ping to be accessible without authentication for basic connectivity testing.
		return true;
	}

	/**
	 * Get ping response.
	 *
	 * @since 4.0.0
	 * @param WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_ping( $request ) {
		$data = array(
			'message' => 'pong',
			'version' => 'v4',
		);

		return rest_ensure_response( $data );
	}

	/**
	 * Get the ping schema, conforming to JSON Schema.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public function get_item_schema() {
		$schema = $this->get_base_schema();

		$schema['title']      = 'ping';
		$schema['properties'] = array(
			'message' => array(
				'description' => __( 'The ping response message.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
			'version' => array(
				'description' => __( 'The API version responding to the ping.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
		);

		return $schema;
	}
}
