<?php

/**
 * Plugin Name: Test Helper APIs
 * Description: Utility REST API designed for E2E testing purposes. Allows turning features on or off, and setting option values
 */
function register_helper_api() {
	register_rest_route(
		'e2e-feature-flags',
		'/update',
		array(
			'methods'             => 'POST',
			'callback'            => 'update_feature_flags',
			'permission_callback' => 'is_allowed',
		)
	);

	register_rest_route(
		'e2e-feature-flags',
		'/reset',
		array(
			'methods'             => 'GET',
			'callback'            => 'reset_feature_flags',
			'permission_callback' => 'is_allowed',
		)
	);

	register_rest_route(
		'e2e-options',
		'/update',
		array(
			'methods'             => 'POST',
			'callback'            => 'api_update_option',
			'permission_callback' => 'is_allowed',
		)
	);

	register_rest_route(
		'e2e-options',
		'/delete',
		array(
			'methods'             => 'POST',
			'callback'            => 'api_delete_option',
			'permission_callback' => 'is_allowed',
		)
	);

	register_rest_route(
		'e2e-environment',
		'/info',
		array(
			'methods'             => 'GET',
			'callback'            => 'get_environment_info',
			'permission_callback' => 'is_allowed',
		)
	);

	register_rest_route(
		'e2e-theme',
		'/activate',
		array(
			'methods'             => 'POST',
			'callback'            => 'activate_theme',
			'permission_callback' => 'is_allowed',
		)
	);

	register_rest_route(
		'e2e-bis',
		'/rate-limiter/reset',
		array(
			'methods'             => 'POST',
			'callback'            => 'bis_reset_rate_limiter',
			'permission_callback' => 'is_allowed',
		)
	);

	register_rest_route(
		'e2e-bis',
		'/notifications/count',
		array(
			'methods'             => 'GET',
			'callback'            => 'bis_get_notification_count',
			'permission_callback' => 'is_allowed',
			'args'                => array(
				'product_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => static function ( $value ) {
						return is_numeric( $value ) && (int) $value > 0;
					},
				),
			),
		)
	);
}

add_action( 'rest_api_init', 'register_helper_api' );

/**
 * Update feature flags
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function update_feature_flags( WP_REST_Request $request ) {
	$features     = get_option( 'e2e_feature_flags', array() );
	$new_features = json_decode( $request->get_body(), true );

	if ( is_array( $new_features ) ) {
		$features = array_merge( $features, $new_features );
		update_option( 'e2e_feature_flags', $features );
		return new WP_REST_Response( 'Feature flags updated', 200 );
	}

	return new WP_REST_Response( 'Invalid request body', 400 );
}

/**
 * Reset feature flags
 * @return WP_REST_Response
 */
function reset_feature_flags() {
	delete_option( 'e2e_feature_flags' );
	return new WP_REST_Response( 'Feature flags reset', 200 );
}

/**
 * Enable experimental features
 * @param array $features Array of features.
 * @return array
 */
function enable_experimental_features( $features ) {
	$stored_features = get_option( 'e2e_feature_flags', array() );

	return array_merge( $features, $stored_features );
}

add_filter( 'woocommerce_admin_get_feature_config', 'enable_experimental_features' );

// Pin BIS signup rate-limiter thresholds to known low values for the e2e
// suite. The rate-limiting spec has matching IP_LIMIT/EMAIL_LIMIT constants;
// these named pins make the contract explicit so the spec can't drift
// silently if the production defaults are tuned upstream.
/**
 * Pin the BIS per-IP signup threshold for e2e tests.
 *
 * @return int
 */
function bis_test_max_per_ip(): int {
	return 5;
}
add_filter( 'woocommerce_bis_signup_rate_limit_max_per_ip', 'bis_test_max_per_ip' );

/**
 * Pin the BIS per-email signup threshold for e2e tests.
 *
 * @return int
 */
function bis_test_max_per_email(): int {
	return 3;
}
add_filter( 'woocommerce_bis_signup_rate_limit_max_per_email', 'bis_test_max_per_email' );

