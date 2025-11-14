<?php
/**
 * REST API Shipping Zones Controller
 *
 * Handles requests to the /shipping-zones endpoint.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones\ShippingZoneService;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Http;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Shipping Zones Controller Class.
 *
 * @extends AbstractController
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'shipping-zones';

	/**
	 * Schema instance.
	 *
	 * @var ShippingZoneSchema
	 */
	protected $item_schema;

	/**
	 * Shipping service instance.
	 *
	 * @var ShippingZoneSchemaService
	 */
	protected $shipping_zone_service;

	/**
	 * Custom error constant for shipping-specific errors.
	 */
	const INVALID_ZONE_ID = 'invalid_zone_id';

	/**
	 * Initialize the controller.
	 *
	 * @param ShippingZoneSchema  $zone_schema           Order schema class.
	 * @param ShippingZoneService $shipping_zone_service Service for shipping zone operations.
	 * @internal
	 */
	final public function init( ShippingZoneSchema $zone_schema, ShippingZoneService $shipping_zone_service ) {
		$this->item_schema           = $zone_schema;
		$this->shipping_zone_service = $shipping_zone_service;
	}

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 */
	protected function get_schema(): array {
		return $this->item_schema->get_item_schema();
	}

	/**
	 * Register the routes for shipping zones.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
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
			)
		);
	}

	/**
	 * Get shipping zone by ID.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$zone_id = (int) $request['id'];

		$zone = WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );

		if ( ! $zone ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_id',
				__( 'Invalid resource ID.', 'woocommerce' ),
				WP_Http::NOT_FOUND
			);
		}

		return rest_ensure_response( $this->prepare_item_for_response( $zone, $request ) );
	}

	/**
	 * Get all shipping zones.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		$zones = $this->shipping_zone_service->get_sorted_shipping_zones();

		$items = array();
		foreach ( $zones as $zone_data ) {
			$zone_id = $zone_data['zone_id'];
			$zone    = WC_Shipping_Zones::get_zone( $zone_id );
			$items[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $zone, $request ) );
		}

		return rest_ensure_response( $items );
	}

	/**
	 * Prepare a single order object for response.
	 *
	 * @param WC_Shipping_Zone $zone Shipping zone object.
	 * @param WP_REST_Request  $request Request object.
	 * @return array
	 */
	protected function get_item_response( $zone, WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $zone, $request, $this->get_fields_for_response( $request ) );
	}

	/**
	 * Check if a given request has permission to manage shipping zones.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has permission, WP_Error otherwise.
	 */
	public function check_permissions( $request ) {
		if ( ! wc_shipping_enabled() ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'disabled',
				__( 'Shipping is disabled.', 'woocommerce' ),
				WP_Http::SERVICE_UNAVAILABLE
			);
		}

		$method = $request->get_method();

		// GET requests require 'read' permission, all others require 'edit'.
		$permission_type = ( WP_REST_Server::READABLE === $method ) ? 'read' : 'edit';

		if ( ! wc_rest_check_manager_permissions( 'settings', $permission_type ) ) {
			return $this->get_authentication_error_by_method( $method );
		}

		return true;
	}

	/**
	 * Create a new shipping zone.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object or WP_Error.
	 */
	public function create_item( $request ) {
		$zone = $this->shipping_zone_service->create_shipping_zone( $request->get_params() );
		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		if ( 0 === $zone->get_id() ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'cannot_create',
				__( 'Resource cannot be created. Check for validation errors or server logs for details.', 'woocommerce' ),
				WP_Http::INTERNAL_SERVER_ERROR
			);
		}

		$response = rest_ensure_response( $this->prepare_item_for_response( $zone, $request ) );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $zone->get_id() ) ) );

		return $response;
	}

	/**
	 * Get route error by code, including custom shipping zone errors.
	 *
	 * @param string $error_code Error code.
	 * @return WP_Error
	 */
	protected function get_route_error_by_code( string $error_code ): WP_Error {
		$custom_errors = array(
			self::INVALID_ZONE_ID => array(
				'message' => __( 'Invalid shipping zone ID.', 'woocommerce' ),
				'status'  => WP_Http::NOT_FOUND,
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
	 * Update a shipping zone.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object or WP_Error.
	 */
	public function update_item( $request ) {
		$zone_id = (int) $request['id'];

		$zone = $this->validate_zone( $zone_id );
		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		$result = $this->shipping_zone_service->update_shipping_zone( $zone, $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $this->prepare_item_for_response( $result, $request ) );
	}
}
