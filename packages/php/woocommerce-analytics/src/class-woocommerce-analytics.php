<?php
/**
 * Main class for the WooCommerce Analytics package.
 * Originally ported from the Jetpack_Google_Analytics code.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic;

use Automattic\Jetpack\Assets;
use Automattic\Jetpack\Connection\Manager as Jetpack_Connection;
use Automattic\Woocommerce_Analytics\My_Account;
use Automattic\Woocommerce_Analytics\Universal;
use Automattic\Woocommerce_Analytics\WC_Analytics_Tracking_Proxy;
use Composer\InstalledVersions;

/**
 * Instantiate WooCommerce Analytics
 */
class Woocommerce_Analytics {
	/**
	 * Package version.
	 */
	const PACKAGE_VERSION = '0.17.1';

	/**
	 * Proxy speed module version option.
	 *
	 * @var string
	 */
	const PROXY_SPEED_MODULE_VERSION_OPTION = 'woocommerce_analytics_proxy_speed_module_version';

	/**
	 * Proxy speed module version check transient.
	 *
	 * @var string
	 */
	const PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT = 'woocommerce_analytics_proxy_speed_module_version_check';

	/**
	 * Last resolved state of the proxy tracking feature.
	 *
	 * The speed module runs before plugins load, where the proxy tracking filter
	 * reads false everywhere. Only `yes` serves: the module is installed from a
	 * request that already wrote this, and on multisite one network-wide module
	 * file answers for sites that never enabled anything.
	 *
	 * @since 0.17.1
	 *
	 * @var string
	 */
	const PROXY_TRACKING_ENABLED_OPTION = 'woocommerce_analytics_proxy_tracking_enabled';

	/**
	 * Whether proxy tracking has ever been enabled on this site.
	 *
	 * Decides whether the REST route exists at all. Turning the feature off cannot
	 * unregister it, because pages cached while it was on still tell their visitors
	 * to POST events: a 404 loses every one of those silently, while the registered
	 * route answers 403 with a reason. Sites that never turned it on never get the
	 * endpoint.
	 *
	 * Sticky on purpose — it records that cached pages may exist, which staying off
	 * for a while does not undo. `reset_proxy_tracking_state()` is the way to clear
	 * it, for an uninstall routine or a site that wants the endpoint gone once the
	 * caches holding those pages have expired.
	 *
	 * @since 0.17.1
	 *
	 * @var string
	 */
	const PROXY_TRACKING_EVER_ENABLED_OPTION = 'woocommerce_analytics_proxy_tracking_ever_enabled';

	/**
	 * Initializer.
	 * Used to configure the WooCommerce Analytics package.
	 *
	 * @return void
	 */
	public static function init() {
		if ( ! self::should_track_store() || did_action( 'woocommerce_analytics_init' ) ) {
			return;
		}

		// loading _wca.
		add_action( 'wp_head', array( __CLASS__, 'wp_head_top' ), 1 );

		// loading s.js.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_tracking_script' ) );

		// loading client-side analytics script.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_client_script' ) );

		// Initialize general store tracking actions.
		add_action( 'init', array( new Universal(), 'init_hooks' ) );
		add_action( 'init', array( new My_Account(), 'init_hooks' ) );

		// Initialize REST API endpoints.
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

		/**
		 * Fires after the WooCommerce Analytics package is initialized
		 *
		 * @since 0.1.5
		 */
		do_action( 'woocommerce_analytics_init' );
	}

