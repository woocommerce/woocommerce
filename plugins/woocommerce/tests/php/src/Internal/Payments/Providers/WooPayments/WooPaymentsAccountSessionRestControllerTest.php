<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountSessionRestController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsEmbeddedAccountSessionService;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for the WooPaymentsAccountSessionRestController class.
 */
class WooPaymentsAccountSessionRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsAccountSessionRestController
	 */
	private WooPaymentsAccountSessionRestController $sut;

	/**
	 * Recording service.
	 *
	 * @var WooPaymentsEmbeddedAccountSessionService
	 */
	private WooPaymentsEmbeddedAccountSessionService $service;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->service = $this->create_service();
		$this->sut     = $this->create_controller( true );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'rest_api_init', array( $this->sut, 'register_routes' ) );
		parent::tearDown();
	}

	/**
	 * @testdox Account session route is registered under wc/v3 when native owns runtime.
	 */
	public function test_registers_route_when_native_owns_runtime(): void {
		$this->sut->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/v3/payments/accounts/session', $routes );
		$this->assertRouteHasMethod( $routes['/wc/v3/payments/accounts/session'], WP_REST_Server::READABLE );
	}

	/**
	 * @testdox Account session route is not registered when native does not own runtime.
	 */
	public function test_registers_no_route_when_native_does_not_own_runtime(): void {
		$this->sut = $this->create_controller( false );
		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
	}

	/**
	 * @testdox Account session route requires manage_woocommerce before calling the service.
	 */
	public function test_route_requires_manage_woocommerce(): void {
		$this->sut->register_routes();
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/accounts/session' ) );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( '', $this->service->last_call );
	}

	/**
	 * @testdox Account session route returns the mapped service payload.
	 */
	public function test_route_returns_service_payload(): void {
		$this->service->response = array(
			'clientSecret'   => 'cs_test',
			'expiresAt'      => 1781740800,
			'accountId'      => 'acct_native',
			'isLive'         => false,
			'publishableKey' => 'pk_test_native',
			'locale'         => get_user_locale(),
		);
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/accounts/session' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'create_session', $this->service->last_call );
		$this->assertSame( $this->service->response, $response->get_data() );
	}

	/**
	 * @testdox Account session route returns a sanitized 500 when the service throws.
	 */
	public function test_route_returns_sanitized_error_when_service_throws(): void {
		$this->service->exception = new \RuntimeException( 'secret platform failure: sk_test_123' );
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/accounts/session' ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_woopayments_account_session_error', $response->as_error()->get_error_code() );
		$this->assertStringNotContainsString( 'sk_test_123', wp_json_encode( $response->get_data() ) );
		$this->assertStringNotContainsString( 'secret platform failure', wp_json_encode( $response->get_data() ) );
	}

	/**
	 * Create a native account-session REST controller.
	 *
	 * @param bool $native_register Whether native should own route registration.
	 * @return WooPaymentsAccountSessionRestController
	 */
	private function create_controller( bool $native_register ): WooPaymentsAccountSessionRestController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$controller = new WooPaymentsAccountSessionRestController();
		$controller->init( $arbiter, $this->service );

		return $controller;
	}

	/**
	 * Create a recording service.
	 *
	 * @return WooPaymentsEmbeddedAccountSessionService
	 */
	private function create_service(): WooPaymentsEmbeddedAccountSessionService {
		return new class() extends WooPaymentsEmbeddedAccountSessionService {

			/**
			 * Last called method.
			 *
			 * @var string
			 */
			public string $last_call = '';

			/**
			 * Response returned by the service.
			 *
			 * @var array<string,mixed>
			 */
			public array $response = array();

			/**
			 * Optional exception thrown by the next call.
			 *
			 * @var \Throwable|null
			 */
			public ?\Throwable $exception = null;

			/**
			 * Create an embedded account session.
			 *
			 * @return array<string,mixed>
			 * @throws \Throwable When configured.
			 */
			public function create_session(): array {
				$this->last_call = __FUNCTION__;

				if ( null !== $this->exception ) {
					throw $this->exception;
				}

				return $this->response;
			}
		};
	}

	/**
	 * Assert a route handler supports a method.
	 *
	 * @param array<int,array<string,mixed>> $route_handlers Route handlers.
	 * @param string                         $method         Method constant.
	 */
	private function assertRouteHasMethod( array $route_handlers, string $method ): void {
		foreach ( $route_handlers as $handler ) {
			if ( isset( $handler['methods'][ $method ] ) && true === $handler['methods'][ $method ] ) {
				return;
			}
		}

		$this->fail( 'Route does not accept method ' . $method . '.' );
	}
}
