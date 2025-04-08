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
 * WC_Helper_Orders_API
 *
 * Pings WooCommerce.com to create an order and pull in the necessary data to start the installation process.
 */
class WC_Helper_Orders_API {
	/**
	 * Loads the class, runs on init
	 *
	 * @return void
	 */
	public static function load() {
		add_filter( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Registers the REST routes for the Marketplace Orders API.
	 * These endpoints are used by the Marketplace Subscriptions React UI.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'wc/v3',
			'/marketplace/create-order',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_order' ),
				'permission_callback' => array( __CLASS__, 'get_permission' ),
				'args'                => array(
					'product_id' => array(
						'required'          => true,
						'validate_callback' => function( $argument ) {
							return is_int( $argument );
						},
					),
				),
			)
		);
	}

	/**
	 * The Extensions page can only be accessed by users with the manage_woocommerce
	 * capability. So the API mimics that behavior.
	 *
	 * @return bool
	 */
	public static function get_permission() {
		return WC_Helper_Subscriptions_API::get_permission();
	}

	/**
	 * Core function to create an order on WooCommerce.com. Pings the API and catches the exceptions if any.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public static function create_order( $request ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new \WP_REST_Response(
				array(
					'message' => __( 'You do not have permission to install plugins.', 'woocommerce' ),
				),
				403
			);
		}

		try {
			$response = WC_Helper_API::post(
				'create-order',
				array(
					'authenticated' => true,
					'body'          => http_build_query(
						array(
							'product_id' => $request['product_id'],
						),
					),
				)
			);

			return new \WP_REST_Response(
				json_decode( wp_remote_retrieve_body( $response ), true ),
				wp_remote_retrieve_response_code( $response )
			);
		} catch ( Exception $e ) {
			return new \WP_REST_Response(
				array(
					'message' => __( 'Could not start the installation process. Reason: ', 'woocommerce' ) . $e->getMessage(),
					'code'    => 'could-not-install',
				),
				500
			);
		}
	}
}

WC_Helper_Orders_API::load();
