<?php
/**
 * Finance REST controller.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataException;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataQuery;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller for the payments finance data endpoints under /wc-admin/payments/finance.
 *
 * @internal
 */
class FinanceRestController extends RestApiControllerBase {

	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'payments/finance';

	/**
	 * The finance service.
	 *
	 * @var FinanceService
	 */
	private FinanceService $service;

	/**
	 * The response schemas.
	 *
	 * @var FinanceDataSchemas
	 */
	private FinanceDataSchemas $schemas;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param FinanceService     $service The finance service.
	 * @param FinanceDataSchemas $schemas The response schemas.
	 */
	final public function init( FinanceService $service, FinanceDataSchemas $schemas ): void {
		$this->service = $service;
		$this->schemas = $schemas;
	}

	/**
	 * Get the WooCommerce REST API namespace for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-admin-payments-finance';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @param bool $override Whether to override the existing routes. Useful for testing.
	 */
	public function register_routes( bool $override = false ): void {
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/providers',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_providers' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
				),
				'schema' => fn() => $this->schemas->get_providers_schema(),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/providers/(?P<gateway_id>[a-zA-Z0-9_-]+)/balance',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_balances' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => $this->get_gateway_id_arg(),
				),
				'schema' => fn() => $this->schemas->get_balances_schema(),
			),
			$override
		);
		register_rest_route(
			$this->route_namespace,
			'/' . $this->rest_base . '/providers/(?P<gateway_id>[a-zA-Z0-9_-]+)/payouts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( $request ) => $this->run( $request, 'get_payouts' ),
					'permission_callback' => fn( $request ) => $this->check_permissions( $request ),
					'args'                => array_merge( $this->get_gateway_id_arg(), $this->get_args_for_get_payouts() ),
				),
				'schema' => fn() => $this->schemas->get_payouts_schema(),
			),
			$override
		);
	}

	/**
	 * Get the payment gateways that can return finance data.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array
	 */
	protected function get_providers( WP_REST_Request $request ): array {
		// The providers list takes no parameters.
		unset( $request );

		return $this->service->get_providers();
	}

	/**
	 * Get the balances of a payment gateway.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array|WP_Error
	 */
	protected function get_balances( WP_REST_Request $request ) {
		try {
			return $this->service->get_balances( (string) $request->get_param( 'gateway_id' ) );
		} catch ( FinanceDataException $e ) {
			return $this->to_wp_error( $e );
		}
	}

	/**
	 * Get a page of payouts of a payment gateway.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array|WP_Error
	 */
	protected function get_payouts( WP_REST_Request $request ) {
		$next_cursor = $request->get_param( 'next_cursor' );
		$prev_cursor = $request->get_param( 'prev_cursor' );
		$per_page    = $request->get_param( 'per_page' );
		$query       = new FinanceDataQuery(
			is_string( $next_cursor ) ? $next_cursor : null,
			is_string( $prev_cursor ) ? $prev_cursor : null,
			null === $per_page ? FinanceDataQuery::DEFAULT_PER_PAGE : (int) $per_page
		);

		try {
			return $this->service->get_payouts( (string) $request->get_param( 'gateway_id' ), $query );
		} catch ( FinanceDataException $e ) {
			return $this->to_wp_error( $e );
		}
	}

	/**
	 * Get the route argument definition for the gateway id path parameter.
	 *
	 * @return array
	 */
	private function get_gateway_id_arg(): array {
		return array(
			'gateway_id' => array(
				'description' => esc_html__( 'The payment gateway id.', 'woocommerce' ),
				'type'        => 'string',
				'pattern'     => '^[a-zA-Z0-9_-]+$',
				'required'    => true,
			),
		);
	}

	/**
	 * Get the pagination arguments of the payouts endpoint.
	 *
	 * The arguments rely on schema validation and sanitization; a custom sanitize callback would
	 * disable the maxLength check and could alter opaque cursors.
	 *
	 * @return array
	 */
	private function get_args_for_get_payouts(): array {
		return array(
			'next_cursor' => array(
				'description' => esc_html__( 'Opaque cursor from a previous response, to fetch the next page.', 'woocommerce' ),
				'type'        => 'string',
				'maxLength'   => 2048,
				'required'    => false,
			),
			'prev_cursor' => array(
				'description' => esc_html__( 'Opaque cursor from a previous response, to fetch the previous page.', 'woocommerce' ),
				'type'        => 'string',
				'maxLength'   => 2048,
				'required'    => false,
			),
			'per_page'    => array(
				'description' => esc_html__( 'Maximum number of payouts to return.', 'woocommerce' ),
				'type'        => 'integer',
				'default'     => FinanceDataQuery::DEFAULT_PER_PAGE,
				'minimum'     => 1,
				'maximum'     => FinanceDataQuery::MAX_PER_PAGE,
				'required'    => false,
			),
		);
	}

	/**
	 * Turn a finance data exception into the REST error response.
	 *
	 * @param FinanceDataException $e The exception.
	 * @return WP_Error
	 */
	private function to_wp_error( FinanceDataException $e ): WP_Error {
		return new WP_Error(
			'woocommerce_rest_payments_finance_' . $e->get_error_code(),
			$e->getMessage(),
			array( 'status' => $e->get_http_status() )
		);
	}

	/**
	 * General permissions check for the finance REST API endpoints.
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or false if no error is available for the request method.
	 */
	private function check_permissions( WP_REST_Request $request ) {
		$context = 'read';
		if ( 'POST' === $request->get_method() ) {
			$context = 'edit';
		} elseif ( 'DELETE' === $request->get_method() ) {
			$context = 'delete';
		}

		if ( wc_rest_check_manager_permissions( 'payment_gateways', $context ) ) {
			return true;
		}

		$error_information = $this->get_authentication_error_by_method( $request->get_method() );
		if ( is_null( $error_information ) ) {
			return false;
		}

		return new WP_Error(
			$error_information['code'],
			$error_information['message'],
			array( 'status' => rest_authorization_required_code() )
		);
	}
}
