<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\NotificationPreferencesRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use WC_REST_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;

/**
 * Tests for the NotificationPreferencesRestController class.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
class NotificationPreferencesRestControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * Shop manager user ID for testing.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Subscriber user ID for testing.
	 *
	 * @var int
	 */
	private $subscriber_id;

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

		( new NotificationPreferencesRestController() )->register_routes();

		$this->user_id       = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		$this->subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );

		delete_user_meta( $this->user_id, NotificationPreferencesService::META_KEY );
		wp_delete_user( $this->user_id );
		wp_delete_user( $this->subscriber_id );

		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * @testdox GET should reject unauthenticated requests.
	 */
	public function test_get_preferences_requires_authentication() {
		$this->mock_jetpack_connection_manager_is_connected( true );

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/preferences' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * @testdox GET should reject users without a push-notifications role.
	 */
	public function test_get_preferences_rejects_users_without_role() {
		wp_set_current_user( $this->subscriber_id );
		$this->mock_jetpack_connection_manager_is_connected( true );

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/preferences' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::FORBIDDEN, $response->get_status() );
	}

	/**
	 * @testdox GET should return the current user's preferences merged with defaults.
	 */
	public function test_get_preferences_returns_user_preferences() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );

		wc_get_container()
			->get( NotificationPreferencesService::class )
			->save_preferences( $this->user_id, array( 'store_order' => false ) );

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/preferences' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'store_order', $data );
		$this->assertFalse( $data['store_order'] );
		$this->assertArrayHasKey( 'store_review', $data );
		$this->assertTrue( $data['store_review'] );
	}

	/**
	 * @testdox POST should persist new preferences to the authenticated user.
	 */
	public function test_post_preferences_updates_settings() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', false );
		$request->set_param( 'store_review', false );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$stored = get_user_meta( $this->user_id, NotificationPreferencesService::META_KEY, true );
		$this->assertIsArray( $stored );
		$this->assertFalse( $stored['preferences']['store_order'] );
		$this->assertFalse( $stored['preferences']['store_review'] );
	}

	/**
	 * @testdox POST should reject non-boolean values via the REST validation layer.
	 */
	public function test_post_preferences_validates_input() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', 'not-a-boolean' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * @testdox PATCH should be accepted and update preferences like POST.
	 */
	public function test_patch_preferences_updates_settings() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );

		$request = new WP_REST_Request( 'PATCH', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', false );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$stored = get_user_meta( $this->user_id, NotificationPreferencesService::META_KEY, true );
		$this->assertFalse( $stored['preferences']['store_order'] );
	}

	/**
	 * @testdox POST should return the merged preferences after partial update.
	 */
	public function test_post_preferences_returns_merged_result() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );

		wc_get_container()
			->get( NotificationPreferencesService::class )
			->save_preferences(
				$this->user_id,
				array(
					'store_order'  => false,
					'store_review' => false,
				)
			);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_review', true );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['store_order'] );
		$this->assertTrue( $data['store_review'] );
	}

	/**
	 * @testdox Should not collide with PushTokenRestController on the WC REST namespaces filter.
	 *
	 * Both controllers share the URL route namespace `wc-push-notifications`, but they must use
	 * distinct class identifiers via `get_rest_api_namespace()` so that neither overwrites the
	 * other in the `woocommerce_rest_api_get_rest_namespaces` filter output.
	 */
	public function test_does_not_overwrite_sibling_controller_in_rest_namespaces_filter() {
		$preferences_controller = new NotificationPreferencesRestController();
		$push_token_controller  = new PushTokenRestController();

		$preferences_controller->register();
		$push_token_controller->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Triggering an existing filter from RestApiControllerBase, not defining one.
		$namespaces = apply_filters( 'woocommerce_rest_api_get_rest_namespaces', array( 'wc/v3' => array() ) );

		$this->assertArrayHasKey( 'wc/v3', $namespaces );

		$registered_classes = array_values( $namespaces['wc/v3'] );

		$this->assertContains( NotificationPreferencesRestController::class, $registered_classes );
		$this->assertContains( PushTokenRestController::class, $registered_classes );
	}

	/**
	 * Mocks the JetpackConnectionManager class to report whether Jetpack is connected.
	 *
	 * @param bool $is_connected Whether the manager should report Jetpack is connected.
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
	 * Sets up the FeaturesController mock to enable the push_notifications feature.
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
	 * Resets the cached enablement state on the container's PushNotifications instance.
	 */
	private function reset_push_notifications_cache() {
		$push_notifications = wc_get_container()->get( PushNotifications::class );
		$reflection         = new ReflectionClass( $push_notifications );
		$property           = $reflection->getProperty( 'enabled' );

		$property->setAccessible( true );
		$property->setValue( $push_notifications, null );
	}
}
