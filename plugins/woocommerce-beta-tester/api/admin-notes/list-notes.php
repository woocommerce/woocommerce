<?php

defined( 'ABSPATH' ) || exit;

register_woocommerce_admin_test_helper_rest_route(
	'/admin-notes/notes',
	'admin_notes_list_notes',
	array(
		'methods' => 'GET',
	)
);


/**
 * Adds an admin note.
 *
 * @param WP_REST_Request $request Full data about the request.
 */
function admin_notes_list_notes( $request ) {
	global $wpdb;
	$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_admin_notes ORDER BY note_id desc", ARRAY_A);
	return new WP_REST_Response( $results, 200 );
}