/**
 * Update a WordPress option.
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function api_update_option( WP_REST_Request $request ) {
	$option_name  = sanitize_text_field( $request['option_name'] );
	$option_value = sanitize_text_field( $request['option_value'] );

	$existing_value = get_option( $option_name );

	if ( $existing_value === $option_value ) {
		return new WP_REST_Response( 'Option ' . $option_name . ' already set to: ' . $option_value, 200 );
	}

	if ( update_option( $option_name, $option_value ) ) {
		return new WP_REST_Response( 'Update option SUCCESS: ' . $option_name . ' => ' . $option_value, 200 );
	}

	return new WP_REST_Response( 'Update option FAILED: ' . $option_name . ' => ' . $option_value, 400 );
}

/**
 * Delete a WordPress option.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function api_delete_option( WP_REST_Request $request ) {
	$option_name  = sanitize_text_field( $request['option_name'] );

	$option_exists = get_option( $option_name, null );

	if ( null === $option_exists ) {
		return new WP_REST_Response( 'Option ' . $option_name . ' does not exist.', 200 );
	}

	if ( delete_option( $option_name ) ) {
		return new WP_REST_Response( 'Delete option SUCCESS: ' . $option_name, 200 );
	}

	return new WP_REST_Response( 'Delete option FAILED: ' . $option_name, 400 );
}

/**
 * Check if user is admin
 * @return bool
 */
function is_allowed() {
	return current_user_can( 'manage_options' );
}

/**
 * Get environment info
 * @return WP_REST_Response
 */
function get_environment_info() {
	$data['Core'] = get_bloginfo( 'version' );
	$data['PHP']  = sprintf( '%s.%s', PHP_MAJOR_VERSION, PHP_MINOR_VERSION );

	$all_plugins = get_plugins();

	foreach ( $all_plugins as $plugin_file => $plugin_data ) {
		if ( is_plugin_active( $plugin_file ) ) {
			$data[ $plugin_data['Name'] ] = $plugin_data['Version'];
		}
	}

	return new WP_REST_Response( $data, 200 );
}

/**
 * Activate a theme via the REST API.
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function activate_theme( WP_REST_Request $request ) {
	$theme_name = sanitize_text_field( $request['theme_name'] );

	if ( empty( $theme_name ) ) {
		return new WP_REST_Response( array( 'message' => 'Theme name is empty.' ), 400 );
	}

	if ( wp_get_theme( $theme_name )->exists() ) {
		switch_theme( $theme_name );
		return new WP_REST_Response( array( 'message' => "Theme '$theme_name' activated successfully." ), 200 );
	} else {
		return new WP_REST_Response( array( 'message' => "Theme '$theme_name' does not exist." ), 400 );
	}
}

/**
 * Reset the Back-in-Stock Notifications sign-up rate-limit counters.
 *
 * Accepts either a JSON body with an optional `ip` and/or `email` key to reset
 * a specific scope, or no body to wipe all counter transients.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function bis_reset_rate_limiter( WP_REST_Request $request ) {
	$ip_prefix    = \Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupRateLimiter::IP_PREFIX;
	$email_prefix = \Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupRateLimiter::EMAIL_PREFIX;

	$body = json_decode( $request->get_body(), true );
	if ( is_array( $body ) && ( ! empty( $body['ip'] ) || ! empty( $body['email'] ) ) ) {
		if ( ! empty( $body['ip'] ) ) {
			delete_transient( $ip_prefix . md5( trim( (string) $body['ip'] ) ) );
		}
		if ( ! empty( $body['email'] ) ) {
			delete_transient( $email_prefix . md5( strtolower( trim( (string) $body['email'] ) ) ) );
		}

		return new WP_REST_Response( array( 'message' => 'BIS rate-limiter counters reset for scope.' ), 200 );
	}

	// Collect every BIS rate-limit transient name, then hand each to delete_transient()
	// so both the options-table row and the object cache are invalidated — avoids the
	// blast radius of wp_cache_flush() on parallel e2e runs.
	global $wpdb;
	$transient_names = array();
	foreach ( array( $ip_prefix, $email_prefix ) as $prefix ) {
		$like  = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
		$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( $names as $name ) {
			$transient_names[] = preg_replace( '/^_transient_/', '', $name );
		}
	}
	foreach ( array_unique( $transient_names ) as $transient ) {
		delete_transient( $transient );
	}

	return new WP_REST_Response( array( 'message' => 'All BIS rate-limiter counters reset.' ), 200 );
}

/**
 * Return the number of Back-in-Stock notifications stored for a product.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function bis_get_notification_count( WP_REST_Request $request ) {
	// product_id is required + validated at the route level, so the callback just trusts the sanitized value.
	$product_id = (int) $request->get_param( 'product_id' );

	global $wpdb;
	$table = $wpdb->prefix . 'wc_stock_notifications';
	$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE product_id = %d", $product_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return new WP_REST_Response( array( 'count' => $count ), 200 );
}
