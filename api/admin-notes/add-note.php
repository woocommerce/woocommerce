<?php
use Automattic\WooCommerce\Admin\Notes\Note;

register_woocommerce_admin_test_helper_rest_route(
	'/admin-notes/add-note/v1',
	'admin_notes_add_note'
);

register_woocommerce_admin_test_helper_rest_route(
	'/admin-notes/add-email-note/v1',
	'admin_notes_add_email_note'
);


function admin_notes_add_note( $request ) {
	$note = new Note();

	$note->set_name( $request->get_param( 'name' ) );
	$note->set_title( $request->get_param( 'title' ) );
	
	$note->save();

	return true;
}

function admin_notes_add_email_note( $request ) {
	$note = new Note();

	$additional_data = array(
		'role' => 'administrator',
	);
	$action_name = sprintf(
		'test-action-%s',
		$request->get_param( 'name' )
	);

	$content = $request->get_param( 'content' ) ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.';
	
	$note->set_name( $request->get_param( 'name' ) );
	$note->set_title( $request->get_param( 'title' ) );
	$note->set_type( 'email' );
	$note->set_content( $content );
	$note->set_content_data( (object) $additional_data );
	$note->add_action( $action_name, 'Test action', wc_admin_url() );
	
	$note->save();

	return true;
}
