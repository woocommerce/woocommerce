<?php
/**
 * WooCommerce Admin Helper - React admin interface
 *
 * @package WooCommerce\Admin\Helper
 */

use Automattic\WooCommerce\Internal\Admin\Marketplace;
use Automattic\WooCommerce\Admin\PluginsHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper Class
 *
 * The main entry-point for all things related to the Helper.
 * The Helper manages the connection between the store and
 * an account on WooCommerce.com.
 */
class WC_Helper_Admin {
	/**
	 * Clear cache tool identifier.
	 */
	const CACHE_TOOL_ID = 'clear_woocommerce_helper_cache';

	/**
	 * Loads the class, runs on init
	 *
	 * @return void
	 */
	public static function load() {
		if ( is_admin() ) {
			$is_wc_home_or_in_app_marketplace = (
				isset( $_GET['page'] ) && 'wc-admin' === $_GET['page'] //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);

			if ( $is_wc_home_or_in_app_marketplace ) {
				add_filter( 'woocommerce_admin_shared_settings', array( __CLASS__, 'add_marketplace_settings' ) );
			}

			add_filter( 'woocommerce_debug_tools', array( __CLASS__, 'register_cache_clear_tool' ) );
		}

		add_filter( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Pushes settings onto the WooCommerce Admin global settings object (wcSettings).
	 *
	 * @param mixed $settings The settings object we're amending.
	 *
	 * @return mixed $settings
	 */
	public static function add_marketplace_settings( $settings ) {
		if ( ! WC_Helper::is_site_connected() && isset( $_GET['connect'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( self::get_connection_url() );
			exit;
		}

		// Handle return from Jetpack authorization - try to create WCCOM connection via Jetpack.
		if ( isset( $_GET['jp_wccom_connect'] ) && '1' === $_GET['jp_wccom_connect'] && ! WC_Helper::is_site_connected() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::handle_jetpack_wccom_connect();
		}

		$auth_user_data  = WC_Helper_Options::get( 'auth_user_data', array() );
		$auth_user_email = isset( $auth_user_data['email'] ) ? $auth_user_data['email'] : '';

		// Get the all installed themes and plugins. Knowing this will help us decide to show Add to Store button on product cards.
		$installed_products = array_merge( WC_Helper::get_local_plugins(), WC_Helper::get_local_themes() );
		$installed_products = array_map(
			function ( $product ) {
				return $product['slug'];
			},
			$installed_products
		);

		$blog_name = get_bloginfo( 'name' );

		$settings['wccomHelper'] = array(
			'isConnected'                => WC_Helper::is_site_connected(),
			'connectURL'                 => self::get_connection_url(),
			'reConnectURL'               => self::get_connection_url( true ),
			'userEmail'                  => $auth_user_email,
			'userAvatar'                 => get_avatar_url( $auth_user_email, array( 'size' => '48' ) ),
			'storeCountry'               => wc_get_base_location()['country'],
			'storeName'                  => $blog_name ? $blog_name : '',
			'inAppPurchaseURLParams'     => WC_Admin_Addons::get_in_app_purchase_url_params(),
			'installedProducts'          => $installed_products,
			'mySubscriptionsTabLoaded'   => WC_Helper_Options::get( 'my_subscriptions_tab_loaded' ),
			'wooUpdateManagerInstalled'  => WC_Woo_Update_Manager_Plugin::is_plugin_installed(),
			'wooUpdateManagerActive'     => WC_Woo_Update_Manager_Plugin::is_plugin_active(),
			'wooUpdateManagerInstallUrl' => WC_Woo_Update_Manager_Plugin::generate_install_url(),
			'wooUpdateManagerPluginSlug' => WC_Woo_Update_Manager_Plugin::WOO_UPDATE_MANAGER_SLUG,
			'dismissNoticeNonce'         => wp_create_nonce( 'dismiss_notice' ),
			'trackingAllowed'            => 'yes' === get_option( 'woocommerce_allow_tracking' ),
			'isJetpackConnected'         => self::is_jetpack_connected(),
			'isJetpackSiteConnected'     => self::is_jetpack_site_connected(),
			'isJetpackUserConnected'     => self::is_jetpack_user_connected(),
		);


		// This data is only used in the `Extensions` screen, so only populate it there.
		// More specifically, it's used in `My Subscriptions`, however, switching tabs doesn't require
		// a page reload, so we just check for `path` (/extensions), rather than `tab` (my-subscriptions).
		if ( ! empty( $_GET['path'] ) && '/extensions' === $_GET['path'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$settings['wccomHelper']['wooUpdateCount']          = WC_Helper_Updater::get_updates_count_based_on_site_status();
			$settings['wccomHelper']['connected_notice']        = PluginsHelper::get_wccom_connected_notice( $auth_user_email );
			$settings['wccomHelper']['woocomConnectNoticeType'] = WC_Helper_Updater::get_woo_connect_notice_type();

			if ( WC_Helper::is_site_connected() ) {
				$settings['wccomHelper']['subscription_expired_notice']  = PluginsHelper::get_expired_subscription_notice( false );
				$settings['wccomHelper']['subscription_expiring_notice'] = PluginsHelper::get_expiring_subscription_notice( false );
				$settings['wccomHelper']['subscription_missing_notice']  = PluginsHelper::get_missing_subscription_notice();
				$settings['wccomHelper']['connection_url_notice']        = WC_Woo_Helper_Connection::get_connection_url_notice();
				$settings['wccomHelper']['has_host_plan_orders']         = WC_Woo_Helper_Connection::has_host_plan_orders();
			} else {
				$settings['wccomHelper']['disconnected_notice'] = PluginsHelper::get_wccom_disconnected_notice();
			}
		}

		return $settings;
	}

	/**
	 * Generates the URL for connecting or disconnecting the store to/from WooCommerce.com.
	 * Approach taken from existing helper code that isn't exposed.
	 *
	 * @param bool $reconnect indicate if the site is being reconnected.
	 *
	 * @return string
	 */
	public static function get_connection_url( $reconnect = false ) {
		// Default to wc-addons, although this can be changed from the frontend
		// in the function `connectUrl()` within marketplace functions.tsx.
		$connect_url_args = array(
			'page'    => 'wc-addons',
			'section' => 'helper',
		);

		// No active connection.
		if ( WC_Helper::is_site_connected() && ! $reconnect ) {
			$connect_url_args['wc-helper-disconnect'] = 1;
			$connect_url_args['wc-helper-nonce']      = wp_create_nonce( 'disconnect' );
		} else {
			$connect_url_args['wc-helper-connect'] = 1;
			$connect_url_args['wc-helper-nonce']   = wp_create_nonce( 'connect' );
		}

		if ( ! empty( $_GET['utm_source'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$connect_url_args['utm_source'] = wc_clean( wp_unslash( $_GET['utm_source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( ! empty( $_GET['utm_campaign'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$connect_url_args['utm_campaign'] = wc_clean( wp_unslash( $_GET['utm_campaign'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return add_query_arg(
			$connect_url_args,
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Handles the return from Jetpack authorization and attempts to create WCCOM connection.
	 *
	 * This is called when the user returns from Jetpack auth with jp_wccom_connect=1.
	 * It tries to connect to WooCommerce.com via the Jetpack connection, and falls back
	 * to the direct WCCOM OAuth flow if that fails.
	 *
	 * @since 10.5.0
	 *
	 * @return void
	 */
	private static function handle_jetpack_wccom_connect() {
		// Build the base redirect URL (current page without the jp_wccom_connect param).
		$redirect_url = remove_query_arg( 'jp_wccom_connect' );

		// Check if user is Jetpack connected.
		if ( ! self::is_jetpack_user_connected() ) {
			// User is not Jetpack connected, fall back to direct WCCOM connect.
			wp_safe_redirect( self::get_connection_url() );
			exit;
		}

		// Try to connect via Jetpack.
		$result = self::try_wccom_connect_via_jetpack();

		if ( is_wp_error( $result ) ) {
			// Failed, fall back to direct WCCOM connect.
			wp_safe_redirect( self::get_connection_url() );
			exit;
		}

		// Success! Redirect back to the page without the param.
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Attempts to connect to WooCommerce.com via Jetpack.
	 *
	 * Makes a POST request to the WordPress.com WCCOM connect endpoint using the
	 * Jetpack user token, and stores the returned credentials.
	 *
	 * @since 10.5.0
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	private static function try_wccom_connect_via_jetpack() {
		if ( ! class_exists( 'Automattic\Jetpack\Connection\Client' ) ) {
			return new WP_Error(
				'jetpack_client_not_available',
				__( 'Jetpack connection client is not available.', 'woocommerce' )
			);
		}

		$response = \Automattic\Jetpack\Connection\Client::wpcom_json_api_request_as_user(
			'wccom/connect',
			'2',
			array(
				'method'  => 'POST',
				'headers' => array( 'Content-Type' => 'application/json' ),
				'timeout' => 30,
			),
			null,
			'wpcom'
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( $response_code >= 400 ) {
			$error_message = $data['message'] ?? __( 'Failed to connect to WooCommerce.com via Jetpack.', 'woocommerce' );
			return new WP_Error(
				$data['code'] ?? 'wccom_connect_failed',
				$error_message
			);
		}

		if ( empty( $data['access_token'] ) || empty( $data['access_token_secret'] ) || empty( $data['site_id'] ) ) {
			return new WP_Error(
				'wccom_connect_invalid_response',
				__( 'Invalid response from WooCommerce.com connection endpoint.', 'woocommerce' )
			);
		}

		// Store the connection credentials.
		WC_Helper::update_auth_option(
			$data['access_token'],
			$data['access_token_secret'],
			(int) $data['site_id'],
			home_url()
		);

		return true;
	}

	/**
	 * Checks if the site is fully connected to WordPress.com via Jetpack.
	 *
	 * A site is fully connected when both the site has a blog token
	 * and there is a connected owner user.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if fully connected to Jetpack/WordPress.com.
	 */
	public static function is_jetpack_connected() {
		if ( ! class_exists( 'WC_Jetpack' ) ) {
			return false;
		}

		return WC_Jetpack::instance()->is_connected();
	}

	/**
	 * Checks if the site has a blog token registered with WordPress.com via Jetpack.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if site is registered with Jetpack/WordPress.com.
	 */
	public static function is_jetpack_site_connected() {
		if ( ! class_exists( 'WC_Jetpack' ) ) {
			return false;
		}

		return WC_Jetpack::instance()->is_site_connected();
	}

	/**
	 * Checks if the current user is connected to WordPress.com via Jetpack.
	 *
	 * @since 10.5.0
	 *
	 * @return bool True if current user is connected to Jetpack/WordPress.com.
	 */
	public static function is_jetpack_user_connected() {
		if ( ! class_exists( 'WC_Jetpack' ) ) {
			return false;
		}

		return WC_Jetpack::instance()->is_user_connected();
	}

	/**
	 * Registers the REST routes for the featured products and product
	 * previews endpoints.
	 */
	public static function register_rest_routes() {
		/* Used by the WooCommerce > Extensions > Discover page. */
		register_rest_route(
			'wc/v3',
			'/marketplace/featured',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_featured' ),
				'permission_callback' => array( __CLASS__, 'get_permission' ),
			)
		);

		/* Used to show previews of products in a modal in in-app marketplace. */
		register_rest_route(
			'wc/v1',
			'/marketplace/product-preview',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_product_preview' ),
				'permission_callback' => array( __CLASS__, 'get_permission' ),
			)
		);
	}

	/**
	 * The Extensions page can only be accessed by users with the manage_woocommerce
	 * capability. So the API mimics that behavior.
	 */
	public static function get_permission() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Fetch featured products from WooCommerce.com and serve them
	 * as JSON.
	 */
	public static function get_featured() {
		$featured = WC_Admin_Addons::fetch_featured();

		if ( is_wp_error( $featured ) ) {
			wp_send_json_error( array( 'message' => $featured->get_error_message() ) );
		}

		wp_send_json( $featured );
	}

	/**
	 * Fetch data for product previews from WooCommerce.com.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public static function get_product_preview( $request ) {
		$product_id = (int) $request->get_param( 'product_id' );

		if ( ! $product_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Missing product ID', 'woocommerce' ),
				),
				400
			);
		}

		$product_preview = WC_Admin_Addons::fetch_product_preview( $product_id );

		if ( ! $product_preview ) {
			wp_send_json_error(
				array(
					'message' => __( 'We couldn\'t find a preview for this product.', 'woocommerce' ),
				),
				404
			);
		}

		if ( is_wp_error( $product_preview ) ) {
			wp_send_json_error(
				array(
					'message' => $product_preview->get_error_message(),
				)
			);
		}

		if (
			! isset( $product_preview['css'] )
			|| ! is_string( $product_preview['css'] )
			|| ! isset( $product_preview['html'] )
			|| ! is_string( $product_preview['html'] )
		) {
			wp_send_json_error(
				array(
					'message' => __(
						'API response is missing required elements, or they are in the wrong form.',
						'woocommerce'
					),
				),
				500
			);
		}

		$sanitized_product_preview = array(
			'css'  => WC_Helper_Sanitization::sanitize_css( $product_preview['css'] ),
			'html' => WC_Helper_Sanitization::sanitize_html( $product_preview['html'] ),
		);

		wp_send_json( $sanitized_product_preview );
	}

	/**
	 * Register the cache clearing tool on the WooCommerce > Status > Tools page.
	 *
	 * @param array $debug_tools Available debug tool registrations.
	 * @return array Filtered debug tool registrations.
	 */
	public static function register_cache_clear_tool( $debug_tools ) {
		$debug_tools[ self::CACHE_TOOL_ID ] = array(
			'name'     => __( 'Clear WooCommerce.com cache', 'woocommerce' ),
			'button'   => __( 'Clear', 'woocommerce' ),
			'desc'     => sprintf(
				__( 'This tool will empty the WooCommerce.com data cache, used in WooCommerce Extensions.', 'woocommerce' ),
			),
			'callback' => array( __CLASS__, 'run_clear_cache_tool' ),
		);

		return $debug_tools;
	}


	/**
	 * "Clear" helper cache by invalidating it.
	 */
	public static function run_clear_cache_tool() {
		WC_Helper::_flush_subscriptions_cache();
		WC_Helper::flush_product_usage_notice_rules_cache();
		WC_Helper::flush_connection_data_cache();
		WC_Helper_Updater::flush_updates_cache();

		return __( 'Helper cache cleared.', 'woocommerce' );
	}
}

WC_Helper_Admin::load();
