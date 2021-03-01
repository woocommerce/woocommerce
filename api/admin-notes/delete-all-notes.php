<?php
add_action( 'rest_api_init', function() {
	register_rest_route(
		'wc-admin-test-helper/v1',
		'/admin-notes/delete-all-notes',
		array(
			'methods'  => 'POST',
			'callback' => 'admin_notes_delete_all_notes',
			'permission_callback' => function( $request ) {
				if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
					return new \WP_Error(
						'woocommerce_rest_cannot_edit',
						__( 'Sorry, you cannot perform this action', 'woocommerce-admin-test-helper' )
					);
				}
				return true;
			},
		)
	);
} );

function admin_notes_delete_all_notes() {
	global $wpdb;
	
	$deleted_note_count   = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_admin_notes" );
	$deleted_action_count = $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_admin_note_actions" );

	return array(
		'deleted_note_count'   => $deleted_note_count,
		'deleted_action_count' => $deleted_action_count,
	);
}

