<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Data;
use WP_Error;
use WP_REST_Request;

/**
 * Enforces POS-specific capabilities on WooCommerce REST API endpoints.
 *
 * POS roles (pos_cashier, pos_manager) have granular capabilities that need
 * to be checked in addition to the standard WordPress post-type capabilities.
 * This class hooks into REST API permission filters to add those checks.
 *
 * @internal
 * @since 10.8.0
 */
class POSCapabilityEnforcement implements RegisterHooksInterface {

	private const POS_ROLES = array( 'pos_cashier', 'pos_manager' );

	/**
	 * Register hooks and filters.
	 *
	 * @since 10.8.0
	 */
	public function register(): void {
		add_filter( 'woocommerce_rest_check_permissions', array( $this, 'enforce_pos_capabilities' ), 10, 4 );
		add_filter(
			'woocommerce_rest_pre_insert_shop_order_object',
			array( $this, 'enforce_cancel_capability' ),
			10,
			3
		);
	}

	/**
	 * Enforce POS capabilities on REST API permission checks.
	 *
	 * Hooks into the woocommerce_rest_check_permissions filter to deny access
	 * when POS role users attempt actions they lack capabilities for.
	 *
	 * @since 10.8.0
	 *
	 * @param bool   $permission Whether the user has permission.
	 * @param string $context    Request context (read, create, edit, delete, batch).
	 * @param int    $object_id  Object ID.
	 * @param string $post_type  Post type or object type.
	 * @return bool
	 */
	public function enforce_pos_capabilities( bool $permission, string $context, int $object_id, string $post_type ): bool {
		if ( ! $permission ) {
			return false;
		}

		if ( ! $this->is_current_user_pos_role() ) {
			return $permission;
		}

		if ( 'shop_order_refund' === $post_type && 'create' === $context ) {
			return $this->user_has_pos_capability( 'woocommerce_refund_orders' );
		}

		return $permission;
	}

	/**
	 * Enforce the woocommerce_void_orders capability when an order is set to cancelled.
	 *
	 * @since 10.8.0
	 *
	 * @param WC_Data                    $order    The order object being updated.
	 * @param WP_REST_Request<array<mixed>> $request  The request object.
	 * @param bool                       $creating Whether this is a new order.
	 * @return WC_Data|WP_Error The order or WP_Error if capability check fails.
	 */
	public function enforce_cancel_capability( $order, WP_REST_Request $request, bool $creating ) {
		if ( $creating ) {
			return $order;
		}

		if ( ! $this->is_current_user_pos_role() ) {
			return $order;
		}

		$status = $request->get_param( 'status' );
		if ( 'cancelled' !== $status ) {
			return $order;
		}

		if ( ! $this->user_has_pos_capability( 'woocommerce_void_orders' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_cancel',
				__( 'Sorry, you are not allowed to cancel orders.', 'woocommerce' ),
				array( 'status' => 403 )
			);
		}

		return $order;
	}

	/**
	 * Check whether the current user has a POS role.
	 *
	 * @since 10.8.0
	 *
	 * @return bool
	 */
	private function is_current_user_pos_role(): bool {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return false;
		}

		return ! empty( array_intersect( self::POS_ROLES, (array) $user->roles ) );
	}

	/**
	 * Check whether the current user has a specific POS capability.
	 *
	 * Applies the woocommerce_pos_capability_check filter to allow
	 * overrides (e.g. approval tokens in future tasks).
	 *
	 * @since 10.8.0
	 *
	 * @param string $capability The capability to check.
	 * @return bool
	 */
	private function user_has_pos_capability( string $capability ): bool {
		$user_id = get_current_user_id();
		$has_cap = current_user_can( $capability );

		/**
		 * Filters whether a POS user has a specific capability.
		 *
		 * This filter allows overriding POS capability checks, for example
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
