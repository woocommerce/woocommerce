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

require_once __DIR__ . '/class-wc-external-http-guard.php';

/**
 * Installs WC_External_HTTP_Guard and fails each test whose requests it blocked. A listener
 * rather than an exception from the filter, because code under test often catches exceptions.
 * Registered in phpunit.xml, so it covers every test whatever its base class. A request sent
 * outside any test, such as from class setup or teardown, fails the next test.
 */
final class WC_External_HTTP_Guard_Listener implements TestListener {

	use TestListenerDefaultImplementation;

	/**
	 * Appended to a request blocked outside any test, which fails the next test instead.
	 */
	private const BEFORE_TEST_NOTE = "sent before this test started, during class setup or teardown (possibly the previous class's teardown)";

	/**
	 * Requests blocked between the previous test and this one, already labelled with BEFORE_TEST_NOTE.
	 *
	 * @var string[]
	 */
	private $requests_before_test = array();

	/**
	 * Install the guard. PHPUnit creates listeners after the bootstrap and before any class setup.
	 */
	public function __construct() {
		WC_External_HTTP_Guard::register();
	}

	/**
	 * Keep requests blocked since the previous test for this test's failure, then allow external
	 * requests for tests in the external-http group. Requests from the last class's
	 * tearDownAfterClass have no following test and go unreported.
	 *
	 * @param Test $test The test about to run.
	 */
	public function startTest( Test $test ): void {
		$this->requests_before_test = array();
		foreach ( WC_External_HTTP_Guard::take_blocked_requests() as $request ) {
			$this->requests_before_test[] = $request . ', ' . self::BEFORE_TEST_NOTE;
		}
		WC_External_HTTP_Guard::allow_external( $test instanceof TestCase && in_array( WC_External_HTTP_Guard::GROUP, $test->getGroups(), true ) );
	}

	/**
	 * Fail the test if the guard blocked any request during it or before it started, then
	 * block external requests until the next test starts.
	 *
	 * @param Test  $test The test that just ran.
	 * @param float $time How long it took, in seconds.
	 */
	public function endTest( Test $test, float $time ): void {
		WC_External_HTTP_Guard::allow_external( false );

		$before_test                = $this->requests_before_test;
		$this->requests_before_test = array();
		$blocked                    = array_merge( $before_test, WC_External_HTTP_Guard::take_blocked_requests() );

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
