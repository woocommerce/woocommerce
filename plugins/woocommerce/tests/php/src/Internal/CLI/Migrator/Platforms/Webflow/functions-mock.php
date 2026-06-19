<?php
/**
 * Test function shadows for the Webflow platform namespace.
 *
 * Defining sleep() inside the WebflowClient namespace lets the 429 retry/backoff
 * tests run instantly. PHP resolves the unqualified sleep() call in WebflowClient
 * to this namespaced function before falling back to the global one, so production
 * code stays untouched and no sleep dependency has to be injected.
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow;

if ( ! function_exists( __NAMESPACE__ . '\\sleep' ) ) {
	/**
	 * No-op replacement for the global sleep() within this namespace so retry/backoff
	 * tests do not actually wait.
	 *
	 * @param int $seconds Number of seconds (ignored).
	 * @return int Always 0, matching a completed sleep.
	 */
	function sleep( $seconds ) { // phpcs:ignore Squiz.Commenting.FunctionComment.Missing, WordPress.NamingConventions
		unset( $seconds );
		return 0;
	}
}
