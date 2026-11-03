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
use PHPUnit\Framework\TestSuite;

/**
 * Turns requests blocked by WC_External_HTTP_Guard into a failure of the test that sent them.
 *
 * A listener rather than an exception from the filter, because code under test often
 * catches exceptions and the test would carry on. Registered in phpunit.xml, so it
 * covers every test whatever its base class, and it arms the guard. A request sent
 * outside any test, such as from class setup or teardown, fails the next test.
 */
final class WC_External_HTTP_Guard_Listener implements TestListener {

	use TestListenerDefaultImplementation;

	/**
	 * Appended to a request blocked outside any test, which fails the next test instead.
	 */
	const BEFORE_TEST_NOTE = "sent before this test started, during class setup or teardown (possibly the previous class's teardown)";

	/**
	 * Requests blocked between the previous test and this one, as the guard recorded them.
	 *
	 * @var string[]
	 */
	private $requests_before_test = array();

	/**
	 * Arm the guard. PHPUnit starts the root suite before any class setup runs; nested suites arm it again, harmlessly.
	 *
	 * @param TestSuite $suite The suite about to run.
	 */
	public function startTestSuite( TestSuite $suite ): void {
		WC_External_HTTP_Guard::arm();
	}

	/**
	 * Keep requests blocked since the previous test for this test's failure, then allow external requests for tests in the external-http group.
	 *
	 * Requests from the last class's tearDownAfterClass have no following test and go unreported.
	 *
	 * @param Test $test The test about to run.
	 */
	public function startTest( Test $test ): void {
		$this->requests_before_test = WC_External_HTTP_Guard::get_blocked_requests();
		WC_External_HTTP_Guard::clear_blocked_requests();
		WC_External_HTTP_Guard::allow_external( $test instanceof TestCase && in_array( WC_External_HTTP_Guard::GROUP, $test->getGroups(), true ) );
	}

	/**
	 * Fail the test if the guard blocked any request during it or before it started, then block external requests until the next test starts.
	 *
	 * @param Test  $test The test that just ran.
	 * @param float $time How long it took, in seconds.
	 */
	public function endTest( Test $test, float $time ): void {
		$before_test = array_map(
			function ( string $request ): string {
				return $request . ', ' . self::BEFORE_TEST_NOTE;
			},
			$this->requests_before_test
		);
		$blocked     = array_merge( $before_test, WC_External_HTTP_Guard::get_blocked_requests() );

		$this->requests_before_test = array();
		WC_External_HTTP_Guard::clear_blocked_requests();
		WC_External_HTTP_Guard::allow_external( false );

		if ( array() === $blocked || ! $test instanceof TestCase ) {
			return;
		}

		$result = $test->getTestResultObject();
		if ( null === $result ) {
			return;
		}

		// TestResult::endTest() runs listeners before it records the test as passed, so this failure sticks.
		$result->addFailure( $test, new AssertionFailedError( self::failure_message( $blocked, array() !== $before_test ) ), $time );
	}

	/**
	 * Explain which requests were blocked and how to fix the test.
	 *
	 * @param string[] $blocked         The blocked requests.
	 * @param bool     $any_before_test Whether any of them was sent outside the test.
	 * @return string
	 */
	private static function failure_message( array $blocked, bool $any_before_test ): string {
		$message = sprintf(
			"The PHPUnit external HTTP guard blocked %d HTTP request(s) to external hosts:\n- %s\nMock the request with \$this->http_responder or a pre_http_request filter, or add @group %s if the test needs the real network.",
			count( $blocked ),
			implode( "\n- ", $blocked ),
			WC_External_HTTP_Guard::GROUP
		);
		if ( $any_before_test ) {
			$message .= sprintf( ' @group %s does not cover requests sent outside a test; mock those or move them into the test.', WC_External_HTTP_Guard::GROUP );
		}
		return $message;
	}
}
