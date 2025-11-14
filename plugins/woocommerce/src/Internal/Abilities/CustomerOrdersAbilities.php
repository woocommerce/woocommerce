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
		wp_register_ability(
			'woocommerce/list-my-orders',
			array(
				'label'               => __( 'List My Orders', 'woocommerce' ),
				'description'         => __( 'Retrieve a list of orders placed by the current logged-in customer, with optional filters for status and pagination.', 'woocommerce' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'status' => array(
							'type'        => 'string',
							'description' => 'Filter by order status (e.g., completed, processing, pending)',
							'enum'        => array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ),
						),
						'limit'  => array(
							'type'        => 'integer',
							'description' => 'Number of orders to return (default: 10, max: 100)',
							'minimum'     => 1,
							'maximum'     => 100,
						),
						'offset' => array(
							'type'        => 'integer',
							'description' => 'Number of orders to skip (for pagination)',
							'minimum'     => 0,
						),
						'order'  => array(
							'type'        => 'string',
							'description' => 'Sort order: desc (newest first) or asc (oldest first)',
							'enum'        => array( 'desc', 'asc' ),
						),
					),
					'required'   => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'      => array(
							'type'        => 'boolean',
							'description' => 'Whether the operation was successful',
						),
						'orders'       => array(
							'type'        => 'array',
							'description' => 'Array of order summaries',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'id'           => array( 'type' => 'integer' ),
									'status'       => array( 'type' => 'string' ),
									'total'        => array( 'type' => 'number' ),
									'currency'     => array( 'type' => 'string' ),
									'date_created' => array( 'type' => 'string' ),
									'item_count'   => array( 'type' => 'integer' ),
								),
							),
						),
						'total_orders' => array(
							'type'        => 'integer',
							'description' => 'Total orders matching filter for current customer',
						),
						'message'      => array(
							'type'        => 'string',
							'description' => 'Status message or error description',
						),
					),
					'required'   => array( 'success' ),
				),
				'execute_callback'    => function ( $input = null ) {
					// Default parameters.
					$limit  = isset( $input['limit'] ) ? min( (int) $input['limit'], 100 ) : 10;
					$offset = isset( $input['offset'] ) ? (int) $input['offset'] : 0;
					$order  = isset( $input['order'] ) && 'asc' === $input['order'] ? 'ASC' : 'DESC';
					$status = isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : '';

					// Get current user.
					$current_user_id = get_current_user_id();

					// Build query args.
					$args = array(
						'customer_id' => $current_user_id,
						'limit'       => $limit,
						'offset'      => $offset,
						'orderby'     => 'date',
						'order'       => $order,
						'return'      => 'objects',
					);

					// Add status filter if provided.
					if ( ! empty( $status ) ) {
						$args['status'] = 'wc-' . $status;
					}

					try {
						// Get orders with pagination info.
						$args['paginate'] = true;
						$results = wc_get_orders( $args );
						$orders = $results->orders;
						$total_orders = $results->total;

						// Format orders for response.
						$orders_data = array();
						foreach ( $orders as $order ) {
							$orders_data[] = array(
								'id'           => $order->get_id(),
								'status'       => $order->get_status(),
								'total'        => $order->get_total(),
								'currency'     => $order->get_currency(),
								'date_created' => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
								'item_count'   => $order->get_item_count(),
							);
						}

						return array(
							'success'      => true,
							'orders'       => $orders_data,
							'total_orders' => $total_orders,
							// translators: %d is the number of orders found.
							'message'      => sprintf( __( 'Found %d order(s).', 'woocommerce' ), count( $orders_data ) ),
						);

					} catch ( \Exception $e ) {
						return array(
							'success' => false,
							'orders'  => array(),
							// translators: %s is the error message.
							'message' => sprintf( __( 'Error retrieving orders: %s', 'woocommerce' ), $e->getMessage() ),
						);
					}
				},
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'category'            => 'woocommerce-rest',
				'meta'                => array(
					'show_in_rest' => true,
					'instructions' => <<<'MARKDOWN'
### Listing Customer Orders

When customers ask about their orders:
- "Show me my orders" → List all orders (default: 10 most recent)
- "What orders are pending?" → Filter by status: pending
- "Show me more orders" → Use offset for pagination

**Presenting order lists:**
- Format dates in human-readable form (e.g., "November 14, 2025")
- Show status in customer-friendly terms:
  - pending → "Awaiting payment"
  - processing → "Being prepared"
  - completed → "Delivered"
- Summarize item count (e.g., "3 items")
- Offer to show details for specific orders

**Example conversational flow:**
User: "Show me my recent orders"
Agent: [Calls list-my-orders with default parameters]
Agent: "You have 3 recent orders:
- Order #123 (November 10) - $45.00 - Being prepared - 2 items
- Order #118 (November 5) - $89.99 - Delivered - 1 item
- Order #112 (October 28) - $32.50 - Delivered - 3 items

Would you like details about any of these orders?"
MARKDOWN
					,
				),
			)
		);
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
