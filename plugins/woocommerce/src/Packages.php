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
	 * Array of package names and their main package classes.
	 *
	 * @var array Key is the package name/directory, value is the main package class which handles init.
	 */
	protected static $packages = array(
		'woocommerce-admin' => '\\Automattic\\WooCommerce\\Admin\\Composer\\Package',
		'woocommerce-blocks' => '\\Automattic\\WooCommerce\\Blocks\\Package',
	);

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
		'woocommerce-admin',
		// Accommodate all of the different names WooCommerce Blocks may be referred to using.
		'woo-gutenberg-products-block',
		'woocommerce-gutenberg-products-block',
		'woocommerce-blocks',
	);

	/**
	 * Init the package loader.
	 *
	 * @since 3.7.0
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'on_init' ) );
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
		// Once a package has been merged it always exists.
		if ( in_array( $package, self::$merged_packages, true ) ) {
			return true;
		}

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

		// Since we are relying on the Jetpack Autloader to resolve class versioning differences we can use it to
		// detect whether or not an mu-plugin is registered in the autoloader. This is powerful because it means
		// we don't have to do expensive mu-plugin checks on every request.
		global $jetpack_autoloader_cached_plugin_paths;
		$active_plugins = get_option( 'active_plugins', array() );
		foreach ( $jetpack_autoloader_cached_plugin_paths as $plugin_dir_path ) {
			$plugin_dir = basename( $plugin_dir_path );

			if ( ! in_array( $plugin_dir, self::$merged_packages, true ) ) {
				continue;
			}

			// Deactivate the plugin if possible so that there are no conflicts.
			foreach ( $active_plugins as $active_plugin_path ) {
				$active_plugin_dir = dirname( plugin_basename( $active_plugin_path ) );
				if ( $active_plugin_dir !== $plugin_dir ) {
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
							'<code>' . $plugin_data[ 'Name' ] . '</code>',
							'<code>WooCommerce</code>'
						);
						echo '</p></div>';
					}
				);

				continue 2;
			}

			// Since no active plugin was found this is most likely an mu-plugin. We can't deactivate this ourselves so we need
			// to display a message informing the site administrator that the plugin needs to be disabled.
			add_action(
				'admin_notices',
				function() use ( $plugin_dir ) {
					echo '<div class="error"><p>';
					printf(
						/* translators: %s: is referring to the plugin's name. */
						esc_html__( 'The %1$s Must-Use plugin has been merged into WooCommerce and should be removed from your site.', 'woocommerce' ),
						'<code>' . $plugin_dir . '</code>'
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
