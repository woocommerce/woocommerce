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

/**
 * Instantiate WooCommerce Analytics
 */
class Woocommerce_Analytics {
	/**
	 * Package version.
	 */
	const PACKAGE_VERSION = '0.10.0';

	/**
	 * Proxy speed module version.
	 *
	 * @var string
	*/
	const PROXY_SPEED_MODULE_VERSION = '1.0.0';

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
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
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
	 */
	public static function register_rest_routes() {
		$controller = new WC_Analytics_Tracking_Proxy();
		$controller->register_routes();
	}

	/**
	 * Maybe add proxy speed module.
	 */
	public static function maybe_add_proxy_speed_module() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize the WP filesystem.
		WP_Filesystem();

		// Create the mu-plugin directory if it doesn't exist.
		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			wp_mkdir_p( WPMU_PLUGIN_DIR );
		}

		// If the mu-plugin directory doesn't exist, we can't copy the files.
		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			return;
		}

		if ( get_option( 'woocommerce_analytics_proxy_speed_module_version' ) === self::PROXY_SPEED_MODULE_VERSION ) {
			// No need to copy the files again.
			return;
		}

		update_option( 'woocommerce_analytics_proxy_speed_module_version', self::PROXY_SPEED_MODULE_VERSION );
		$mu_plugin_src_file  = __DIR__ . '/mu-plugin/woocommerce-analytics-proxy-speed-module.php';
		$mu_plugin_dest_file = WPMU_PLUGIN_DIR . '/woocommerce-analytics-proxy-speed-module.php';
		$results             = copy( $mu_plugin_src_file, $mu_plugin_dest_file );

		if ( ! $results ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'Failed to copy the WooCommerce Analytics proxy speed module files.', array( 'source' => 'woocommerce-analytics' ) );
			}
		}
	}

	/**
	 * Maybe removes the proxy speed module. This should be invoked when the plugin is deactivated.
	 */
	public static function maybe_remove_proxy_speed_module() {
		/**
		 * Clean up MU plugin.
		 */
		$file_path = WPMU_PLUGIN_DIR . '/woocommerce-analytics-proxy-speed-module.php';

		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}

		delete_option( 'woocommerce_analytics_proxy_speed_module_version' );
	}
}
