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
	}

	/**
	 * Get all shipping zones.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_items( $request ) {
		if ( ! wc_shipping_enabled() ) {
			return new WP_Error( 'woocommerce_rest_shipping_disabled', __( 'Shipping is disabled.', 'woocommerce' ), array( 'status' => 404 ) );
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
			$zone   = WC_Shipping_Zones::get_zone( $zone_data['id'] );
			$item   = $this->prepare_item_for_response( $zone, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}


	/**
	 * Get the item response for list view.
	 *
	 * @param WC_Shipping_Zone $zone    Shipping zone object.
	 * @param WP_REST_Request  $request Request object.
	 * @return array
	 */
	protected function get_item_response( $zone, WP_REST_Request $request ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return array(
			'id'        => $zone->get_id(),
			'name'      => $zone->get_zone_name(),
			'order'     => $zone->get_zone_order(),
			'locations' => $this->get_location_names_array( $zone ),
			'methods'   => $this->get_formatted_methods_summary( $zone ),
		);
	}

	/**
	 * Get array of location names for display.
	 *
	 * @param WC_Shipping_Zone $zone Shipping zone object.
	 * @return array
	 */
	protected function get_location_names_array( $zone ) {
		if ( 0 === $zone->get_id() ) {
			return array( __( 'All regions not covered above', 'woocommerce' ) );
		}

		$locations      = $zone->get_zone_locations();
		$location_names = array();

		foreach ( $locations as $location ) {
			$location_names[] = $this->get_location_name( $location );
		}

		if ( empty( $location_names ) ) {
			return array();
		}

		return $location_names;
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
				$parts  = explode( ':', $location->code );
				$states = WC()->countries->get_states( $parts[0] );
				return isset( $states[ $parts[1] ] ) ? $states[ $parts[1] ] : $location->code;

			case 'postcode':
				return $location->code;

			default:
				return $location->code;
		}
	}

	/**
	 * Get formatted methods summary for list view.
	 *
	 * @param WC_Shipping_Zone $zone Shipping zone object.
	 * @return array
	 */
	protected function get_formatted_methods_summary( $zone ) {
		$methods           = $zone->get_shipping_methods( false, 'json' );
		$formatted_methods = array();

		foreach ( $methods as $method ) {
			$formatted_method = array(
				'instance_id' => $method->instance_id,
				'title'       => $method->title,
				'enabled'     => 'yes' === $method->enabled,
			);

			// Get rate description based on method type.
			$formatted_method['rate_description'] = $this->get_method_rate_description( $method );

			$formatted_methods[] = $formatted_method;
		}

		return $formatted_methods;
	}

	/**
	 * Get method rate description for display.
	 *
	 * @param object $method Shipping method object.
	 * @return string
	 */
	protected function get_method_rate_description( $method ) {
		switch ( $method->id ) {
			case 'free_shipping':
				if ( ! empty( $method->min_amount ) ) {
					/* translators: minimum amount of order over which the shipping cost would be free */
					return sprintf( __( 'Free over %s', 'woocommerce' ), wc_price( $method->min_amount ) );
				}
				return __( 'Free', 'woocommerce' );

			case 'flat_rate':
				if ( ! empty( $method->cost ) ) {
					return wc_price( $method->cost );
				}
				return __( 'Flat rate', 'woocommerce' );

			case 'local_pickup':
				if ( ! empty( $method->cost ) ) {
					return wc_price( $method->cost );
				}
				return __( 'Local pickup', 'woocommerce' );

			default:
				// For custom methods, try to get cost if available.
				if ( isset( $method->cost ) && '' !== $method->cost ) {
					return wc_price( $method->cost );
				}
				return $method->title;
		}
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
