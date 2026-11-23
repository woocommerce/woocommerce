<?php
/**
 * REST API Settings V4 controller (compatibility stub).
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Settings V4 controller class.
 *
 * Kept for compatibility with the upgrade path to 11.5.0. Registers nothing.
 *
 * @deprecated 11.5.0
 */
class WC_REST_Settings_V4_Controller {

	/**
	 * Register routes. Intentionally a no-op.
	 */
	public function register_routes(): void {}
}
