<?php
/**
 * Loads the admin screens built with wp-build.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Screens;

use Automattic\WooCommerce\Admin\Features\Features;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the wp-build output in `assets/client/routes`, which defines the generated page, route and
 * script module functions of every page that `admin-screens` builds.
 *
 * @internal
 */
class AdminScreens {

	/**
	 * Feature flag that controls the experimental admin screens until they are released.
	 */
	public const FEATURE_FLAG = 'experimental-admin-screens';

	/**
	 * Register hooks.
	 *
	 * @internal
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Load the build output in admin requests while the flag is on.
	 *
	 * @internal
	 *
	 * @since 11.3.0
	 */
	public function handle_init(): void {
		if ( ! is_admin() || ! Features::is_enabled( self::FEATURE_FLAG ) ) {
			return;
		}

		// Development builds that only watch the admin client don't produce this file.
		$build_file = $this->get_build_file();
		if ( is_readable( $build_file ) ) {
			require_once $build_file;
		}
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
