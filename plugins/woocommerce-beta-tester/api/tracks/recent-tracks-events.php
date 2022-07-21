<?php

register_woocommerce_admin_test_helper_rest_route(
	'/recent-tracks-events',
	'wca_test_helper_get_recent_tracks_events',
	[
		'methods' => 'GET'
	]
);

register_woocommerce_admin_test_helper_rest_route(
	'/recent-tracks-events',
	'wca_test_helper_add_recent_tracks_event',
	[
		'methods' => 'PUT'
	]
);

add_filter( 'woocommerce_tracks_event_properties', 'wca_test_helper_tracks_event_properties_filter', 10, 2 );

function wca_test_helper_get_recent_tracks_events( $request ) {
	$events = get_recent_tracks_events();

	return new WP_REST_Response( $events, 200 );
}

function wca_test_helper_add_recent_tracks_event( $request ) {
	$event = json_decode( $request->get_body() );

	log_recent_tracks_event( $event );

	return new WP_REST_Response( $event, 200 );
}

function wca_test_helper_tracks_event_properties_filter( $properties, $event_name ) {
	if ( $event_name === false ) {
		return $properties;
	}

	$event = build_tracks_event( $properties, $event_name );

	log_recent_tracks_event( $event );

	return $properties;
}

function log_recent_tracks_event( $event ) {
	$event_as_json = json_encode( $event );

	$log_file_name = get_recent_tracks_events_log_file_name();
	file_put_contents( $log_file_name, $event_as_json . "\n", FILE_APPEND );
}

function build_tracks_event( $properties, $event_name ) {
	$event = [
		'eventname' => $event_name,
		'eventprops' => $properties
	];
	return $event;
}

function get_recent_tracks_events() {
	// TODO: this implementation feels very inefficient; it would be nice to be able to just send the string back
	// and set content type to JSON
	$log_file_name = get_recent_tracks_events_log_file_name();

	$log_lines = file( $log_file_name, FILE_IGNORE_NEW_LINES );

	$events = [];
	foreach ( $log_lines as $line ) {
		$events[] = json_decode( $line );
	}

	return $events;
}

function get_recent_tracks_events_log_file_name() {
	return trailingslashit( WC_LOG_DIR ) . 'wca_test_helper_recent_tracks_events.log';
}
