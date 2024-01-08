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
 * The main entry-point for all things related to the Marketplace Subscriptions API.
 * The Subscriptions API manages WooCommerce.com Subscriptions.
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
	 */
	public static function get_permission() {
		return current_user_can( 'manage_woocommerce' );
	}

	public static function create_order( $request ) {
		$response = WC_Helper_API::post(
			'create-order',
			array(
				'authenticated' => true,
				'body' 		=> array(
					'product_id' => $request['product_id'],
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'wc_helper_api_error', $response->get_error_message(), array( 'status' => $response->get_error_code() ) );
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'wc_helper_api_error', wp_remote_retrieve_response_message( $response ), array( 'status' => wp_remote_retrieve_response_code( $response ) ) );
		}

		$subscription = wp_remote_retrieve_body( $response );

		$response = WC_Helper::get_subscription_local_data( $subscription );

		var_dump( $response );
		die();

		return $response;

		// See what the response is.
			// If it's an error, return the error.
			// If it's a redirect, relay it.
			// If it's a success, return the data.
	}
}

WC_Helper_Orders_API::load();
