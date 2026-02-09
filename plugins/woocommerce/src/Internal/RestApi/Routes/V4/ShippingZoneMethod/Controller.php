<?php
/**
 * Shipping Zone Methods Controller.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod\ShippingZoneMethodService;
use WC_Shipping_Zones;
use WC_Shipping_Zone;
use WP_Http;
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
	protected $rest_base = 'shipping-zone-method';

	/**
	 * Shipping method schema instance.
	 *
	 * @var ShippingMethodSchema
	 */
	protected $method_schema;

	/**
	 * Shipping service instance.
	 *
	 * @var ShippingZoneMethodService
	 */
	protected $shipping_method_service;

	/**
	 * Custom error constants for shipping-specific errors.
	 */
	const INVALID_ZONE_ID     = 'invalid_zone_id';
	const INVALID_METHOD_TYPE = 'invalid_method_type';
	const ZONE_MISMATCH       = 'zone_mismatch';

	/**
	 * Initialize the controller with schema dependency injection.
	 *
	 * @internal
	 * @param ShippingMethodSchema      $method_schema            Schema for shipping methods.
	 * @param ShippingZoneMethodService $shipping_method_service Service for shipping method operations.
	 */
	final public function init( ShippingMethodSchema $method_schema, ShippingZoneMethodService $shipping_method_service ) {
		$this->method_schema           = $method_schema;
		$this->shipping_method_service = $shipping_method_service;
	}

	/**
	 * Register the routes for shipping zone methods.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'schema' => array( $this, 'get_public_item_schema' ),
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
				),
			)
		);
	}

	/**
	 * Check if a given request has permission to manage shipping methods.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has permission, WP_Error otherwise.
	 */
	public function check_permissions( $request ) {
		if ( ! wc_shipping_enabled() ) {
			return new WP_Error(
				'rest_shipping_disabled',
				__( 'Shipping is disabled.', 'woocommerce' ),
				array( 'status' => WP_Http::SERVICE_UNAVAILABLE )
			);
		}

		$method = $request->get_method();

		if ( 'GET' === $method ) {
			$context = 'read';
		} elseif ( 'DELETE' === $method ) {
			$context = 'delete';
		} else {
			$context = 'edit';
		}

		if ( ! wc_rest_check_manager_permissions( 'settings', $context ) ) {
			return $this->get_authentication_error_by_method( $method );
		}

		return true;
	}

	/**
	 * Get shipping zone method by ID.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$instance_id = (int) $request['id'];

		$method = WC_Shipping_Zones::get_shipping_method( $instance_id );

		if ( ! $method ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		return rest_ensure_response( $this->prepare_item_for_response( $method, $request ) );
	}

	/**
	 * Create a shipping method.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error.
	 */
	public function create_item( $request ) {
		$zone = $this->validate_zone( $request['zone_id'] );
		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		$method_validation = $this->validate_method_type( $request['method_id'] );
		if ( is_wp_error( $method_validation ) ) {
			return $method_validation;
		}

		$instance_id = $zone->add_shipping_method( $request['method_id'] );

		if ( ! $instance_id ) {
			return $this->get_route_error_by_code( self::CANNOT_CREATE );
		}

		$method = WC_Shipping_Zones::get_shipping_method( $instance_id );
		if ( ! $method ) {
			return $this->get_route_error_by_code( self::CANNOT_CREATE );
		}

		$result = $this->shipping_method_service->update_shipping_zone_method( $method, $instance_id, $request->get_params(), $zone->get_id() );
		if ( is_wp_error( $result ) ) {
			// Rollback: delete the method instance to prevent orphaned records.
			$zone->delete_shipping_method( $instance_id );
			return $result;
		}

		$request['zone_id'] = $zone->get_id();
		$response           = $this->prepare_item_for_response( $method, $request );
		$response->set_status( 201 );
		return $response;
	}

	/**
	 * Update a shipping method.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object or WP_Error.
	 */
	public function update_item( $request ) {
		$instance_id = (int) $request['id'];

		$method = WC_Shipping_Zones::get_shipping_method( $instance_id );
		if ( ! $method ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		$zone = $this->validate_zone_by_method_instance( $instance_id );
		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		if ( isset( $request['enabled'] ) || isset( $request['settings'] ) || isset( $request['order'] ) ) {
			$result = $this->shipping_method_service->update_shipping_zone_method( $method, $instance_id, $request->get_params(), $zone->get_id() );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$request['zone_id'] = $zone->get_id();
		return $this->prepare_item_for_response( $method, $request );
	}

	/**
	 * Delete shipping zone method by ID.
	 *
	 * Note: In v2/v3, this endpoint required a `force` parameter, but since shipping zone methods
	 * do not support trashing, it would either delete (force=true) or return a 501 error (force=false).
	 * We removed the `force` parameter in v4 as it serves no purpose when soft delete is not supported.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$instance_id = (int) $request['id'];

		// Get the method before deletion to return in response.
		$method = WC_Shipping_Zones::get_shipping_method( $instance_id );
		if ( ! $method ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		$zone = $this->validate_zone_by_method_instance( $instance_id );
		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		// Prepare response before deletion.
		$request->set_param( 'context', 'view' );
		$request['zone_id'] = $zone->get_id();
		$response           = $this->prepare_item_for_response( $method, $request );

		// Perform the deletion.
		$result = $zone->delete_shipping_method( $instance_id );

		if ( ! $result ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		/**
		 * Fires after a shipping zone method is deleted via the REST API.
		 *
		 * @since 10.5.0
		 *
		 * @param WC_Shipping_Method $method   The shipping zone method being deleted.
		 * @param WC_Shipping_Zone   $zone     The shipping zone the method belonged to.
		 * @param WP_REST_Response   $response The response data.
		 * @param WP_REST_Request    $request  The request sent to the API.
		 */
		do_action( 'woocommerce_rest_delete_shipping_zone_method', $method, $zone, $response, $request );

		return $response;
	}

	/**
	 * Get the schema for shipping methods.
	 *
	 * @return array
	 */
	protected function get_schema(): array {
		return $this->method_schema->get_item_schema();
	}

	/**
	 * Get the item response for a shipping method.
	 *
	 * @param mixed           $zone    Shipping method data.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $zone, WP_REST_Request $request ): array {
		return $this->method_schema->get_item_response( $zone, $request, $this->get_fields_for_response( $request ) );
	}

	/**
	 * Get route error by code, including custom shipping method errors.
	 *
	 * @param string $error_code Error code.
	 * @return WP_Error
	 */
	protected function get_route_error_by_code( string $error_code ): WP_Error {
		$custom_errors = array(
			self::INVALID_ZONE_ID     => array(
				'message' => __( 'Invalid shipping zone ID.', 'woocommerce' ),
				'status'  => WP_Http::NOT_FOUND,
			),
			self::INVALID_METHOD_TYPE => array(
				'message' => __( 'Invalid shipping method type.', 'woocommerce' ),
				'status'  => WP_Http::BAD_REQUEST,
			),
			self::ZONE_MISMATCH       => array(
				'message' => __( 'Shipping method does not belong to the specified zone.', 'woocommerce' ),
				'status'  => WP_Http::BAD_REQUEST,
			),
		);

		if ( isset( $custom_errors[ $error_code ] ) ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . $error_code,
				$custom_errors[ $error_code ]['message'],
				$custom_errors[ $error_code ]['status']
			);
		}

		return parent::get_route_error_by_code( $error_code );
	}

	/**
	 * Validate that a shipping zone exists.
	 *
	 * @param int $zone_id Zone ID.
	 * @return WC_Shipping_Zone|WP_Error Zone object or error.
	 */
	protected function validate_zone( $zone_id ) {
		$zone = WC_Shipping_Zones::get_zone( $zone_id );

		if ( ! $zone || ( 0 !== $zone->get_id() && ! $zone->get_zone_name() ) ) {
			return $this->get_route_error_by_code( self::INVALID_ZONE_ID );
		}

		return $zone;
	}

	/**
	 * Validate that a shipping method type is valid.
	 *
	 * @param string $method_id Shipping method ID.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	protected function validate_method_type( $method_id ) {
		$available_methods = WC()->shipping()->get_shipping_methods();

		if ( ! isset( $available_methods[ $method_id ] ) ) {
			return $this->get_route_error_by_code( self::INVALID_METHOD_TYPE );
		}

		return true;
	}

	/**
	 * Get zone by method instance ID.
	 *
	 * @param int $instance_id Method instance ID.
	 * @return WC_Shipping_Zone|WP_Error Zone object or error.
	 */
	protected function validate_zone_by_method_instance( $instance_id ) {
		$zone = WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id );

		if ( ! $zone ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		return $zone;
	}
}
