<?php

defined( 'ABSPATH' ) || exit;

register_woocommerce_admin_test_helper_rest_route(
	'/tools/set-wccom-request-errors/v1',
	'tools_set_wccom_request_errors',
	array(
		'methods' => 'POST',
		'args'    => array(
			'mode' => array(
				'description' => 'wccom request error mode',
				'type'        => 'enum',
				'enum'        => array( 'timeout', 'error', 'disabled' ),
			),
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/tools/get-wccom-request-errors/v1',
	'tools_get_wccom_request_errors',
	array(
		'methods' => 'GET',
	)
);

/**
 * A tool to set wccom request errors mode.
 *
 * @param WP_REST_Request $request Request object.
 */
function tools_set_wccom_request_errors( $request ) {
	$mode = $request->get_param( 'mode' );

	update_option( 'wc_admin_test_helper_modify_wccom_request_responses', $mode );

	return new WP_REST_Response( $mode, 200 );
}

/**
 * A tool to get wccom request mode.
 */
function tools_get_wccom_request_errors() {
	$mode = get_option( 'wc_admin_test_helper_modify_wccom_request_responses', 'disabled' );

	return new WP_REST_Response( $mode, 200 );
}
