<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\WooCommerce\Internal\PushNotifications\Controllers\TestNotificationRestController;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\TestNotification;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for the TestNotificationRestController class.
 */
class TestNotificationRestControllerTest extends WC_Unit_Test_Case {
	/**
	 * The pending notification store the controller queues into.
	 *
	 * @var PendingNotificationStore&MockObject
	 */
	private $pending_notification_store;

	/**
	 * REST server with the controller's routes registered.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->pending_notification_store = $this->createMock( PendingNotificationStore::class );

		$sut = new class() extends TestNotificationRestController {
			/**
			 * Stands in for a request WPCOM signed with the Jetpack blog token.
			 *
			 * @return bool
			 */
			protected function is_signed_with_blog_token(): bool {
				return true;
			}
		};
		$sut->init( $this->pending_notification_store, wc_get_container()->get( PushTokensDataStore::class ) );

		$this->server = $this->create_rest_server_with_routes( array( array( $sut, 'register_routes' ) ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			$this->clear_rest_server();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should queue a test notification for the named token and accept the request.
	 */
	public function test_create_queues_a_test_notification_for_the_token(): void {
		$token_id = $this->create_token_for( 'shop_manager' );

		$this->pending_notification_store
			->expects( $this->once() )
			->method( 'add' )
			->with(
				$this->callback(
					fn( $notification ) => $notification instanceof TestNotification
						&& 42 === $notification->get_resource_id()
						&& $token_id === $notification->get_target_token_id()
				)
			);

		$response = $this->send( 42, $token_id );

		$this->assertSame( WP_Http::ACCEPTED, $response->get_status() );
		$this->assertSame( 42, $response->get_data()['test_id'] );
	}

	/**
	 * @testdox Should return not found for a token the store does not have.
	 */
	public function test_create_returns_not_found_for_an_unknown_token(): void {
		$this->pending_notification_store->expects( $this->never() )->method( 'add' );

		$response = $this->send( 42, 999999 );

		$this->assertSame( WP_Http::NOT_FOUND, $response->get_status() );
	}

	/**
	 * @testdox Should refuse a token whose owner does not receive push notifications, since no acknowledgement would ever arrive.
	 */
	public function test_create_refuses_a_token_owned_by_an_ineligible_user(): void {
		$this->pending_notification_store->expects( $this->never() )->method( 'add' );

		$response = $this->send( 42, $this->create_token_for( 'customer' ) );

		$this->assertSame( WP_Http::UNPROCESSABLE_ENTITY, $response->get_status() );
		$this->assertSame( 'woocommerce_push_notification_token_not_eligible', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should refuse a test ID that has already been sent, instead of accepting a request that sends nothing.
	 */
	public function test_create_refuses_a_used_test_id(): void {
		$token_id = $this->create_token_for( 'administrator' );
		( new TestNotification( 42, $token_id ) )->write_meta( NotificationProcessor::SENT_META_KEY );

		$this->pending_notification_store->expects( $this->never() )->method( 'add' );

		$response = $this->send( 42, $token_id );

		$this->assertSame( WP_Http::CONFLICT, $response->get_status() );
	}

	/**
	 * @testdox Should reject a test ID outside the range the apps can read.
	 * @testWith [0]
	 *           [2147483648]
	 *
	 * @param int $test_id The test ID sent.
	 */
	public function test_create_rejects_an_out_of_range_test_id( int $test_id ): void {
		$response = $this->send( $test_id, $this->create_token_for( 'administrator' ) );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * @testdox Should reject a request not signed with the Jetpack blog token.
	 */
	public function test_route_rejects_an_unsigned_request(): void {
		$sut = new TestNotificationRestController();
		$sut->init( $this->pending_notification_store, wc_get_container()->get( PushTokensDataStore::class ) );
		$this->server = $this->create_rest_server_with_routes( array( array( $sut, 'register_routes' ) ) );

		$response = $this->send( 42, $this->create_token_for( 'administrator' ) );

		$this->assertSame( WP_Http::UNAUTHORIZED, $response->get_status() );
	}

	/**
	 * Sends a test notification request through the REST server.
	 *
	 * @param int $test_id  The test ID.
	 * @param int $token_id The push token ID.
	 * @return WP_REST_Response
	 */
	private function send( int $test_id, int $token_id ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/test-notifications' );
		$request->set_param( 'test_id', $test_id );
		$request->set_param( 'token_id', $token_id );

		return $this->server->dispatch( $request );
	}

	/**
	 * Creates a push token owned by a new user with the given role.
	 *
	 * @param string $role The owner's role.
	 * @return int The token ID.
	 */
	private function create_token_for( string $role ): int {
		$token = wc_get_container()->get( PushTokensDataStore::class )->create(
			array(
				'user_id'       => $this->factory->user->create( array( 'role' => $role ) ),
				'token'         => 'test-token-' . wp_rand(),
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'test-device-' . wp_rand(),
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
			)
		);

		return (int) $token->get_id();
	}
}
