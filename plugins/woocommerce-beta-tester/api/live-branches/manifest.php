<?php
/**
 * Register REST endpoint for fetching live branches manifest.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

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
	$response = wp_remote_get( 'https://betadownload.jetpack.me/woocommerce-branches.json' );

	if ( is_wp_error( $response ) ) {
		// Handle the error case.
		$error_message = $response->get_error_message();
		return new WP_REST_Response( array( 'error' => $error_message ), 500 );
	}

	$body      = wp_remote_retrieve_body( $response );
	$installer = new WC_Beta_Tester_Live_Branches_Installer();

	$obj = json_decode( $body );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		// Handle JSON decoding error.
		return new WP_REST_Response( array( 'error' => 'Error decoding JSON' ), 500 );
	}

	// Check if the expected properties exist in the JSON.
	if ( ! isset( $obj->pr ) || ! isset( $obj->master ) ) {
		return new WP_REST_Response( array( 'error' => 'Missing properties in JSON' ), 500 );
	}

	foreach ( $obj->pr as $key => $value ) {
		$value->install_status = $installer->check_install_status( $value->version );
	}

	$obj->master->install_status = $installer->check_install_status( $obj->master->version );

	return new WP_REST_Response( $obj, 200 );
}
