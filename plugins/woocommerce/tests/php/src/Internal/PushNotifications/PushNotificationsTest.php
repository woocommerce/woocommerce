<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\NotificationPreferencesRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushNotificationRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushNotificationStatusRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\StaleTokenCleanupService;
use Automattic\WooCommerce\Internal\PushNotifications\Triggers\NewOrderNotificationTrigger;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Helpers\PushNotificationsTestTrait;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WC_Logger;
use WC_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PushNotifications test.
 *
 * @covers \Automattic\WooCommerce\Internal\PushNotifications\PushNotifications
 */
class PushNotificationsTest extends WC_Unit_Test_Case {
	use PushNotificationsTestTrait;

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );

		global $wp_rest_server;
		$wp_rest_server = null;

		// Unregister the push token post type if it was registered.
		if ( post_type_exists( PushToken::POST_TYPE ) ) {
			unregister_post_type( PushToken::POST_TYPE );
		}

		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * @testdox Tests the functionality is enabled when Jetpack is connected.
	 */
	public function test_it_can_tell_push_notifications_should_be_enabled_if_jetpack_is_connected() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$push_notifications = new PushNotifications();

		$this->assertTrue( $push_notifications->should_be_enabled() );
	}

	/**
	 * @testdox Tests the functionality is disabled if Jetpack is not connected
	 * even when feature flag is enabled.
	 */
	public function test_it_can_tell_push_notifications_should_not_be_enabled_if_jetpack_is_not_connected() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$push_notifications = new PushNotifications();

		$this->assertFalse( $push_notifications->should_be_enabled() );
	}

	/**
	 * @testdox Tests the functionality can be manually disabled via the
	 * woocommerce_enhanced_push_notifications_disabled filter, skipping the Jetpack connection check.
	 */
	public function test_it_can_tell_push_notifications_should_not_be_enabled_when_disabled_via_filter() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->never() )
			->method( 'is_connected' );

		add_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );

		$push_notifications = new PushNotifications();

		$this->assertFalse( $push_notifications->should_be_enabled() );

		remove_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );
	}

	/**
	 * @testdox Tests that errors are logged when exception is thrown during
	 * enablement check.
	 */
	public function test_it_logs_error_when_jetpack_connection_check_throws_exception() {
		$logger_mock = $this->createMock( WC_Logger::class );
		$logger_mock->expects( $this->once() )
			->method( 'error' )
			->with(
				$this->stringContains( 'Error determining Jetpack connection state for push notifications' ),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);

		$this->register_legacy_proxy_function_mocks( array( 'wc_get_logger' => fn () => $logger_mock ) );
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willThrowException( new Exception( 'Connection check failed' ) );

		$push_notifications = new PushNotifications();
		$result             = $push_notifications->should_be_enabled();

		$this->assertFalse( $result, 'Should be disabled when exception is thrown' );
	}

	/**
	 * @testdox Tests that enablement state is cached within an instance.
	 */
	public function test_it_caches_enablement_state_correctly() {
		// First instance with Jetpack disconnected - should return false.
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$push_notifications = new PushNotifications();

		$this->assertFalse( $push_notifications->should_be_enabled(), 'Should be disabled when Jetpack is not connected' );

		// Second call should return cached false without calling is_connected again.
		$this->assertFalse( $push_notifications->should_be_enabled(), 'Should return cached false value' );

		// Create a new instance with Jetpack connected.
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$push_notifications_2 = new PushNotifications();

		// Should now return true with the new instance.
		$this->assertTrue( $push_notifications_2->should_be_enabled(), 'Should be enabled with new instance when Jetpack connected' );

		// Subsequent call should return cached true.
		$this->assertTrue( $push_notifications_2->should_be_enabled(), 'Should return cached true value' );
	}

	/**
	 * @testdox Tests that register() hooks on_init to init action.
	 */
	public function test_it_hooks_on_init_to_init_action() {
		$push_notifications = new PushNotifications();
		$push_notifications->register();

		$callback_priority = has_action( 'init', array( $push_notifications, 'on_init' ) );

		$this->assertTrue( (bool) $callback_priority, 'on_init should be hooked to init' );
		$this->assertEquals( 10, $callback_priority, 'on_init should have priority 10' );
	}

	/**
	 * @testdox Tests that on_init registers post types when enabled.
	 */
	public function test_on_init_registers_post_types_when_enabled() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$push_notifications = new PushNotifications();
		$push_notifications->on_init();

		$this->assertTrue( post_type_exists( PushToken::POST_TYPE ), 'Push token post type should be registered' );
	}

	/**
	 * @testdox Tests that register_post_types() registers push_token post type with correct properties.
	 */
	public function test_it_registers_push_token_post_type_with_correct_properties() {
		$push_notifications = new PushNotifications();
		$push_notifications->register_post_types();

		$this->assertTrue( post_type_exists( PushToken::POST_TYPE ), 'Push token post type should be registered' );

		$post_type_object = get_post_type_object( PushToken::POST_TYPE );

		$this->assertNotNull( $post_type_object );
		$this->assertFalse( $post_type_object->public );
		$this->assertFalse( $post_type_object->publicly_queryable );
		$this->assertTrue( $post_type_object->delete_with_user );
	}

	/**
	 * @testdox Tests that on_init does not register post types when Jetpack is not connected,
	 * leaving the tokens endpoint to register them if it is called.
	 */
	public function test_on_init_does_not_register_post_types_when_jetpack_is_not_connected() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$push_notifications = new PushNotifications();
		$push_notifications->on_init();

		$this->assertFalse(
			post_type_exists( PushToken::POST_TYPE ),
			'Push token post type should not be registered when Jetpack is not connected'
		);
	}

	/**
	 * @testdox Tests that on_init does not register post types when disabled via the filter.
	 */
	public function test_on_init_does_not_register_post_types_when_disabled_via_filter() {
		add_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );

		$push_notifications = new PushNotifications();
		$push_notifications->on_init();

		remove_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );

		$this->assertFalse(
			post_type_exists( PushToken::POST_TYPE ),
			'Push token post type should not be registered when disabled via the filter'
		);
	}

	/**
	 * @testdox Tests that on_init records when push notifications were first enabled, as an autoloaded option.
	 */
	public function test_on_init_records_enabled_at_when_enabled() {
		global $wpdb;

		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
		$this->jetpack_connection_manager_mock->method( 'is_connected' )->willReturn( true );

		$push_notifications = new PushNotifications();

		$before = time();
		$push_notifications->on_init();
		$after = time();

		$enabled_at = $push_notifications->get_enabled_at();
		$this->assertNotNull( $enabled_at, 'The enablement time should be recorded' );
		$this->assertGreaterThanOrEqual( $before, $enabled_at, 'The enablement time should not predate on_init' );
		$this->assertLessThanOrEqual( $after, $enabled_at, 'The enablement time should not postdate on_init' );

		$autoload = $wpdb->get_var(
			$wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", PushNotifications::ENABLED_AT_OPTION )
		);
		$this->assertSame( 'on', $autoload, 'The option should be autoloaded' );
	}

	/**
	 * @testdox Tests that on_init keeps the first recorded enablement time.
	 */
	public function test_on_init_does_not_overwrite_enabled_at() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
		$this->jetpack_connection_manager_mock->method( 'is_connected' )->willReturn( true );

		update_option( PushNotifications::ENABLED_AT_OPTION, 1000 );
		$push_notifications = new PushNotifications();

		$push_notifications->on_init();

		$this->assertSame( 1000, $push_notifications->get_enabled_at(), 'The first recorded enablement time should be kept' );
	}

	/**
	 * @testdox Tests that on_init does not record an enablement time when Jetpack is not connected.
	 */
	public function test_on_init_does_not_record_enabled_at_when_jetpack_is_not_connected() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
		$this->jetpack_connection_manager_mock->method( 'is_connected' )->willReturn( false );

		$push_notifications = new PushNotifications();

		$push_notifications->on_init();

		$this->assertNull( $push_notifications->get_enabled_at(), 'No enablement time should be recorded while disabled' );
	}

	/**
	 * @testdox Tests that on_init does not read the enablement time while disabled.
	 */
	public function test_on_init_does_not_read_enabled_at_while_disabled() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
		$this->jetpack_connection_manager_mock->method( 'is_connected' )->willReturn( false );
		$reads = 0;
		add_filter(
			'pre_option_' . PushNotifications::ENABLED_AT_OPTION,
			function ( $pre ) use ( &$reads ) {
				++$reads;
				return $pre;
			}
		);

		( new PushNotifications() )->on_init();

		$this->assertSame( 0, $reads, 'The option should only be read when an order moves into a notifiable status' );
	}

	/**
	 * @testdox Tests that on_init keeps marking orders while disabled only on a site that has used push notifications.
	 * @testWith [1000, true]
	 *           [null, false]
	 *
	 * @param int|null $enabled_at    The recorded enablement time, or null when never enabled.
	 * @param bool     $expect_marked Whether the order should be marked.
	 */
	public function test_on_init_marks_orders_while_disabled_only_after_first_enablement( ?int $enabled_at, bool $expect_marked ) {
		$this->remove_trigger_callbacks_registered_at_boot();
		if ( null !== $enabled_at ) {
			update_option( PushNotifications::ENABLED_AT_OPTION, $enabled_at );
		}
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
		$this->jetpack_connection_manager_mock->method( 'is_connected' )->willReturn( false );

		( new PushNotifications() )->on_init();
		$order = wc_create_order( array( 'status' => OrderStatus::PROCESSING ) );

		$this->assertSame(
			$expect_marked,
			wc_get_order( $order->get_id() )->meta_exists( NewOrderNotificationTrigger::NOTIFIABLE_META_KEY ),
			'Orders should only be marked while disabled once push notifications have been enabled'
		);
	}

	/**
	 * @testdox Tests that on_init registers the status and token controllers, but not the send
	 * or preferences controllers, when Jetpack is not connected.
	 */
	public function test_on_init_registers_status_and_token_controllers_when_jetpack_is_not_connected() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		wc_get_container()->get( PushNotifications::class )->on_init();

		$registered = $this->get_registered_rest_controllers();

		$this->assertContains( PushNotificationStatusRestController::class, $registered );
		$this->assertContains( PushTokenRestController::class, $registered );
		$this->assertNotContains( PushNotificationRestController::class, $registered );
		$this->assertNotContains( NotificationPreferencesRestController::class, $registered );
	}

	/**
	 * @testdox Tests that on_init registers the status and token controllers, but not the send
	 * or preferences controllers, when disabled via the filter.
	 */
	public function test_on_init_registers_status_and_token_controllers_when_disabled_via_filter() {
		add_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );

		$push_notifications = new PushNotifications();
		$push_notifications->on_init();

		remove_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );

		$registered = $this->get_registered_rest_controllers();

		$this->assertContains( PushNotificationStatusRestController::class, $registered );
		$this->assertContains( PushTokenRestController::class, $registered );
		$this->assertNotContains( PushNotificationRestController::class, $registered );
		$this->assertNotContains( NotificationPreferencesRestController::class, $registered );
	}

	/**
	 * @testdox Tests that on_init registers the stale token cleanup callbacks whether push notifications are enabled ($enabled) or not.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $enabled Whether push notifications are enabled.
	 */
	public function test_on_init_registers_stale_token_cleanup( bool $enabled ) {
		if ( $enabled ) {
			$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
			$this->jetpack_connection_manager_mock->method( 'is_connected' )->willReturn( true );
		} else {
			add_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );
		}

		wc_get_container()->get( PushNotifications::class )->on_init();

		$cleanup = wc_get_container()->get( StaleTokenCleanupService::class );

		$this->assertNotFalse( has_action( StaleTokenCleanupService::CLEANUP_HOOK, array( $cleanup, 'handle_stale_token_cleanup' ) ), 'The cleanup callback should always be registered.' );
		$this->assertNotFalse( has_action( 'action_scheduler_ensure_recurring_actions', array( $cleanup, 'handle_ensure_recurring_actions' ) ), 'The scheduling callback should always be registered, to unschedule while off.' );
	}

	/**
	 * @testdox Tests that the tokens endpoint returns the store's registered tokens while
	 * the module is disabled via the filter.
	 */
	public function test_tokens_endpoint_returns_tokens_when_disabled_via_filter() {
		add_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );

		try {
			$tokens = $this->dispatch_tokens_request_after_on_init( 'filter-disabled-token' );
		} finally {
			remove_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );
		}

		$this->assertCount( 1, $tokens );
		$this->assertSame( 'filter-disabled-token', $tokens[0]['token'] );
	}

	/**
	 * @testdox Tests that the tokens endpoint returns the store's registered tokens while
	 * Jetpack is not connected.
	 */
	public function test_tokens_endpoint_returns_tokens_when_jetpack_is_not_connected() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( false );

		$tokens = $this->dispatch_tokens_request_after_on_init( 'disconnected-token' );

		$this->assertCount( 1, $tokens );
		$this->assertSame( 'disconnected-token', $tokens[0]['token'] );
	}

	/**
	 * @testdox Tests that registering a token returns a missing route while the module is disabled.
	 */
	public function test_token_registration_returns_no_route_when_disabled_via_filter() {
		$request = new WP_REST_Request( 'POST', '/wc-push-notifications/push-tokens' );
		$request->set_param( 'token', str_repeat( 'a', 64 ) );
		$request->set_param( 'platform', PushToken::PLATFORM_APPLE );
		$request->set_param( 'device_uuid', 'refused-device-uuid' );
		$request->set_param( 'origin', PushToken::ORIGIN_WOOCOMMERCE_IOS );
		$request->set_param( 'device_locale', 'en_US' );

		$response = $this->dispatch_as_shop_manager_while_disabled( $request );

		$this->assertSame( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertSame( 'rest_no_route', $response->get_data()['code'] );
	}

	/**
	 * @testdox Tests that deleting a token returns a missing route while the module is disabled.
	 */
	public function test_token_deletion_returns_no_route_when_disabled_via_filter() {
		$response = $this->dispatch_as_shop_manager_while_disabled(
			new WP_REST_Request( 'DELETE', '/wc-push-notifications/push-tokens/1' )
		);

		$this->assertSame( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertSame( 'rest_no_route', $response->get_data()['code'] );
	}

	/**
	 * Dispatches a request as a shop manager against the token routes, with the
	 * module disabled through the filter.
	 *
	 * @param WP_REST_Request $request The request to dispatch.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	private function dispatch_as_shop_manager_while_disabled( WP_REST_Request $request ): WP_REST_Response {
		add_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );

		try {
			wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

			$server = $this->create_rest_server_with_routes(
				array( array( new PushTokenRestController(), 'register_routes' ) ),
				true
			);

			return $server->dispatch( $request );
		} finally {
			remove_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );
		}
	}

	/**
	 * Stores one token against a shop manager, runs on_init with the post type
	 * unregistered, then dispatches a GET to the tokens endpoint as WPCOM and returns
	 * the tokens it responded with.
	 *
	 * @param string $token The token value to store.
	 * @return array[]
	 */
	private function dispatch_tokens_request_after_on_init( string $token ): array {
		$push_notifications = new PushNotifications();

		// Seed the token the way an enabled store would have, then drop the post type
		// again, so the request starts from the state a disabled store is left in.
		$push_notifications->register_post_types();

		wc_get_container()->get( PushTokensDataStore::class )->create(
			array(
				'user_id'       => self::factory()->user->create( array( 'role' => 'shop_manager' ) ),
				'token'         => $token,
				'platform'      => PushToken::PLATFORM_APPLE,
				'device_uuid'   => 'device-' . $token,
				'origin'        => PushToken::ORIGIN_WOOCOMMERCE_IOS,
				'device_locale' => 'en_US',
			)
		);

		unregister_post_type( PushToken::POST_TYPE );

		$push_notifications->on_init();

		$this->assertFalse(
			post_type_exists( PushToken::POST_TYPE ),
			'Push token post type should still be unregistered when the request is dispatched'
		);

		$controller = new class() extends PushTokenRestController {
			/**
			 * Stands in for a request WPCOM signed with the Jetpack blog token.
			 *
			 * @return bool
			 */
			protected function is_signed_with_blog_token(): bool {
				return true;
			}
		};

		$server = $this->create_rest_server_with_routes(
			array( array( $controller, 'register_routes' ) ),
			true
		);

		$response = $server->dispatch( new WP_REST_Request( 'GET', '/wc-push-notifications/push-tokens' ) );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$this->assertFalse(
			post_type_exists( PushToken::POST_TYPE ),
			'Reading the token list should not need the push token post type registered'
		);

		return $response->get_data()['tokens'];
	}

	/**
	 * Returns the controller classes that have added themselves to the WooCommerce REST
	 * API namespaces.
	 *
	 * @return string[]
	 */
	private function get_registered_rest_controllers(): array {
		// The status controller registers on rest_api_init rather than during
		// on_init, so a front-end request does not resolve it for nothing. WooCommerce
		// applies the namespaces filter on rest_api_init at priority 10, and the
		// controller registers at priority 0, so it is always in place in time.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Triggering a WordPress core hook, not defining one.
		do_action( 'rest_api_init' );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Triggering an existing filter from RestApiControllerBase, not defining one.
		$namespaces = apply_filters( 'woocommerce_rest_api_get_rest_namespaces', array( 'wc/v3' => array() ) );

		return array_values( $namespaces['wc/v3'] );
	}

	/**
	 * Sets up the Jetpack connection manager mocking.
	 *
	 * @param array $methods The methods that will be mocked.
	 */
	private function set_up_jetpack_connection_manager_mock( array $methods ) {
		$this->jetpack_connection_manager_mock = $this
			->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( $methods )
			->getMock();

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $this->jetpack_connection_manager_mock )
		);
	}
}
