<?php
/**
 * Shipping Zone Methods Controller.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones\Method;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractController;
use WP_REST_Request;
use WP_REST_Server;
use WP_Error;

/**
 * Shipping Zone Methods Controller class.
 */
class Controller extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'shipping-zones/method';

	/**
	 * Shipping method schema instance.
	 *
	 * @var ShippingMethodSchema
	 */
	protected $method_schema;

	/**
	 * Error constants for consistent error handling.
	 */
	const INVALID_ZONE_ID      = 'invalid_zone_id';
	const INVALID_METHOD_TYPE  = 'invalid_method_type';
	const INVALID_INSTANCE_ID  = 'invalid_instance_id';
	const CANNOT_CREATE_METHOD = 'cannot_create_method';
	const CANNOT_UPDATE_METHOD = 'cannot_update_method';
	const ZONE_MISMATCH        = 'zone_mismatch';

	/**
	 * Initialize the controller with schema dependency injection.
	 *
	 * @param ShippingMethodSchema $method_schema Schema for shipping methods.
	 */
	final public function init( ShippingMethodSchema $method_schema ) {
		$this->method_schema = $method_schema;
	}

	/**
	 * Register the routes for shipping zone methods.
	 */
	public function register_routes() {
		// POST - Create shipping method
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			)
		);

		// PUT - Update shipping method
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			)
		);
	}

	/**
	 * Check if a given request has permission to create shipping methods.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has permission, WP_Error otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_create',
				__( 'Sorry, you cannot create shipping methods.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Check if a given request has permission to update shipping methods.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has permission, WP_Error otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_update',
				__( 'Sorry, you cannot update shipping methods.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Create a shipping method.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error.
	 */
	public function create_item( $request ) {
		// TODO: Implement create_item
		return rest_ensure_response( array( 'message' => 'Create method endpoint - not implemented yet' ) );
	}

	/**
	 * Update a shipping method.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error.
	 */
	public function update_item( $request ) {
		// TODO: Implement update_item
		return rest_ensure_response( array( 'message' => 'Update method endpoint - not implemented yet' ) );
	}

	/**
	 * Get the schema for shipping methods.
	 *
	 * @return array
	 */
	protected function get_schema(): array {
		return $this->method_schema->get_item_schema();
	}

	protected function get_item_response( $zone, WP_REST_Request $request ): array {
		return $this->method_schema->get_item_response( $zone, $request, $this->get_fields_for_response( $request ) );
	}
}
