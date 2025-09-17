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
	}

	/**
	 * Prepare a shipping zone item for response.
	 *
	 * @param mixed      $zone Note object.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_item_response( $zone, WP_REST_Request $request ): array {
		return array(
			/* @todo:add class variables */
			'id'=>""
		);
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
