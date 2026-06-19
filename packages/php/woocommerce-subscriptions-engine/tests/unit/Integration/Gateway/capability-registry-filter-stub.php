<?php
/**
 * Namespaced `apply_filters()` stub for CapabilityRegistry unit tests.
 *
 * Defined in the same namespace as CapabilityRegistry so its unqualified
 * `apply_filters()` call resolves here instead of WordPress core, which the
 * unit bootstrap does not load. The stub records each call and returns either a
 * configured override or the passed-through value (the WordPress default when
 * no callback is attached).
 *
 * Kept in its own file (not the *Test.php class file) so the PSR-12 "one
 * namespace per file" / "functions separate from OO" sniffs stay satisfied.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway;

if ( ! function_exists( __NAMESPACE__ . '\\apply_filters' ) ) {
	/**
	 * Namespaced test double for WordPress `apply_filters()`.
	 *
	 * @param string $hook    Filter hook name.
	 * @param mixed  $value   Value being filtered.
	 * @param mixed  ...$args Additional filter arguments.
	 * @return mixed Configured override, or `$value` unchanged.
	 */
	function apply_filters( string $hook, $value, ...$args ) {
		$GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_calls'][] = array(
			'hook'  => $hook,
			'value' => $value,
			'args'  => $args,
		);

		return $GLOBALS['woocommerce_subscriptions_engine_test_apply_filters_return'] ?? $value;
	}
}
