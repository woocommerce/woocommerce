<?php
/**
 * Plugin name: Filter Setter
 * Description: Utility intended to be used during E2E testing, to make it easy to setup WordPress filters.
 *
 * Intended to function as a (mu-)plugin while tests are running, this code works by inspecting the current cookie
 * for an entry called 'e2e-filters', which is expected to be a JSON description of filter hooks and the values we want
 * to set via those filters. For example, given the JSON (pretty printed here for clarity):
 *
 *     {
 *         "woocommerce_system_timeout": 10
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

