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
 * WC_Helper_Subscriptions_API
 *
 * The main entry-point for all things related to the Marketplace Subscriptions API.
 * The Subscriptions API manages WooCommerce.com Subscriptions.
 */
class WC_Helper_Subscriptions_API {

	/**
	 * Loads the class, runs on init
	 *
	 * @return void
	 */
	public static function load() {
		add_filter( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Registers the REST routes for the Marketplace Subscriptions API.
	 * These endpoints are used by the Marketplace Subscriptions React UI.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'wc/v3',
			'/marketplace/refresh',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'refresh' ),
				'permission_callback' => array( __CLASS__, 'get_permission' ),
			)
		);
		register_rest_route(
			'wc/v3',
			'/marketplace/subscriptions',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_subscriptions' ),
				'permission_callback' => array( __CLASS__, 'get_permission' ),
			)
		);
		register_rest_route(
			'wc/v3',
			'/marketplace/subscriptions/activate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'activate' ),
				'permission_callback' => array( __CLASS__, 'get_permission' ),
			)
		);
		register_rest_route(
			'wc/v3',
			'/marketplace/subscriptions/deactivate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'deactivate' ),
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
	 * Fetch subscriptions from WooCommerce.com and serve them
	 * as JSON.
	 */
	public static function get_subscriptions() {
		$subscriptions = WC_Helper::get_subscription_list_data();
		wp_send_json(
			array_values(
				$subscriptions
			)
		);
	}

	/**
	 * Refresh account and subscriptions from WooCommerce.com and serve subscriptions
	 * as JSON.
	 */
	public static function refresh() {
		WC_Helper::refresh_helper_subscriptions();
		self::get_subscriptions();
	}

	/**
	 * Activate a WooCommerce.com subscription.
	 */
	public static function activate( $request ) {
		$product_key = $request->get_param('product_key');
		try {
			$success = WC_Helper::activate_helper_subscription( $product_key );
		} catch (Exception $e) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage()
				),
				400
			);
		}
		if ( $success ) {
			wp_send_json_success(
				array(
					'message' => __( 'Your subscription has been activated.', 'woocommerce' )
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'There was an error activating your subscription. Please try again.', 'woocommerce' )
				)
			);
		}
	}

	/**
	 * Deactivate a WooCommerce.com subscription.
	 */
	public static function deactivate( $request ) {
		$product_key = $request->get_param('product_key');
		try {
			$success = WC_Helper::deactivate_helper_subscription( $product_key );
		} catch (Exception $e) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage()
				),
				400
			);
		}
		if ( $success ) {
			wp_send_json_success(
				array(
					'message' => __( 'Your subscription has been deactivated.', 'woocommerce' )
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'There was an error deactivating your subscription. Please try again.', 'woocommerce' )
				)
			);
		}
	}
}

WC_Helper_Subscriptions_API::load();
