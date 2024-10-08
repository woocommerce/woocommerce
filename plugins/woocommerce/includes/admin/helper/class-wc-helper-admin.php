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
	 * Loads the class, runs on init
	 *
	 * @return void
	 */
	public static function load() {
		global $pagenow;

		if ( is_admin() ) {
			$is_in_app_marketplace = ( 'admin.php' === $pagenow
				&& isset( $_GET['page'] ) && 'wc-admin' === $_GET['page'] //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				&& isset( $_GET['path'] ) && '/extensions' === $_GET['path'] //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);

			if ( $is_in_app_marketplace ) {
				add_filter( 'woocommerce_admin_shared_settings', array( __CLASS__, 'add_marketplace_settings' ) );
			}
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

		$woo_connect_notice_type = WC_Helper_Updater::get_woo_connect_notice_type();

		$settings['wccomHelper'] = array(
			'isConnected'                => WC_Helper::is_site_connected(),
			'connectURL'                 => self::get_connection_url(),
			'userEmail'                  => $auth_user_email,
			'userAvatar'                 => get_avatar_url( $auth_user_email, array( 'size' => '48' ) ),
			'storeCountry'               => wc_get_base_location()['country'],
			'inAppPurchaseURLParams'     => WC_Admin_Addons::get_in_app_purchase_url_params(),
			'installedProducts'          => $installed_products,
			'wooUpdateManagerInstalled'  => WC_Woo_Update_Manager_Plugin::is_plugin_installed(),
			'wooUpdateManagerActive'     => WC_Woo_Update_Manager_Plugin::is_plugin_active(),
			'wooUpdateManagerInstallUrl' => WC_Woo_Update_Manager_Plugin::generate_install_url(),
			'wooUpdateManagerPluginSlug' => WC_Woo_Update_Manager_Plugin::WOO_UPDATE_MANAGER_SLUG,
			'wooUpdateCount'             => WC_Helper_Updater::get_updates_count_based_on_site_status(),
			'woocomConnectNoticeType'    => $woo_connect_notice_type,
			'dismissNoticeNonce'         => wp_create_nonce( 'dismiss_notice' ),
		);

		if ( WC_Helper::is_site_connected() ) {
			$settings['wccomHelper']['subscription_expired_notice']  = PluginsHelper::get_expired_subscription_notice( false );
			$settings['wccomHelper']['subscription_expiring_notice'] = PluginsHelper::get_expiring_subscription_notice( false );
			$settings['wccomHelper']['subscription_missing_notice']  = PluginsHelper::get_missing_subscription_notice();
		}

		return $settings;
	}

	/**
	 * Generates the URL for connecting or disconnecting the store to/from WooCommerce.com.
	 * Approach taken from existing helper code that isn't exposed.
	 *
	 * @return string
	 */
	public static function get_connection_url() {
		global $current_screen;

		$connect_url_args = array(
			'page'    => 'wc-addons',
			'section' => 'helper',
		);

		// No active connection.
		if ( WC_Helper::is_site_connected() ) {
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
	 * Registers the REST routes for the featured products endpoint.
	 * This endpoint is used by the WooCommerce > Extensions > Discover
	 * page.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'wc/v3',
			'/marketplace/featured',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_featured' ),
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
}

WC_Helper_Admin::load();
