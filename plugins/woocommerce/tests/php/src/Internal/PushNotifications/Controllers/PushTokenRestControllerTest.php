<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Exceptions\PushTokenNotFoundException;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use WC_REST_Unit_Test_Case;
use WP_Error;
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
	 * @var FeaturesController|MockObject
	 */
	private $features_controller_mock;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->set_up_features_controller_mock();
		$this->reset_push_notifications_cache();

		( new PushTokenRestController() )->register_routes();

		$this->user_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );

		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * @testdox Test it cannot create a push token without authentication.
	 */
	public function test_it_cannot_create_push_token_without_authentication() {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::UNAUTHORIZED, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'woocommerce_rest_cannot_view', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token without required role.
	 */
	public function test_it_cannot_create_push_token_without_required_role() {
		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
	}

	/**
	 * @testdox Test it cannot create a push token for iOS if the token is not
	 * in the correct format.
	 */
	public function test_it_cannot_create_push_token_with_invalid_ios_token() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'invalid-token' );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token for iOS with non-hex
	 * characters.
	 */
	public function test_it_cannot_create_push_token_for_ios_with_non_hex_characters() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		// Token with 'g' which is not a valid hex character.
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'g', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'test-device-uuid-nonhex' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token for iOS with wrong length.
	 */
	public function test_it_cannot_create_push_token_for_ios_with_wrong_length() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 32 ) ); // Only 32 characters instead of 64.
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'test-device-uuid-short' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token for Android if the token is
	 * not in the correct format.
	 */
	public function test_it_cannot_create_push_token_with_invalid_android_token() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'invalid token with spaces' );
		$request->set_param( 'platform', PushToken::PLATFORM_ANDROID );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_ANDROID );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token for Android if the token is
	 * too long.
	 */
	public function test_it_cannot_create_push_token_with_android_token_that_is_too_long() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 4097 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_ANDROID );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_ANDROID );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token for browser with invalid
	 * JSON token.
	 */
	public function test_it_cannot_create_push_token_for_browser_with_invalid_json() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'not-valid-json' );
		$request->set_param( 'platform', PushToken::PLATFORM_BROWSER );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token for browser with missing
	 * required keys.
	 */
	public function test_it_cannot_create_push_token_for_browser_with_missing_keys() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$token = wp_json_encode(
			array(
				'endpoint' => 'https://example.com/push',
				// Missing 'keys' array.
			)
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', $token );
		$request->set_param( 'platform', PushToken::PLATFORM_BROWSER );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token for browser with non-HTTPS
	 * endpoint.
	 */
	public function test_it_cannot_create_push_token_for_browser_with_non_https_endpoint() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$token = wp_json_encode(
			array(
				'endpoint' => 'http://example.com/push',
				'keys'     => array(
					'auth'   => 'test-auth-key',
					'p256dh' => 'test-p256dh-key',
				),
			)
		);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', $token );
		$request->set_param( 'platform', PushToken::PLATFORM_BROWSER );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token without required token
	 * parameter.
	 */
	public function test_it_cannot_create_push_token_with_a_missing_token() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token without required platform
	 * parameter.
	 */
	public function test_it_cannot_create_push_token_with_a_missing_platform() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token without required device_uuid
	 * parameter for non-browser platforms.
	 */
	public function test_it_cannot_create_push_token_for_non_browser_with_a_missing_device_uuid() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
		$this->assertStringContainsString( 'device_uuid', $data['message'] );
	}

	/**
	 * @testdox Test it cannot create a push token with invalid platform value.
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
	 * @testdox Test it cannot create a push token without required origin
	 * parameter.
	 */
	public function test_it_cannot_create_push_token_with_a_missing_origin() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'test-device-uuid' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token with invalid origin value.
	 */
	public function test_it_cannot_create_push_token_with_invalid_origin() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', 'anything' );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'test-device-uuid' );
		$request->set_param( 'origin', 'development' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Test it cannot create a push token when push notifications are
	 * disabled.
	 */
	public function test_it_cannot_create_push_token_when_push_notifications_disabled() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( false );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'test-device-uuid-123' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
	}

	/**
	 * @testdox Test it cannot create a push token with device_uuid exceeding
	 * 255 characters.
	 */
	public function test_it_cannot_create_push_token_with_device_uuid_too_long() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', str_repeat( 'a', 256 ) );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
		$this->assertStringContainsString( 'device_uuid', $data['message'] );
	}

	/**
	 * @testdox Test it cannot create a push token with device_uuid containing
	 * invalid characters.
	 */
	public function test_it_cannot_create_push_token_with_device_uuid_invalid_characters() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'invalid device uuid with spaces' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $data['code'] );
		$this->assertStringContainsString( 'device_uuid', $data['message'] );
	}

	/**
	 * @testdox Test the schema is correctly formatted.
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
			PushToken::PLATFORMS,
			$schema['properties']['platform']['enum']
		);

		$this->assertEquals(
			PushToken::ORIGINS,
			$schema['properties']['origin']['enum']
		);
	}

	/**
	 * @testdox Test convert_exception_to_wp_error hides message for generic
	 * Exception class.
	 */
	public function test_it_hides_internal_error_message_for_generic_exception() {
		$controller = new PushTokenRestController();
		$exception  = new Exception( 'Sensitive internal error details' );

		$reflection = new ReflectionClass( $controller );
		$method     = $reflection->getMethod( 'convert_exception_to_wp_error' );
		$method->setAccessible( true );

		$result = $method->invoke( $controller, $exception );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_internal_error', $result->get_error_code() );
		$this->assertEquals( 'Internal server error', $result->get_error_message() );
		$this->assertEquals( WP_Http::INTERNAL_SERVER_ERROR, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Test convert_exception_to_wp_error exposes message for
	 * PushTokenNotFoundException.
	 */
	public function test_it_exposes_message_for_push_token_not_found_exception() {
		$controller = new PushTokenRestController();
		$exception  = new PushTokenNotFoundException( 'Push token could not be found.' );

		$reflection = new ReflectionClass( $controller );
		$method     = $reflection->getMethod( 'convert_exception_to_wp_error' );
		$method->setAccessible( true );

		$result = $method->invoke( $controller, $exception );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_invalid_push_token', $result->get_error_code() );
		$this->assertEquals( 'Push token could not be found.', $result->get_error_message() );
		$this->assertEquals( WP_Http::NOT_FOUND, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Test convert_exception_to_wp_error exposes message for
	 * InvalidArgumentException.
	 */
	public function test_it_exposes_message_for_invalid_argument_exception() {
		$controller = new PushTokenRestController();
		$exception  = new InvalidArgumentException( 'Invalid argument provided.' );

		$reflection = new ReflectionClass( $controller );
		$method     = $reflection->getMethod( 'convert_exception_to_wp_error' );
		$method->setAccessible( true );

		$result = $method->invoke( $controller, $exception );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_invalid_argument', $result->get_error_code() );
		$this->assertEquals( 'Invalid argument provided.', $result->get_error_message() );
		$this->assertEquals( WP_Http::BAD_REQUEST, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Test convert_exception_to_wp_error hides message for unknown
	 * exception subclasses.
	 */
	public function test_it_hides_internal_error_message_for_unknown_exception_subclass() {
		$controller = new PushTokenRestController();
		// RuntimeException is a subclass of Exception but not in our mapping.
		$exception = new RuntimeException( 'Sensitive runtime error details' );

		$reflection = new ReflectionClass( $controller );
		$method     = $reflection->getMethod( 'convert_exception_to_wp_error' );
		$method->setAccessible( true );

		$result = $method->invoke( $controller, $exception );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_internal_error', $result->get_error_code() );
		$this->assertEquals( 'Internal server error', $result->get_error_message() );
		$this->assertEquals( WP_Http::INTERNAL_SERVER_ERROR, $result->get_error_data()['status'] );
	}

	/**
	 * Sets up the Jetpack connection manager mocking, and ensures the
	 * PushNotifications class state is reset so `should_be_enabled` calculates
	 * this from scratch.
	 *
	 * @param bool $is_connected Whether the manager should report Jetpack is
	 * connected or not.
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

		$this->reset_push_notifications_cache();
	}

	/**
	 * Sets up the FeaturesController mock to enable push_notifications feature.
	 */
	private function set_up_features_controller_mock() {
		$this->features_controller_mock = $this
			->getMockBuilder( FeaturesController::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'feature_is_enabled' ) )
			->getMock();

		$this->features_controller_mock
			->method( 'feature_is_enabled' )
			->willReturnCallback(
				function ( $feature_id ) {
					return PushNotifications::FEATURE_NAME === $feature_id;
				}
			);

		wc_get_container()->replace( FeaturesController::class, $this->features_controller_mock );
	}

	/**
	 * Resets the cached enablement state on the container's PushNotifications
	 * instance.
	 */
	private function reset_push_notifications_cache() {
		$push_notifications = wc_get_container()->get( PushNotifications::class );
		$reflection         = new ReflectionClass( $push_notifications );
		$property           = $reflection->getProperty( 'enabled' );

		$property->setAccessible( true );
		$property->setValue( $push_notifications, null );
	}
}
