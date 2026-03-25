<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Dispatchers;

use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\InternalNotificationDispatcher;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Stubs\StubOrderNotification;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Stubs\StubReviewNotification;
use WC_Unit_Test_Case;

/**
 * Tests for the InternalNotificationDispatcher class.
 */
class InternalNotificationDispatcherTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var InternalNotificationDispatcher
	 */
	private $sut;

	/**
	 * Captured HTTP request arguments from the last wp_remote_post call.
	 *
	 * @var array|null
	 */
	private $captured_request;

	/**
	 * Captured URL from the last wp_remote_post call.
	 *
	 * @var string|null
	 */
	private $captured_url;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut              = new InternalNotificationDispatcher();
		$this->captured_request = null;
		$this->captured_url     = null;

		add_filter( 'pre_http_request', array( $this, 'intercept_http_request' ), 10, 3 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'intercept_http_request' ), 10 );
		parent::tearDown();
	}

	/**
	 * Intercepts wp_remote_post calls and captures request data.
	 *
	 * @param false|array $preempt   Whether to preempt the request.
	 * @param array       $args      Request arguments.
	 * @param string      $url       Request URL.
	 * @return array Fake successful response.
	 */
	public function intercept_http_request( $preempt, $args, $url ) {
		unset( $preempt );
		$this->captured_request = $args;
		$this->captured_url     = $url;

		return array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => '',
		);
	}

	/**
	 * @testdox Should fire a non-blocking POST to the send endpoint URL.
	 */
	public function test_dispatch_fires_non_blocking_post_to_send_endpoint(): void {
		$notifications = array( new StubOrderNotification( 1 ) );

		$this->sut->dispatch( $notifications );

		$this->assertStringContainsString(
			InternalNotificationDispatcher::SEND_ENDPOINT,
			$this->captured_url,
			'Request URL should contain the send endpoint'
		);
		$this->assertFalse(
			$this->captured_request['blocking'],
			'Request should be non-blocking'
		);
		$this->assertSame(
			1,
			$this->captured_request['timeout'],
			'Request timeout should be 1 second'
		);
		$this->assertSame(
			'application/json',
			$this->captured_request['headers']['Content-Type'],
			'Request Content-Type should be application/json'
		);
	}

	/**
	 * @testdox Should include a valid JWT with correct claims and body hash.
	 */
	public function test_dispatch_includes_valid_jwt_with_correct_claims(): void {
		$notifications = array( new StubOrderNotification( 1 ) );

		$this->sut->dispatch( $notifications );

		$auth_header = $this->captured_request['headers']['Authorization'];
		$token       = str_replace( 'Bearer ', '', $auth_header );

		$this->assertTrue(
			JsonWebToken::validate( $token, wp_salt( 'auth' ) ),
			'JWT should be valid when verified with the auth salt'
		);

		$parts     = JsonWebToken::get_parts( $token );
		$body_hash = hash( 'sha256', $this->captured_request['body'] );

		$this->assertSame( get_site_url(), $parts->payload->iss, 'JWT issuer should be the site URL' );
		$this->assertGreaterThan( time(), (int) $parts->payload->exp, 'JWT should not be expired' );
		$this->assertSame(
			$body_hash,
			$parts->payload->body_hash,
			'JWT body_hash should match SHA-256 hash of the request body'
		);
	}

	/**
	 * @testdox Should include encoded notifications in the request body.
	 */
	public function test_dispatch_body_contains_encoded_notifications(): void {
		$notifications = array(
			new StubOrderNotification( 10 ),
			new StubReviewNotification( 20 ),
		);

		$this->sut->dispatch( $notifications );

		$body = json_decode( $this->captured_request['body'], true );

		$this->assertArrayHasKey( 'notifications', $body );
		$this->assertCount( 2, $body['notifications'] );
		$this->assertSame( 'store_order', $body['notifications'][0]['type'] );
		$this->assertSame( 10, $body['notifications'][0]['resource_id'] );
		$this->assertSame( 'store_review', $body['notifications'][1]['type'] );
		$this->assertSame( 20, $body['notifications'][1]['resource_id'] );
	}

	/**
	 * @testdox Should skip dispatch when notifications array is empty.
	 */
	public function test_dispatch_skips_when_empty(): void {
		$this->sut->dispatch( array() );

		$this->assertNull( $this->captured_url, 'No HTTP request should be made for empty notifications' );
	}
}
