<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWooPaySessionController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWooPaySessionService;
use WC_REST_Unit_Test_Case;
use WPAjaxDieContinueException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for the WooPaymentsWooPaySessionController class.
 */
class WooPaymentsWooPaySessionControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsWooPaySessionController
	 */
	private $sut;

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		if ( $this->sut instanceof WooPaymentsWooPaySessionController ) {
			remove_action( 'rest_api_init', array( $this->sut, 'register_routes' ) );
			foreach ( $this->get_expected_ajax_hooks() as $hook => $method ) {
				remove_action( $hook, array( $this->sut, $method ) );
			}
		}

		remove_all_filters( 'wcpay_woopay_is_signed_with_blog_token' );
		remove_all_filters( 'wp_die_ajax_handler' );
		remove_all_filters( 'wp_doing_ajax' );
		$_POST    = array();
		$_REQUEST = array();
		parent::tearDown();
	}

	/**
	 * @testdox Should register WooPay route and AJAX hooks when native owns runtime and WooPay is enabled.
	 */
	public function test_registers_woopay_route_and_ajax_hooks_when_native_owns_runtime_and_woopay_is_enabled(): void {
		$this->sut = $this->create_controller( true, true );

		$this->sut->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$this->assertArrayHasKey( '/payments/woopay/session', $this->server->get_routes() );
		$this->assertRouteHasMethod( $this->server->get_routes()['/payments/woopay/session'], WP_REST_Server::READABLE );

		foreach ( $this->get_expected_ajax_hooks() as $hook => $method ) {
			$this->assertNotFalse( has_action( $hook, array( $this->sut, $method ) ), "{$hook} should be registered." );
		}
	}

	/**
	 * @testdox Should register no hooks when native does not own runtime.
	 */
	public function test_registers_no_hooks_when_native_does_not_own_runtime(): void {
		$this->sut = $this->create_controller( false, true );

		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
		foreach ( $this->get_expected_ajax_hooks() as $hook => $method ) {
			$this->assertFalse( has_action( $hook, array( $this->sut, $method ) ) );
		}
	}

	/**
	 * @testdox Should register no hooks when WooPay is disabled.
	 */
	public function test_registers_no_hooks_when_platform_checkout_is_disabled(): void {
		$this->sut = $this->create_controller( true, false );

		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
		foreach ( $this->get_expected_ajax_hooks() as $hook => $method ) {
			$this->assertFalse( has_action( $hook, array( $this->sut, $method ) ) );
		}
	}

	/**
	 * @testdox Should require WooPay user agent and a signed request.
	 */
	public function test_woopay_rest_route_requires_woopay_user_agent_and_signed_request(): void {
		$this->sut = $this->create_controller( true, true );
		$this->sut->register_routes();

		$missing_agent = new WP_REST_Request( 'GET', '/payments/woopay/session' );
		$missing_agent->set_param( 'email', 'shopper@example.com' );
		$this->assertSame( rest_authorization_required_code(), $this->server->dispatch( $missing_agent )->get_status() );

		$unsigned = new WP_REST_Request( 'GET', '/payments/woopay/session' );
		$unsigned->set_header( 'User-Agent', 'WooPay' );
		$unsigned->set_param( 'email', 'shopper@example.com' );
		$this->assertSame( rest_authorization_required_code(), $this->server->dispatch( $unsigned )->get_status() );

		add_filter( 'wcpay_woopay_is_signed_with_blog_token', '__return_true' );
		$signed = new WP_REST_Request( 'GET', '/payments/woopay/session' );
		$signed->set_header( 'User-Agent', 'WooPay' );
		$signed->set_param( 'email', 'shopper@example.com' );

		$this->assertSame( 200, $this->server->dispatch( $signed )->get_status() );
	}

	/**
	 * @testdox Should return session data for signed WooPay requests.
	 */
	public function test_rest_session_route_returns_session_data_for_signed_woopay_request(): void {
		$service   = new RecordingWooPaySessionService();
		$this->sut = $this->create_controller( true, true, $service );
		$this->sut->register_routes();
		add_filter( 'wcpay_woopay_is_signed_with_blog_token', '__return_true' );

		$request = new WP_REST_Request( 'GET', '/payments/woopay/session' );
		$request->set_header( 'User-Agent', 'WooPay' );
		$request->set_param( 'email', 'shopper@example.com' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'session' => 'native' ), $response->get_data() );
		$this->assertSame( 'shopper@example.com', $service->last_session_email );
	}

	/**
	 * @testdox Should build WooPay AJAX-compatible response payloads.
	 */
	public function test_builds_ajax_response_payloads(): void {
		$service   = new RecordingWooPaySessionService();
		$this->sut = $this->create_controller( true, true, $service );

		$this->assertSame( array( 'result' => 'success' ), $this->sut->get_init_woopay_response( array( 'email' => 'shopper@example.com' ) ) );
		$this->assertSame( array( 'encrypted' => 'session' ), $this->sut->get_encrypted_session_response( array( 'email' => 'shopper@example.com' ) ) );
		$this->assertSame( array( 'result' => 'success' ), $this->sut->get_phone_session_response( array( 'phone_number' => '+15555550123' ) ) );
		$this->assertSame( array( 'signature' => 'signed' ), $this->sut->get_signature_response( array() ) );
		$this->assertSame( array( 'encrypted' => 'minimum' ), $this->sut->get_minimum_session_response( array() ) );
		$this->assertSame( array( 'result' => 'success' ), $this->sut->get_admin_appearance_response( array( 'appearance' => $this->get_valid_appearance() ) ) );
		$this->assertSame( array( 'stored' => true ), $this->sut->get_shopper_appearance_response( array( 'appearance' => $this->get_valid_appearance() ) ) );
		$this->assertSame( '+15555550123', $service->last_phone_request['phone_number'] );
		$this->assertSame( $this->get_valid_appearance(), $service->last_appearance );
	}

	/**
	 * @testdox Should return the preserved WooPay signature AJAX success envelope.
	 */
	public function test_signature_ajax_handler_returns_success_envelope(): void {
		$service   = new RecordingWooPaySessionService();
		$this->sut = $this->create_controller( true, true, $service );
		$_POST     = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'_ajax_nonce' => wp_create_nonce( 'woopay_signature_nonce' ),
		);
		$_REQUEST  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$response = $this->dispatch_signature_ajax();

		$this->assertTrue( $response['success'] );
		$this->assertSame( array( 'signature' => 'signed' ), $response['data'] );
	}

	/**
	 * @testdox Should report false when shopper appearance was already stored.
	 */
	public function test_shopper_appearance_response_reports_when_appearance_slot_is_filled(): void {
		$service                    = new RecordingWooPaySessionService();
		$service->appearance_stored = false;
		$this->sut                  = $this->create_controller( true, true, $service );

		$response = $this->sut->get_shopper_appearance_response( array( 'appearance' => $this->get_valid_appearance() ) );

		$this->assertSame( array( 'stored' => false ), $response );
	}

	/**
	 * Create the System Under Test.
	 *
	 * @param bool                                 $native_register Whether native should register hooks.
	 * @param bool                                 $woopay_enabled  Whether WooPay should be enabled.
	 * @param WooPaymentsWooPaySessionService|null $service         Optional service double.
	 * @return WooPaymentsWooPaySessionController
	 */
	private function create_controller( bool $native_register, bool $woopay_enabled, ?WooPaymentsWooPaySessionService $service = null ): WooPaymentsWooPaySessionController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$service                 = $service ?? new RecordingWooPaySessionService();
		$service->woopay_enabled = $woopay_enabled;

		$controller = new WooPaymentsWooPaySessionController();
		$controller->init( $arbiter, $service );

		return $controller;
	}

	/**
	 * Dispatch the WooPay signature AJAX handler and decode the JSON response.
	 *
	 * @return array{success:bool,data:mixed}
	 */
	private function dispatch_signature_ajax(): array {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function (): void {
					throw new WPAjaxDieContinueException();
				};
			}
		);

		ob_start();
		try {
			$this->sut->handle_get_woopay_signature();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$body = (string) ob_get_clean();

		$decoded = json_decode( $body, true );
		$this->assertIsArray( $decoded, 'WooPay signature AJAX should emit a JSON object.' );

		return $decoded;
	}

	/**
	 * Get a valid WooPay appearance payload.
	 *
	 * @return array<string,mixed>
	 */
	private function get_valid_appearance(): array {
		return array(
			'theme'     => 'stripe',
			'variables' => array(
				'colorText' => '#111111',
			),
		);
	}

	/**
	 * Get expected AJAX hooks and callbacks.
	 *
	 * @return array<string,string>
	 */
	private function get_expected_ajax_hooks(): array {
		return array(
			'wc_ajax_wcpay_init_woopay'                   => 'handle_init_woopay',
			'wc_ajax_wcpay_get_woopay_session'            => 'handle_get_woopay_session',
			'wc_ajax_wcpay_set_woopay_phone_number'       => 'handle_set_woopay_phone_number',
			'wc_ajax_wcpay_get_woopay_signature'          => 'handle_get_woopay_signature',
			'wc_ajax_wcpay_get_woopay_minimum_session_data' => 'handle_get_woopay_minimum_session_data',
			'wp_ajax_wcpay_admin_set_woopay_appearance'   => 'handle_set_admin_woopay_appearance',
			'wc_ajax_wcpay_shopper_set_woopay_appearance' => 'handle_set_shopper_woopay_appearance',
		);
	}

	/**
	 * Assert a route handler accepts a method.
	 *
	 * @param array<int,array<string,mixed>> $route_handlers Route handlers.
	 * @param string                         $method         HTTP method.
	 */
	private function assertRouteHasMethod( array $route_handlers, string $method ): void {
		foreach ( $route_handlers as $handler ) {
			if ( isset( $handler['methods'][ $method ] ) ) {
				$this->assertTrue( true );
				return;
			}
		}

		$this->fail( "Route did not register {$method}." );
	}
}