	/**
	 * WooCommerce Analytics is only available to Jetpack connected WooCommerce stores
	 * with WooCommerce version 3.0 or higher
	 *
	 * @return bool
	 */
	public static function should_track_store() {
		// Ensure this is available, even with mu-plugins.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		/**
		 * Make sure WooCommerce is installed and active
		 *
		 * This action is documented in https://docs.woocommerce.com/document/create-a-plugin
		 */
		if ( ! defined( 'WC_PLUGIN_FILE' ) || ! is_plugin_active( plugin_basename( WC_PLUGIN_FILE ) ) ) {
			return false;
		}
		// Ensure the WooCommerce class exists and is a valid version.
		$minimum_woocommerce_active = class_exists( 'WooCommerce' ) && version_compare( \WC_VERSION, '3.0', '>=' );
		if ( ! $minimum_woocommerce_active ) {
			return false;
		}

		// Ensure the WC Tracks classes exist.
		if ( ! class_exists( 'WC_Tracks' ) ) {
			if ( ! defined( 'WC_ABSPATH' ) || ! file_exists( WC_ABSPATH . 'includes/tracks/class-wc-tracks.php' ) ) {
				return false;
			}

			include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks.php';
			include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-event.php';
			include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-client.php';
		}

		add_action( 'admin_init', array( __CLASS__, 'maybe_update_proxy_speed_module' ) );

		// Late on `init`, so every plugin has registered its feature filters.
		add_action( 'init', array( __CLASS__, 'sync_proxy_tracking_state' ), 20 );

		// Tracking only Site pages.
		if ( is_admin() || wp_doing_ajax() || wp_is_xml_request() || is_login() || is_feed() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}

		// Make sure the site is connected to WordPress.com.
		if ( ! ( new Jetpack_Connection() )->is_connected() ) {
			return false;
		}

		return true;
	}

	/**
	 * Make _wca available to queue events
	 */
	public static function wp_head_top() {
		if ( is_cart() || is_checkout() || is_checkout_pay_page() || is_order_received_page() || is_add_payment_method_page() ) {
			echo '<script>window._wca_prevent_referrer = true;</script>' . "\r\n";
		}
		echo '<script>window._wca = window._wca || [];</script>' . "\r\n";
	}

	/**
	 * Place script to call s.js, Store Analytics.
	 */
	public static function enqueue_tracking_script() {
		$url = sprintf(
			'https://stats.wp.com/s-%d.js',
			gmdate( 'YW' )
		);

		wp_enqueue_script(
			'woocommerce-analytics',
			$url,
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- The version is set in the URL.
			array(
				'in_footer' => false,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Enqueue client-side analytics script.
	 */
	public static function enqueue_client_script() {
		Assets::register_script(
			'woocommerce-analytics-client',
			'../build/woocommerce-analytics-client.js',
			__FILE__,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
				'enqueue'   => true,
			)
		);
	}

	/**
	 * Register REST API routes.
	 *
	 * The tracking proxy endpoint is unauthenticated by design — it exists to
	 * receive front-end events — so a site that has never used proxy tracking never
	 * gets it. Once a site has used it the route stays registered and refuses while
	 * the feature is off, rather than disappearing; see
	 * PROXY_TRACKING_EVER_ENABLED_OPTION and `WC_Analytics_Tracking_Proxy::track_events()`.
	 */
	public static function register_rest_routes() {
		if ( 'yes' !== get_option( self::PROXY_TRACKING_EVER_ENABLED_OPTION ) ) {
			return;
		}

		$controller = new WC_Analytics_Tracking_Proxy();
		$controller->register_routes();
	}

	/**
	 * Mirror the resolved proxy tracking state into PROXY_TRACKING_ENABLED_OPTION.
	 *
	 * @since 0.17.1
	 *
	 * @return void
	 */
	public static function sync_proxy_tracking_state() {
		$enabled = \Automattic\Woocommerce_Analytics\Features::is_proxy_tracking_enabled() ? 'yes' : 'no';

		if ( 'yes' === $enabled && 'yes' !== get_option( self::PROXY_TRACKING_EVER_ENABLED_OPTION ) ) {
			update_option( self::PROXY_TRACKING_EVER_ENABLED_OPTION, 'yes' );
		}

		if ( get_option( self::PROXY_TRACKING_ENABLED_OPTION ) === $enabled ) {
			return;
		}

		update_option( self::PROXY_TRACKING_ENABLED_OPTION, $enabled );
	}

	/**
	 * Forget that proxy tracking was ever enabled, so the REST route stops being
	 * registered.
	 *
	 * Separate from `maybe_remove_proxy_speed_module()`, which clears the module's
	 * authorization but deliberately leaves this alone: removing the module does
	 * not expire the cached pages this records.
	 *
	 * @since 0.17.1
	 *
	 * @return void
	 */
	public static function reset_proxy_tracking_state() {
		delete_option( self::PROXY_TRACKING_EVER_ENABLED_OPTION );
		delete_option( self::PROXY_TRACKING_ENABLED_OPTION );
	}

	/**
	 * Maybe update proxy speed module.
	 *
	 * Turning proxy tracking off uninstalls the module, and the REST route starts
	 * refusing, so both halves stop serving. The module refuses on its own too,
	 * because this runs on `admin_init` behind a day-long transient and cannot be
	 * relied on to take effect promptly.
	 */
	public static function maybe_update_proxy_speed_module() {
		// Skip if we've already checked recently.
		if ( get_transient( self::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT ) ) {
			return;
		}

		$version = get_option( self::PROXY_SPEED_MODULE_VERSION_OPTION, false );

		if ( self::should_install_proxy_speed_module() ) {
			if ( $version !== self::PACKAGE_VERSION ) {
				self::maybe_add_proxy_speed_module();
			}
		} elseif ( $version !== false ) {
			self::maybe_remove_proxy_speed_module();
		}

		// Set the transient after the update attempt to prevent checking on every admin_init.
		// If the update failed, it will be retried after the transient expires (1 day).
		set_transient( self::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT, 1, DAY_IN_SECONDS );
	}

	/**
	 * Whether the proxy speed module belongs on this site.
	 *
	 * It is an accelerator for the tracking proxy, so it needs both its own
	 * opt-in and the feature it accelerates. Without the second condition,
	 * turning proxy tracking off would leave an installed module answering
	 * requests the REST route no longer serves.
	 *
	 * @since 0.17.1
	 *
	 * @return bool
	 */
	private static function should_install_proxy_speed_module() {
		return \Automattic\Woocommerce_Analytics\Features::is_proxy_speed_module_enabled()
			&& \Automattic\Woocommerce_Analytics\Features::is_proxy_tracking_enabled();
	}

	/**
	 * Maybe add proxy speed module.
	 */
	public static function maybe_add_proxy_speed_module() {
		if ( ! self::should_install_proxy_speed_module() ) {
			return;
		}

		// The module fails closed on this option, so it must be written before the
		// file exists, not merely on the next `init`.
		self::sync_proxy_tracking_state();

		if ( ! self::init_filesystem() ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'WooCommerce Analytics proxy speed module not installed: filesystem unavailable.', array( 'source' => 'woocommerce-analytics' ) );
			}
			return;
		}

		global $wp_filesystem;

		// Create the mu-plugin directory if it doesn't exist.
		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			wp_mkdir_p( WPMU_PLUGIN_DIR );
		}

