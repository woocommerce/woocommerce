<?php
/**
 * Loads WooCommerce packages from the /packages directory. These are packages developed outside of core.
 */

namespace Automattic\WooCommerce;

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Packages class.
 *
 * @since 3.7.0
 */
class Packages {

	/**
	 * Static-only class.
	 */
	private function __construct() {}

	/**
	 * Array of package names and their main package classes. Once a package has been merged into WooCommerce
	 * directly it should be removed from here and added to the merged packages array.
	 *
	 * @var array Key is the package name/directory, value is the main package class which handles init.
	 */
	protected static $packages = array();

	/**
	 * Array of package names and their main package classes.
	 *
	 * One a package has been merged into WooCommerce Core it should be moved fron the package list and placed in
	 * this list. This will ensure that the feature plugin is disabled as well as provide the class to handle
	 * initialization for the now-merged feature plugin.
	 *
	 * Once a package has been merged into WooCommerce Core it should have its slug added here. This will ensure
	 * that we deactivate the feature plugin automaticatlly to prevent any problems caused by conflicts between
	 * the two versions caused by them both being active.
	 *
	 * @var array Key is the package name/directory, value is the main package class which handles init.
	 */
	protected static $merged_packages = array(
		'woocommerce-admin'                    => '\\Automattic\\WooCommerce\\Admin\\Composer\\Package',
		'woocommerce-gutenberg-products-block' => '\\Automattic\\WooCommerce\\Blocks\\Package',
	);

	/**
	 * Init the package loader.
	 *
	 * @since 3.7.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'on_init' ) );
	}

	/**
	 * Callback for WordPress init hook.
	 */
	public static function on_init() {
		self::deactivate_merged_packages();
		self::initialize_packages();
	}

	/**
	 * Checks a package exists by looking for it's directory.
	 *
	 * @param string $package Package name.
	 * @return boolean
	 */
	public static function package_exists( $package ) {
		return file_exists( dirname( __DIR__ ) . '/packages/' . $package );
	}

	/**
	 * Deactivates merged feature plugins.
	 *
	 * Once a feature plugin is merged into WooCommerce Core it should be deactivated. This method will
	 * ensure that a plugin gets deactivated. Note that for the first request it will still be active,
	 * and as such, there may be some odd behavior. This is unlikely to cause any issues though
	 * because it will be deactivated on the request that updates or activates WooCommerce.
	 */
	protected static function deactivate_merged_packages() {
		// Developers may need to be able to run merged feature plugins alongside merged packages for testing purposes.
		if ( Constants::is_true( 'WC_ALLOW_MERGED_FEATURE_PLUGINS' ) ) {
			return;
		}

		// Scroll through all of the active plugins and disable them if they're merged packages.
		$active_plugins = get_option( 'active_plugins', array() );
		// Deactivate the plugin if possible so that there are no conflicts.
		foreach ( $active_plugins as $active_plugin_path ) {
			$plugin_file = basename( plugin_basename( $active_plugin_path ), '.php' );
			if ( ! isset( self::$merged_packages[ $plugin_file ] ) ) {
				continue;
			}

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			// Make sure to display a message informing the user that the plugin has been deactivated.
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $active_plugin_path );
			deactivate_plugins( $active_plugin_path );
			add_action(
				'admin_notices',
				function() use ( $plugin_data ) {
					echo '<div class="error"><p>';
					printf(
						/* translators: %s: is referring to the plugin's name. */
						esc_html__( 'The %1$s plugin has been deactivated as the latest improvements are now included with the %2$s plugin.', 'woocommerce' ),
						'<code>' . esc_html( $plugin_data['Name'] ) . '</code>',
						'<code>WooCommerce</code>'
					);
					echo '</p></div>';
				}
			);
		}
	}

	/**
	 * Loads packages after plugins_loaded hook.
	 *
	 * Each package should include an init file which loads the package so it can be used by core.
	 */
	protected static function initialize_packages() {
		foreach ( self::$merged_packages as $package_name => $package_class ) {
			call_user_func( array( $package_class, 'init' ) );
		}

		foreach ( self::$packages as $package_name => $package_class ) {
			if ( ! self::package_exists( $package_name ) ) {
				self::missing_package( $package_name );
				continue;
			}
			call_user_func( array( $package_class, 'init' ) );
		}

		// Proxies "activated_plugin" hook for embedded packages listen on WC plugin activation
		// https://github.com/woocommerce/woocommerce/issues/28697.
		if ( is_admin() ) {
			$activated_plugin = get_transient( 'woocommerce_activated_plugin' );
			if ( $activated_plugin ) {
				delete_transient( 'woocommerce_activated_plugin' );

				/**
				 * WooCommerce is activated hook.
				 *
				 * @since 5.0.0
				 * @param bool $activated_plugin Activated plugin path,
				 *                               generally woocommerce/woocommerce.php.
				 */
				do_action( 'woocommerce_activated_plugin', $activated_plugin );
			}
		}
	}

	/**
	 * If a package is missing, add an admin notice.
	 *
	 * @param string $package Package name.
	 */
	protected static function missing_package( $package ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(  // phpcs:ignore
				sprintf(
					/* Translators: %s package name. */
					esc_html__( 'Missing the WooCommerce %s package', 'woocommerce' ),
					'<code>' . esc_html( $package ) . '</code>'
				) . ' - ' . esc_html__( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, please refer to this document to set up your development environment: https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment', 'woocommerce' )
			);
		}
		add_action(
			'admin_notices',
			function() use ( $package ) {
				?>
				<div class="notice notice-error">
					<p>
						<strong>
							<?php
							printf(
								/* Translators: %s package name. */
								esc_html__( 'Missing the WooCommerce %s package', 'woocommerce' ),
								'<code>' . esc_html( $package ) . '</code>'
							);
							?>
						</strong>
						<br>
						<?php
						printf(
							/* translators: 1: is a link to a support document. 2: closing link */
							esc_html__( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'woocommerce' ),
							'<a href="' . esc_url( 'https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment' ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		);
	}
}
