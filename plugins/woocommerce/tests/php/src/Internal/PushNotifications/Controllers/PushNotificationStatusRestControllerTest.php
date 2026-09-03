<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushNotificationStatusRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Controllers\PushTokenRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Services\DriverAvailabilityService;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Helpers\PushNotificationsTestTrait;
use WC_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;
use WP_REST_Server;
use WP_UnitTest_Factory;

/**
 * Tests for the PushNotificationStatusRestController class.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
class PushNotificationStatusRestControllerTest extends WC_Unit_Test_Case {
	use PushNotificationsTestTrait;

	/**
	 * REST server used to dispatch status requests.
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
	 * Register the controller's routes using the container so init() auto-wires
	 * the push-notifications dependencies.
	 */
	private function register_routes(): void {
		$controller   = wc_get_container()->get( PushNotificationStatusRestController::class );
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

		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();
		$this->clear_rest_server();
		unset( $this->server );

		parent::tearDown();
	}

	/**
	 * @testdox GET should reject unauthenticated requests.
	 */
	public function test_get_status_requires_authentication() {
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/status' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * @testdox GET should reject a logged in user who holds no push-notifications role.
	 */
	public function test_get_status_rejects_users_without_role() {
		wp_set_current_user( $this->subscriber_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/status' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * WPCOM signs its requests with the Jetpack blog token, which identifies no
	 * user, so the endpoint has to authorize them without one.
	 *
	 * @testdox GET should accept a blog token signed request that carries no user.
	 */
	public function test_get_status_accepts_a_blog_token_signed_request_without_a_user() {
		wp_set_current_user( 0 );

		$controller = new class() extends PushNotificationStatusRestController {
			/**
			 * Stands in for a request WPCOM signed with the Jetpack blog token.
			 *
			 * @return bool
			 */
			protected function is_signed_with_blog_token(): bool {
				return true;
			}
		};

		$request = new WP_REST_Request( 'GET', '/wc-push-notifications/status' );

		$this->assertTrue( $controller->authorize_as_from_wpcom_or_authenticated( $request ) );
	}

	/**
	 * @testdox GET should report the remote proxy driver as connected, available, and enabled when Jetpack is connected.
	 */
	public function test_get_status_reports_remote_proxy_enabled_when_connected() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		$this->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/status' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'installed_drivers', $data );

		$proxy = $data['installed_drivers'][ DriverAvailabilityService::DRIVER_REMOTE_PROXY ];
		$this->assertTrue( $proxy['connected'] );
		$this->assertTrue( $proxy['enabled'] );
		$this->assertTrue( $proxy['available'] );
		$this->assertSame( DriverAvailabilityService::DRIVER_REMOTE_PROXY, $data['preferred_driver'] );
	}

	/**
	 * @testdox GET should stay reachable and report the remote proxy unavailable when disabled via the filter.
	 */
	public function test_get_status_reports_remote_proxy_unavailable_when_feature_filtered_off() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( true );
		add_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );
		$this->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/status' );
		$response = $this->server->dispatch( $request );

		remove_filter( 'woocommerce_enhanced_push_notifications_disabled', '__return_true' );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data  = $response->get_data();
		$proxy = $data['installed_drivers'][ DriverAvailabilityService::DRIVER_REMOTE_PROXY ];
		$this->assertTrue( $proxy['connected'] );
		$this->assertFalse( $proxy['enabled'] );
		$this->assertFalse( $proxy['available'] );
		$this->assertNull( $data['preferred_driver'] );
	}

	/**
	 * @testdox GET should stay reachable and report the remote proxy not connected when Jetpack is disconnected.
	 */
	public function test_get_status_reports_remote_proxy_disconnected_when_jetpack_not_connected() {
		wp_set_current_user( $this->user_id );
		$this->mock_jetpack_connection_manager_is_connected( false );
		$this->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc-push-notifications/status' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( WP_Http::OK, $response->get_status() );

		$data  = $response->get_data();
		$proxy = $data['installed_drivers'][ DriverAvailabilityService::DRIVER_REMOTE_PROXY ];
		$this->assertFalse( $proxy['connected'] );
		$this->assertTrue( $proxy['enabled'] );
		$this->assertFalse( $proxy['available'] );
		$this->assertNull( $data['preferred_driver'] );
	}

	/**
	 * @testdox Should not collide with sibling controllers on the WC REST namespaces filter.
	 *
	 * Sibling controllers share the URL route namespace `wc-push-notifications`, but they must use
	 * distinct class identifiers via `get_rest_api_namespace()` so that neither overwrites the
	 * other in the `woocommerce_rest_api_get_rest_namespaces` filter output.
	 */
	public function test_does_not_overwrite_sibling_controller_in_rest_namespaces_filter() {
		$status_controller     = new PushNotificationStatusRestController();
		$push_token_controller = new PushTokenRestController();

		$status_controller->register();
		$push_token_controller->register();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Triggering an existing filter from RestApiControllerBase, not defining one.
		$namespaces = apply_filters( 'woocommerce_rest_api_get_rest_namespaces', array( 'wc/v3' => array() ) );

		$this->assertArrayHasKey( 'wc/v3', $namespaces );

		$registered_classes = array_values( $namespaces['wc/v3'] );

		$this->assertContains( PushNotificationStatusRestController::class, $registered_classes );
		$this->assertContains( PushTokenRestController::class, $registered_classes );
	}
	/**
	 * The schema has to be a sibling of the endpoint array rather than a key within
	 * it: WP_REST_Server promotes only non-numeric top-level keys into its route
	 * options and reads the schema exclusively from there, so misplacing it drops
	 * the schema silently with every other test still passing.
	 *
	 * @testdox The route exposes its schema, so OPTIONS and the help context return it.
	 */
	public function test_route_exposes_its_schema() {
		$server = rest_get_server();
		$data   = $server->get_data_for_route( '/wc-push-notifications/status', $server->get_routes()['/wc-push-notifications/status'], 'help' );

		$this->assertArrayHasKey( 'schema', $data, 'The schema was not promoted into the route options.' );
		$this->assertSame( 'push_notification_status', $data['schema']['title'] );
		$this->assertArrayHasKey( 'installed_drivers', $data['schema']['properties'] );
		$this->assertArrayHasKey( 'preferred_driver', $data['schema']['properties'] );

		$driver_flags = $data['schema']['properties']['installed_drivers']['additionalProperties']['properties'];
		$this->assertSame( array( 'boolean', 'null' ), $driver_flags['connected']['type'], 'connected must allow null for an undetermined check.' );
		$this->assertSame( array( 'boolean', 'null' ), $driver_flags['enabled']['type'], 'enabled must allow null for an undetermined check.' );
		$this->assertSame( 'boolean', $driver_flags['available']['type'], 'available is strictly boolean.' );
	}
}
