<?php
/**
 * Recent Tracks events.
 *
 * @package WC_Beta_Tester
 */

register_woocommerce_admin_test_helper_rest_route(
	'/recent-tracks-events',
	'wc_beta_tester_handle_get_recent_tracks_events',
	array(
		'methods' => 'GET',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/recent-tracks-events',
	'wc_beta_tester_handle_put_recent_tracks_event',
	array(
		'methods' => 'PUT',
	)
);

add_filter( 'woocommerce_tracks_event_properties', 'wc_beta_tester_log_tracks_event_properties', 10, 2 );

/**
 * Handle request to get recent Tracks events.
 *
 * @param WP_REST_Request $request Request instance.
 */
function wc_beta_tester_handle_get_recent_tracks_events( $request ) {
	if ( ! wc_beta_tester_is_recent_tracks_events_enabled() ) {
		return new WP_REST_Response( 'Recent Tracks events are not enabled', 503 );
	}

	$events = wc_beta_tester_get_recent_tracks_events();

	return new WP_REST_Response( $events, 200 );
}

/**
 * Handle request to add a recent Tracks event.
 *
 * @param WP_REST_Request $request Request instance.
 */
function wc_beta_tester_handle_put_recent_tracks_event( $request ) {
	if ( ! wc_beta_tester_is_recent_tracks_events_enabled() ) {
		return new WP_REST_Response( 'Recent Tracks events are not enabled', 503 );
	}

	$event = json_decode( $request->get_body() );

	wc_beta_tester_log_tracks_event( $event );

	return new WP_REST_Response( $event, 200 );
}

/**
 * Hook that logs the Tracks event.
 *
 * @param array  $properties Event properties.
 * @param string $event_name Event name.
 */
function wc_beta_tester_log_tracks_event_properties( $properties, $event_name ) {
	if ( false === $event_name ) {
		return $properties;
	}

	$event = wc_beta_tester_build_tracks_event( $properties, $event_name );

	wc_beta_tester_log_tracks_event( $event );

	return $properties;
}

/**
 * Log Tracks event.
 *
 * @param object $event Tracks event.
 */
function wc_beta_tester_log_tracks_event( $event ) {
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
function wc_beta_tester_build_tracks_event( $properties, $event_name ) {
	$event = array(
		'eventname'  => $event_name,
		'eventprops' => $properties,
	);
	return $event;
}

/**
 * Get recent Tracks events.
 */
function wc_beta_tester_get_recent_tracks_events() {
	// this implementation feels very inefficient;
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

/**
 * Returns true if the recent Tracks events feature is enabled.
 */
function wc_beta_tester_is_recent_tracks_events_enabled() {
	return get_option( 'wc_beta_tester_recent_tracks_enabled', '0' ) === '1';
}
