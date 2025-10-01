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
use WC_Shipping_Zones;
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
	 * Custom error constants for shipping-specific errors.
	 */
	const INVALID_ZONE_ID     = 'invalid_zone_id';
	const INVALID_METHOD_TYPE = 'invalid_method_type';
	const ZONE_MISMATCH       = 'zone_mismatch';

	/**
	 * Initialize the controller with schema dependency injection.
	 *
	 * @internal
	 * @param ShippingMethodSchema $method_schema Schema for shipping methods.
	 */
	final public function init( ShippingMethodSchema $method_schema ) {
		$this->method_schema = $method_schema;
	}

	/**
	 * Register the routes for shipping zone methods.
	 */
	public function register_routes() {
		// POST - Create shipping method.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array_merge(
					$this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					array(
						'zone_id' => array(
							'description'       => __( 'Shipping zone ID.', 'woocommerce' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					)
				),
			)
		);

		// PUT - Update shipping method.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
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

		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return $this->get_authentication_error_by_method( $request->get_method() );
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
		// Validate zone exists.
		$zone = $this->validate_zone( $request['zone_id'] );
		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		// Validate method type.
		$method_validation = $this->validate_method_type( $request['method_id'] );
		if ( is_wp_error( $method_validation ) ) {
			return $method_validation;
		}

		// Add the shipping method to the zone.
		$instance_id = $zone->add_shipping_method( $request['method_id'] );

		if ( ! $instance_id ) {
			return $this->get_route_error_by_code( self::CANNOT_CREATE );
		}

		// Get the newly created method instance.
		$method = WC_Shipping_Zones::get_shipping_method( $instance_id );
		if ( ! $method ) {
			return $this->get_route_error_by_code( self::CANNOT_CREATE );
		}

		// Update method using zone's business logic.
		$method = $zone->update_shipping_method( $instance_id, $request->get_params() );
		if ( is_wp_error( $method ) ) {
			return $method;
		}

		$response = rest_ensure_response( $this->method_schema->get_item_response( $method, $request ) );
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

		// Get the method by instance ID.
		$method = WC_Shipping_Zones::get_shipping_method( $instance_id );
		if ( ! $method ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		// Validate zone_id matches if provided.
		if ( isset( $request['zone_id'] ) ) {
			$zone = $this->validate_zone( $request['zone_id'] );
			if ( is_wp_error( $zone ) ) {
				return $zone;
			}

			// Ensure the method belongs to the specified zone.
			global $wpdb;
			$actual_zone_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT zone_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d",
					$instance_id
				)
			);

			if ( (int) $request['zone_id'] !== (int) $actual_zone_id ) {
				return $this->get_route_error_by_code( self::ZONE_MISMATCH );
			}
		}

		// Update method using zone's business logic if any updates provided.
		if ( isset( $request['enabled'] ) || isset( $request['settings'] ) || isset( $request['order'] ) ) {
			$zone = $this->validate_zone_by_method_instance( $instance_id );
			if ( is_wp_error( $zone ) ) {
				return $zone;
			}

			$method = $zone->update_shipping_method( $instance_id, $request->get_params() );
			if ( is_wp_error( $method ) ) {
				return $method;
			}
		}

		return rest_ensure_response( $this->method_schema->get_item_response( $method, $request ) );
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
	 * Get error prefix for this controller.
	 *
	 * @return string
	 */
	protected function get_error_prefix(): string {
		// Convert 'shipping-zone-method' to 'shipping_zone_method_'.
		$prefix = str_replace( array( '-', '/' ), '_', $this->rest_base );
		return 'woocommerce_rest_api_v4_' . $prefix . '_';
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
		global $wpdb;

		$zone_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT zone_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d",
				$instance_id
			)
		);

		if ( null === $zone_id ) {
			return $this->get_route_error_by_code( self::INVALID_ID );
		}

		return $this->validate_zone( $zone_id );
	}
}
