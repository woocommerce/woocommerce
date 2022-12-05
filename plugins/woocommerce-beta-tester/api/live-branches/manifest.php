<?php
/**
 * Register REST endpoint for fetching live branches manifest.
 *
 * @package WC_Beta_Tester
 */

register_woocommerce_admin_test_helper_rest_route(
	'/live-branches/manifest/v1',
	'fetch_live_branches_manifest',
	array(
		'methods' => 'GET',
	)
);

/**
 * API endpoint to fetch the manifest of live branches.
 */
function fetch_live_branches_manifest() {
	$response = wp_remote_get( 'https://betadownload.jetpack.me/woocommerce-branches.json' );
	$body     = wp_remote_retrieve_body( $response );

	return new WP_REST_Response( json_decode( $body ), 200 );
}
