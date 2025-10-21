<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_REST_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;

/**
 * Tests for the PushTokenRestController class.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
class PushTokenRestControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * User ID for testing.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * @var JetpackConnectionManager|MockObject
	 */
	private $jetpack_connection_manager_mock;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		( new PushTokenRestController() )->register_routes();

		$this->user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
	}

	/**
	 * Test it can create a push token for iOS.
	 */
	public function test_it_can_create_push_token_for_ios() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid-123' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertIsInt( $data['id'] );
		$this->assertGreaterThan( 0, $data['id'] );
	}

	/**
	 * Test it can create a push token for Android.
	 */
	public function test_it_can_create_push_token_for_android() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'test_android_token_123' );
		$request->set_param( 'platform', PushToken::PLATFORM_ANDROID );
		$request->set_param( 'device_uuid', 'test-device-uuid-456' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertIsInt( $data['id'] );
		$this->assertGreaterThan( 0, $data['id'] );
	}

	/**
	 * Test it can create a push token for browsers.
	 */
	public function test_it_can_create_push_token_for_browser() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$browser_token = wp_json_encode(
			array(
				'endpoint' => 'https://example.com/push/subscription123',
				'keys'     => array(
					'auth'   => 'test_auth_key',
					'p256dh' => 'test_p256_key',
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', $browser_token );
		$request->set_param( 'platform', PushToken::PLATFORM_BROWSER );
		$request->set_param( 'device_uuid', 'test-device-uuid-456' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertIsInt( $data['id'] );
		$this->assertGreaterThan( 0, $data['id'] );
	}

	/**
	 * Test it can create a push token for browsers without device_uuid.
	 */
	public function test_it_can_create_push_token_for_browser_without_device_uuid() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$browser_token = wp_json_encode(
			array(
				'endpoint' => 'https://example.com/push/subscription789',
				'keys'     => array(
					'auth'   => 'test_auth_key_2',
					'p256dh' => 'test_p256_key_2',
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', $browser_token );
		$request->set_param( 'platform', PushToken::PLATFORM_BROWSER );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertIsInt( $data['id'] );
		$this->assertGreaterThan( 0, $data['id'] );
	}

	/**
	 * Test it updates an existing push token by token value.
	 */
	public function test_it_updates_existing_token_by_token_value() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$token_value = str_repeat( 'b', 64 );

		/**
		 * Create initial token.
		 */
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', $token_value );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'device-1' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$first_id = $response->get_data()['id'];

		/**
		 * Create again with the same token but different device UUID.
		 */
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', $token_value );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'device-2' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$second_id = $response->get_data()['id'];

		$this->assertEquals( $first_id, $second_id );
	}

	/**
	 * Test it updates an existing push token by device UUID.
	 */
	public function test_it_updates_existing_token_by_device_uuid() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$device_uuid = 'device-uuid-constant';

		/**
		 * Create initial token.
		 */
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'c', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', $device_uuid );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$first_id = $response->get_data()['id'];

		/**
		 * Create again with different token but same device UUID.
		 */
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'd', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', $device_uuid );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$second_id = $response->get_data()['id'];

		$this->assertEquals( $first_id, $second_id );
	}

	/**
	 * Test it cannot create a push token without authentication.
	 */
	public function test_it_cannot_create_push_token_without_authentication() {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'e', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::UNAUTHORIZED, $response->get_status() );
	}

	/**
	 * Test it can create a push token for iOS with uppercase hex token.
	 */
	public function test_it_can_create_push_token_for_ios_with_uppercase_hex() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'A', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid-uppercase' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertIsInt( $data['id'] );
		$this->assertGreaterThan( 0, $data['id'] );
	}

	/**
	 * Test it can create a push token for iOS with mixed case hex token.
	 */
	public function test_it_can_create_push_token_for_ios_with_mixed_case_hex() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'aB', 32 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid-mixed' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::CREATED, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertIsInt( $data['id'] );
		$this->assertGreaterThan( 0, $data['id'] );
	}

	/**
	 * Test it cannot create a push token for iOS if the token is not in the
	 * correct format.
	 */
	public function test_it_cannot_create_push_token_with_invalid_ios_token() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'invalid-token' );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token for iOS with non-hex characters.
	 */
	public function test_it_cannot_create_push_token_for_ios_with_non_hex_characters() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		// Token with 'g' which is not a valid hex character.
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'g', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid-nonhex' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token for iOS with wrong length.
	 */
	public function test_it_cannot_create_push_token_for_ios_with_wrong_length() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 32 ) ); // Only 32 characters instead of 64.
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid-short' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token for Android if the token is not in the
	 * correct format.
	 */
	public function test_it_cannot_create_push_token_with_invalid_android_token() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'invalid token with spaces' );
		$request->set_param( 'platform', PushToken::PLATFORM_ANDROID );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token for Android if the token is too long.
	 */
	public function test_it_cannot_create_push_token_with_android_token_that_is_too_long() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 4097 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_ANDROID );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token for browser if the token is not valid
	 * JSON.
	 */
	public function test_it_cannot_create_push_token_if_browser_token_is_invalid_json() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'not-valid-json' );
		$request->set_param( 'platform', PushToken::PLATFORM_BROWSER );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token for browser if the token is missing an
	 * endpoint property.
	 */
	public function test_it_cannot_create_push_token_if_browser_token_is_missing_endpoint() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$browser_token = wp_json_encode(
			array(
				'keys' => array(
					'auth'   => 'test_auth_key',
					'p256dh' => 'test_p256_key',
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', $browser_token );
		$request->set_param( 'platform', PushToken::PLATFORM_BROWSER );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token for browser if the endpoint contains
	 * invalid characters.
	 */
	public function test_it_cannot_create_push_token_if_browser_token_contains_invalid_characters() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$browser_token = wp_json_encode(
			array(
				'endpoint' => 'invalid endpoint with spaces',
				'keys'     => array(
					'auth'   => 'test_auth_key',
					'p256dh' => 'test_p256_key',
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', $browser_token );
		$request->set_param( 'platform', PushToken::PLATFORM_BROWSER );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token for browser if the endpoint is not HTTPS.
	 */
	public function test_it_cannot_create_push_token_if_browser_token_endpoint_is_not_https() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$browser_token = wp_json_encode(
			array(
				'endpoint' => 'http://example.com/push/subscription123',
				'keys'     => array(
					'auth'   => 'test_auth_key',
					'p256dh' => 'test_p256_key',
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', $browser_token );
		$request->set_param( 'platform', PushToken::PLATFORM_BROWSER );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token without required token parameter.
	 */
	public function test_it_cannot_create_push_token_with_a_missing_token() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token without required platform parameter.
	 */
	public function test_it_cannot_create_push_token_with_a_missing_platform() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'f', 64 ) );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token without required device_uuid
	 * parameter for non-browser platforms.
	 */
	public function test_it_cannot_create_push_token_for_non_browser_with_a_missing_device_uuid() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'g', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
		$this->assertStringContainsString( 'device_uuid', $data['message'] );
	}

	/**
	 * Test it cannot create a push token with invalid platform value.
	 */
	public function test_it_cannot_create_push_token_with_invalid_platform() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'anything' );
		$request->set_param( 'platform', 'windows' );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token without required origin
	 * parameter.
	 */
	public function test_it_cannot_create_push_token_with_a_missing_origin() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'h', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * Test it cannot create a push token with invalid origin value.
	 */
	public function test_it_cannot_create_push_token_with_invalid_origin() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'anything' );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', 'development' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * Test it can delete a push token.
	 */
	public function test_it_can_delete_push_token() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		/**
		 * Create a token first.
		 */
		$push_token = new PushToken();
		$push_token->set_user_id( $this->user_id );
		$push_token->set_token( str_repeat( 'i', 64 ) );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'device-to-delete' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$data_store = wc_get_container()->get( PushTokensDataStore::class );
		$data_store->create( $push_token );
		$token_id = $push_token->get_id();

		/**
		 * Delete the token.
		 */
		$request  = new WP_REST_Request( 'DELETE', '/wc-push-notifications/push-tokens/' . $token_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::NO_CONTENT, $response->get_status() );

		$this->assertNull( $response->get_data() );
	}

	/**
	 * Test it can't delete a push token without being authenticated.
	 */
	public function test_it_cannot_delete_push_token_without_authentication() {
		$request  = new WP_REST_Request( 'DELETE', '/wc-push-notifications/push-tokens/123' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::UNAUTHORIZED, $response->get_status() );
	}

	/**
	 * Test it can't delete a push token that doesn't belong to the
	 * authenticated user.
	 */
	public function test_it_cannot_delete_push_token_belonging_to_another_user() {
		/**
		 * Create a token for user 1.
		 */
		$other_user_id = $this->factory->user->create( array( 'role' => 'customer' ) );

		$push_token = new PushToken();
		$push_token->set_user_id( $other_user_id );
		$push_token->set_token( str_repeat( 'j', 64 ) );
		$push_token->set_platform( PushToken::PLATFORM_IOS );
		$push_token->set_device_uuid( 'device-other-user' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$data_store = wc_get_container()->get( PushTokensDataStore::class );
		$data_store->create( $push_token );
		$token_id = $push_token->get_id();

		/**
		 * Try to delete as a different user.
		 */
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request  = new WP_REST_Request( 'DELETE', '/wc-push-notifications/push-tokens/' . $token_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_push_token', $data['code'] );
		$this->assertEquals( 'Push token could not be found.', $data['message'] );
	}

	/**
	 * Test it gets 404 response trying to delete a push token that doesn't
	 * exist.
	 */
	public function test_it_cannot_delete_push_token_that_does_not_exist() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request  = new WP_REST_Request( 'DELETE', '/wc-push-notifications/push-tokens/999999' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_push_token', $data['code'] );
		$this->assertEquals( 'Push token could not be found.', $data['message'] );
	}

	/**
	 * Test it cannot create a push token when push notifications are
	 * disabled.
	 *
	 * @skip Temporarily skipped because PushNotifications::should_be_enabled() is hardcoded to return true for testing.
	 */
	public function test_it_cannot_create_push_token_when_push_notifications_disabled() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( false );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_IOS );
		$request->set_param( 'device_uuid', 'test-device-uuid-123' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
	}

	/**
	 * Test the schema is correctly formatted.
	 */
	public function test_get_schema_returns_correct_structure() {
		$controller = new PushTokenRestController();
		$schema     = $controller->get_schema();

		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertEquals( PushToken::POST_TYPE, $schema['title'] );

		$this->assertArrayHasKey( 'token', $schema['properties'] );
		$this->assertArrayHasKey( 'platform', $schema['properties'] );
		$this->assertArrayHasKey( 'device_uuid', $schema['properties'] );
		$this->assertArrayHasKey( 'origin', $schema['properties'] );
		$this->assertArrayHasKey( 'enum', $schema['properties']['platform'] );
		$this->assertArrayHasKey( 'enum', $schema['properties']['origin'] );

		$this->assertArrayNotHasKey( 'validate_callback', $schema['properties']['token'] );
		$this->assertArrayNotHasKey( 'validate_callback', $schema['properties']['platform'] );
		$this->assertArrayNotHasKey( 'validate_callback', $schema['properties']['device_uuid'] );
		$this->assertArrayNotHasKey( 'validate_callback', $schema['properties']['origin'] );

		$this->assertEquals( 'string', $schema['properties']['token']['type'] );
		$this->assertEquals( 'string', $schema['properties']['platform']['type'] );
		$this->assertEquals( 'string', $schema['properties']['device_uuid']['type'] );
		$this->assertEquals( 'string', $schema['properties']['origin']['type'] );

		$this->assertEquals(
			array(
				PushToken::PLATFORM_IOS,
				PushToken::PLATFORM_ANDROID,
				PushToken::PLATFORM_BROWSER,
			),
			$schema['properties']['platform']['enum']
		);

		$this->assertEquals(
			array(
				PushToken::ORIGIN_WOOCOMMERCE_ANDROID,
				PushToken::ORIGIN_WOOCOMMERCE_ANDROID_DEV,
				PushToken::ORIGIN_WOOCOMMERCE_IOS,
				PushToken::ORIGIN_WOOCOMMERCE_IOS_DEV,
			),
			$schema['properties']['origin']['enum']
		);
	}

	/**
	 * Sets up the Jetpack connection manager mocking.
	 *
	 * @param bool $is_connected Whether the manager should report Jetpack is connected or not.
	 */
	private function mock_jetpack_connection_manager_is_connected( bool $is_connected = true ) {
		$this->jetpack_connection_manager_mock = $this
			->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_connected' ) )
			->getMock();

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $this->jetpack_connection_manager_mock )
		);

		$this->jetpack_connection_manager_mock
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( $is_connected );
	}
}