		// If the mu-plugin directory doesn't exist, we can't copy the files.
		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'WooCommerce Analytics proxy speed module not installed: mu-plugins directory could not be created.', array( 'source' => 'woocommerce-analytics' ) );
			}
			return;
		}

		// Check if the mu-plugin directory is writable.
		if ( ! $wp_filesystem->is_writable( WPMU_PLUGIN_DIR ) ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->debug( 'WooCommerce Analytics proxy speed module not installed: mu-plugins directory is not writable.', array( 'source' => 'woocommerce-analytics' ) );
			}
			return;
		}

		if ( get_option( self::PROXY_SPEED_MODULE_VERSION_OPTION ) === self::PACKAGE_VERSION ) {
			// No need to copy the files again.
			return;
		}

		$mu_plugin_src_file  = __DIR__ . '/mu-plugin/woocommerce-analytics-proxy-speed-module-template.php';
		$mu_plugin_dest_file = trailingslashit( WPMU_PLUGIN_DIR ) . 'woocommerce-analytics-proxy-speed-module.php';

		// Verify source file exists before attempting to copy.
		if ( ! file_exists( $mu_plugin_src_file ) ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'WooCommerce Analytics proxy speed module source file not found.', array( 'source' => 'woocommerce-analytics' ) );
			}
			return;
		}

		$content = $wp_filesystem->get_contents( $mu_plugin_src_file );
		if ( false === $content ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'Failed to read the WooCommerce Analytics proxy speed module source file.', array( 'source' => 'woocommerce-analytics' ) );
			}
			return;
		}

		// Get the autoloader path from the current plugin location.
		$autoloader_path = self::locate_autoloader_file();
		if ( null === $autoloader_path ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'WooCommerce Analytics proxy speed module not installed: could not locate autoloader.', array( 'source' => 'woocommerce-analytics' ) );
			}
			return;
		}

		// Replace placeholders with actual values.
		$content = str_replace(
			array( '{{AUTOLOADER_PATH}}', '{{VERSION}}' ),
			array( $autoloader_path, self::PACKAGE_VERSION ),
			$content
		);

		if ( ! $wp_filesystem->put_contents( $mu_plugin_dest_file, $content ) ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'Failed to write the WooCommerce Analytics proxy speed module file.', array( 'source' => 'woocommerce-analytics' ) );
			}
			return;
		}

		update_option( self::PROXY_SPEED_MODULE_VERSION_OPTION, self::PACKAGE_VERSION );
	}

	/**
	 * Maybe removes the proxy speed module. This should be invoked when the plugin is deactivated.
	 *
	 * Also drops the module's authorization. MU-plugins load whether or not the
	 * plugin carrying this package is active, so an undeletable module file left
	 * behind by a deactivation would keep answering on a stale `yes`. The sticky
	 * option is left alone: pages cached while the feature was on outlive both.
	 */
	public static function maybe_remove_proxy_speed_module() {
		if ( ! self::init_filesystem() ) {
			return;
		}

		global $wp_filesystem;

		/**
		 * Clean up MU plugin.
		 */
		$file_path = trailingslashit( WPMU_PLUGIN_DIR ) . 'woocommerce-analytics-proxy-speed-module.php';

		if ( $wp_filesystem->exists( $file_path ) && $wp_filesystem->is_writable( $file_path ) ) {
			$deleted = $wp_filesystem->delete( $file_path );
			if ( ! $deleted && function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'Failed to delete WooCommerce Analytics proxy speed module file. The MU-plugin may continue running.', array( 'source' => 'woocommerce-analytics' ) );
			}
		}

		delete_option( self::PROXY_SPEED_MODULE_VERSION_OPTION );
		delete_option( self::PROXY_TRACKING_ENABLED_OPTION );
		delete_transient( self::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT );
	}

	/**
	 * Finds the path to the autoloader file.
	 *
	 * Uses multiple strategies to locate the autoloader, since this package
	 * can be included in different plugins (Jetpack, WooCommerce Analytics, etc.):
	 * 1. Jetpack autoloader global (if available)
	 * 2. Composer's InstalledVersions API
	 * 3. Directory-based guessing as a fallback
	 *
	 * @return string|null The path to the autoloader file, or null if not found.
	 */
	private static function locate_autoloader_file() {
		global $jetpack_autoloader_loader;

		$autoload_file = null;

		// Try the Jetpack autoloader.
		if ( isset( $jetpack_autoloader_loader ) ) {
			$class_file = $jetpack_autoloader_loader->find_class_file( self::class );
			if ( $class_file ) {
				// Walk up 5 levels: src/ → woocommerce-analytics/ → automattic/ → jetpack_vendor/ → plugin root.
				$autoload_file = dirname( $class_file, 5 ) . '/vendor/autoload.php';
			}
		}

		// Try Composer's InstalledVersions API.
		if ( null === $autoload_file
			&& is_callable( array( InstalledVersions::class, 'getInstallPath' ) )
			&& InstalledVersions::isInstalled( 'automattic/woocommerce-analytics' )
		) {
			$package_file    = InstalledVersions::getInstallPath( 'automattic/woocommerce-analytics' );
			$expected_suffix = '/automattic/woocommerce-analytics';
			if ( substr( $package_file, -strlen( $expected_suffix ) ) === $expected_suffix ) {
				// Walk up 3 levels: woocommerce-analytics/ → automattic/ → jetpack_vendor/ → plugin root.
				$autoload_file = dirname( $package_file, 3 ) . '/vendor/autoload.php';
			}
		}

		// Guess based on directory structure.
		// First try standard vendor layout (vendor/automattic/woocommerce-analytics/src/),
		// then try standalone package with its own vendor dir.
		if ( null === $autoload_file ) {
			// Walk up 4 levels from src/: woocommerce-analytics/ → automattic/ → vendor/ → project root.
			$autoload_file = dirname( __DIR__, 4 ) . '/vendor/autoload.php';
			if ( ! file_exists( $autoload_file ) ) {
				$autoload_file = dirname( __DIR__ ) . '/vendor/autoload.php';
			}
		}

		if ( ! file_exists( $autoload_file ) ) {
			return null;
		}

		return $autoload_file;
	}

	/**
	 * Initialize the WP filesystem.
	 *
	 * @return bool True if filesystem is initialized, false otherwise.
	 */
	private static function init_filesystem() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize the WP filesystem.
		ob_start();
		$initialized = WP_Filesystem();
		ob_end_clean();

		return $initialized;
	}
}
