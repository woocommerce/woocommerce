<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\WooCommerce\Internal\PushNotifications\Controllers\NotificationPreferencesRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\NotificationPreferencesDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Helpers\PushNotificationsTestTrait;
use WC_Data_Exception;
use WC_REST_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;

/**
 * Tests for the NotificationPreferencesRestController class.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
class NotificationPreferencesRestControllerTest extends WC_REST_Unit_Test_Case {
	use PushNotificationsTestTrait;

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

		delete_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY );
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

		$stored = get_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY, true );
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

		$stored = get_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY, true );
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
	 * @testdox POST should return a 500 when the service throws a persistence error.
	 */
	public function test_post_preferences_returns_500_when_service_throws() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );

		$service_mock = $this->createMock( NotificationPreferencesService::class );
		$service_mock->method( 'get_defaults' )->willReturn(
			array(
				'store_order'  => true,
				'store_review' => true,
			)
		);
		$service_mock->method( 'save_preferences' )->willThrowException(
			new WC_Data_Exception(
				'woocommerce_push_notification_preferences_save_failed',
				'Failed to save push notification preferences.',
				WP_Http::INTERNAL_SERVER_ERROR
			)
		);

		wc_get_container()->replace( NotificationPreferencesService::class, $service_mock );

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_review', false );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::INTERNAL_SERVER_ERROR, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertSame( 'woocommerce_internal_error', $data['code'] );
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
}
