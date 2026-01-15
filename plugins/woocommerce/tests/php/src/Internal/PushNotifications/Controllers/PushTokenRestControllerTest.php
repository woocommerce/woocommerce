<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Proxies\LegacyProxy;
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
	 * @var PushTokenRestController
	 */
	private $controller;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->set_up_features_controller_mock();
		$this->reset_push_notifications_cache();

		$this->controller = new PushTokenRestController();
		$this->user_id    = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
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
	 * @testdox Test authorize returns false when no user is logged in.
	 */
	public function test_authorize_returns_false_without_authentication() {
		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$result  = $this->controller->authorize( $request );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox Test authorize returns false when push notifications are disabled.
	 */
	public function test_authorize_returns_false_when_push_notifications_disabled() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( false );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$result  = $this->controller->authorize( $request );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox Test authorize returns false when user doesn't have required role.
	 */
	public function test_authorize_returns_false_without_required_role() {
		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$result  = $this->controller->authorize( $request );

		$this->assertFalse( $result );
	}

	/**
	 * @testdox Test authorize returns true for shop_manager role.
	 */
	public function test_authorize_returns_true_for_shop_manager() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$result  = $this->controller->authorize( $request );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Test authorize returns true for administrator role.
	 */
	public function test_authorize_returns_true_for_administrator() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$result  = $this->controller->authorize( $request );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Test authorize returns error when token ID doesn't exist.
	 */
	public function test_authorize_returns_error_when_token_not_found() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'DELETE', '/wc-push-notifications/push-tokens/999999' );
		$request->set_param( 'id', 999999 );
		$result = $this->controller->authorize( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_invalid_push_token', $result->get_error_code() );
		$this->assertEquals( WP_Http::NOT_FOUND, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Test authorize returns error when token belongs to another user.
	 */
	public function test_authorize_returns_error_when_token_belongs_to_another_user() {
		/**
		 * Create a token for another shop manager.
		 */
		$other_user_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );

		$push_token = new PushToken();
		$push_token->set_user_id( $other_user_id );
		$push_token->set_token( str_repeat( 'a', 64 ) );
		$push_token->set_platform( PushToken::PLATFORM_APPLE );
		$push_token->set_device_uuid( 'device-other-user' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$data_store = wc_get_container()->get( PushTokensDataStore::class );
		$data_store->create( $push_token );
		$token_id = $push_token->get_id();

		/**
		 * Try to authorize as a different user.
		 */
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'DELETE', '/wc-push-notifications/push-tokens/' . $token_id );
		$request->set_param( 'id', $token_id );

		$result = $this->controller->authorize( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'rest_invalid_push_token', $result->get_error_code() );
		$this->assertEquals( WP_Http::NOT_FOUND, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Test authorize returns true when token belongs to current user.
	 */
	public function test_authorize_returns_true_when_token_belongs_to_current_user() {
		wp_set_current_user( $this->user_id );

		$this->mock_jetpack_connection_manager_is_connected( true );

		$push_token = new PushToken();
		$push_token->set_user_id( $this->user_id );
		$push_token->set_token( str_repeat( 'a', 64 ) );
		$push_token->set_platform( PushToken::PLATFORM_APPLE );
		$push_token->set_device_uuid( 'device-current-user' );
		$push_token->set_origin( PushToken::ORIGIN_WOOCOMMERCE_IOS );

		$data_store = wc_get_container()->get( PushTokensDataStore::class );
		$data_store->create( $push_token );
		$token_id = $push_token->get_id();

		$request = new WP_REST_Request( 'DELETE', '/wc-push-notifications/push-tokens/' . $token_id );
		$request->set_param( 'id', $token_id );

		$result = $this->controller->authorize( $request );

		$this->assertTrue( $result );
	}

	/**
	 * @testdox Test the schema is correctly formatted.
	 */
	public function test_get_schema_returns_correct_structure() {
		$schema = $this->controller->get_schema();

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
