<?php
/**
 * PHPUnit listener to log test class names.
 * Helps identify which tests run before a specific test in CI.
 */

namespace Automattic\WooCommerce\Tests;

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;

/**
 * Logs test class names as they start running.
 */
class TestClassLogger implements TestListener {
	use TestListenerDefaultImplementation;

	/**
	 * Track which classes we've already logged.
	 *
	 * @var array
	 */
	private static $logged_classes = array();

	/**
	 * Called when a test suite starts.
	 *
	 * @param TestSuite $suite The test suite.
	 */
	public function startTestSuite( TestSuite $suite ): void {
		$name = $suite->getName();

		// Only log actual test class names (not suite names like "wc-phpunit-legacy")
		if ( class_exists( $name ) && ! isset( self::$logged_classes[ $name ] ) ) {
			self::$logged_classes[ $name ] = true;
			fwrite( STDERR, "\n[TEST CLASS START] {$name}\n" );
		}
	}
}
