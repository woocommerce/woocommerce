<?php
/**
 * REST API Shipping Zones Controller
 *
 * Handles requests to the /shipping-zones endpoint.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractController;
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
	protected $zone_schema;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->zone_schema = new ShippingZoneSchema();
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
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
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
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
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
		if ( ! wc_shipping_enabled() ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'disabled',
				__( 'Shipping is disabled.', 'woocommerce' ),
				WP_Http::SERVICE_UNAVAILABLE
			);
		}

		$zone_id = (int) $request['id'];

		$zone = WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );

		if ( ! $zone ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_id',
				__( 'Invalid resource ID.', 'woocommerce' ),
				WP_Http::NOT_FOUND
			);
		}

		return rest_ensure_response( $this->get_zone_detail( $zone ) );
	}

	/**
	 * Get all shipping zones.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		if ( ! wc_shipping_enabled() ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'disabled',
				__( 'Shipping is disabled.', 'woocommerce' ),
				WP_Http::SERVICE_UNAVAILABLE
			);
		}

		// Get all zones including "Rest of the World".
		$zones             = WC_Shipping_Zones::get_zones();
		$rest_of_the_world = WC_Shipping_Zones::get_zone_by( 'zone_id', 0 );

		// Add "Rest of the World" zone at the end.
		$zones[0] = $rest_of_the_world->get_data();

		// Sort zones by order.
		uasort(
			$zones,
			function ( $a, $b ) {
				return $a['zone_order'] <=> $b['zone_order'];
			}
		);

		$data = array();
		foreach ( $zones as $zone_data ) {
			// Handle both 'zone_id' (from get_zones()) and 'id' (from get_data()) keys.
			$zone_id = isset( $zone_data['zone_id'] ) ? $zone_data['zone_id'] : $zone_data['id'];
			$zone    = WC_Shipping_Zones::get_zone( $zone_id );
			$data[]  = $this->get_zone_summary( $zone );
		}

		return rest_ensure_response( $data );
	}


	/**
	 * Get zone summary for list view.
	 *
	 * @param WC_Shipping_Zone $zone Shipping zone object.
	 * @return array
	 */
	protected function get_zone_summary( $zone ): array {
		return array(
			'id'        => $zone->get_id(),
			'name'      => $zone->get_zone_name(),
			'order'     => $zone->get_zone_order(),
			'locations' => $this->get_formatted_zone_locations( $zone ),
			'methods'   => $this->get_formatted_zone_methods( $zone ),
		);
	}

	/**
	 * Get zone detail for single zone view.
	 *
	 * @param WC_Shipping_Zone $zone Shipping zone object.
	 * @return array
	 */
	protected function get_zone_detail( $zone ): array {
		return array(
			'id'        => $zone->get_id(),
			'name'      => $zone->get_zone_name(),
			'locations' => $this->get_formatted_zone_locations( $zone, 'detailed' ),
			'methods'   => $this->get_formatted_zone_methods( $zone ),
		);
	}

	/**
	 * Get the item response for the parent class compatibility.
	 *
	 * @param WC_Shipping_Zone $zone    Shipping zone object.
	 * @param WP_REST_Request  $request Request object.
	 * @return array
	 */
	protected function get_item_response( $zone, WP_REST_Request $request ): array {
		return $this->get_zone_summary( $zone );
	}

	/**
	 * Get array of location names for display.
	 *
	 * @param WC_Shipping_Zone $zone Shipping zone object.
	 * @param string           $view The view for which the API is requested ('summary' or 'detailed').
	 * @return array
	 */
	protected function get_formatted_zone_locations( WC_Shipping_Zone $zone, string $view = 'summary' ): array {
		if ( 0 === $zone->get_id() ) {
			return array( __( 'All regions not covered above', 'woocommerce' ) );
		}

		$locations = $zone->get_zone_locations();

		if ( 'summary' === $view ) {
			$location_names = array();
			foreach ( $locations as $location ) {
				$location_names[] = $this->get_location_name( $location );
			}
			return $location_names;
		} else {
			// For detailed view, add name property to each location object.
			$detailed_locations = array();
			foreach ( $locations as $location ) {
				$location_copy        = clone $location;
				$location_copy->name  = $this->get_location_name( $location );
				$detailed_locations[] = $location_copy;
			}
			return $detailed_locations;
		}
	}

	/**
	 * Get location name from location object.
	 *
	 * @param object $location Location object.
	 * @return string
	 */
	protected function get_location_name( $location ) {
		switch ( $location->type ) {
			case 'continent':
				$continents = WC()->countries->get_continents();
				return isset( $continents[ $location->code ] ) ? $continents[ $location->code ]['name'] : $location->code;

			case 'country':
				$countries = WC()->countries->get_countries();
				return isset( $countries[ $location->code ] ) ? $countries[ $location->code ] : $location->code;

			case 'state':
				$parts = explode( ':', $location->code );
				if ( count( $parts ) === 2 ) {
					$states = WC()->countries->get_states( $parts[0] );
					return isset( $states[ $parts[1] ] ) ? $states[ $parts[1] ] : $location->code;
				}
				return $location->code;

			case 'postcode':
				return $location->code;

			default:
				return $location->code;
		}
	}

	/**
	 * Get formatted methods for a zone.
	 *
	 * @param WC_Shipping_Zone $zone Shipping zone object.
	 * @return array
	 */
	protected function get_formatted_zone_methods( $zone ) {
		$methods           = $zone->get_shipping_methods( false, 'json' );
		$formatted_methods = array();

		foreach ( $methods as $method ) {
			$formatted_method = array(
				'instance_id' => $method->instance_id,
				'title'       => $method->title,
				'enabled'     => 'yes' === $method->enabled,
				'method_id'   => $method->id,
				'settings'    => $this->get_method_settings( $method ),
			);

			$formatted_methods[] = $formatted_method;
		}

		return $formatted_methods;
	}

	/**
	 * Get raw method settings for frontend processing.
	 *
	 * @param object $method Shipping method object.
	 * @return array
	 */
	protected function get_method_settings( $method ) {
		$settings = array();

		// Common settings that most methods have.
		$common_fields = array( 'cost', 'min_amount', 'requires', 'class_cost', 'no_class_cost' );

		foreach ( $common_fields as $field ) {
			if ( isset( $method->$field ) ) {
				$settings[ $field ] = $method->$field;
			}
		}

		// Return all available settings for maximum flexibility.
		if ( isset( $method->instance_settings ) && is_array( $method->instance_settings ) ) {
			$settings = array_merge( $settings, $method->instance_settings );
		}

		return $settings;
	}


	/**
	 * Check whether a given request has permission to read shipping zones.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get the schema for shipping zones.
	 *
	 * @return array
	 */
	protected function get_schema(): array {
		return $this->zone_schema->get_schema();
	}
}
