<?php
/**
 * WooCommerce Admin Helper - React admin interface
 *
 * @package WooCommerce\Admin\Helper
 */

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
		add_filter( 'woocommerce_admin_shared_settings', array( __CLASS__, 'add_marketplace_settings' ) );
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

		$settings['wccomHelper'] = array(
			'isConnected' => WC_Helper::is_site_connected(),
			'connectURL'  => self::get_connection_url(),
			'userEmail'   => $auth_user_email,
			'userAvatar'  => get_avatar_url( $auth_user_email, array( 'size' => '48' ) ),
			'storeCountry' => wc_get_base_location()['country'],
			'inAppPurchaseURLParams' => WC_Admin_Addons::get_in_app_purchase_url_params(),
		);

		return $settings;
	}

	/**
	 * Generates the URL for connecting or disconnecting the store to/from WooCommerce.com.
	 * Approach taken from existing helper code that isn't exposed.
	 *
	 * @return string
	 */
	public static function get_connection_url() {
		// No active connection.
		if ( ! WC_Helper::is_site_connected() ) {
			$connect_url = add_query_arg(
				array(
					'page'              => 'wc-addons',
					'section'           => 'helper',
					'wc-helper-connect' => 1,
					'wc-helper-nonce'   => wp_create_nonce( 'connect' ),
				),
				admin_url( 'admin.php' )
			);

			return $connect_url;
		}

		$connect_url = add_query_arg(
			array(
				'page'                 => 'wc-addons',
				'section'              => 'helper',
				'wc-helper-disconnect' => 1,
				'wc-helper-nonce'      => wp_create_nonce( 'disconnect' ),
			),
			admin_url( 'admin.php' )
		);

		return $connect_url;
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
	 * Fetch featured procucts from WooCommerce.com and serve them
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
