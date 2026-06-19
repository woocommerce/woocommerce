<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDisputeReadinessRestController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDisputeReadinessService;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests for the native WooPayments dispute readiness REST controller.
 */
class WooPaymentsDisputeReadinessRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsDisputeReadinessRestController
	 */
	private $sut;

	/**
	 * Recording dispute readiness service.
	 *
	 * @var WooPaymentsDisputeReadinessService
	 */
	private $service;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_option( '_wcpay_feature_dispute_readiness_overview' );
		$this->service = $this->create_service();
		$this->sut     = $this->create_controller( true );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( '_wcpay_feature_dispute_readiness_overview' );
		remove_action( 'rest_api_init', array( $this->sut, 'register_routes' ) );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @testdox Dispute readiness routes are registered under wc/v3 when native owns runtime.
	 */
	public function test_registers_routes_when_native_owns_runtime(): void {
		$this->sut->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/v3/payments/dispute-readiness', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/dispute-readiness/dismiss', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/dispute-readiness/statement-descriptor/confirm', $routes );
		$this->assertRouteHasMethod( $routes['/wc/v3/payments/dispute-readiness'], WP_REST_Server::READABLE );
		$this->assertRouteHasMethod( $routes['/wc/v3/payments/dispute-readiness/dismiss'], WP_REST_Server::CREATABLE );
		$this->assertRouteHasMethod( $routes['/wc/v3/payments/dispute-readiness/statement-descriptor/confirm'], WP_REST_Server::CREATABLE );
	}

	/**
	 * @testdox Dispute readiness routes are not registered when native does not own runtime.
	 */
	public function test_registers_no_routes_when_native_does_not_own_runtime(): void {
		$this->sut = $this->create_controller( false );
		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
	}

	/**
	 * @testdox Routes require manage_woocommerce before calling the service.
	 */
	public function test_routes_require_manage_woocommerce(): void {
		update_option( '_wcpay_feature_dispute_readiness_overview', 'yes' );
		$this->sut->register_routes();
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/dispute-readiness' ) );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( '', $this->service->last_call );
	}

	/**
	 * @testdox GET returns enabled payload by default when the feature flag is absent.
	 */
	public function test_get_returns_enabled_payload_when_feature_is_absent(): void {
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/dispute-readiness' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_overview_payload', $this->service->last_call );
		$this->assertTrue( $response->get_data()['overview']['enabled'] );
		$this->assertFalse( $response->get_data()['overview']['hidden'] );
	}

	/**
	 * @testdox GET returns enabled payload when the feature flag is enabled.
	 */
	public function test_get_returns_enabled_payload_when_feature_is_enabled(): void {
		update_option( '_wcpay_feature_dispute_readiness_overview', 'yes' );
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/dispute-readiness' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_overview_payload', $this->service->last_call );
		$this->assertTrue( $response->get_data()['overview']['enabled'] );
	}

	/**
	 * @testdox Mutating routes return forbidden when the feature is disabled.
	 * @dataProvider mutating_route_data
	 *
	 * @param string $route Route path.
	 */
	public function test_mutating_routes_return_forbidden_when_feature_is_disabled( string $route ): void {
		update_option( '_wcpay_feature_dispute_readiness_overview', '0' );
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', $route ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'wcpay_dispute_readiness_disabled', $response->get_data()['code'] );
		$this->assertSame( '', $this->service->last_call );
	}

	/**
	 * @testdox POST dismiss returns the updated overview when the feature is enabled.
	 */
	public function test_dismiss_returns_updated_overview_when_feature_is_enabled(): void {
		update_option( '_wcpay_feature_dispute_readiness_overview', 'yes' );
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/payments/dispute-readiness/dismiss' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'dismiss_overview_card', $this->service->last_call );
		$this->assertTrue( $response->get_data()['overview']['isDismissed'] );
	}

	/**
	 * @testdox POST statement descriptor confirm returns the updated overview when the feature is enabled.
	 */
	public function test_statement_descriptor_confirm_returns_updated_overview_when_feature_is_enabled(): void {
		update_option( '_wcpay_feature_dispute_readiness_overview', 'yes' );
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/payments/dispute-readiness/statement-descriptor/confirm' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'confirm_statement_descriptor', $this->service->last_call );
		$this->assertSame( 'confirmed', $response->get_data()['overview']['signals'][0]['reason'] );
	}

	/**
	 * Data provider for mutating routes.
	 *
	 * @return array<string,array{string}>
	 */
	public function mutating_route_data(): array {
		return array(
			'dismiss route'              => array( '/wc/v3/payments/dispute-readiness/dismiss' ),
			'statement descriptor route' => array( '/wc/v3/payments/dispute-readiness/statement-descriptor/confirm' ),
		);
	}

	/**
	 * Create a native dispute readiness REST controller.
	 *
	 * @param bool $native_register Whether native should own route registration.
	 * @return WooPaymentsDisputeReadinessRestController
	 */
	private function create_controller( bool $native_register ): WooPaymentsDisputeReadinessRestController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$controller = new WooPaymentsDisputeReadinessRestController();
		$controller->init( $arbiter, $this->service );

		return $controller;
	}

	/**
	 * Create a recording service.
	 *
	 * @return WooPaymentsDisputeReadinessService
	 */
	private function create_service(): WooPaymentsDisputeReadinessService {
		return new class() extends WooPaymentsDisputeReadinessService {

			/**
			 * Last called method.
			 *
			 * @var string
			 */
			public string $last_call = '';

			/**
			 * Return disabled overview payload.
			 *
			 * @return array<string,mixed>
			 */
			public function get_disabled_overview_payload(): array {
				$this->last_call = __FUNCTION__;

				return array(
					'overview' => array(
						'enabled'             => false,
						'hidden'              => true,
						'score'               => 0,
						'total'               => 0,
						'state'               => 'incomplete',
						'isDismissed'         => true,
						'completeSignalIds'   => array(),
						'incompleteSignalIds' => array(),
						'signals'             => array(),
						'dismissal'           => array(
							'isDismissed'       => true,
							'isStoredDismissal' => false,
							'reappearReason'    => 'feature_disabled',
						),
					),
				);
			}

			/**
			 * Return enabled overview payload.
			 *
			 * @return array<string,mixed>
			 */
			public function get_overview_payload(): array {
				$this->last_call = __FUNCTION__;

				return array(
					'overview' => array(
						'enabled'             => true,
						'hidden'              => false,
						'score'               => 1,
						'total'               => 4,
						'state'               => 'incomplete',
						'isDismissed'         => false,
						'completeSignalIds'   => array( 'statement_descriptor' ),
						'incompleteSignalIds' => array( 'refund_policy', 'support_contact', 'terms_and_conditions' ),
						'signals'             => array(
							array(
								'id'     => 'statement_descriptor',
								'reason' => 'looks_recognizable',
							),
						),
						'dismissal'           => array(
							'isDismissed'       => false,
							'isStoredDismissal' => false,
							'reappearReason'    => null,
						),
					),
				);
			}

			/**
			 * Return dismissed overview payload.
			 *
			 * @return array<string,mixed>
			 */
			public function dismiss_overview_card(): array {
				$this->last_call = __FUNCTION__;
				$payload         = $this->get_overview_payload();
				$this->last_call = __FUNCTION__;

				$payload['overview']['isDismissed'] = true;

				return $payload;
			}

			/**
			 * Return confirmed overview payload.
			 *
			 * @return array<string,mixed>
			 */
			public function confirm_statement_descriptor(): array {
				$this->last_call = __FUNCTION__;
				$payload         = $this->get_overview_payload();
				$this->last_call = __FUNCTION__;

				$payload['overview']['signals'][0]['reason'] = 'confirmed';

				return $payload;
			}
		};
	}

	/**
	 * Assert that a route handler supports a method.
	 *
	 * @param array<int,array<string,mixed>> $handlers Route handlers.
	 * @param string                         $method   HTTP method.
	 */
	private function assertRouteHasMethod( array $handlers, string $method ): void {
		foreach ( $handlers as $handler ) {
			if ( isset( $handler['methods'][ $method ] ) && $handler['methods'][ $method ] ) {
				$this->assertTrue( true );
				return;
			}
		}

		$this->fail( "Route does not support {$method}." );
	}
}
