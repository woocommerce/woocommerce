<?php
/**
 * Loads the admin screens built with wp-build.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Screens;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the wp-build output in `assets/client/routes`, which defines the generated page, route and
 * script module functions of every page that `admin-screens` builds.
 *
 * @internal
 */
class AdminScreens {

	/**
	 * Load the build output, once.
	 *
	 * @since 11.4.0
	 *
	 * @return void
	 */
	public function load(): void {
		require_once $this->get_build_file();
	}

	/**
	 * Path to the wp-build entry point.
	 *
	 * @return string
	 */
	private function get_build_file(): string {
		return WC_ABSPATH . 'assets/client/routes/build.php';
	}
}
