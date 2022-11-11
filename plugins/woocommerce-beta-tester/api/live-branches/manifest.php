<?php

register_woocommerce_admin_test_helper_rest_route(
	'/live-branches/manifest/v1',
	'fetch_live_branches_manifest'	
);

function fetch_live_branches_manifest() {
	$response = wp_remote_get( 'https://betadownload.jetpack.me/woocommerce-branches.json' );
	$body     = wp_remote_retrieve_body( $response );

	return new WP_REST_Response( json_decode( $body ), 200 );
}
