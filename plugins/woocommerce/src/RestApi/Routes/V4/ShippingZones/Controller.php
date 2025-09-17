<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * REST API Shipping Zones controller
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\RestApi\Routes\V4\ShippingZones;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\Routes\V4\AbstractController;
use WP_REST_Request;
use WP_Http;
use WP_Error;
use WP_Comment;
use WC_Order;
use WP_REST_Response;
use WP_REST_Server;

/**
 * ShippingZones Controller.
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'shipping-zones';

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 */
	protected function get_schema(): array {
		return ShippingZoneSchema::get_item_schema();
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
}
