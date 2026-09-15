<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Controllers;

use Automattic\WooCommerce\Internal\PushNotifications\Controllers\StepLogRestController;
use Automattic\WooCommerce\Internal\PushNotifications\Services\StepLogQuery;
use Automattic\WooCommerce\Tests\Internal\PushNotifications\Helpers\PushNotificationsTestTrait;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the StepLogRestController class.
 */
class StepLogRestControllerTest extends WC_Unit_Test_Case {
	use PushNotificationsTestTrait;

	/**
	 * The System Under Test.
	 *
	 * @var StepLogRestController
	 */
	private $sut;

	/**
	 * Mock query service.
	 *
	 * @var StepLogQuery|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $query;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->query = $this->createMock( StepLogQuery::class );
		$this->sut   = new StepLogRestController();
		$this->sut->init( $this->query );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();
		$this->clear_rest_server();

		parent::tearDown();
	}

	/**
	 * @testdox Should register the three step log routes.
	 */
	public function test_register_routes_adds_the_routes(): void {
		$server = $this->create_rest_server_with_routes( array( array( $this->sut, 'register_routes' ) ), true );
		$routes = array_keys( $server->get_routes( 'wc-push-notifications' ) );

		$this->assertContains( '/wc-push-notifications/step-logs/notifications/(?P<type>[a-z_]+)/(?P<resource_id>\d+)', $routes );
		$this->assertContains( '/wc-push-notifications/step-logs/tokens/(?P<token_id>\d+)', $routes );
		$this->assertContains( '/wc-push-notifications/step-logs/users/(?P<user_id>\d+)', $routes );
	}

	/**
	 * @testdox Should refuse a request that is not signed with the Jetpack blog token.
	 */
	public function test_authorize_rejects_without_blog_token(): void {
		$this->mock_jetpack_connection_manager_is_connected();

		$result = $this->sut->authorize_as_from_wpcom( new WP_REST_Request( 'GET', '/wc-push-notifications/step-logs/tokens/1' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_rest_cannot_view', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse every request while push notifications are disabled.
	 */
	public function test_authorize_returns_false_when_disabled(): void {
		$this->mock_jetpack_connection_manager_is_connected( false );

		$this->assertFalse( $this->sut->authorize_as_from_wpcom( new WP_REST_Request( 'GET', '/wc-push-notifications/step-logs/tokens/1' ) ) );
	}

	/**
	 * @testdox Should pass the requested range through and default it to the last 48 hours ending now.
	 */
	public function test_routes_pass_the_range_to_the_query(): void {
		$server = $this->create_authorized_server();
		$answer = array(
			'rows'         => array(),
			'counts'       => array(),
			'covered_from' => 100,
			'logging'      => array(),
		);

		$this->query->expects( $this->once() )
			->method( 'for_notification' )
			->with( 'store_order', 42, 100, 200 )
			->willReturn( $answer );
		$this->query->expects( $this->once() )
			->method( 'for_token' )
			->with(
				4412,
				$this->callback( fn( int $from ) => abs( $from - ( time() - StepLogRestController::DEFAULT_RANGE_SECONDS ) ) <= 2 ),
				$this->callback( fn( int $to ) => abs( $to - time() ) <= 2 )
			)
			->willReturn( $answer );
		$this->query->expects( $this->once() )
			->method( 'for_user' )
			->with( 7, 100, 200 )
			->willReturn( $answer );

		$this->assertSame(
			$answer,
			$this->dispatch_as_wpcom(
				$server,
				'/wc-push-notifications/step-logs/notifications/store_order/42',
				array(
					'from' => 100,
					'to'   => 200,
				)
			)
		);
		$this->assertSame( $answer, $this->dispatch_as_wpcom( $server, '/wc-push-notifications/step-logs/tokens/4412' ) );
		$this->assertSame(
			$answer,
			$this->dispatch_as_wpcom(
				$server,
				'/wc-push-notifications/step-logs/users/7',
				array(
					'from' => 100,
					'to'   => 200,
				)
			)
		);
	}

	/**
	 * @testdox Should reject an unknown notification type.
	 */
	public function test_rejects_an_unknown_type(): void {
		$server = $this->create_authorized_server();

		$response = $server->dispatch( new WP_REST_Request( 'GET', '/wc-push-notifications/step-logs/notifications/unknown_type/42' ) );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Dispatches a request and returns the response data.
	 *
	 * @param \WP_REST_Server $server The server.
	 * @param string          $route  The route.
	 * @param array           $params Query parameters.
	 * @return array
	 */
	private function dispatch_as_wpcom( \WP_REST_Server $server, string $route, array $params = array() ): array {
		$request = new WP_REST_Request( 'GET', $route );
		$request->set_query_params( $params );

		$response = $server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		return $response->get_data();
	}

	/**
	 * Creates a server whose step log routes accept every request, since a
	 * Jetpack blog token signature cannot be produced in a unit test.
	 *
	 * @return \WP_REST_Server
	 */
	private function create_authorized_server(): \WP_REST_Server {
		$controller = new class() extends StepLogRestController {
			// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
			public function authorize_as_from_wpcom( WP_REST_Request $request ) {
				return true;
			}
		};
		$controller->init( $this->query );

		return $this->create_rest_server_with_routes( array( array( $controller, 'register_routes' ) ), true );
	}
}
