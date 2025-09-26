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
	protected $item_schema;

	/**
	 * Initialize the controller.
	 *
	 * @param ShippingZoneSchema $zone_schema Order schema class.
	 * @internal
	 */
	final public function init( ShippingZoneSchema $zone_schema ) {
		$this->item_schema = $zone_schema;
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

		return rest_ensure_response( $this->prepare_item_for_response( $zone, $request ) );
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

		$items = array();
		foreach ( $zones as $zone_data ) {
			// Handle both 'zone_id' (from get_zones()) and 'id' (from get_data()) keys.
			$zone_id = isset( $zone_data['zone_id'] ) ? $zone_data['zone_id'] : $zone_data['id'];
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
}
