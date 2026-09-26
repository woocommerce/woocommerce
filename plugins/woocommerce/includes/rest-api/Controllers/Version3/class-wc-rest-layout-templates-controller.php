<?php
/**
 * REST API Layout Templates controller (compatibility stub).
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * REST API Layout Templates controller class.
 *
 * The real controller was removed in 11.0 with the deprecated product block editor. This empty
 * stub stays so an in-memory 10.9 controller list, still naming this class while the files are
 * swapped to 11.0 during an update, can instantiate it instead of fataling on the deleted file.
 * It registers nothing.
 *
 * @deprecated 11.0.0
 */
class WC_REST_Layout_Templates_Controller {

	/**
	 * Register routes. Intentionally a no-op.
	 */
	public function register_routes(): void {}
}
