<?php
/**
 * WooPaymentsDisputeReadinessRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments dispute readiness REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsDisputeReadinessRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	private const REST_BASE = '/payments/dispute-readiness';

	private const FEATURE_OPTION = '_wcpay_feature_dispute_readiness_overview';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Dispute readiness service.
	 *
	 * @var WooPaymentsDisputeReadinessService
	 */
	private WooPaymentsDisputeReadinessService $service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter       $arbiter Runtime owner arbiter.
	 * @param WooPaymentsDisputeReadinessService $service Dispute readiness service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsDisputeReadinessService $service ): void {
		$this->arbiter = $arbiter;
		$this->service = $service;
	}

	/**
	 * Register REST hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		if ( false === has_action( 'rest_api_init', array( $this, 'register_routes' ) ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register WooPayments-compatible dispute readiness routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, self::REST_BASE, $this->get_readable_route( 'get_readiness' ) );
		register_rest_route( self::NAMESPACE, self::REST_BASE . '/dismiss', $this->get_creatable_route( 'dismiss_card' ) );
		register_rest_route( self::NAMESPACE, self::REST_BASE . '/statement-descriptor/confirm', $this->get_creatable_route( 'confirm_statement_descriptor' ) );
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
	 * Retrieve the dispute readiness overview payload.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response
	 */
	public function get_readiness( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		if ( ! $this->is_feature_enabled() ) {
			return new WP_REST_Response( $this->service->get_disabled_overview_payload() );
		}

		return new WP_REST_Response( $this->service->get_overview_payload() );
	}

	/**
	 * Dismiss the dispute readiness card.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function dismiss_card( WP_REST_Request $request ) {
		unset( $request );

		if ( ! $this->is_feature_enabled() ) {
			return $this->disabled_error();
		}

		return new WP_REST_Response( $this->service->dismiss_overview_card() );
	}

	/**
	 * Confirm the current statement descriptor.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function confirm_statement_descriptor( WP_REST_Request $request ) {
		unset( $request );

		if ( ! $this->is_feature_enabled() ) {
			return $this->disabled_error();
		}

		return new WP_REST_Response( $this->service->confirm_statement_descriptor() );
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
	 * Get a creatable route definition.
	 *
	 * @param string $callback Callback method.
	 * @return array<string,mixed>
	 */
	private function get_creatable_route( string $callback ): array {
		return array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, $callback ),
			'permission_callback' => array( $this, 'check_permission' ),
		);
	}

	/**
	 * Tell whether the dispute readiness feature flag is enabled.
	 *
	 * @return bool
	 */
	private function is_feature_enabled(): bool {
		$value = get_option( self::FEATURE_OPTION, '1' );

		return true === $value || 1 === $value || '1' === $value || 'yes' === $value;
	}

	/**
	 * Build the disabled feature REST error.
	 *
	 * @return WP_Error
	 */
	private function disabled_error(): WP_Error {
		return new WP_Error(
			'wcpay_dispute_readiness_disabled',
			__( 'Dispute readiness is disabled.', 'woocommerce' ),
			array( 'status' => 403 )
		);
	}
}
