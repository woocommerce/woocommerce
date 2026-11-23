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
use Automattic\Woocommerce_Analytics\Consent_Manager;
use Automattic\Woocommerce_Analytics\My_Account;
use Automattic\Woocommerce_Analytics\Universal;
use Automattic\Woocommerce_Analytics\WC_Analytics_Tracking_Proxy;

/**
 * Instantiate WooCommerce Analytics
 */
class Woocommerce_Analytics {
	/**
	 * Package version. Rewritten from composer.json by tasks/build-package.sh when the package is
	 * published, so bump the version in composer.json instead.
	 */
	const PACKAGE_VERSION = '0.18.0';

	/**
	 * Version of the speed module MU-plugin an earlier package installed, kept until the file is removed.
	 *
	 * @var string
	 */
	const PROXY_SPEED_MODULE_VERSION_OPTION = 'woocommerce_analytics_proxy_speed_module_version';

	/**
	 * Throttles the daily check for a leftover speed module MU-plugin.
	 *
	 * @var string
	 */
	const PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT = 'woocommerce_analytics_proxy_speed_module_version_check';

	/**
	 * Whether the speed module may serve requests.
	 *
	 * It reads this option because filters are unavailable before plugins load.
	 * It mirrors proxy tracking and the host-provided filter on every init.
	 *
	 * @since 0.18.0
	 *
	 * @var string
	 */
	const PROXY_SPEED_MODULE_AUTHORIZED_OPTION = 'woocommerce_analytics_proxy_speed_module_authorized';

	/**
	 * Whether proxy tracking has ever been enabled on this site.
	 *
	 * Keeps the REST route registered to return 403 to cached pages after the
	 * feature is disabled. Clear it only after those cached pages have expired.
	 *
	 * @since 0.18.0
	 *
	 * @var string
	 */
	const PROXY_TRACKING_EVER_ENABLED_OPTION = 'woocommerce_analytics_proxy_tracking_ever_enabled';

