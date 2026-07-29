<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\WooCommerce\Internal\PushNotifications\Controllers\NotificationPreferencesRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\NotificationPreferencesDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use Automattic\WooCommerce\Internal\Utilities\Users;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Helpers\PushNotificationsTestTrait;
use WC_Data_Exception;
use WC_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTest_Factory;

/**
 * Tests for the NotificationPreferencesRestController class.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
class NotificationPreferencesRestControllerTest extends WC_Unit_Test_Case {
	use PushNotificationsTestTrait;

	/**
	 * REST server used to dispatch notification preference requests.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Shop manager fixture user ID.
	 *
	 * @var int
	 */
	private static $fixture_user_id;

	/**
	 * Subscriber fixture user ID.
	 *
	 * @var int
	 */
	private static $fixture_subscriber_id;

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
	 * Create immutable users shared by the test class.
	 *
	 * @param WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$fixture_user_id       = $factory->user->create( array( 'role' => 'shop_manager' ) );
		self::$fixture_subscriber_id = $factory->user->create( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->reset_push_notifications_cache();

		$this->user_id       = self::$fixture_user_id;
		$this->subscriber_id = self::$fixture_subscriber_id;
	}

	/**
	 * Register the controller's routes using the container so init() auto-wires the service
	 * and push-notifications dependencies. Tests call this after setting up any container
	 * mocks they need (e.g. replacing the service) so the resolved controller picks them up.
	 */
	private function register_routes(): void {
		$controller   = wc_get_container()->get( NotificationPreferencesRestController::class );
		$this->server = $this->create_rest_server_with_routes(
			array( array( $controller, 'register_routes' ) ),
			true
		);
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );

		Users::delete_site_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY );

		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();
		$this->clear_rest_server();
		unset( $this->server );

		parent::tearDown();
	}

	/**
	 * @testdox GET should reject unauthenticated requests.
	 */
	public function test_get_preferences_requires_authentication() {
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

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
		$this->register_routes();

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
		$this->register_routes();

		wc_get_container()
			->get( NotificationPreferencesService::class )
			->save_preferences(
				$this->user_id,
				array( 'store_order' => array( 'enabled' => false ) )
			);

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/preferences' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'store_order', $data );
		$this->assertFalse( $data['store_order']['enabled'] );
		$this->assertArrayHasKey( 'store_review', $data );
		$this->assertTrue( $data['store_review']['enabled'] );
	}

	/**
	 * @testdox POST should persist new preferences to the authenticated user.
	 */
	public function test_post_preferences_updates_settings() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', array( 'enabled' => false ) );
		$request->set_param( 'store_review', array( 'enabled' => false ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$stored = Users::get_site_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY );
		$this->assertIsArray( $stored );
		$this->assertFalse( $stored['preferences']['store_order']['enabled'] );
		$this->assertFalse( $stored['preferences']['store_review']['enabled'] );
	}

	/**
	 * @testdox POST should reject non-object values via the REST validation layer.
	 */
	public function test_post_preferences_rejects_non_object_value() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', 'not-an-object' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * @testdox POST should reject non-boolean `enabled` sub-fields via the REST validation layer.
	 */
	public function test_post_preferences_rejects_non_boolean_enabled() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', array( 'enabled' => 'not-a-boolean' ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * @testdox PATCH should be accepted and update preferences like POST.
	 */
	public function test_patch_preferences_updates_settings() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'PATCH', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', array( 'enabled' => false ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$stored = Users::get_site_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY );
		$this->assertFalse( $stored['preferences']['store_order']['enabled'] );
	}

	/**
	 * @testdox POST should return the merged preferences after partial update.
	 */
	public function test_post_preferences_returns_merged_result() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		wc_get_container()
			->get( NotificationPreferencesService::class )
			->save_preferences(
				$this->user_id,
				array(
					'store_order'  => array( 'enabled' => false ),
					'store_review' => array( 'enabled' => false ),
				)
			);

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_review', array( 'enabled' => true ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['store_order']['enabled'] );
		$this->assertTrue( $data['store_review']['enabled'] );
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
				'store_order'  => array( 'enabled' => true ),
				'store_review' => array( 'enabled' => true ),
			)
		);
		$internal_code    = 'woocommerce_push_notification_preferences_save_failed';
		$internal_message = 'Failed to save push notification preferences.';

		$service_mock->method( 'save_preferences' )->willThrowException(
			new WC_Data_Exception(
				$internal_code,
				$internal_message,
				WP_Http::INTERNAL_SERVER_ERROR
			)
		);

		wc_get_container()->replace( NotificationPreferencesService::class, $service_mock );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_review', array( 'enabled' => false ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::INTERNAL_SERVER_ERROR, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertSame( 'woocommerce_internal_error', $data['code'] );

		// Internal exception details must not be leaked to API clients.
		$this->assertNotSame( $internal_code, $data['code'] );
		$serialized = wp_json_encode( $data );
		$this->assertStringNotContainsString( $internal_code, (string) $serialized );
		$this->assertStringNotContainsString( $internal_message, (string) $serialized );
	}

	/**
	 * @testdox POST should reject non-numeric min_amount via the REST validation layer.
	 */
	public function test_post_preferences_rejects_non_numeric_min_amount() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', array( 'min_amount' => 'not-a-number' ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * @testdox POST should reject non-positive min_amount via the REST validation layer.
	 *
	 * @testWith [-10]
	 *           [0]
	 *
	 * @param int|float $value The invalid value.
	 */
	public function test_post_preferences_rejects_non_positive_min_amount( $value ) {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', array( 'min_amount' => $value ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * @testdox POST should accept a positive min_amount and persist it.
	 */
	public function test_post_preferences_accepts_valid_min_amount() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', array( 'min_amount' => 100 ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 100.0, $data['store_order']['min_amount'] );
	}

	/**
	 * @testdox POST should accept null min_amount and persist it.
	 */
	public function test_post_preferences_accepts_null_min_amount() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_order', array( 'min_amount' => null ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertNull( $data['store_order']['min_amount'] );
	}

	/**
	 * @testdox POST should reject non-integer max_rating via the REST validation layer.
	 */
	public function test_post_preferences_rejects_non_integer_max_rating() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_review', array( 'max_rating' => 'not-a-number' ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * @testdox POST should reject out-of-range max_rating via the REST validation layer.
	 *
	 * @testWith [0]
	 *           [6]
	 *
	 * @param int $value The invalid value.
	 */
	public function test_post_preferences_rejects_out_of_range_max_rating( int $value ) {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_review', array( 'max_rating' => $value ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
	}

	/**
	 * @testdox POST should accept a valid max_rating and persist it.
	 */
	public function test_post_preferences_accepts_valid_max_rating() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_review', array( 'max_rating' => 3 ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 3, $data['store_review']['max_rating'] );
	}

	/**
	 * @testdox POST should accept null max_rating and persist it.
	 */
	public function test_post_preferences_accepts_null_max_rating() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_review', array( 'max_rating' => null ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertNull( $data['store_review']['max_rating'] );
	}

	/**
	 * @testdox GET should include a null min_amount in store_order by default.
	 */
	public function test_get_preferences_includes_min_amount_in_store_order() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/preferences' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'min_amount', $data['store_order'] );
		$this->assertNull( $data['store_order']['min_amount'] );
	}

	/**
	 * @testdox GET should include a null max_rating in store_review by default.
	 */
	public function test_get_preferences_includes_max_rating_in_store_review() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/preferences' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'max_rating', $data['store_review'] );
		$this->assertNull( $data['store_review']['max_rating'] );
	}

	/**
	 * @testdox GET should include store_stock with all sub-flags in the defaults.
	 */
	public function test_get_preferences_includes_store_stock_with_sub_flags() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/preferences' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'store_stock', $data );
		$this->assertArrayHasKey( 'enabled', $data['store_stock'] );
		$this->assertArrayHasKey( 'low_stock', $data['store_stock'] );
		$this->assertArrayHasKey( 'out_of_stock', $data['store_stock'] );
		$this->assertArrayHasKey( 'on_backorder', $data['store_stock'] );
	}

	/**
	 * @testdox POST should accept and persist store_stock sub-flag updates.
	 */
	public function test_post_preferences_updates_stock_sub_flags() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param(
			'store_stock',
			array(
				'low_stock'    => false,
				'on_backorder' => true,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['store_stock']['low_stock'] );
		$this->assertTrue( $data['store_stock']['on_backorder'] );
	}

	/**
	 * @testdox POST should reject non-boolean store_stock sub-fields via REST validation.
	 */
	public function test_post_preferences_rejects_non_boolean_stock_sub_flag() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/preferences' );
		$request->set_param( 'store_stock', array( 'low_stock' => 'not-a-boolean' ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::BAD_REQUEST, $response->get_status() );
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
