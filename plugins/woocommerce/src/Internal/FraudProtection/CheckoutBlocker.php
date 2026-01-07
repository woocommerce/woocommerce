<?php
/**
 * CheckoutBlocker class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks checkout for sessions marked as blocked by fraud protection.
 *
 * This class hooks into WooCommerce checkout flow to prevent payment processing
 * when a session has been blocked due to fraud concerns. It handles:
 * - Filtering out payment gateways (both shortcode and block-based checkout)
 * - Displaying user-friendly blocked messages
 * - Blocking REST API requests for blocked sessions
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class CheckoutBlocker implements RegisterHooksInterface {

	/**
	 * Session clearance manager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private SessionClearanceManager $session_manager;

	/**
	 * Initialize with dependencies.
	 *
	 * @internal
	 *
	 * @param SessionClearanceManager $session_manager The session clearance manager instance.
	 */
	final public function init( SessionClearanceManager $session_manager ): void {
		$this->session_manager = $session_manager;
	}

	/**
	 * Register checkout blocking hooks.
	 *
	 * Hooks into WooCommerce checkout flow to block payment processing for blocked sessions.
	 * This method should only be called when fraud protection is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		// Filter out payment gateways for blocked sessions (affects both shortcode and block checkout).
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'handle_filter_payment_gateways' ), 10, 1 );

		// Display blocked message on shortcode checkout.
		add_action( 'woocommerce_before_checkout_form', array( $this, 'handle_display_blocked_message' ), 1, 0 );

		// Block REST API requests for blocked sessions (affects Store API / block checkout).
		add_filter( 'rest_pre_dispatch', array( $this, 'handle_rest_api_blocked_session' ), 10, 3 );
	}

	/**
	 * Filter payment gateways for non-allowed sessions.
	 *
	 * Returns an empty array of payment gateways when the session is not allowed,
	 * effectively preventing any payment processing.
	 *
	 * @internal
	 *
	 * @param array<string, \WC_Payment_Gateway> $available_gateways Available payment gateways.
	 * @return array<string, \WC_Payment_Gateway> Filtered payment gateways.
	 */
	public function handle_filter_payment_gateways( array $available_gateways ): array {
		if ( ! $this->session_manager->is_session_allowed() ) {
			return array();
		}

		return $available_gateways;
	}

	/**
	 * Display blocked message on checkout page for blocked sessions.
	 *
	 * Shows a user-friendly message explaining that the session has been blocked
	 * and provides guidance on what to do.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_display_blocked_message(): void {
		if ( ! $this->session_manager->is_session_blocked() ) {
			return;
		}

		wc_print_notice( $this->get_blocked_message(), 'error' );
	}

	/**
	 * Block REST API checkout requests for blocked sessions.
	 *
	 * Intercepts checkout requests for blocked sessions and returns an error response.
	 *
	 * @internal
	 *
	 * @param mixed            $result  Response to replace the requested version with.
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return mixed|\WP_Error Original result or WP_Error for blocked sessions.
	 */
	public function handle_rest_api_blocked_session( $result, \WP_REST_Server $server, \WP_REST_Request $request ) {
		$route = $request->get_route();
		if ( ! $this->is_checkout_route( $route ) ) {
			return $result;
		}

		if ( $this->session_manager->is_session_blocked() ) {
			return new \WP_Error(
				$this->get_error_code_for_request( $request ),
				$this->get_blocked_message(),
				array( 'status' => 403 )
			);
		}

		return $result;
	}

	/**
	 * Check if a route is a checkout route.
	 *
	 * @param string $route The REST API route.
	 * @return bool True if it's a checkout route.
	 */
	private function is_checkout_route( string $route ): bool {
		return str_contains( $route, '/wc/store/v1/checkout' );
	}

	/**
	 * Get the error code for a given request.
	 *
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return string Error code.
	 */
	private function get_error_code_for_request( \WP_REST_Request $request ): string {
		$method = $request->get_method();

		$error_codes = array(
			'POST'   => 'woocommerce_rest_cannot_create',
			'PUT'    => 'woocommerce_rest_cannot_update',
			'PATCH'  => 'woocommerce_rest_cannot_update',
			'DELETE' => 'woocommerce_rest_cannot_delete',
		);

		return $error_codes[ $method ] ?? 'woocommerce_rest_cannot_create';
	}

	/**
	 * Get the user-friendly blocked message.
	 *
	 * @return string The blocked message.
	 */
	private function get_blocked_message(): string {
		return __( 'Sorry, we are unable to process your order at this time. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' );
	}
}
