<?php
/**
 * Runs WC_Early_Exit_Guard_Listener in its own process, with its shutdown check registered, and ends that process with exit( 0 ).
 *
 * The check exits the process, so WC_Early_Exit_Guard_Listener_Test runs this script with the PHP CLI and reads its
 * exit status and stderr. Usage: `php early-exit-guard-runner.php <scenario>`, where the scenario is `unfinished`
 * (the process ends while a test is running) or `finished` (the suite and test end first).
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;

require dirname( __DIR__, 3 ) . '/vendor/autoload.php';
require dirname( __DIR__, 2 ) . '/legacy/framework/class-wc-early-exit-guard-listener.php';

/**
 * A test for the listener to observe. Never run.
 */
final class WC_Early_Exit_Guard_Runner_Test extends TestCase {

	/**
	 * Never run; the listener only needs a test to observe.
	 */
	public function test_noop(): void {}
}

$wc_listener = new WC_Early_Exit_Guard_Listener();
$wc_suite    = new TestSuite( 'runner' );
$wc_test     = new WC_Early_Exit_Guard_Runner_Test( 'test_noop' );

$wc_listener->startTestSuite( $wc_suite );
$wc_listener->startTest( $wc_test );

if ( 'finished' === ( $argv[1] ?? '' ) ) {
	$wc_listener->endTest( $wc_test, 0.0 );
	$wc_listener->endTestSuite( $wc_suite );
}

exit( 0 );
