<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Logging\RemoteLogger;

register_woocommerce_admin_test_helper_rest_route(
	'/remote-logging/status',
	'get_remote_logging_status',
	array(
		'methods' => 'GET',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/remote-logging/toggle',
	'toggle_remote_logging',
	array(
		'methods' => 'POST',
		'args'    => array(
			'enable' => array(
				'required'          => true,
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
			),
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/remote-logging/log-event',
	'log_remote_event',
	array(
		'methods' => 'POST',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/remote-logging/reset-rate-limit',
	'reset_php_rate_limit',
	array(
		'methods' => 'POST',
	)
);


/**
 * Get the remote logging status.
 *
 * @return WP_REST_Response The response object.
 */
function get_remote_logging_status() {
	$remote_logger = wc_get_container()->get( RemoteLogger::class );

	return new WP_REST_Response(
		array(
			'isEnabled'     => $remote_logger->is_remote_logging_allowed(),
			'wpEnvironment' => wp_get_environment_type(),
		),
		200
	);
}

/**
 * Toggle remote logging on or off.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response The response object.
 */
function toggle_remote_logging( $request ) {
	$enable = $request->get_param( 'enable' );

	if ( $enable ) {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );
		update_option( 'woocommerce_allow_tracking', 'yes' );
		update_option( 'woocommerce_remote_variant_assignment', 1 );
		set_site_transient( RemoteLogger::WC_NEW_VERSION_TRANSIENT, WC()->version );
	} else {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'no' );
	}

	$remote_logger = wc_get_container()->get( RemoteLogger::class );
	return new WP_REST_Response(
		array(
			'isEnabled' => $remote_logger->is_remote_logging_allowed(),
		),
		200
	);
}


/**
 * Log a remote event for testing purposes.
 *
 * @return WP_REST_Response The response object.
 */
function log_remote_event() {
	$remote_logger = wc_get_container()->get( RemoteLogger::class );
	$result        = $remote_logger->handle(
		time(),
		'critical',
		'Test PHP event from WC Beta Tester',
		array(
			'source'         => 'wc-beta-tester',
			'remote-logging' => true,
		)
	);

	if ( $result ) {
		return new WP_REST_Response( array( 'message' => 'Remote event logged successfully.' ), 200 );
	} else {
		return new WP_REST_Response( array( 'message' => 'Failed to log remote event.' ), 500 );
	}
}

/**
 * Reset the PHP rate limit.
 *
 * @return WP_REST_Response The response object.
 */
function reset_php_rate_limit() {
	global $wpdb;
	$wpdb->query(
		"DELETE FROM {$wpdb->prefix}wc_rate_limits"
	);
	wp_cache_flush();

	return new WP_REST_Response( array( 'success' => true ), 200 );
}
