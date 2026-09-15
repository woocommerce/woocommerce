<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushNotificationRestController;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use WC_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests for the PushNotificationRestController class.
 */
class PushNotificationRestControllerTest extends WC_Unit_Test_Case {
	use LoggerSpyTrait;


	/**
	 * REST server used to verify route registration.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * The System Under Test.
	 *
	 * @var PushNotificationRestController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut    = new PushNotificationRestController();
		$this->server = $this->create_rest_server_with_routes(
			array( array( $this->sut, 'register_routes' ) ),
			true
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->clear_rest_server();
		unset( $this->server, $this->sut );

		parent::tearDown();
	}

	/**
	 * @testdox Should register the send route.
	 */
	public function test_register_routes_adds_send_endpoint(): void {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey(
			'/wc-push-notifications/send',
			$routes,
			'Send route should be registered'
		);
	}

	/**
	 * @testdox Should reject requests without an authorization header.
	 */
	public function test_authorize_rejects_missing_header(): void {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_body( '{}' );

		$result = $this->sut->authorize( $request );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_rest_unauthorized', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject requests with an invalid JWT.
	 */
	public function test_authorize_rejects_invalid_jwt(): void {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_header( 'Authorization', 'Bearer invalid.token.here' );
		$request->set_body( '{}' );

		$result = $this->sut->authorize( $request );

		$this->assertWPError( $result );
	}

	/**
	 * @testdox Should reject requests with a mismatched body hash.
	 */
	public function test_authorize_rejects_body_hash_mismatch(): void {
		$token = JsonWebToken::create(
			array(
				'iss'       => get_site_url(),
				'exp'       => time() + 30,
				'body_hash' => hash( 'sha256', 'original body' ),
			),
			wp_salt( 'auth' )
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_header( 'Authorization', 'Bearer ' . $token );
		$request->set_body( 'tampered body' );

		$result = $this->sut->authorize( $request );

		$this->assertWPError( $result );
	}

	/**
	 * @testdox Should reject requests with a wrong issuer.
	 */
	public function test_authorize_rejects_wrong_issuer(): void {
		$body  = '{"notifications":[]}';
		$token = JsonWebToken::create(
			array(
				'iss'       => 'https://evil.example.com',
				'exp'       => time() + 30,
				'body_hash' => hash( 'sha256', $body ),
			),
			wp_salt( 'auth' )
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_header( 'Authorization', 'Bearer ' . $token );
		$request->set_body( $body );

		$result = $this->sut->authorize( $request );

		$this->assertWPError( $result );
	}

	/**
	 * @testdox Should accept a valid JWT with correct issuer and body hash.
	 */
	public function test_authorize_accepts_valid_jwt(): void {
		$body  = '{"notifications":[]}';
		$token = JsonWebToken::create(
			array(
				'iss'       => get_site_url(),
				'exp'       => time() + 30,
				'body_hash' => hash( 'sha256', $body ),
			),
			wp_salt( 'auth' )
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_header( 'Authorization', 'Bearer ' . $token );
		$request->set_body( $body );

		$result = $this->sut->authorize( $request );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Should return success when no notifications are provided.
	 */
	public function test_create_returns_success_for_empty_notifications(): void {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_body( '{}' );

		$result = $this->sut->create( $request );

		$this->assertSame( 200, $result->get_status() );
		$this->assertTrue( $result->get_data()['success'] );
	}

	/**
	 * @testdox Should return 200 when notifications are provided.
	 */
	public function test_create_returns_ok_with_notifications(): void {
		$order   = wc_create_order( array( 'status' => 'processing' ) );
		$body    = wp_json_encode(
			array(
				'notifications' => array(
					array(
						'type'        => 'store_order',
						'resource_id' => $order->get_id(),
					),
				),
			)
		);
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_body( $body );

		$result = $this->sut->create( $request );

		$this->assertSame( 200, $result->get_status() );
		$this->assertTrue( $result->get_data()['success'] );
	}

	/**
	 * @testdox Should log a refused request only when the body carries a notifications key.
	 */
	public function test_authorize_logs_refusals_only_for_loopback_shaped_bodies(): void {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_body( '{"notifications":[{"type":"store_order","resource_id":1}]}' );
		$this->sut->authorize( $request );

		$this->assertLogged(
			'warning',
			'Loopback request refused: Missing authorization header.',
			array(
				'source'        => PushNotifications::FEATURE_NAME,
				'step'          => 'received',
				'outcome'       => 'auth_failed',
				'reason'        => PushNotificationRestController::AUTH_FAILURE_CREDENTIAL_MISSING,
				'notifications' => 1,
			)
		);

		$this->captured_logs = array();
		$scan                = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$scan->set_body( '{"anything":"else"}' );
		$this->sut->authorize( $scan );

		$this->assertSame( array(), $this->captured_logs, 'A refused request without our body must not be logged.' );
	}

	/**
	 * @testdox Should log a malformed body with the step fields.
	 */
	public function test_create_logs_malformed_body(): void {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_body( '{}' );

		$this->sut->create( $request );

		$this->assertLogged(
			'warning',
			'empty or missing notifications array',
			array(
				'source'  => PushNotifications::FEATURE_NAME,
				'step'    => 'received',
				'outcome' => 'malformed_body',
			)
		);
	}

	/**
	 * @testdox Should log a notification that cannot be built, with the type and resource it named.
	 */
	public function test_create_logs_invalid_notification(): void {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_body( '{"notifications":[{"type":"unknown_type","resource_id":7}]}' );

		$result = $this->sut->create( $request );

		$this->assertSame( 200, $result->get_status() );
		$this->assertLogged(
			'error',
			'Failed to process notification:',
			array(
				'source'      => PushNotifications::FEATURE_NAME,
				'step'        => 'received',
				'outcome'     => 'invalid_notification',
				'type'        => 'unknown_type',
				'resource_id' => 7,
			)
		);
	}
}
