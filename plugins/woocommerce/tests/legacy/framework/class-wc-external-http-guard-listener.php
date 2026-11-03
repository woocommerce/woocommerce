<?php
/**
 * Fails a test that sent an HTTP request to an external host.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

/**
 * Turns requests blocked by WC_External_HTTP_Guard into a failure of the test that sent them.
 *
 * A listener rather than an exception from the filter, because code under test often
 * catches exceptions and the test would carry on. Registered in phpunit.xml, so it
 * covers every test whatever its base class.
 */
final class WC_External_HTTP_Guard_Listener implements TestListener {

	use TestListenerDefaultImplementation;

	/**
	 * Reset the guard, allowing external requests for tests in the external-http group.
	 *
	 * @param Test $test The test about to run.
	 */
	public function startTest( Test $test ): void {
		WC_External_HTTP_Guard::reset( $test instanceof TestCase && in_array( WC_External_HTTP_Guard::GROUP, $test->getGroups(), true ) );
	}

	/**
	 * Fail the test if the guard blocked any of its requests.
	 *
	 * @param Test  $test The test that just ran.
	 * @param float $time How long it took, in seconds.
	 */
	public function endTest( Test $test, float $time ): void {
		$blocked = WC_External_HTTP_Guard::get_blocked_requests();
		if ( array() === $blocked || ! $test instanceof TestCase ) {
			return;
		}

		$result = $test->getTestResultObject();
		if ( null === $result ) {
			return;
		}

		// TestResult::endTest() runs listeners before it records the test as passed, so this failure sticks.
		$result->addFailure( $test, new AssertionFailedError( self::failure_message( $blocked ) ), $time );
	}

	/**
	 * Explain which requests were blocked and how to fix the test.
	 *
	 * @param string[] $blocked The blocked requests.
	 * @return string
	 */
	private static function failure_message( array $blocked ): string {
		return sprintf(
			"This test sent %d HTTP request(s) to external hosts, which the PHPUnit external HTTP guard blocked:\n- %s\nMock the request with \$this->http_responder or a pre_http_request filter, or add @group %s if the test needs the real network.",
			count( $blocked ),
			implode( "\n- ", $blocked ),
			WC_External_HTTP_Guard::GROUP
		);
	}
}
