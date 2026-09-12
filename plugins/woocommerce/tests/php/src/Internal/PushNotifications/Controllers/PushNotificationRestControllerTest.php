<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushNotificationRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Dispatchers\InternalNotificationDispatcher;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use WC_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests for the PushNotificationRestController class.
 */
class PushNotificationRestControllerTest extends WC_Unit_Test_Case {

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
	 * Builds a request shaped like the one the dispatcher sends: a JSON POST
	 * whose credential is in the query string. The content type matters,
	 * because {@see WP_REST_Request::set_param()} writes into whichever bucket
	 * the parameter order puts first, so without it a parameter set here would
	 * land in the POST body rather than the query string and the test would
	 * pass without exercising the URL at all.
	 *
	 * @param string $body       The request body.
	 * @param string $token      The credential, or an empty string to omit it.
	 * @param string $auth_token Authorization header credential, or an empty string to omit the header.
	 * @return WP_REST_Request
	 */
	private function build_request( string $body, string $token = '', string $auth_token = '' ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( $body );

		if ( '' !== $token ) {
			$request->set_query_params( array( InternalNotificationDispatcher::TOKEN_QUERY_PARAM => $token ) );
		}

		if ( '' !== $auth_token ) {
			$request->set_header( 'Authorization', 'Bearer ' . $auth_token );
		}

		return $request;
	}

	/**
	 * Builds a token valid for the given body.
	 *
	 * @param string $body The body the token is signed over.
	 * @return string
	 */
	private function build_token( string $body ): string {
		return JsonWebToken::create(
			array(
				'iss'       => get_site_url(),
				'exp'       => time() + 30,
				'body_hash' => hash( 'sha256', $body ),
			),
			wp_salt( 'auth' )
		);
	}

	/**
	 * @testdox Should accept a valid JWT supplied via the token query parameter when the header is absent.
	 */
	public function test_authorize_accepts_valid_jwt_via_query_param(): void {
		$body = '{"notifications":[]}';

		$result = $this->sut->authorize( $this->build_request( $body, $this->build_token( $body ) ) );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Should reject an invalid JWT supplied via the token query parameter.
	 */
	public function test_authorize_rejects_invalid_jwt_via_query_param(): void {
		$result = $this->sut->authorize( $this->build_request( '{}', 'invalid.token.here' ) );

		$this->assertWPError( $result );
	}

	/**
	 * The credential is sent in the URL, so the request body must never be
	 * consulted for it. WP_REST_Request::get_param() searches the JSON body
	 * before the query string, so reading the token that way would find this
	 * one. Asserting on the missing-credential message rather than only on the
	 * error proves the body was not read, since a body that was read would
	 * produce a different rejection reason.
	 *
	 * @testdox Should not read the credential from the request body.
	 */
	public function test_authorize_does_not_read_credential_from_body(): void {
		$body = (string) wp_json_encode(
			array(
				'notifications' => array(),
				InternalNotificationDispatcher::TOKEN_QUERY_PARAM => $this->build_token( '{}' ),
			)
		);

		$result = $this->sut->authorize( $this->build_request( $body ) );

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'Missing credential', $result->get_error_message() );
	}

	/**
	 * @testdox Should reject an array-valued token query parameter without a type error.
	 */
	public function test_authorize_rejects_array_token_query_param(): void {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/send' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( '{}' );
		$request->set_query_params( array( InternalNotificationDispatcher::TOKEN_QUERY_PARAM => array( 'a', 'b' ) ) );

		$result = $this->sut->authorize( $request );

		$this->assertWPError( $result );
	}

	/**
	 * @testdox Should prefer the Authorization header over the token query parameter.
	 */
	public function test_authorize_prefers_header_over_query_param(): void {
		$body = '{"notifications":[]}';

		$result = $this->sut->authorize(
			$this->build_request( $body, $this->build_token( $body ), 'invalid.token.here' )
		);

		$this->assertWPError( $result );
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
