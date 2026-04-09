<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Internal\POS\Service\POSApprovalService;
use WC_Data;
use WP_Error;
use WP_REST_Request;

/**
 * Enforces WooCommerce capabilities on REST API endpoints.
 *
 * Hooks into REST API permission filters to check granular capabilities
 * such as woocommerce_refund_orders and woocommerce_void_orders for any
 * user, regardless of their role.
 *
 * @internal
 * @since 10.8.0
 */
class CapabilityEnforcement implements RegisterHooksInterface {

	/**
	 * Maps capabilities to their corresponding approval actions.
	 *
	 * @var array<string, string>
	 */
	private const CAPABILITY_ACTION_MAP = array(
		'woocommerce_refund_orders' => 'woocommerce_refund_orders',
		'woocommerce_void_orders'   => 'woocommerce_void_orders',
	);

	/**
	 * Approval service instance.
	 *
	 * @var POSApprovalService
	 */
	private POSApprovalService $approval_service;

	/**
	 * Approval token extracted from the current request's POST body.
	 * Set before capability checks so the filter can use it without touching $_REQUEST.
	 *
	 * @var string
	 */
	private string $current_approval_token = '';

	/**
	 * The order ID from the current request, for approval scope validation.
	 *
	 * @var int
	 */
	private int $current_order_id = 0;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 * @since 10.8.0
	 * @param POSApprovalService $approval_service Approval service instance.
	 */
	final public function init( POSApprovalService $approval_service ): void {
		$this->approval_service = $approval_service;
	}

	/**
	 * Register hooks and filters.
	 *
	 * @since 10.8.0
	 */
	public function register(): void {
		add_filter( 'woocommerce_rest_check_permissions', array( $this, 'enforce_capabilities' ), 10, 4 );
		add_filter(
			'woocommerce_rest_pre_insert_shop_order_object',
			array( $this, 'enforce_cancel_capability' ),
			10,
			3
		);
		add_filter( 'woocommerce_pos_capability_check', array( $this, 'check_approval_token' ), 10, 3 );
	}

	/**
	 * Enforce capabilities on REST API permission checks.
	 *
	 * Hooks into the woocommerce_rest_check_permissions filter to deny access
	 * when users attempt actions they lack capabilities for.
	 *
	 * @since 10.8.0
	 *
	 * @param bool   $permission Whether the user has permission.
	 * @param string $context    Request context (read, create, edit, delete, batch).
	 * @param int    $object_id  Object ID.
	 * @param string $post_type  Post type or object type.
	 * @return bool
	 */
	public function enforce_capabilities(
		bool $permission,
		string $context,
		int $object_id,
		string $post_type
	): bool {
		if ( 'shop_order_refund' === $post_type && 'create' === $context ) {
			$this->extract_approval_context_from_rest_request();
			return $this->user_has_capability( 'woocommerce_refund_orders' );
		}

		if ( ! $permission ) {
			return false;
		}

		return $permission;
	}