	/**
	 * Script handle for s.js, registered with no src when proxy tracking is enabled. WordPress prints its tag with the id `{handle}-js`.
	 *
	 * @var string
	 */
	private const TRACKING_SCRIPT_HANDLE = 'woocommerce-analytics';

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
		// Register before early returns so the MU-plugin does not keep stale state.
		add_action( 'init', array( __CLASS__, 'sync_proxy_tracking_state' ), 20 );

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
	 * Place script to call s.js, Store Analytics, or only its handle when proxy tracking is enabled.
	 */
	public static function enqueue_tracking_script() {
		// Proxy tracking sends events without s.js, which would only set referrer cookies.
		if ( \Automattic\Woocommerce_Analytics\Features::is_proxy_tracking_enabled() ) {
			wp_register_script( self::TRACKING_SCRIPT_HANDLE, false, array(), null, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- There is no src to version.
			wp_enqueue_script( self::TRACKING_SCRIPT_HANDLE );
			return;
		}

		$url = sprintf(
			'https://stats.wp.com/s-%d.js',
			gmdate( 'YW' )
		);

		// s.js sets referrer cookies as soon as it runs, so with a consent plugin it prints inert for the client to run after consent.
		if ( Consent_Manager::is_consent_api_active() ) {
			add_filter( 'wp_script_attributes', array( __CLASS__, 'hold_tracking_script_until_consent' ) );
		}

		wp_enqueue_script(
			self::TRACKING_SCRIPT_HANDLE,
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
	 * Print the s.js tag as `type="text/plain"` so it does not run until the client activates it after statistics consent.
	 *
	 * @since 0.18.1
	 *
	 * @param mixed $attributes The script tag attributes.
	 * @return mixed
	 */
	public static function hold_tracking_script_until_consent( $attributes ) {
		if ( ! is_array( $attributes ) || ( $attributes['id'] ?? '' ) !== self::TRACKING_SCRIPT_HANDLE . '-js' ) {
			return $attributes;
		}

		$attributes['type']                      = 'text/plain';
		$attributes['data-wc-analytics-consent'] = Consent_Manager::WP_CONSENT_API_STATISTICS_TYPE;

		return $attributes;
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
	 * A site that has never used proxy tracking does not get the endpoint. It stays
	 * registered after being disabled, so cached pages receive a visible 403.
	 */
	public static function register_rest_routes() {
		if ( 'yes' !== get_option( self::PROXY_TRACKING_EVER_ENABLED_OPTION ) ) {
			return;
		}

		$controller = new WC_Analytics_Tracking_Proxy();
		$controller->register_routes();
	}

	/**
	 * Sync proxy tracking state for the REST route and MU-plugin speed module.
	 *
	 * @since 0.18.0
	 *
	 * @return void
	 */
	public static function sync_proxy_tracking_state() {
		if ( \Automattic\Woocommerce_Analytics\Features::is_proxy_tracking_enabled()
			&& 'yes' !== get_option( self::PROXY_TRACKING_EVER_ENABLED_OPTION ) ) {
			update_option( self::PROXY_TRACKING_EVER_ENABLED_OPTION, 'yes' );
		}

		// Track module eligibility so a removed module cannot be reauthorized.
		$authorized = self::should_authorize_proxy_speed_module() ? 'yes' : 'no';
		$current    = get_option( self::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );

		if ( $current === $authorized ) {
			return;
		}

		// An absent option already means unauthorized, so most sites installing this
		// package never need a row saying so.
		if ( false === $current && 'no' === $authorized ) {
			return;
		}

		update_option( self::PROXY_SPEED_MODULE_AUTHORIZED_OPTION, $authorized );
	}

	/**
	 * Forget that proxy tracking was enabled, so the REST route is unregistered.
	 *
	 * This is separate from removing the speed module because cached pages may remain.
	 *
	 * @since 0.18.0
	 *
	 * @return void
	 */
	public static function reset_proxy_tracking_state() {
		delete_option( self::PROXY_TRACKING_EVER_ENABLED_OPTION );
		delete_option( self::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );
	}

	/**
	 * Remove a speed module MU-plugin that an earlier package version installed.
	 *
	 * A leftover copy runs its own stale code, and would serve again once a host authorizes the module.
	 */
	public static function maybe_update_proxy_speed_module() {
		if ( get_transient( self::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT ) ) {
			return;
		}

		if ( false !== get_option( self::PROXY_SPEED_MODULE_VERSION_OPTION, false ) ) {
			self::maybe_remove_proxy_speed_module();
		}

		// A failed removal keeps the version option, so it is retried once this expires.
		set_transient( self::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT, 1, DAY_IN_SECONDS );
	}

	/**
	 * Whether the host-provided speed module may serve.
	 *
	 * @since 0.19.0
	 *
	 * @return bool
	 */
	private static function should_authorize_proxy_speed_module() {
		return \Automattic\Woocommerce_Analytics\Features::is_proxy_tracking_enabled()
			&& \Automattic\Woocommerce_Analytics\Features::is_proxy_speed_module_host_provided();
	}

	/**
	 * Formerly installed the speed module MU-plugin; the package no longer writes it.
	 *
	 * @deprecated 0.19.0 Hosts return true from `woocommerce_analytics_proxy_speed_module_host_provided` and call Proxy_Speed_Module::maybe_serve().
	 */
	public static function maybe_add_proxy_speed_module() {
		// Kept as a no-op for callers such as the Jetpack activation hook.
	}

	/**
	 * Remove a speed module MU-plugin left by an earlier package version, and revoke the module.
	 *
	 * Clear its authorization because an undeletable MU-plugin can still load.
	 */
	public static function maybe_remove_proxy_speed_module() {
		// Revoked before anything can fail: WP_Filesystem() returns false outright on
		// hosts that ask for credentials, and that is exactly the case where the file
		// survives and keeps loading.
		delete_option( self::PROXY_SPEED_MODULE_AUTHORIZED_OPTION );

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
		delete_transient( self::PROXY_SPEED_MODULE_VERSION_CHECK_TRANSIENT );
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
