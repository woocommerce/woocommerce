<?php
/**
 * WooPaymentsCapitalRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments Capital REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsCapitalRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter         Runtime owner arbiter.
	 * @param WooPaymentsApiClient         $api_client      Native WooPayments API client.
	 * @param WooPaymentsAccountService    $account_service WooPayments account service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsApiClient $api_client, WooPaymentsAccountService $account_service ): void {
		$this->arbiter         = $arbiter;
		$this->api_client      = $api_client;
		$this->account_service = $account_service;
	}

	/**
	 * Register REST hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() || ! $this->can_access_capital_admin_area() ) {
			return;
		}

		if ( false === has_action( 'rest_api_init', array( $this, 'register_routes' ) ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		if ( false === has_action( 'admin_init', array( $this, 'redirect_loan_offer_request' ) ) ) {
			add_action( 'admin_init', array( $this, 'redirect_loan_offer_request' ) );
		}
	}

	/**
	 * Register WooPayments-compatible Capital routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/payments/capital/active_loan_summary', $this->get_readable_route( 'get_active_loan_summary' ) );
		register_rest_route( self::NAMESPACE, '/payments/capital/loans', $this->get_readable_route( 'get_loans' ) );
		register_rest_route( self::NAMESPACE, '/payments/capital/loan_offer', $this->get_readable_route( 'get_loan_offer' ) );
	}

	/**
	 * Check route permissions.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get active loan summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_active_loan_summary( WP_REST_Request $request ) {
		unset( $request );

		try {
			return new WP_REST_Response( $this->api_client->get_capital_active_loan_summary() );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get Capital loans.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_loans( WP_REST_Request $request ) {
		unset( $request );

		try {
			return new WP_REST_Response( $this->api_client->get_capital_loans() );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Create and redirect to a Capital loan offer link.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response
	 */
	public function get_loan_offer( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		return $this->redirect_response( $this->get_loan_offer_redirect_url() );
	}

	/**
	 * Redirect legacy Capital offer links to a fresh provider loan offer link.
	 *
	 * @return void
	 */
	public function redirect_loan_offer_request(): void {
		if (
			wp_doing_ajax()
			|| ! current_user_can( 'manage_woocommerce' )
			|| ! $this->arbiter->should_native_register()
			|| ! $this->can_access_capital_admin_area()
		) {
			return;
		}

		// This mirrors the legacy automatic email/expired-link redirect. The GET flag only indicates that we should create a fresh Capital link.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['wcpay-loan-offer'] ) ) {
			return;
		}

		wp_safe_redirect( $this->get_loan_offer_redirect_url() );
		exit;
	}

	/**
	 * Get a readable route definition.
	 *
	 * @param string $callback Callback method.
	 * @return array<string,mixed>
	 */
	private function get_readable_route( string $callback ): array {
		return array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, $callback ),
			'permission_callback' => array( $this, 'check_permission' ),
		);
	}

	/**
	 * Check whether the native Capital admin area should be reachable.
	 *
	 * @return bool
	 */
	private function can_access_capital_admin_area(): bool {
		return $this->account_service->is_gateway_enabled()
			&& $this->account_service->has_valid_account_for_admin_navigation()
			&& ! $this->account_service->is_account_rejected()
			&& ! $this->account_service->is_account_under_review()
			&& $this->account_service->has_previous_capital_loans();
	}

	/**
	 * Convert an API exception to a REST error.
	 *
	 * @param WooPaymentsApiException $exception API exception.
	 * @return WP_Error
	 */
	private function api_exception_to_wp_error( WooPaymentsApiException $exception ): WP_Error {
		return new WP_Error(
			'' !== $exception->get_error_code() ? $exception->get_error_code() : 'wcpay_api_error',
			$exception->getMessage()
		);
	}

	/**
	 * Build the native WooPayments overview URL.
	 *
	 * @param array<string,string> $query Query args.
	 * @return string
	 */
	private function get_overview_url( array $query = array() ): string {
		return Utils::wc_payments_settings_url( WooPaymentsService::OVERVIEW_PATH, $query );
	}

	/**
	 * Build the refresh URL used by expired loan offer links.
	 *
	 * @return string
	 */
	private function get_loan_offer_refresh_url(): string {
		return add_query_arg(
			array(
				'wcpay-loan-offer' => '',
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Build the loan offer failure URL.
	 *
	 * @return string
	 */
	private function get_loan_offer_error_url(): string {
		return $this->get_overview_url(
			array(
				'wcpay-loan-offer-error' => '1',
			)
		);
	}

	/**
	 * Build a testable REST redirect response.
	 *
	 * @param string $url Redirect URL.
	 * @return WP_REST_Response
	 */
	private function redirect_response( string $url ): WP_REST_Response {
		$response = new WP_REST_Response( null, 302 );
		$response->header( 'Location', $url );

		return $response;
	}

	/**
	 * Build the redirect URL for the Capital loan offer flow.
	 *
	 * @return string
	 */
	private function get_loan_offer_redirect_url(): string {
		try {
			$capital_link = $this->api_client->create_capital_link(
				$this->get_overview_url(),
				$this->get_loan_offer_refresh_url()
			);
			$url          = isset( $capital_link['url'] ) ? (string) $capital_link['url'] : '';

			if ( '' !== $url ) {
				return $url;
			}
		} catch ( WooPaymentsApiException $exception ) {
			unset( $exception );
		}

		return $this->get_loan_offer_error_url();
	}
}
