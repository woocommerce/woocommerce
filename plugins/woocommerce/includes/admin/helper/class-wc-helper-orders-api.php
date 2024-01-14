<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Helper_Orders_API {
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
		try {
			$response = WC_Helper_API::post(
				'create-order',
				array(
					'authenticated' => true,
					'body' 		=> array(
						'product_id' => $request['product_id'],
					)
				)
			);

			return new \WP_REST_Response(
				json_decode( wp_remote_retrieve_body( $response ), true ),
				wp_remote_retrieve_response_code( $response )
			);
		} catch ( Exception $e ) {
			return new \WP_REST_Response(
				array(
					'message' => 'Could not start the installation process. Reason: ' . $e->getMessage(),
					'code'    => 'could-not-install',
				),
				500
			);
		}
	}
}

WC_Helper_Orders_API::load();
