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
	 * Register the routes for orders.
	 */
	public function register_routes() {}
}
