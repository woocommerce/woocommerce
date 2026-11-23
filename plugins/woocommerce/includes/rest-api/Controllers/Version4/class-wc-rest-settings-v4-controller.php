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
 * The real controller was removed when the experimental `rest-api-v4` namespace was removed. This
 * empty stub stays so a pre-swap in-memory controller list, still naming this class while the files
 * are swapped during an update, can instantiate it instead of fataling on the deleted file. It
 * registers nothing.
 *
 * @deprecated 11.4.0
 */
class WC_REST_Settings_V4_Controller {

	/**
	 * Register routes. Intentionally a no-op.
	 */
	public function register_routes(): void {}
}
