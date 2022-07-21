<?php
/**
 * Recent Tracks events.
 *
 * @package WC_Beta_Tester
 */

register_woocommerce_admin_test_helper_rest_route(
	'/recent-tracks-events',
	'wca_test_helper_get_recent_tracks_events',
	array(
		'methods' => 'GET',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/recent-tracks-events',
	'wca_test_helper_add_recent_tracks_event',
	array(
		'methods' => 'PUT',
	)
);

add_filter( 'woocommerce_tracks_event_properties', 'wca_test_helper_tracks_event_properties_filter', 10, 2 );

/**
 * Handle request to get recent Tracks events.
 *
 * @param WP_REST_Request $request Request instance.
 */
function wca_test_helper_get_recent_tracks_events( $request ) {
	$events = get_recent_tracks_events();

	return new WP_REST_Response( $events, 200 );
}

/**
 * Handle request to add a recent Tracks event.
 *
 * @param WP_REST_Request $request Request instance.
 */
function wca_test_helper_add_recent_tracks_event( $request ) {
	$event = json_decode( $request->get_body() );

	log_recent_tracks_event( $event );

	return new WP_REST_Response( $event, 200 );
}

/**
 * Hook that logs the Tracks event.
 *
 * @param array  $properties Event properties.
 * @param string $event_name Event name.
 */
function wca_test_helper_tracks_event_properties_filter( $properties, $event_name ) {
	if ( false === $event_name ) {
		return $properties;
	}

	$event = build_tracks_event( $properties, $event_name );

	log_recent_tracks_event( $event );

	return $properties;
}

/**
 * Log Tracks event.
 *
 * @param object $event Tracks event.
 */
function log_recent_tracks_event( $event ) {
	$event_as_json = wp_json_encode( $event );

	$log_file_name = get_recent_tracks_events_log_file_name();
	file_put_contents( $log_file_name, $event_as_json . "\n", FILE_APPEND );
}

/**
 * Build a Tracks event from the supplied properties and event name.
 *
 * @param array  $properties Event properties.
 * @param string $event_name Event name.
 */
function build_tracks_event( $properties, $event_name ) {
	$event = array(
		'eventname'  => $event_name,
		'eventprops' => $properties,
	);
	return $event;
}

/**
 * Get recent Tracks events.
 */
function get_recent_tracks_events() {
	// TODO: this implementation feels very inefficient;
	// it would be nice to be able to just send the string back and set content type to JSON.
	$log_file_name = get_recent_tracks_events_log_file_name();

	$log_lines = file( $log_file_name, FILE_IGNORE_NEW_LINES );

	$events = array();
	foreach ( $log_lines as $line ) {
		$events[] = json_decode( $line );
	}

	return $events;
}

/**
 * Get the file name for the recent Tracks events log.
 */
function get_recent_tracks_events_log_file_name() {
	return trailingslashit( WC_LOG_DIR ) . 'wca_test_helper_recent_tracks_events.log';
}
