<?php // @codingStandardsIgnoreLine
/**
 * Register REST endpoint for fetching live branches manifest.
 *
 * @package WC_Beta_Tester
 */

require_once __DIR__ . '/../../includes/class-wc-beta-tester-live-branches-installer.php';

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
	$response  = wp_remote_get( 'https://betadownload.jetpack.me/woocommerce-branches.json' );
	$body      = wp_remote_retrieve_body( $response );
	$installer = new WC_Beta_Tester_Live_Branches_Installer();

	$obj = json_decode( $body );

	foreach ( $obj->pr as $key => $value ) {
		$value->install_status = $installer->check_install_status( $value->version );
	}

	return new WP_REST_Response( $obj, 200 );
}
