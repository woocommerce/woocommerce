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
				'args'                => array(
					'product_key' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
		register_rest_route(
			'wc/v3',
			'/marketplace/subscriptions/deactivate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'deactivate' ),
				'permission_callback' => array( __CLASS__, 'get_permission' ),
				'args'                => array(
					'product_key' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
		register_rest_route(
			'wc/v3',
			'/marketplace/subscriptions/install',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'install' ),
				'permission_callback' => array( __CLASS__, 'get_permission' ),
				'args'                => array(
					'product_key' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
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
		$success = WC_Helper::activate_helper_subscription( $product_key );
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
				),
				400
			);
		}
	}

	/**
	 * Deactivate a WooCommerce.com subscription.
	 */
	public static function deactivate( $request ) {
		$product_key = $request->get_param('product_key');
		$success = WC_Helper::deactivate_helper_subscription( $product_key );
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
				),
				400
			);
		}
	}

	/**
	 * Install a WooCommerce.com produc
	 */
	public static function install( $request ) {
		$product_key = $request->get_param('product_key');
		$subscriptions = WC_Helper::get_subscription( $product_key );

		if ( empty( $subscriptions ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'We couldn\'t find this subscription.', 'woocommerce' )
				),
				404
			);
		}

		if ( $subscriptions['expired'] === true ) {
			wp_send_json_error(
				array(
					'message' => __( 'This subscription has expired.', 'woocommerce' )
				),
				402
			);
		}

		if ( $subscriptions['maxed'] === true ) {
			wp_send_json_error(
				array(
					'message' => __( 'All licenses for this subscription are already in use.', 'woocommerce' )
				),
				402
			);
		}

		$activation_success = WC_Helper::activate_helper_subscription( $product_key );
		if ( ! $activation_success ) {
			wp_send_json_error(
				array(
					'message' => __( 'We couldn\'t activate your subscription.', 'woocommerce' )
				),
				400
			);
		}

		$product_id = $subscriptions['product_id'];

		// Delete any existing state for this product.
		$state = WC_WCCOM_Site_Installation_State_Storage::get_state( $product_id );
		if ( $state !== null ) {
			WC_WCCOM_Site_Installation_State_Storage::delete_state( $state );
		}

		// Run the installation.
		$installation_manager = new WC_WCCOM_Site_Installation_Manager( $product_id, $product_id );
		$installation_success = $installation_manager->run_installation( 'activate_product' );

		if ( ! $installation_success ) {
			WC_Helper::deactivate_helper_subscription( $product_key );
			wp_send_json_error(
				array(
					'message' => __( 'We couldn\'t install your subscription.', 'woocommerce' )
				),
				400
			);
		}

		wp_send_json(
			array(
				'message' => __( 'Your subscription has been installed.', 'woocommerce' )
			)
		);
	}
}

WC_Helper_Subscriptions_API::load();
