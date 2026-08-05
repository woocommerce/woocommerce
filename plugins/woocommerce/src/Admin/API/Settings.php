<?php
/**
 * REST API Settings Controller (compatibility stub).
 *
 * @package WooCommerce\Admin\API
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Settings controller.
 *
 * The real controller was removed in 10.9 with the settings editor. This empty stub stays so an
 * in-memory 10.8 controller list, still naming this class while the files are swapped to 10.9
 * during an update, can instantiate it instead of fataling on the deleted file. It registers
 * nothing.
 *
 * @deprecated 10.9.0
 */
class Settings {

	/**
	 * Register routes. Intentionally a no-op.
	 */
	public function register_routes(): void {}
}
