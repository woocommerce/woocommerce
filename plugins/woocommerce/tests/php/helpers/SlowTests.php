<?php

namespace Automattic\WooCommerce\Tests;

use Automattic\Jetpack\IdentityCrisis\Exception;
use PHPUnit\Runner\AfterTestHook;

/**
 *
 */
class SlowTests implements AfterTestHook {
	/**
	 * Threshold of what's considered "slow".
	 */
	protected const MAX_SECONDS_ALLOWED = 3;

	/**
	 *
	 *
	 * @param string $test Name of the test.
	 * @param float  $time Elapsed time in seconds of the test.
	 *
	 * @return void
	 */
	public function executeAfterTest( string $test, float $time ): void {
		if ( $time > self::MAX_SECONDS_ALLOWED ) {
			fwrite( STDERR, sprintf( "\nThe %s test took %s seconds!\n", $test, $time ) );
		}
	}
}