	/**
	 * Enforce the woocommerce_void_orders capability when an order is set to cancelled.
	 *
	 * @since 10.8.0
	 *
	 * @param WC_Data                       $order    The order object being updated.
	 * @param WP_REST_Request<array<mixed>> $request  The request object.
	 * @param bool                          $creating Whether this is a new order.
	 * @return WC_Data|WP_Error The order or WP_Error if capability check fails.
	 */
	public function enforce_cancel_capability( $order, WP_REST_Request $request, bool $creating ) {
		if ( $creating ) {
			return $order;
		}

		$status = $request->get_param( 'status' );
		if ( 'cancelled' !== $status ) {
			return $order;
		}

		$this->current_approval_token = (string) $request->get_param( '_pos_approval' );
		if ( method_exists( $order, 'get_id' ) ) {
			$this->current_order_id = (int) $order->get_id();
		}

		if ( ! $this->user_has_capability( 'woocommerce_void_orders' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_cancel',
				__( 'Sorry, you are not allowed to cancel orders.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		return $order;
	}

	/**
	 * Check if a valid approval token is present when a user lacks a capability.
	 *
	 * Hooks into the woocommerce_pos_capability_check filter. When the user does not
	 * have the required capability, reads `_pos_approval` from the request and validates
	 * the token via POSApprovalService. Adds an order note when the token is consumed
	 * on an order-related action.
	 *
	 * @since 10.8.0
	 *
	 * @param bool   $has_cap    Whether the user has the capability.
	 * @param string $capability The capability being checked.
	 * @param int    $user_id    The user ID.
	 * @return bool
	 */
	public function check_approval_token( bool $has_cap, string $capability, int $user_id ): bool {
		if ( $has_cap ) {
			return true;
		}

		$action = self::CAPABILITY_ACTION_MAP[ $capability ] ?? null;
		if ( null === $action ) {
			return false;
		}

		if ( '' === $this->current_approval_token ) {
			return false;
		}

		$approval_data = $this->approval_service->validate_and_consume(
			$this->current_approval_token,
			$action
		);
		if ( false === $approval_data ) {
			return false;
		}

		// Validate the approval is scoped to the correct order if applicable.
		$approved_order_id = (int) ( $approval_data['context']['order_id'] ?? 0 );
		if ( $approved_order_id > 0 && $this->current_order_id > 0
			&& $approved_order_id !== $this->current_order_id
		) {
			return false;
		}

		$this->maybe_add_order_note( $approval_data, $capability, $user_id );

		// Clear token after consumption to prevent reuse within the same request.
		$this->current_approval_token = '';

		return true;
	}

	/**
	 * Add an order note when an approval token is consumed for an order-related action.
	 *
	 * @since 10.8.0
	 *
	 * @param array  $approval_data The approval data returned by POSApprovalService.
	 * @param string $capability    The capability that was checked.
	 * @param int    $user_id       The user performing the action.
	 */
	private function maybe_add_order_note( array $approval_data, string $capability, int $user_id ): void {
		$order_id = $approval_data['context']['order_id'] ?? 0;
		if ( empty( $order_id ) ) {
			return;
		}

		$order = wc_get_order( (int) $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$approver      = get_userdata( $approval_data['approver_id'] );
		$approver_name = $approver ? $approver->display_name : (string) $approval_data['approver_id'];
		$actor         = get_userdata( $user_id );
		$actor_name    = $actor ? $actor->display_name : (string) $user_id;

		$order->add_order_note(
			sprintf(
				/* translators: 1: capability name, 2: actor display name, 3: approver display name */
				__( 'POS override: %1$s granted to %2$s, approved by %3$s.', 'woocommerce' ),
				$capability,
				$actor_name,
				$approver_name
			)
		);
	}

	/**
	 * Check whether the current user has a specific capability.
	 *
	 * Applies the woocommerce_pos_capability_check filter to allow
	 * overrides (e.g. approval tokens).
	 *
	 * @since 10.8.0
	 *
	 * @param string $capability The capability to check.
	 * @return bool
	 */
	/**
	 * Extract approval token and order context from the current REST request.
	 *
	 * Used by enforce_capabilities() which receives the permission filter
	 * but not the WP_REST_Request. Reads from POST body only (never GET)
	 * to prevent token leakage in query strings and server logs.
	 *
	 * @since 10.8.0
	 */
	private function extract_approval_context_from_rest_request(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$token = isset( $_POST['_pos_approval'] ) ? sanitize_text_field( wp_unslash( $_POST['_pos_approval'] ) ) : '';

		$this->current_approval_token = $token;

		// Extract order ID from the request URI (e.g., /orders/123/refunds).
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( preg_match( '#/orders/(\d+)/#', $uri, $matches ) ) {
			$this->current_order_id = (int) $matches[1];
		}
	}

	private function user_has_capability( string $capability ): bool {
		$user_id = get_current_user_id();
		$has_cap = current_user_can( $capability );

		/**
		 * Filters whether a user has a specific capability.
		 *
		 * This filter allows overriding capability checks, for example
		 * to grant temporary elevated permissions via approval tokens.
		 *
		 * @since 10.8.0
		 *
		 * @param bool   $has_cap    Whether the user has the capability.
		 * @param string $capability The capability being checked.
		 * @param int    $user_id    The user ID.
		 */
		return (bool) apply_filters( 'woocommerce_pos_capability_check', $has_cap, $capability, $user_id );
	}
}
