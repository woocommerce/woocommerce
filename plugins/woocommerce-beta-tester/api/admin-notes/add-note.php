<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Notes\Note;

register_woocommerce_admin_test_helper_rest_route(
	'/admin-notes/add-note/v1',
	'admin_notes_add_note'
);

/**
 * Adds an admin note.
 *
 * @param WP_REST_Request $request Full data about the request.
 */
function admin_notes_add_note( $request ) {
	$note           = new Note();
	$mock_note_data = get_mock_note_data();
	$type           = $request->get_param( 'type' );
	$layout         = $request->get_param( 'layout' );

	$note->set_name( $request->get_param( 'name' ) );
	$note->set_title( $request->get_param( 'title' ) );
	$note->set_content( $mock_note_data['content'] );
	$note->set_image( $mock_note_data[ $type ][ $layout ] );
	$note->set_layout( $layout );
	$note->set_type( $type );
	possibly_add_action( $note );

	if ( 'email' === $type ) {
		add_email_note_params( $note );
	}

	$note->save();

	return true;
}

/**
 * Adds an email note parameter.
 *
 * @param Note $note The note to add parameters to.
 */
function add_email_note_params( $note ) {
	$additional_data = array(
		'role' => 'administrator',
	);
	$note->set_content_data( (object) $additional_data );
}

/**
 * Possibly adds an action to a note.
 *
 * @param Note $note The note to check and add an action to.
 */
function possibly_add_action( $note ) {
	if ( $note->get_type() === 'info' ) {
		return;
	}
	$action_name = sprintf(
		'test-action-%s',
		$note->get_name()
	);
	$note->add_action( $action_name, 'Test action', wc_admin_url() );
}

/**
 * Gets mock note data.
 */
function get_mock_note_data() {
	$plugin_url = site_url() . '/wp-content/plugins/woocommerce-admin-test-helper/';
	return array(
		'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud.',
		'info'    => array(
			'thumbnail' => $plugin_url . 'images/admin-notes/thumbnail.jpg',
			'plain'     => '',
		),
		'email'   => array(
			'plain' => $plugin_url . 'images/admin-notes/woocommerce-logo-vector.png',
		),
		'update'  => array(
			'plain' => '',
		),
	);
}
