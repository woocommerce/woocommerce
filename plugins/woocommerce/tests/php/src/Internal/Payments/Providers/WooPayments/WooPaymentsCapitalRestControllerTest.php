<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCapitalRestController;
use PHPUnit\Framework\MockObject\MockObject;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for the native WooPayments Capital REST controller.
 */
class WooPaymentsCapitalRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsCapitalRestController
	 */
	private WooPaymentsCapitalRestController $sut;

	/**
	 * Recording API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_client = $this->create_api_client();
		$this->sut        = $this->create_controller( true );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'rest_api_init', array( $this->sut, 'register_routes' ) );
		remove_action( 'admin_init', array( $this->sut, 'redirect_loan_offer_request' ) );
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_all_filters( 'allowed_redirect_hosts' );
		remove_all_filters( 'wp_redirect' );
		unset( $_GET['wcpay-loan-offer'] );
		parent::tearDown();
	}

	/**
	 * @testdox Capital routes are registered under wc/v3 when native owns runtime.
	 */
	public function test_registers_capital_routes_when_native_owns_runtime(): void {
		$this->sut->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/v3/payments/capital/active_loan_summary', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/capital/loans', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/capital/loan_offer', $routes );
		$this->assertRouteHasMethod( $routes['/wc/v3/payments/capital/active_loan_summary'], WP_REST_Server::READABLE );
		$this->assertRouteHasMethod( $routes['/wc/v3/payments/capital/loans'], WP_REST_Server::READABLE );
		$this->assertRouteHasMethod( $routes['/wc/v3/payments/capital/loan_offer'], WP_REST_Server::READABLE );
		$this->assertNotFalse( has_action( 'admin_init', array( $this->sut, 'redirect_loan_offer_request' ) ) );
	}

	/**
	 * @testdox Capital routes are not registered when native does not own runtime.
	 */
	public function test_registers_no_routes_when_native_does_not_own_runtime(): void {
		$this->sut = $this->create_controller( false );
		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
		$this->assertFalse( has_action( 'admin_init', array( $this->sut, 'redirect_loan_offer_request' ) ) );
	}

	/**
	 * @testdox Capital routes are not registered when Capital is not eligible.
	 */
	public function test_registers_no_routes_when_capital_is_not_eligible(): void {
		$this->sut = $this->create_controller( true, false );
		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
		$this->assertFalse( has_action( 'admin_init', array( $this->sut, 'redirect_loan_offer_request' ) ) );
	}

	/**
	 * @testdox Capital routes are not registered when the account lacks full admin access.
	 * @dataProvider provider_capital_admin_unavailable_account_states
	 *
	 * @param array<string,bool> $account_state Account state overrides.
	 */
	public function test_registers_no_routes_when_account_lacks_full_admin_access( array $account_state ): void {
		$this->sut = $this->create_controller( true, true, $account_state );
		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
		$this->assertFalse( has_action( 'admin_init', array( $this->sut, 'redirect_loan_offer_request' ) ) );
	}

	/**
	 * @testdox Capital routes require manage_woocommerce before calling the platform API.
	 */
	public function test_routes_require_manage_woocommerce(): void {
		$this->sut->register_routes();
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/capital/active_loan_summary' ) );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( '', $this->api_client->last_call );
	}

	/**
	 * @testdox Loan offer route requires manage_woocommerce before creating a Capital link.
	 */
	public function test_loan_offer_route_requires_manage_woocommerce(): void {
		$this->sut->register_routes();
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/capital/loan_offer' ) );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( '', $this->api_client->last_call );
	}

	/**
	 * @testdox Active loan summary returns the raw platform envelope.
	 */
	public function test_get_active_loan_summary_returns_raw_envelope(): void {
		$this->api_client->active_loan_summary_response = array(
			'loan_id' => 'loan_test',
			'status'  => 'active',
		);

		$response = $this->sut->get_active_loan_summary( new WP_REST_Request( 'GET', '/wc/v3/payments/capital/active_loan_summary' ) );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 'get_capital_active_loan_summary', $this->api_client->last_call );
		$this->assertSame( $this->api_client->active_loan_summary_response, $response->get_data() );
	}

	/**
	 * @testdox Loans route returns the raw platform envelope.
	 */
	public function test_get_loans_returns_raw_envelope(): void {
		$this->api_client->loans_response = array(
			'data' => array(
				array(
					'id' => 'loan_test',
				),
			),
		);

		$response = $this->sut->get_loans( new WP_REST_Request( 'GET', '/wc/v3/payments/capital/loans' ) );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 'get_capital_loans', $this->api_client->last_call );
		$this->assertSame( $this->api_client->loans_response, $response->get_data() );
	}

	/**
	 * @testdox Loan offer route redirects to the returned Capital link URL.
	 */
	public function test_loan_offer_route_redirects_to_returned_capital_link_url(): void {
		$this->api_client->capital_link_response = array(
			'url' => 'https://capital.example.test/view-offer',
		);
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/capital/loan_offer' ) );

		$this->assertSame( 'create_capital_link', $this->api_client->last_call );
		$this->assertSame( 302, $response->get_status() );
		$this->assertSame( 'https://capital.example.test/view-offer', $response->get_headers()['Location'] );
		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/overview', rawurldecode( $this->api_client->last_return_url ) );
		$this->assertStringContainsString( 'admin.php?wcpay-loan-offer', $this->api_client->last_refresh_url );
	}

	/**
	 * @testdox Legacy loan offer query arg redirects to a fresh Capital link.
	 */
	public function test_loan_offer_query_arg_redirects_to_returned_capital_link_url(): void {
		$this->api_client->capital_link_response = array(
			'url' => 'https://connect.stripe.com/capital/view-offer',
		);
		$_GET['wcpay-loan-offer']                = '';
		add_filter( 'allowed_redirect_hosts', array( $this, 'allow_stripe_redirect_host' ) );
		add_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );

		try {
			$this->sut->redirect_loan_offer_request();
			$this->fail( 'Expected the redirect to be intercepted.' );
		} catch ( \RuntimeException $exception ) {
			$this->assertSame( 'wp_redirect intercepted: https://connect.stripe.com/capital/view-offer', $exception->getMessage() );
		}

		$this->assertSame( 'create_capital_link', $this->api_client->last_call );
		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/overview', rawurldecode( $this->api_client->last_return_url ) );
		$this->assertStringContainsString( 'admin.php?wcpay-loan-offer', $this->api_client->last_refresh_url );
	}

	/**
	 * @testdox Legacy loan offer query arg is ignored during AJAX requests.
	 */
	public function test_loan_offer_query_arg_does_not_redirect_during_ajax_requests(): void {
		$_GET['wcpay-loan-offer'] = '';
		add_filter( 'wp_doing_ajax', '__return_true' );

		$this->sut->redirect_loan_offer_request();

		$this->assertSame( '', $this->api_client->last_call );
	}

	/**
	 * @testdox Legacy loan offer query arg is ignored when Capital is not eligible.
	 */
	public function test_loan_offer_query_arg_is_ignored_when_capital_is_not_eligible(): void {
		$this->sut                = $this->create_controller( true, false );
		$_GET['wcpay-loan-offer'] = '';
		add_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );

		try {
			$this->sut->redirect_loan_offer_request();
		} catch ( \RuntimeException $exception ) {
			$this->fail( 'Capital-ineligible loan offer requests should not redirect: ' . $exception->getMessage() );
		}

		$this->assertSame( '', $this->api_client->last_call );
	}

	/**
	 * @testdox Legacy loan offer query arg is ignored when the account lacks full admin access.
	 * @dataProvider provider_capital_admin_unavailable_account_states
	 *
	 * @param array<string,bool> $account_state Account state overrides.
	 */
	public function test_loan_offer_query_arg_is_ignored_when_account_lacks_full_admin_access( array $account_state ): void {
		$this->sut                = $this->create_controller( true, true, $account_state );
		$_GET['wcpay-loan-offer'] = '';
		add_filter( 'wp_redirect', array( $this, 'intercept_redirect' ) );

		try {
			$this->sut->redirect_loan_offer_request();
		} catch ( \RuntimeException $exception ) {
			$this->fail( 'Full-admin-ineligible loan offer requests should not redirect: ' . $exception->getMessage() );
		}

		$this->assertSame( '', $this->api_client->last_call );
	}

	/**
	 * @testdox Loan offer route redirects to the overview error notice when the API request fails.
	 */
	public function test_loan_offer_route_redirects_to_overview_error_when_api_fails(): void {
		$this->api_client->exception = new WooPaymentsApiException( 'Capital unavailable.', 'capital_unavailable', 503 );
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/capital/loan_offer' ) );
		$location = rawurldecode( (string) ( $response->get_headers()['Location'] ?? '' ) );

		$this->assertSame( 'create_capital_link', $this->api_client->last_call );
		$this->assertSame( 302, $response->get_status() );
		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/overview', $location );
		$this->assertStringContainsString( 'wcpay-loan-offer-error=1', $location );
	}

	/**
	 * @testdox Loan offer route redirects to the overview error notice when the response omits a URL.
	 */
	public function test_loan_offer_route_redirects_to_overview_error_when_response_omits_url(): void {
		$this->api_client->capital_link_response = array(
			'id' => 'link_without_url',
		);
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/capital/loan_offer' ) );
		$location = rawurldecode( (string) ( $response->get_headers()['Location'] ?? '' ) );

		$this->assertSame( 'create_capital_link', $this->api_client->last_call );
		$this->assertSame( 302, $response->get_status() );
		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=checkout&path=/woopayments/overview', $location );
		$this->assertStringContainsString( 'wcpay-loan-offer-error=1', $location );
	}

	/**
	 * @testdox API exceptions preserve the legacy Capital REST error envelope.
	 */
	public function test_api_exceptions_preserve_legacy_error_envelope(): void {
		$this->api_client->exception = new WooPaymentsApiException( 'Capital unavailable.', 'capital_unavailable', 503 );

		$response = $this->sut->get_active_loan_summary( new WP_REST_Request( 'GET', '/wc/v3/payments/capital/active_loan_summary' ) );

		$this->assertSame( 'capital_unavailable', $response->get_error_code() );
		$this->assertSame( 'Capital unavailable.', $response->get_error_message() );
		$this->assertNull( $response->get_error_data() );
	}

	/**
	 * Add the Stripe redirect host for redirect tests.
	 *
	 * @param string[] $hosts Allowed hosts.
	 * @return string[]
	 */
	public function allow_stripe_redirect_host( array $hosts ): array {
		$hosts[] = 'connect.stripe.com';

		return $hosts;
	}

	/**
	 * Intercept redirects so production exit paths do not stop the test runner.
	 *
	 * @param string $location Redirect target.
	 * @return never
	 * @throws \RuntimeException Always.
	 */
	public function intercept_redirect( string $location ): void {
		throw new \RuntimeException( 'wp_redirect intercepted: ' . esc_url_raw( $location ) );
	}

	/**
	 * Account states that should not expose Capital admin surfaces.
	 *
	 * @return array<string,array{account_state:array<string,bool>}>
	 */
	public function provider_capital_admin_unavailable_account_states(): array {
		return array(
			'gateway disabled'    => array(
				'account_state' => array(
					'is_gateway_enabled' => false,
				),
			),
			'invalid admin state' => array(
				'account_state' => array(
					'has_valid_account_for_admin_navigation' => false,
				),
			),
			'rejected account'    => array(
				'account_state' => array(
					'is_account_rejected' => true,
				),
			),
			'under review'        => array(
				'account_state' => array(
					'is_account_under_review' => true,
				),
			),
		);
	}

	/**
	 * Create a native Capital REST controller.
	 *
	 * @param bool               $native_register            Whether native should own route registration.
	 * @param bool               $has_previous_capital_loans Whether the account is Capital eligible.
	 * @param array<string,bool> $account_state              Account state overrides.
	 * @return WooPaymentsCapitalRestController
	 */
	private function create_controller( bool $native_register, bool $has_previous_capital_loans = true, array $account_state = array() ): WooPaymentsCapitalRestController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$controller = new WooPaymentsCapitalRestController();
		$controller->init( $arbiter, $this->api_client, $this->create_account_service( $has_previous_capital_loans, $account_state ) );

		return $controller;
	}

	/**
	 * Create a native account service test double.
	 *
	 * @param bool               $has_previous_capital_loans Whether the account is Capital eligible.
	 * @param array<string,bool> $overrides                  Account state overrides.
	 * @return WooPaymentsAccountService&MockObject
	 */
	private function create_account_service( bool $has_previous_capital_loans, array $overrides = array() ): WooPaymentsAccountService {
		$state = array_merge(
			array(
				'has_previous_capital_loans'             => $has_previous_capital_loans,
				'is_gateway_enabled'                     => true,
				'has_valid_account_for_admin_navigation' => true,
				'is_account_rejected'                    => false,
				'is_account_under_review'                => false,
			),
			$overrides
		);

		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array_keys( $state ) )
			->getMock();

		foreach ( $state as $method => $value ) {
			$account_service->method( $method )->willReturn( $value );
		}

		return $account_service;
	}

	/**
	 * Create a recording API client.
	 *
	 * @return WooPaymentsApiClient
	 */
	private function create_api_client(): WooPaymentsApiClient {
		return new class() extends WooPaymentsApiClient {

			/**
			 * Last called method.
			 *
			 * @var string
			 */
			public string $last_call = '';

			/**
			 * Active loan summary response.
			 *
			 * @var array<string,mixed>
			 */
			public array $active_loan_summary_response = array();

			/**
			 * Loans response.
			 *
			 * @var array<string,mixed>
			 */
			public array $loans_response = array();

			/**
			 * Capital link response.
			 *
			 * @var array<string,mixed>
			 */
			public array $capital_link_response = array();

			/**
			 * Last return URL sent to the API client.
			 *
			 * @var string
			 */
			public string $last_return_url = '';

			/**
			 * Last refresh URL sent to the API client.
			 *
			 * @var string
			 */
			public string $last_refresh_url = '';

			/**
			 * Optional exception thrown by the next call.
			 *
			 * @var WooPaymentsApiException|null
			 */
			public ?WooPaymentsApiException $exception = null;

			/**
			 * Get active loan summary.
			 *
			 * @return array<string,mixed>
			 */
			public function get_capital_active_loan_summary(): array {
				$this->last_call = __FUNCTION__;
				$this->throw_if_configured();

				return $this->active_loan_summary_response;
			}

			/**
			 * Get Capital loans.
			 *
			 * @return array<string,mixed>
			 */
			public function get_capital_loans(): array {
				$this->last_call = __FUNCTION__;
				$this->throw_if_configured();

				return $this->loans_response;
			}

			/**
			 * Create a Capital link.
			 *
			 * @param string $return_url  Return URL.
			 * @param string $refresh_url Refresh URL.
			 * @return array<string,mixed>
			 */
			public function create_capital_link( string $return_url, string $refresh_url ): array {
				$this->last_call        = __FUNCTION__;
				$this->last_return_url  = $return_url;
				$this->last_refresh_url = $refresh_url;
				$this->throw_if_configured();

				return $this->capital_link_response;
			}

			/**
			 * Throw configured exception.
			 *
			 * @throws WooPaymentsApiException When configured.
			 */
			private function throw_if_configured(): void {
				if ( null !== $this->exception ) {
					throw $this->exception;
				}
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
