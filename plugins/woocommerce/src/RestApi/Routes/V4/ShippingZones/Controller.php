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
					'args'                => $this->get_collection_params(),
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
			function( $a, $b ) {
				return $a['zone_order'] <=> $b['zone_order'];
			}
		);

		$data = array();
		foreach ( $zones as $zone_data ) {
			$zone = WC_Shipping_Zones::get_zone( $zone_data['id'] );
			$item = $this->prepare_item_for_response( $zone, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}

	}

	/**
	 * Get the item response based on the request context.
	 *
	 * @param WC_Shipping_Zone $zone    Shipping zone object.
	 * @param WP_REST_Request  $request Request object.
	 * @return array
	 */
	protected function get_item_response( $zone, WP_REST_Request $request ): array {
		// Basic zone data.
		$data = array(
			'id'    => $zone->get_id(),
			'name'  => $zone->get_zone_name(),
			'order' => $zone->get_zone_order(),
		);

		return $data;
	}

	/**
	 * Register the routes for shipping zones.
	 */
	public function register_routes() {}

	/**
	 * Retrieve a shipping zone by ID.
	 *
	 * @param int $zone_id Shipping zone ID.
	 * @return WC_Shipping_Zone|WP_Error
	 */
	protected function get_zone( $zone_id ) {
		$zone = WC_Shipping_Zones::get_zone_by( 'zone_id', $zone_id );

		if ( false === $zone ) {
			return new WP_Error( 'woocommerce_rest_shipping_zone_invalid', __( 'Resource does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		return $zone;
	}

	/**
	 * Check whether a given request has permission to read shipping zones.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
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
