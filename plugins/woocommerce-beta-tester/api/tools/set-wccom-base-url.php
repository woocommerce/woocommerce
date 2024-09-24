<?php
/**
 * WooCommerce Beta Tester WooCommerce.com Base URL
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register REST API routes for setting and getting WooCommerce.com base URL.
 */
register_woocommerce_admin_test_helper_rest_route(
	'/tools/set-wccom-base-url/v1',
	'tools_set_wccom_base_url',
	array(
		'methods' => 'POST',
		'args'    => array(
			'url' => array(
				'description'       => 'WooCommerce.com base URL',
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'esc_url_raw',
			),
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/tools/get-wccom-base-url/v1',
	'tools_get_wccom_base_url',
	array(
		'methods' => 'GET',
	)
);

/**
 * Set WooCommerce.com base URL.
 *
 * @param WP_REST_Request $request Full details about the request.
 * @return WP_REST_Response
 */
function tools_set_wccom_base_url( $request ) {
	$url = $request->get_param( 'url' );

	if ( empty( $url ) ) {
		delete_option( 'wc_beta_tester_wccom_base_url' );
		return new WP_REST_Response( array( 'message' => 'WooCommerce.com base URL is reset' ), 200 );
	}

	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return new WP_REST_Response( array( 'message' => 'Invalid URL' ), 400 );
	}

	update_option( 'wc_beta_tester_wccom_base_url', $url );
	return new WP_REST_Response( $url, 200 );
}

/**
 * Get WooCommerce.com base URL.
 *
 * @return WP_REST_Response
 */
function tools_get_wccom_base_url() {
	if ( class_exists( 'WC_Helper' ) && method_exists( 'WC_Helper', 'get_woocommerce_com_base_url' ) ) {
		$url = WC_Helper::get_woocommerce_com_base_url();
	} else {
		$url = get_option( 'wc_beta_tester_wccom_base_url', '' );
	}
	return new WP_REST_Response( $url, 200 );
}

/**
 * Filter WooCommerce.com base URL.
 *
 * @param string $default_url Default WooCommerce.com base URL.
 * @return string
 */
function filter_wccom_base_url( $default_url ) {
	$custom_url = get_option( 'wc_beta_tester_wccom_base_url', '' );
	return ! empty( $custom_url ) ? $custom_url : $default_url;
}

add_filter( 'woo_com_base_url', 'filter_wccom_base_url' );

/**
 * Filter HTTP request arguments to disable SSL verification for custom WooCommerce.com base URL.
 *
 * @param array  $args HTTP request arguments.
 * @param string $url HTTP request URL.
 * @return array Modified HTTP request arguments.
 */
function filter_http_request_args_for_custom_wccom_url( $args, $url ) {
	$custom_url = get_option( 'wc_beta_tester_wccom_base_url', false );
	if ( $custom_url && strpos( $url, $custom_url ) !== false ) {
		// Disable SSL verification for requests to the custom URL since local dev might not have a valid SSL certificate.
		$args['sslverify'] = false;
	}
	return $args;
}

add_filter( 'http_request_args', 'filter_http_request_args_for_custom_wccom_url', 10, 2 );
