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
	 * One a package has been merged into WooCommerce Core it should be moved from the package list and placed in
	 * this list. This will ensure that the feature plugin is disabled as well as provide the class to handle
	 * initialization for the now-merged feature plugin.
	 *
	 * Once a package has been merged into WooCommerce Core it should have its slug added here. This will ensure
	 * that we deactivate the feature plugin automatically to prevent any problems caused by conflicts between
	 * the two versions caused by them both being active.
	 *
	 * The packages included in this array cannot be deactivated and will always load with WooCommerce core.
	 *
	 * @var array Key is the package name/directory, value is the main package class which handles init.
	 */
	protected static $base_packages = array(
		'woocommerce-admin'                    => '\\Automattic\\WooCommerce\\Admin\\Composer\\Package',
		'woocommerce-gutenberg-products-block' => '\\Automattic\\WooCommerce\\Blocks\\Package',
	);

	/**
	 * Similar to $base_packages, but
	 * the packages included in this array can be deactivated via the 'woocommerce_merged_packages' filter.
	 *
	 * @var array Key is the package name/directory, value is the main package class which handles init.
	 */
	protected static $merged_packages = array(
		'woocommerce-brands' => '\\Automattic\\WooCommerce\\Internal\\Brands',
	);


	/**
	 * Init the package loader.
	 *
	 * @since 3.7.0
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'prepare_packages' ), -100 );
		add_action( 'plugins_loaded', array( __CLASS__, 'on_init' ), 10 );

		// Prevent plugins already merged into WooCommerce core from getting activated as standalone plugins.
		add_action( 'activate_plugin', array( __CLASS__, 'deactivate_merged_plugins' ) );

		// Display a notice in the Plugins tab next to plugins already merged into WooCommerce core.
		add_filter( 'all_plugins', array( __CLASS__, 'mark_merged_plugins_as_pending_update' ), 10, 1 );
		add_action( 'after_plugin_row', array( __CLASS__, 'display_notice_for_merged_plugins' ), 10, 1 );
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
	 * Checks a package exists by looking for it's directory.
	 *
	 * @param string $class_name Class name.
	 * @return boolean
	 */
	public static function should_load_class( $class_name ) {

		foreach ( self::$merged_packages as $merged_package_name => $merged_package_class ) {
			if ( str_replace( 'woocommerce-', 'wc_', $merged_package_name ) === $class_name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets all merged, enabled packages.
	 *
	 * @return array
	 */
	protected static function get_enabled_packages() {
		$enabled_packages = array();

		foreach ( self::$merged_packages as $merged_package_name => $package_class ) {

			// For gradual rollouts, ensure that a package is enabled for user's remote variant number.
			$experimental_package_enabled = method_exists( $package_class, 'is_enabled' ) ?
				call_user_func( array( $package_class, 'is_enabled' ) ) :
				false;

			if ( ! $experimental_package_enabled ) {
				continue;
			}

			$option = 'wc_feature_' . str_replace( '-', '_', $merged_package_name ) . '_enabled';
			if ( 'yes' === get_option( $option, 'no' ) ) {
				$enabled_packages[ $merged_package_name ] = $package_class;
			}
		}

		return array_merge( $enabled_packages, self::$base_packages );
	}

	/**
	 * Checks if a package is enabled.
	 *
	 * @param string $package Package name.
	 * @return boolean
	 */
	public static function is_package_enabled( $package ) {
		return array_key_exists( $package, self::get_enabled_packages() );
	}

	/**
	 * Prepare merged packages for initialization.
	 * Especially useful when running actions early in the 'plugins_loaded' timeline.
	 */
	public static function prepare_packages() {
		foreach ( self::get_enabled_packages() as $package_name => $package_class ) {
			if ( method_exists( $package_class, 'prepare' ) ) {
				call_user_func( array( $package_class, 'prepare' ) );
			}
		}
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

			if ( ! self::is_package_enabled( $plugin_file ) ) {
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
	 * Prevent plugins already merged into WooCommerce core from getting activated as standalone plugins.
	 *
	 * @param string $plugin Plugin name.
	 */
	public static function deactivate_merged_plugins( $plugin ) {
		$plugin_dir = basename( dirname( $plugin ) );

		if ( self::is_package_enabled( $plugin_dir ) ) {
			$plugins_url = esc_url( admin_url( 'plugins.php' ) );
			wp_die(
				esc_html__( 'This plugin cannot be activated because its functionality is now included in WooCommerce core.', 'woocommerce' ),
				esc_html__( 'Plugin Activation Error', 'woocommerce' ),
				array(
					'link_url'  => esc_url( $plugins_url ),
					'link_text' => esc_html__( 'Return to the Plugins page', 'woocommerce' ),
				),
			);
		}
	}

	/**
	 * Mark merged plugins as pending update.
	 * This is required for correctly displaying maintenance notices.
	 *
	 * @param array $plugins Plugins list.
	 */
	public static function mark_merged_plugins_as_pending_update( $plugins ) {
		foreach ( $plugins as $plugin_name => $plugin_data ) {
			$plugin_dir = basename( dirname( $plugin_name ) );
			if ( self::is_package_enabled( $plugin_dir ) ) {
				// Necessary to properly display notice within row.
				$plugins[ $plugin_name ]['update'] = 1;
			}
		}
		return $plugins;
	}

	/**
	 * Displays a maintenance notice next to merged plugins, to inform users
	 * that the plugin functionality is now offered by WooCommerce core.
	 *
	 * Requires 'mark_merged_plugins_as_pending_update' to properly display this notice.
	 *
	 * @param string $plugin_file Plugin file.
	 */
	public static function display_notice_for_merged_plugins( $plugin_file ) {
		global $wp_list_table;

		$plugin_dir    = basename( dirname( $plugin_file ) );
		$columns_count = $wp_list_table->get_column_count();
		$notice        = __( 'This plugin can no longer be activated because its functionality is now included in <strong>WooCommerce</strong>. It is recommended to <strong>delete</strong> it.', 'woocommerce' );

		if ( self::is_package_enabled( $plugin_dir ) ) {
			echo '<tr class="plugin-update-tr"><td colspan="' . esc_attr( $columns_count ) . '" class="plugin-update"><div class="update-message notice inline notice-error notice-alt"><p>' . wp_kses_post( $notice ) . '</p></div></td></tr>';
		}
	}

	/**
	 * Loads packages after plugins_loaded hook.
	 *
	 * Each package should include an init file which loads the package so it can be used by core.
	 */
	protected static function initialize_packages() {
		foreach ( self::get_enabled_packages() as $package_name => $package_class ) {
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
			function () use ( $package ) {
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
