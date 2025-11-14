<?php
/**
 * Customer Orders Abilities class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Customer Orders Abilities class for WooCommerce.
 *
 * Registers customer-facing abilities for order management: list, view, and update orders.
 * All abilities enforce strict security - customers can ONLY access their own orders.
 */
class CustomerOrdersAbilities {

	/**
	 * Initialize the ability registration.
	 *
	 * @internal
	 */
	final public static function init(): void {
		/*
		 * Register abilities when Abilities API is ready.
		 * Support both old (pre-6.9) and new (6.9+) action names.
		 */
		add_action( 'abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Register all customer order abilities.
	 */
	public static function register_abilities(): void {
		// Only register if the function exists.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		self::register_list_my_orders();
		self::register_get_my_order();
		self::register_update_my_order();
	}

	/**
	 * Register the list-my-orders ability.
	 */
	private static function register_list_my_orders(): void {
		// TODO: Implement in Task 2
	}

	/**
	 * Register the get-my-order ability.
	 */
	private static function register_get_my_order(): void {
		// TODO: Implement in Task 3
	}

	/**
	 * Register the update-my-order ability.
	 */
	private static function register_update_my_order(): void {
		// TODO: Implement in Task 4
	}

	/**
	 * Validate that the current user owns the specified order.
	 *
	 * @param int $order_id Order ID to validate.
	 * @return array{valid: bool, order: \WC_Order|false, error: string|null} Validation result.
	 */
	private static function validate_customer_ownership( int $order_id ): array {
		// Get the order.
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'valid' => false,
				'order' => false,
				'error' => __( 'Order not found.', 'woocommerce' ),
			);
		}

		// Verify customer owns this order.
		$customer_id     = $order->get_customer_id();
		$current_user_id = get_current_user_id();

		if ( $customer_id !== $current_user_id ) {
			return array(
				'valid' => false,
				'order' => false,
				'error' => __( 'You do not have permission to access this order.', 'woocommerce' ),
			);
		}

		return array(
			'valid' => true,
			'order' => $order,
			'error' => null,
		);
	}
}
