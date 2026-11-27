<?php
/**
 * Stand-in for the shutdown handler the Jetpack autoloader generates, under the namespace it generates.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Jetpack\Autoloader\jp_test\al6_0_4;

/**
 * Empties the autoloader's plugin cache when a request ends before `plugins_loaded`.
 */
class Shutdown_Handler {

	/**
	 * Run on shutdown.
	 */
	public function __invoke() {}
}
