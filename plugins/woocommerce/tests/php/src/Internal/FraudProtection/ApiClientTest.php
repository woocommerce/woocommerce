<?php
/**
 * ApiClientTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\ApiClient;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the ApiClient class.
 *
 * Tests the Blackbox API client which provides:
 * - verify(): Verify a session and get a fraud decision (allow/block)
 * - report(): Report fraud events for feedback
 */
class ApiClientTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var ApiClient
	 */
	private ApiClient $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new ApiClient();

		update_option( 'jetpack_options', array( 'id' => 12345 ) );
		update_option( 'jetpack_private_options', array( 'blog_token' => 'IAM.AJETPACKBLOGTOKEN' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( 'jetpack_options' );
		delete_option( 'jetpack_private_options' );
		parent::tearDown();
	}

	/*
	|--------------------------------------------------------------------------
	| verify() Tests
	|--------------------------------------------------------------------------
	*/

	/**
	 * Test verify calls correct endpoint with payload.
	 *
	 * @testdox verify() calls Blackbox API /verify endpoint with the correct payload
	 */
	public function test_verify_calls_verify_endpoint(): void {
		$captured_url  = null;
		$captured_body = null;

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$captured_url, &$captured_body ) {
				unset( $preempt );
				$captured_body = json_decode( $args['body'], true );
				$captured_url  = $url;
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'decision' => 'allow' ) ),
				);
			},
			10,
			3
		);

		$this->sut->verify( 'test-session-id', array( 'event_type' => 'checkout_started' ) );

		$this->assertStringContainsString( 'blackbox-api.wp.com/v1/verify', $captured_url );
		$this->assertSame( 'test-session-id', $captured_body['session_id'] );
		$this->assertArrayHasKey( 'extra', $captured_body );
		$this->assertArrayHasKey( 'blog_id', $captured_body['extra'] );
		$this->assertSame( 12345, $captured_body['extra']['blog_id'] );
	}

	/**
	 * Test verify returns allow decision.
	 *
	 * @testdox verify() returns allow decision from API
	 */
	public function test_verify_returns_allow_decision(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'decision' => 'allow' ) ),
			)
		);

		$result = $this->sut->verify( 'test-session-id', array( 'event_type' => 'checkout_started' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
	}

	/**
	 * Test verify returns block decision.
	 *
	 * @testdox verify() returns block decision from API
	 */
	public function test_verify_returns_block_decision(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'decision' => 'block' ) ),
			)
		);

		$result = $this->sut->verify( 'test-session-id', array( 'event_type' => 'checkout_started' ) );

		$this->assertSame( ApiClient::DECISION_BLOCK, $result );
	}

	/**
	 * Test verify fails open when blog_id not found.
	 *
	 * @testdox verify() fails open with allow when blog_id not found
	 */
	public function test_verify_fails_open_when_blog_id_not_found(): void {
		update_option( 'jetpack_options', array( 'id' => null ) );

		$result = $this->sut->verify( 'test-session-id', array( 'event_type' => 'checkout_started' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
		$this->assertLogged( 'error', 'Jetpack blog ID not found' );
	}

	/**
	 * Test verify fails open on HTTP error.
	 *
	 * @testdox verify() fails open with allow when HTTP request fails
	 */
	public function test_verify_fails_open_on_http_error(): void {
		add_filter(
			'pre_http_request',
			fn() => new WP_Error( 'http_error', 'Connection timeout' )
		);

		$result = $this->sut->verify( 'test-session-id', array( 'event_type' => 'checkout_started' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
		$this->assertLogged( 'error', 'Connection timeout' );
	}

	/**
	 * Test verify fails open on server error.
	 *
	 * @testdox verify() fails open with allow when API returns 5xx error
	 */
	public function test_verify_fails_open_on_server_error(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 500 ),
				'body'     => 'Internal Server Error',
			)
		);

		$result = $this->sut->verify( 'test-session-id', array( 'event_type' => 'checkout_started' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
		$this->assertLogged( 'error', 'status code 500' );
	}

	/**
	 * Test verify fails open on invalid JSON.
	 *
	 * @testdox verify() fails open with allow when API returns invalid JSON
	 */
	public function test_verify_fails_open_on_invalid_json(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => 'not valid json',
			)
		);

		$result = $this->sut->verify( 'test-session-id', array( 'event_type' => 'checkout_started' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
		$this->assertLogged( 'error', 'Failed to decode JSON' );
	}

	/**
	 * Test verify fails open when decision field missing.
	 *
	 * @testdox verify() fails open with allow when response missing decision field
	 */
	public function test_verify_fails_open_when_missing_decision(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'risk_score' => 50 ) ),
			)
		);

		$result = $this->sut->verify( 'test-session-id', array( 'event_type' => 'checkout_started' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
		$this->assertLogged( 'error', 'missing "decision" field' );
	}

	/**
	 * Test verify fails open on invalid decision value.
	 *
	 * @testdox verify() fails open with allow when decision value is invalid
	 */
	public function test_verify_fails_open_on_invalid_decision(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'decision' => 'unknown_value' ) ),
			)
		);

		$result = $this->sut->verify( 'test-session-id', array( 'event_type' => 'checkout_started' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
		$this->assertLogged( 'error', 'Invalid decision value' );
	}

	/*
	|--------------------------------------------------------------------------
	| report() Tests
	|--------------------------------------------------------------------------
	*/

	/**
	 * Test report calls correct endpoint.
	 *
	 * @testdox report() calls Blackbox API /report endpoint
	 */
	public function test_report_calls_report_endpoint(): void {
		$captured_url  = null;
		$captured_body = null;

		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$captured_url, &$captured_body ) {
				unset( $preempt );
				$captured_url  = $url;
				$captured_body = json_decode( $args['body'], true );
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => wp_json_encode( array( 'status' => 'ok' ) ),
				);
			},
			10,
			3
		);

		$this->sut->report( 'test-session-id', array( 'event_type' => 'payment_success' ) );

		$this->assertStringContainsString( 'blackbox-api.wp.com/v1/report', $captured_url );
		$this->assertSame( 'test-session-id', $captured_body['session_id'] );
		$this->assertArrayHasKey( 'extra', $captured_body );
		$this->assertArrayHasKey( 'blog_id', $captured_body['extra'] );
		$this->assertSame( 12345, $captured_body['extra']['blog_id'] );
	}

	/**
	 * Test report returns true on success.
	 *
	 * @testdox report() returns true on success
	 */
	public function test_report_returns_true_on_success(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'status' => 'ok' ) ),
			)
		);

		$result = $this->sut->report( 'test-session-id', array( 'event_type' => 'payment_success' ) );

		$this->assertTrue( $result );
		$this->assertLogged( 'info', 'Event reported successfully' );
	}

	/**
	 * Test report returns false when blog_id not found.
	 *
	 * @testdox report() returns false when blog_id not found
	 */
	public function test_report_returns_false_when_blog_id_not_found(): void {
		update_option( 'jetpack_options', array( 'id' => null ) );

		$result = $this->sut->report( 'test-session-id', array( 'event_type' => 'payment_success' ) );

		$this->assertFalse( $result );
		$this->assertLogged( 'error', 'Jetpack blog ID not found' );
	}

	/**
	 * Test report returns false on HTTP error.
	 *
	 * @testdox report() returns false when HTTP request fails
	 */
	public function test_report_returns_false_on_http_error(): void {
		add_filter(
			'pre_http_request',
			fn() => new WP_Error( 'http_error', 'Connection timeout' )
		);

		$result = $this->sut->report( 'test-session-id', array( 'event_type' => 'payment_success' ) );

		$this->assertFalse( $result );
		$this->assertLogged( 'error', 'Failed to report event' );
	}

	/**
	 * Test report returns false on server error.
	 *
	 * @testdox report() returns false when API returns error status
	 */
	public function test_report_returns_false_on_server_error(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 500 ),
				'body'     => 'Internal Server Error',
			)
		);

		$result = $this->sut->report( 'test-session-id', array( 'event_type' => 'payment_success' ) );

		$this->assertFalse( $result );
		$this->assertLogged( 'error', 'status code 500' );
	}
}
