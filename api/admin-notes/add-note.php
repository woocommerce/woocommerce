<?php
use Automattic\WooCommerce\Admin\Notes\Note;

register_woocommerce_admin_test_helper_rest_route(
	'/admin-notes/add-note/v1',
	'admin_notes_add_note'
);


function admin_notes_add_note( $request ) {
	$note = new Note();

	$note->set_name( $request->get_param( 'name' ) );
	$note->set_title( $request->get_param( 'title' ) );
	
	$note->save();

	return true;
}
