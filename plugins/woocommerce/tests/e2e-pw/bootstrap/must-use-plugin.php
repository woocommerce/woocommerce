<?php

/*
 * Plugin name: Woo E2E Tests Mu-Plugin
 */

namespace WooE2EMuPlugin;

use WP_REST_Request;
use WP_REST_Response;
use ActionScheduler_QueueRunner;

/**
 * Filter Setter
 * Utility intended to be used during E2E testing, to make it easy to setup WordPress filters.
 *
 * Intended to function as a (mu-)plugin while tests are running, this code works by inspecting the current cookie
 * for an entry called 'e2e-filters', which is expected to be a JSON description of filter hooks and the values we want
 * to set via those filters. For example, given the JSON (pretty printed here for clarity):
 *
 *     {
 *         "wooocommerce_system_timeout": 10
 *     }
 *
 * Then a filter will be added that returns 10 when 'woocommerce_system_timeout' is invoked. Or, given:
 *
 *     {
 *         "woocommerce_enable_deathray": {
 *             "callback": "__return_false"
 *         }
 *     }
 *
 * Then the `__return_false()` convenience function will be set up in relation to filter hook
 * 'woocommerce_enable_deathray'. Additionally, priorities can be specified. Example:
 *
 *     {
 *         "woocommerce_enable_deathray": {
 *             "callback": "__return_false",
 *              "priority": 20
 *         }
 *     }
 *
 * Priorities can also be used in combination with literal values. For example:
 *
 *     {
 *         "woocommerce_default_username": {
 *             "value": "Geoffrey",
 *             "priority": 20
 *         }
 *     }
 *
 * It hopefully goes without saying, this should not be used in a production environment.
 *
 * @package Automattic\WooCommerce\E2EPlaywright
 */
function filter_setter() {
	if ( ! isset( $_COOKIE ) || ! isset( $_COOKIE['e2e-filters'] ) ) {
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

/**
 * Process Waiting Actions
 * Utility intended to be used during E2E testing, to make it easy to process any pending scheduled actions.
 *
 * Intended to function as a (mu-)plugin while tests are running. It listens for requests made with the
 * 'process-waiting-actions' query parameter and then starts an Action Scheduler queue runner. It exits immediately
 * after this, to avoid overhead of building up a full response.
 *
 * @package Automattic\WooCommerce\E2EPlaywright
 */
function process_waiting_actions() {
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
}

/**
 * Test Helper APIs
 * Utility REST API designed for E2E testing purposes. Allows turning features on or off, and setting option values
 */
function test_helper_apis() {
	add_action( 'rest_api_init', function () {
		$is_allowed = function () {
			return current_user_can( 'manage_options' );
		};

		register_rest_route(
			'e2e-feature-flags',
			'/update',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$features     = get_option( 'e2e_feature_flags', array() );
					$new_features = json_decode( $request->get_body(), true );

					if ( is_array( $new_features ) ) {
						$features = array_merge( $features, $new_features );
						update_option( 'e2e_feature_flags', $features );

						return new WP_REST_Response( 'Feature flags updated', 200 );
					}

					return new WP_REST_Response( 'Invalid request body', 400 );
				},
				'permission_callback' => $is_allowed,
			)
		);

		register_rest_route(
			'e2e-feature-flags',
			'/reset',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					delete_option( 'e2e_feature_flags' );

					return new WP_REST_Response( 'Feature flags reset', 200 );
				},
				'permission_callback' => $is_allowed,
			)
		);

		register_rest_route(
			'e2e-options',
			'/update',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$option_name  = sanitize_text_field( $request['option_name'] );
					$option_value = sanitize_text_field( $request['option_value'] );

					update_option( $option_name, $option_value );

					return new WP_REST_Response( 'Option updated', 200 );
				},
				'permission_callback' => $is_allowed,
			)
		);
	} );

	/**
	 * Enable experimental features
	 *
	 * @param array $features Array of features.
	 *
	 * @return array
	 */
	add_filter( 'woocommerce_admin_get_feature_config', function ( $features ) {
		$stored_features = get_option( 'e2e_feature_flags', array() );

		// We always enable this for tests at the moment.
		$features['product-variation-management'] = true;

		return array_merge( $features, $stored_features );
	} );
}

filter_setter();
process_waiting_actions();
test_helper_apis();