<?php
/**
 * ServerLogger class file
 */

namespace Automattic\WooCommerce\Utilities;

if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {

	/**
	 * Class with tools for server-side logging.
	 */
	class ServerLogger
	{
		public static function log(...$args): void
		{
			$stdout = fopen('php://stdout', 'w');

			foreach ($args as $arg) {
				if (is_object($arg) || is_array($arg) || is_resource($arg)) {
					$output = print_r($arg, true);
				} else {
					$output = (string)$arg;
				}

				fwrite($stdout, $output . "\n");
			}

			fclose($stdout);
		}
	}
}
