<?php
/**
 * Tests for the listener that fails tests sending external HTTP requests.
 *
 * @package WooCommerce\Tests\Framework
 */

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;

/**
 * WC_External_HTTP_Guard_Listener_Test class.
 *
 * Drives the listener against a separate, never-run test and its own TestResult,
 * so the failures it adds do not land on this test.
 */
class WC_External_HTTP_Guard_Listener_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_External_HTTP_Guard_Listener
	 */
	private $sut;

	/**
	 * The test the listener observes.
	 *
	 * @var TestCase
	 */
	private $observed_test;

	/**
	 * The result the listener reports the observed test's failures to.
	 *
	 * @var TestResult
	 */
	private $observed_result;

	/**
	 * Create the listener and the test it observes.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut             = new WC_External_HTTP_Guard_Listener();
		$this->observed_result = new TestResult();
		$this->observed_test   = new class( 'test_noop' ) extends TestCase {
			/**
			 * Never run; the listener only needs a test to observe.
			 */
			public function test_noop(): void {}
		};
		$this->observed_test->setTestResultObject( $this->observed_result );
	}

	/**
	 * Drop requests recorded here, disallow external requests again and keep the guard armed, so the listener does not fail this test.
	 */
	public function tearDown(): void {
		try {
			WC_External_HTTP_Guard::clear_blocked_requests();
			WC_External_HTTP_Guard::allow_external( false );
			$this->armed_property()->setValue( null, true );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * The guard's private armed flag, which only the listener sets in a real run.
	 *
	 * @return ReflectionProperty
	 */
	private function armed_property(): ReflectionProperty {
		$property = new ReflectionProperty( WC_External_HTTP_Guard::class, 'armed' );
		$property->setAccessible( true );
		return $property;
	}

	/**
	 * @testdox A test that sent an external request fails with the request and both fixes in the message.
	 */
	public function test_blocked_request_fails_the_test(): void {
		$this->sut->startTest( $this->observed_test );
		WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), 'https://api.example.com/feed?token=secret' );
		$this->sut->endTest( $this->observed_test, 0.0 );

		$this->assertSame( 1, $this->observed_result->failureCount(), 'The observed test should fail once.' );
		$message = $this->observed_result->failures()[0]->exceptionMessage();
		$this->assertStringContainsString( 'GET https://api.example.com/feed (from ', $message, 'The message should name the blocked request.' );
		$this->assertStringContainsString( '$this->http_responder', $message, 'The message should suggest mocking the request.' );
		$this->assertStringContainsString( '@group external-http', $message, 'The message should name the opt-out.' );
	}

	/**
	 * @testdox A test that sent no external request is left untouched.
	 */
	public function test_test_without_blocked_requests_passes(): void {
		$this->sut->startTest( $this->observed_test );
		$this->sut->endTest( $this->observed_test, 0.0 );

		$this->assertSame( 0, $this->observed_result->failureCount(), 'The observed test should not fail.' );
	}

	/**
	 * @testdox A test in the external-http group may reach external hosts.
	 */
	public function test_external_http_group_allows_external_requests(): void {
		$this->observed_test->setGroups( array( 'default', WC_External_HTTP_Guard::GROUP ) );

		$this->sut->startTest( $this->observed_test );
		$result = WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), 'https://api.example.com/feed' );
		$this->sut->endTest( $this->observed_test, 0.0 );

		$this->assertFalse( $result, 'The request should reach the network.' );
		$this->assertSame( 0, $this->observed_result->failureCount(), 'The observed test should not fail.' );
	}

	/**
	 * @testdox Requests recorded before a test starts fail that test, labelled as sent before it.
	 */
	public function test_requests_before_start_fail_the_next_test(): void {
		WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), 'https://api.example.com/before' );

		$this->sut->startTest( $this->observed_test );
		WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), 'https://api.example.com/during' );
		$this->sut->endTest( $this->observed_test, 0.0 );

		$this->assertSame( 1, $this->observed_result->failureCount(), 'The observed test should fail once.' );
		$message = $this->observed_result->failures()[0]->exceptionMessage();
		$this->assertMatchesRegularExpression( '#GET https://api\.example\.com/before \(from [^)]+\), sent before this test started#', $message, 'The earlier request should be labelled as sent before the test.' );
		$this->assertMatchesRegularExpression( '#GET https://api\.example\.com/during \(from [^)]+\)$#m', $message, 'The test\'s own request should be listed without the label.' );
	}

	/**
	 * @testdox Ending a test forgets its requests.
	 */
	public function test_end_clears_blocked_requests(): void {
		$this->sut->startTest( $this->observed_test );
		WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), 'https://api.example.com/feed' );
		$this->sut->endTest( $this->observed_test, 0.0 );

		$this->assertSame( array(), WC_External_HTTP_Guard::get_blocked_requests(), 'The next test should not see the requests of the one that ended.' );
	}

	/**
	 * @testdox Ending a test in the external-http group blocks external requests again.
	 */
	public function test_end_disallows_external_requests(): void {
		$this->observed_test->setGroups( array( WC_External_HTTP_Guard::GROUP ) );

		$this->sut->startTest( $this->observed_test );
		$this->sut->endTest( $this->observed_test, 0.0 );

		$this->assertWPError( WC_External_HTTP_Guard::filter_request( false, array( 'method' => 'GET' ), 'https://api.example.com/feed' ), 'A request between tests should be blocked.' );
	}

	/**
	 * @testdox Starting a test suite arms the guard.
	 */
	public function test_start_test_suite_arms_the_guard(): void {
		$this->armed_property()->setValue( null, false );

		$this->sut->startTestSuite( new TestSuite() );

		$this->assertTrue( $this->armed_property()->getValue(), 'The guard should block requests once a suite starts.' );
	}
}
