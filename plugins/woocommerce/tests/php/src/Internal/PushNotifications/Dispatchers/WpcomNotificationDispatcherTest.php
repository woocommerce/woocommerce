<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Dispatchers;

use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\WpcomNotificationDispatcher;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationStepLogger;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the WpcomNotificationDispatcher class.
 */
class WpcomNotificationDispatcherTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WpcomNotificationDispatcher
	 */
	private WpcomNotificationDispatcher $sut;

	/**
	 * The response to return from intercepted HTTP requests.
	 *
	 * @var array|WP_Error
	 */
	private $mock_response;

	/**
	 * Captured HTTP request arguments from the last intercepted call.
	 *
	 * @var array|null
	 */
	private ?array $captured_request;

	/**
	 * Captured URL from the last intercepted call.
	 *
	 * @var string|null
	 */
	private ?string $captured_url;

	/**
	 * Mock step logger.
	 *
	 * @var NotificationStepLogger|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $step_logger;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->step_logger = $this->createMock( NotificationStepLogger::class );
		$this->sut         = new WpcomNotificationDispatcher();
		$this->sut->init( $this->step_logger );
		$this->mock_response    = $this->make_response( 200 );
		$this->captured_request = null;
		$this->captured_url     = null;

		add_filter( 'pre_option_jetpack_options', array( $this, 'filter_jetpack_options' ) );
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'filter_jetpack_private_options' ) );
		add_filter( 'pre_http_request', array( $this, 'intercept_http_request' ), 10, 3 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_option_jetpack_options', array( $this, 'filter_jetpack_options' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'filter_jetpack_private_options' ) );
		remove_filter( 'pre_http_request', array( $this, 'intercept_http_request' ), 10 );
		parent::tearDown();
	}

	/**
	 * Returns fake Jetpack options with a site ID.
	 *
	 * @return array
	 */
	public function filter_jetpack_options(): array {
		return array( 'id' => 12345 );
	}

	/**
	 * Returns fake Jetpack private options with a blog token.
	 *
	 * @return array
	 */
	public function filter_jetpack_private_options(): array {
		return array( 'blog_token' => 'test.blogtokenvalue' );
	}

	/**
	 * Intercepts HTTP requests and captures request data.
	 *
	 * @param false|array $preempt Whether to preempt the request.
	 * @param array       $args    Request arguments.
	 * @param string      $url     Request URL.
	 * @return array|WP_Error The mock response.
	 */
	public function intercept_http_request( $preempt, $args, $url ) {
		unset( $preempt );
		$this->captured_request = $args;
		$this->captured_url     = $url;
		return $this->mock_response;
	}

	/**
	 * @testdox Should return success on 200 response.
	 */
	public function test_dispatch_returns_success_on_200(): void {
		$result = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * @testdox Should return failure on WP_Error response.
	 */
	public function test_dispatch_returns_failure_on_wp_error(): void {
		$this->mock_response = new WP_Error( 'http_request_failed', 'Connection timed out' );

		$result = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );

		$this->assertFalse( $result['success'] );
	}

	/**
	 * @testdox Should return failure with correct retry_after for non-200 responses.
	 * @dataProvider non_200_responses_provider
	 *
	 * @param int      $status_code    The HTTP status code.
	 * @param int|null $expected_retry The expected retry_after value.
	 * @param array    $headers        The response headers.
	 */
	public function test_dispatch_handles_non_200_responses( int $status_code, ?int $expected_retry, array $headers ): void {
		$this->mock_response = $this->make_response( $status_code, $headers );

		$result = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );

		$this->assertFalse( $result['success'] );
		$this->assertSame( $expected_retry, $result['retry_after'] );
	}

	/**
	 * @testdox Should return failure when Jetpack site ID is unavailable.
	 */
	public function test_dispatch_returns_failure_when_no_site_id(): void {
		remove_filter( 'pre_option_jetpack_options', array( $this, 'filter_jetpack_options' ) );
		add_filter( 'pre_option_jetpack_options', array( $this, 'filter_jetpack_options_empty' ) );

		$result = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );

		remove_filter( 'pre_option_jetpack_options', array( $this, 'filter_jetpack_options_empty' ) );

		$this->assertFalse( $result['success'] );
		$this->assertNull( $result['retry_after'] );
		$this->assertNull( $this->captured_url, 'No HTTP request should be made' );
	}

	/**
	 * @testdox Should return failure when notification payload is null.
	 */
	public function test_dispatch_returns_failure_when_payload_is_null(): void {
		$notification = $this->create_notification( null );
		$result       = $this->sut->dispatch( $notification, $this->create_tokens() );

		$this->assertFalse( $result['success'] );
		$this->assertNull( $this->captured_url, 'No HTTP request should be made' );
	}

	/**
	 * @testdox Should fire request to the send endpoint with payload and formatted tokens in the body.
	 */
	public function test_dispatch_sends_request_with_payload_and_tokens(): void {
		$notification = $this->create_notification(
			array(
				'type'        => 'store_order',
				'title'       => array( 'format' => 'New Order' ),
				'resource_id' => 1,
			)
		);

		$tokens = array(
			new PushToken(
				array(
					'user_id'       => 1,
					'token'         => 'abc123',
					'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
					'platform'      => PushToken::PLATFORM_APPLE,
					'device_locale' => 'en_US',
					'device_uuid'   => 'uuid-1',
				)
			),
			new PushToken(
				array(
					'user_id'       => 2,
					'token'         => 'def456',
					'origin'        => PushToken::ORIGIN_WOOCOMMERCE_ANDROID,
					'platform'      => PushToken::PLATFORM_ANDROID,
					'device_locale' => 'fr_FR',
					'device_uuid'   => 'uuid-2',
				)
			),
		);

		$this->sut->dispatch( $notification, $tokens );

		$this->assertStringContainsString(
			WpcomNotificationDispatcher::SEND_ENDPOINT,
			$this->captured_url
		);

		$body = json_decode( $this->captured_request['body'], true );

		$this->assertArrayNotHasKey( 'payload', $body );
		$this->assertSame( 'store_order', $body['type'] );
		$this->assertSame( array( 'format' => 'New Order' ), $body['title'] );
		$this->assertSame( 1, $body['resource_id'] );

		$this->assertCount( 2, $body['tokens'] );
		$this->assertSame(
			array(
				'user_id'       => 1,
				'token'         => 'abc123',
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
			),
			$body['tokens'][0]
		);
		$this->assertSame(
			array(
				'user_id'       => 2,
				'token'         => 'def456',
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_ANDROID,
				'device_locale' => 'fr_FR',
			),
			$body['tokens'][1]
		);
	}

	/**
	 * Returns fake Jetpack options without a site ID.
	 *
	 * @return array
	 */
	public function filter_jetpack_options_empty(): array {
		return array();
	}

	/**
	 * Data provider for non-200 response scenarios.
	 *
	 * @return array<string, array{int, int|null, array}>
	 */
	public function non_200_responses_provider(): array {
		return array(
			'500 without retry-after' => array( 500, null, array() ),
			'429 with retry-after'    => array( 429, 60, array( 'retry-after' => '60' ) ),
			'503 without retry-after' => array( 503, null, array() ),
		);
	}

	/**
	 * @testdox Should report every token accepted when WPCOM queued the batch.
	 */
	public function test_dispatch_logs_accepted_when_wpcom_queued_the_batch(): void {
		$this->mock_response = $this->make_response( 200, array(), '{"queued":1}' );

		$this->step_logger->expects( $this->once() )
			->method( 'log_notification_step' )
			->with(
				$this->anything(),
				'send',
				WpcomNotificationDispatcher::OUTCOME_ACCEPTED,
				array(
					'recipients'   => 1,
					'queued'       => 1,
					'deduplicated' => 0,
				)
			);

		$result = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );

		$this->assertTrue( $result['success'] );
		$this->assertSame( WpcomNotificationDispatcher::OUTCOME_ACCEPTED, $result['outcome'] );
		$this->assertSame( array(), $result['invalid_tokens'] );
	}

	/**
	 * @testdox Should treat a batch refused by the deduplication window as sent, with a deduplicated outcome.
	 */
	public function test_dispatch_logs_deduplicated_when_wpcom_queued_nothing(): void {
		$this->mock_response = $this->make_response( 200, array(), '{"queued":0,"deduplicated":1}' );

		$this->step_logger->expects( $this->once() )
			->method( 'log_notification_step' )
			->with( $this->anything(), 'send', WpcomNotificationDispatcher::OUTCOME_DEDUPLICATED );

		$result = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );

		$this->assertTrue( $result['success'] );
		$this->assertSame( WpcomNotificationDispatcher::OUTCOME_DEDUPLICATED, $result['outcome'] );
	}

	/**
	 * @testdox Should name the refused tokens when WPCOM rejects the batch for an invalid token.
	 */
	public function test_dispatch_returns_the_invalid_tokens_wpcom_named(): void {
		$this->mock_response = $this->make_response(
			422,
			array(),
			'{"code":"invalid_tokens","message":"One or more tokens are invalid.","data":{"status":422,"invalid_tokens":["test-token"]}}'
		);

		$this->step_logger->expects( $this->once() )
			->method( 'log_failure' )
			->with(
				$this->anything(),
				'send',
				WpcomNotificationDispatcher::OUTCOME_REJECTED_INVALID_TOKEN,
				'error',
				'Push notification request returned HTTP 422.',
				$this->callback(
					fn( array $context ) => 422 === $context['http_status']
						&& 'invalid_tokens' === $context['error_code']
						&& 1 === $context['invalid_tokens']
				)
			);

		$result = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );

		$this->assertFalse( $result['success'] );
		$this->assertSame( WpcomNotificationDispatcher::OUTCOME_REJECTED_INVALID_TOKEN, $result['outcome'] );
		$this->assertSame( array( 'test-token' ), $result['invalid_tokens'] );
	}

	/**
	 * @testdox Should report a rejected notification, naming no tokens, when WPCOM fails validation.
	 */
	public function test_dispatch_reports_rejected_notification_on_rest_invalid_param(): void {
		$this->mock_response = $this->make_response( 400, array(), '{"code":"rest_invalid_param","message":"Invalid parameter(s): resource_id"}' );

		$result = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );

		$this->assertFalse( $result['success'] );
		$this->assertSame( WpcomNotificationDispatcher::OUTCOME_REJECTED_INVALID_NOTIFICATION, $result['outcome'] );
		$this->assertSame( array(), $result['invalid_tokens'] );
	}

	/**
	 * @testdox Should report a plain failure with the status and retry delay on any other error.
	 */
	public function test_dispatch_reports_failure_with_status_and_retry_after(): void {
		$this->mock_response = $this->make_response( 503, array( 'retry-after' => '120' ) );

		$this->step_logger->expects( $this->once() )
			->method( 'log_failure' )
			->with(
				$this->anything(),
				'send',
				WpcomNotificationDispatcher::OUTCOME_FAILED,
				'error',
				$this->anything(),
				$this->callback( fn( array $context ) => 503 === $context['http_status'] && 120 === $context['retry_after'] )
			);

		$result = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );

		$this->assertSame( WpcomNotificationDispatcher::OUTCOME_FAILED, $result['outcome'] );
		$this->assertSame( 120, $result['retry_after'] );
	}

	/**
	 * @testdox Should log the missing site ID and the missing resource as failures.
	 */
	public function test_dispatch_logs_failures_before_the_request(): void {
		$this->step_logger->expects( $this->exactly( 2 ) )
			->method( 'log_failure' )
			->withConsecutive(
				array( $this->anything(), 'send', WpcomNotificationDispatcher::OUTCOME_RESOURCE_MISSING, 'error' ),
				array( $this->anything(), 'send', WpcomNotificationDispatcher::OUTCOME_REQUEST_FAILED, 'error' )
			);

		$result = $this->sut->dispatch( $this->create_notification( null ), $this->create_tokens() );
		$this->assertSame( WpcomNotificationDispatcher::OUTCOME_RESOURCE_MISSING, $result['outcome'] );

		$this->mock_response = new WP_Error( 'http_request_failed', 'Connection timed out' );
		$result              = $this->sut->dispatch( $this->create_notification(), $this->create_tokens() );
		$this->assertSame( WpcomNotificationDispatcher::OUTCOME_REQUEST_FAILED, $result['outcome'] );
	}

	/**
	 * Creates a mock HTTP response array.
	 *
	 * @param int    $status_code HTTP status code.
	 * @param array  $headers     Response headers.
	 * @param string $body        Response body.
	 * @return array
	 */
	private function make_response( int $status_code, array $headers = array(), string $body = '' ): array {
		return array(
			'response' => array(
				'code'    => $status_code,
				'message' => 'Mock',
			),
			'body'     => $body,
			'headers'  => $headers,
		);
	}

	/**
	 * Creates a mock Notification instance for testing.
	 *
	 * @param array|null $payload The payload to return from to_payload().
	 * @return NewOrderNotification
	 */
	private function create_notification( ?array $payload = array( 'test' => true ) ): NewOrderNotification {
		$mock = $this->getMockBuilder( NewOrderNotification::class )
			->setConstructorArgs( array( 1 ) )
			->onlyMethods( array( 'to_payload', 'has_meta', 'write_meta' ) )
			->getMock();
		$mock->method( 'to_payload' )->willReturn( $payload );

		return $mock;
	}

	/**
	 * Creates a default set of push tokens for tests that don't need specific token data.
	 *
	 * @return PushToken[]
	 */
	private function create_tokens(): array {
		return array(
			new PushToken(
				array(
					'user_id'       => 1,
					'token'         => 'test-token',
					'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
					'platform'      => PushToken::PLATFORM_APPLE,
					'device_locale' => 'en_US',
					'device_uuid'   => 'test-uuid',
				)
			),
		);
	}
}
