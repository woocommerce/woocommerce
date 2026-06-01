<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushNotificationRestController;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests for the PushNotificationRestController class.
 */
class PushNotificationRestControllerTest extends WC_REST_Unit_Test_Case {

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

		$this->sut = new PushNotificationRestController();
		$this->sut->register_routes();
	}

	/**
	 * @testdox Should register the send route.
	 */
	public function test_register_routes_adds_send_endpoint(): void {
		$routes = rest_get_server()->get_routes();

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
}
