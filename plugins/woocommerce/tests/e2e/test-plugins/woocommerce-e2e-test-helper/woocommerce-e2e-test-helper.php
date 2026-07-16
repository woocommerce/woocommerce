<?php
/**
 * Plugin Name: WooCommerce E2E Test Helper
 * Description: Always-on utilities for the WooCommerce E2E suite: cookie-driven filter overrides, synchronous Action Scheduler processing, and a REST API for feature flags, options, environment info and theme switching.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Author: WooCommerce
 *
 * This bundles three previously separate helpers (filter-setter, process-waiting-actions and
 * test-helper-apis). They share the same lifecycle — mounted and auto-activated for every E2E run
 * via the .wp-env.e2e.json "plugins" array — so they live together here. Each concern is kept in its
 * own section below and none of them touch the others.
 *
 * It hopefully goes without saying, none of this should ever run in a production environment.
 *
 * @package Automattic\WooCommerce\E2EPlaywright
 */

declare(strict_types=1);

/*
 * -----------------------------------------------------------------------------
 * Filter setter
 * -----------------------------------------------------------------------------
 *
 * Registers WordPress filters from an 'e2e-filters' cookie, so a spec can override filtered values on
 * the fly. The cookie is a JSON map of hook => spec. For example (pretty printed here for clarity):
 *
 *     { "woocommerce_system_timeout": 10 }
 *
 * adds a filter returning 10 for 'woocommerce_system_timeout'. A spec may instead be an object naming
 * a callback and/or a priority:
 *
 *     { "woocommerce_enable_deathray": { "callback": "__return_false", "priority": 20 } }
 *
 * or a literal value with a priority:
 *
 *     { "woocommerce_default_username": { "value": "Geoffrey", "priority": 20 } }
 *
 * Runs at plugin load so the filters are in place before anything reads them.
 */

/**
 * Read the `e2e-filters` cookie and register the filters it describes.
 */
function woocommerce_e2e_apply_cookie_filters(): void {
	if ( ! isset( $_COOKIE['e2e-filters'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$filters = json_decode( $_COOKIE['e2e-filters'], true );

	if ( ! is_array( $filters ) ) {
		return;
	}

	foreach ( $filters as $hook => $spec ) {
		// A priority may be specified as part of the spec, else use the default priority (10).
		$priority = isset( $spec['priority'] ) && is_int( $spec['priority'] )
			? $spec['priority']
			: 10;

		// If the spec is not an array, then it is probably intended as the literal value.
		if ( ! is_array( $spec ) ) {
			$value = $spec;
		} elseif ( isset( $spec['value'] ) ) {
			$value = $spec['value'];
		}

		// If we know the value, we can establish our filter callback.
		if ( isset( $value ) ) {
			$callback = function () use ( $value ) {
				return $value;
			};
		}

		// We also support specifying a callback function.
		if ( is_array( $spec ) && isset( $spec['callback'] ) && is_string( $spec['callback'] ) ) {
			$callback = $spec['callback'];
		}

		// Ensure we have a callback, then setup the filter.
		if ( isset( $callback ) ) {
			add_filter( $hook, $callback, $priority );
		}
	}
}

woocommerce_e2e_apply_cookie_filters();

/*
 * -----------------------------------------------------------------------------
 * Process waiting actions
 * -----------------------------------------------------------------------------
 *
 * Listens for requests carrying the 'process-waiting-actions' query parameter and starts an Action
 * Scheduler queue runner, exiting immediately afterwards to avoid the overhead of a full response.
 * Used by the analytics suite so scheduled order data lands in reports synchronously.
 */
add_action(
	'init',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['process-waiting-actions'] ) ) {
			return;
		}

		if ( ! class_exists( ActionScheduler_QueueRunner::class ) ) {
			return;
		}

		exit( ActionScheduler_QueueRunner::instance()->run( 'E2E Tests' ) ? 1 : 0 );
	}
);

/*
 * -----------------------------------------------------------------------------
 * Test helper REST API
 * -----------------------------------------------------------------------------
 *
 * REST routes for toggling feature flags, setting/deleting options, reading environment info and
 * switching themes during a test.
 */

/**
 * Register the E2E test helper REST routes (feature flags and options).
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

/**
 * Disable WordPress comment flood protection during E2E runs.
 *
 * Parallel specs post comments and reviews as the shared customer account.
 * WordPress' 15-second flood throttle ("You are posting comments too quickly")
 * then rejects whichever request lands second, causing cross-spec flakes that
 * have nothing to do with the behaviour under test. Override core's
 * `wp_throttle_comment_flood` (priority 10) with a later filter that always
 * allows the comment.
 */
add_filter( 'comment_flood_filter', '__return_false', 99 );

/**
 * Update a WordPress option.
 *
 * @param WP_REST_Request $request The REST request, carrying `option_name` and `option_value`.
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
 * @param WP_REST_Request $request The REST request, carrying `option_name`.
 * @return WP_REST_Response
 */
function api_delete_option( WP_REST_Request $request ) {
	$option_name = sanitize_text_field( $request['option_name'] );

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
