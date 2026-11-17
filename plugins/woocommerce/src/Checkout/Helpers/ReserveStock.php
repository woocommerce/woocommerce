<?php
/**
 * Handle product stock reservation during checkout.
 */

namespace Automattic\WooCommerce\Checkout\Helpers;

use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderItemType;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;

defined( 'ABSPATH' ) || exit;

/**
 * Stock Reservation class.
 */
final class ReserveStock {

	/**
	 * Session key listing orders this shopper has taken a stock hold for.
	 *
	 * Holds array( 'customer' => string, 'order_ids' => int[] ), where `customer`
	 * is the session customer id that recorded them.
	 */
	private const OWN_ORDERS_SESSION_KEY = 'stock_holding_orders';

	/**
	 * Upper bound on own orders considered when excluding a shopper's stale holds.
	 *
	 * Keeps the generated IN clause bounded. A shopper who exceeds it loses the
	 * oldest ids from the list, and those holds block again until they expire.
	 */
	private const MAX_OWN_ORDERS = 10;

	/**
	 * Is stock reservation enabled?
	 *
	 * @var boolean
	 */
	private $enabled = true;

	/**
	 * Request scoped memo of a signed in customer's potential stock-holding order ids.
	 *
	 * A new ReserveStock is constructed for each wc_get_held_stock_quantity()
	 * call, so the memo cannot live on the instance.
	 *
	 * @var array<int, int[]>
	 */
	private static $potential_order_ids_by_customer = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Table needed for this feature are added in 4.3.
		$this->enabled = get_option( 'woocommerce_schema_version', 0 ) >= 430;
	}

	/**
	 * Is stock reservation enabled?
	 *
	 * @return boolean
	 */
	protected function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Query for any existing holds on stock for this item.
	 *
	 * @param \WC_Product $product Product to get reserved stock for.
	 * @param int         $exclude_order_id Optional order to exclude from the results.
	 *
	 * @return int|float Amount of stock already reserved.
	 */
	public function get_reserved_stock( $product, $exclude_order_id = 0 ) {
		global $wpdb;

		if ( ! $this->is_enabled() ) {
			return 0;
		}

		return wc_stock_amount(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->get_var( $this->get_query_for_reserved_stock( $product->get_stock_managed_by_id(), $exclude_order_id ) )
		);
	}

	/**
	 * Put a temporary hold on stock for an order if enough is available.
	 *
	 * @throws ReserveStockException If stock cannot be reserved.
	 *
	 * @param \WC_Order $order Order object.
	 * @param int       $minutes How long to reserve stock in minutes. Defaults to 60.
	 */
	public function reserve_stock_for_order( $order, $minutes = 60 ) {
		$this->reserve_stock_for_order_items( $order, (int) $minutes, array() );
	}

	/**
	 * Tells whether a shopper can still pay an order that holds no stock.
	 *
	 * A pending order or an order with reduced stock always passes. Holds of other orders count against the stock.
	 *
	 * @since 11.4.0
	 *
	 * @param \WC_Order $order Order object.
	 * @return array{code: string, message: string}|null Null when the order can be paid, else the error.
	 */
	public function get_pay_order_stock_error( \WC_Order $order ): ?array {
		return $this->check_pay_order_stock( $order )['error'];
	}

	/**
	 * Holds the stock of a failed order while the shopper pays it.
	 *
	 * The check runs for every non-pending payable status. Only a failed order gets the hold, and its status does not change.
	 *
	 * @since 11.4.0
	 *
	 * @throws ReserveStockException Code `woocommerce_rest_out_of_stock`, HTTP 400, when the stock is gone or cannot be held.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function reserve_stock_for_failed_order( \WC_Order $order ): void {
		$check = $this->check_pay_order_stock( $order );

		if ( null !== $check['error'] ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Callers escape the message when they output it.
			throw new ReserveStockException( $check['error']['code'], $check['error']['message'], 400 );
		}

		if ( ! $check['applies'] || ! $order->has_status( OrderStatus::FAILED ) || ! $this->is_enabled() ) {
			return;
		}

		/**
		 * This filter is documented in woocommerce/includes/wc-stock-functions.php
		 *
		 * @since 4.1.0
		 */
		if ( ! apply_filters( 'woocommerce_hold_stock_for_checkout', wc_string_to_bool( get_option( 'woocommerce_manage_stock', 'yes' ) ) ) ) {
			return;
		}

		$minutes = (int) get_option( 'woocommerce_hold_stock_minutes', 60 );
		if ( $minutes <= 0 ) {
			return;
		}

		try {
			$this->reserve_stock_for_order_items( $order, $minutes, $check['skip_managed_ids'] );
		} catch ( ReserveStockException $e ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Callers escape the message when they output it.
			throw new ReserveStockException( 'woocommerce_rest_out_of_stock', $e->getMessage(), 400, $e->getErrorData() );
		}
	}

	/**
	 * Applies the stock rule that the two pay routes and the order read share.
	 *
	 * The woocommerce_pay_order_product_in_stock and woocommerce_pay_order_product_has_enough_stock filters win over the raw count.
	 * Products they let through go into skip_managed_ids, so the reservation leaves them out.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array{applies: bool, error: array{code: string, message: string}|null, skip_managed_ids: int[]}
	 */
	private function check_pay_order_stock( \WC_Order $order ): array {
		$result = array(
			'applies'          => true,
			'error'            => null,
			'skip_managed_ids' => array(),
		);

		if ( $order->has_status( wc_get_is_pending_statuses() ) || $order->get_order_stock_reduced() ) {
			$result['applies'] = false;
			return $result;
		}

		$products   = array();
		$quantities = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item || ! is_callable( array( $item, 'get_product' ) ) ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			$managed_by_id = $product->get_stock_managed_by_id();

			$products[]                   = $product;
			$quantities[ $managed_by_id ] = $item->get_quantity() + ( $quantities[ $managed_by_id ] ?? 0 );
		}

		$held_stock_by_id = array();

		foreach ( $products as $product ) {
			$managed_by_id = $product->get_stock_managed_by_id();
			$is_in_stock   = $product->is_in_stock();

			/**
			 * Filters whether or not the product is in stock for this pay for order.
			 *
			 * @param boolean $is_in_stock True if in stock.
			 * @param \WC_Product $product Product.
			 * @param \WC_Order $order Order.
			 *
			 * @since 8.1.0
			 */
			if ( ! apply_filters( 'woocommerce_pay_order_product_in_stock', $is_in_stock, $product, $order ) ) {
				$result['error'] = array(
					'code'    => 'woocommerce_rest_out_of_stock',
					/* translators: %s: product name */
					'message' => sprintf( __( 'Sorry, "%s" is no longer in stock so this order cannot be paid for. We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name() ),
				);
				return $result;
			}

			if ( ! $is_in_stock ) {
				$result['skip_managed_ids'][] = $managed_by_id;
			}

			if ( ! $product->managing_stock() || $product->backorders_allowed() ) {
				continue;
			}

			if ( ! isset( $held_stock_by_id[ $managed_by_id ] ) ) {
				$held_stock_by_id[ $managed_by_id ] = wc_get_held_stock_quantity( $product, $order->get_id() );
			}
			$held_stock       = $held_stock_by_id[ $managed_by_id ];
			$required_stock   = $quantities[ $managed_by_id ];
			$has_enough_stock = $product->get_stock_quantity() >= ( $held_stock + $required_stock );

			/**
			 * Filters whether or not the product has enough stock.
			 *
			 * @param boolean $has_enough_stock True if has enough stock.
			 * @param \WC_Product $product Product.
			 * @param \WC_Order $order Order.
			 *
			 * @since 8.1.0
			 */
			if ( ! apply_filters( 'woocommerce_pay_order_product_has_enough_stock', $has_enough_stock, $product, $order ) ) {
				$result['error'] = array(
					'code'    => 'woocommerce_rest_out_of_stock',
					/* translators: 1: product name 2: quantity in stock */
					'message' => sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s available). We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name(), wc_format_stock_quantity_for_display( $product->get_stock_quantity() - $held_stock, $product ) ),
				);
				return $result;
			}

			if ( ! $has_enough_stock ) {
				$result['skip_managed_ids'][] = $managed_by_id;
			}
		}

		$result['skip_managed_ids'] = array_values( array_unique( $result['skip_managed_ids'] ) );

		return $result;
	}

	/**
	 * Holds stock for the line items of an order, one row per stock-managing product.
	 *
	 * When one product cannot be held, every hold of the order is released before the throw.
	 *
	 * @throws ReserveStockException If stock cannot be reserved.
	 *
	 * @param \WC_Order $order            Order object.
	 * @param int       $minutes          How long to reserve stock in minutes.
	 * @param int[]     $skip_managed_ids Stock-managing product ids that get no hold.
	 */
	private function reserve_stock_for_order_items( \WC_Order $order, int $minutes, array $skip_managed_ids ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		/**
		 * Filters the number of minutes an order should reserve stock for.
		 *
		 * This hook allows the number of minutes that stock in an order should be reserved for to be filtered, useful for third party developers to increase/reduce the number of minutes if the order meets certain criteria, or to exclude an order from stock reservation using a zero value.
		 *
		 * @since 8.8.0
		 *
		 * @param int       $minutes How long to reserve stock for the order in minutes.
		 * @param \WC_Order $order Order object.
		 */
		$minutes = (int) apply_filters( 'woocommerce_order_hold_stock_minutes', $minutes, $order );
		if ( ! $minutes ) {
			return;
		}

		$held_stock_notes = array();
		try {
			$rows = array();

			foreach ( $order->get_items() as $item ) {
				$is_target_item = $item->is_type( OrderItemType::LINE_ITEM ) && $item->get_quantity() > 0;
				if ( ! $is_target_item ) {
					continue;
				}

				/** @var \WC_Order_Item_Product $item */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
				$product = $item->get_product();
				if ( ! $product instanceof \WC_Product ) {
					continue;
				}

				if ( in_array( $product->get_stock_managed_by_id(), $skip_managed_ids, true ) ) {
					continue;
				}

				if ( ! $product->is_in_stock() ) {
					throw new ReserveStockException(
						'woocommerce_product_out_of_stock',
						sprintf(
							/* translators: %s: product name */
							__( '&quot;%s&quot; is out of stock and cannot be purchased.', 'woocommerce' ),
							$product->get_name()
						),
						403
					);
				}

				// If stock management is off, no need to reserve any stock here.
				if ( ! $product->managing_stock() || $product->backorders_allowed() ) {
					continue;
				}

				$managed_by_id = $product->get_stock_managed_by_id();

				/**
				 * Filter order item quantity.
				 *
				 * @since 4.5.0
				 * @param int|float              $quantity Quantity.
				 * @param \WC_Order              $order    Order data.
				 * @param \WC_Order_Item_Product $item     Order item data.
				 */
				$item_quantity = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );

				$rows[ $managed_by_id ] = $item_quantity + ( $rows[ $managed_by_id ] ?? 0 );

				if ( count( $held_stock_notes ) < 5 ) {
					// translators: %1$s is a product's formatted name, %2$d: is the quantity of said product to which the stock hold applied.
					$held_stock_notes[] = sprintf( _x( '- %1$s &times; %2$d', 'held stock note', 'woocommerce' ), $product->get_formatted_name(), $rows[ $managed_by_id ] );
				}
			}

			if ( ! empty( $rows ) ) {
				// Reliability: consistent lock order = no cross-product ordering deadlocks from concurrent orders with same products added in different sequences.
				ksort( $rows );
				foreach ( $rows as $product_id => $quantity ) {
					$this->reserve_stock_for_product( $product_id, $quantity, $order, $minutes );
				}

				$this->remember_order_for_shopper( $order );
			}
		} catch ( ReserveStockException $e ) {
			$this->release_stock_for_order( $order );
			throw $e;
		}

		// Add order note after successfully holding the stock.
		if ( ! empty( $held_stock_notes ) ) {
			$remaining_count = count( $rows ) - count( $held_stock_notes );
			if ( $remaining_count > 0 ) {
				$held_stock_notes[] = sprintf(
					// translators: %d is the remaining order items count.
					_nx( '- ...and %d more item.', '- ... and %d more items.', $remaining_count, 'held stock note', 'woocommerce' ),
					$remaining_count
				);
			}

			$order->add_order_note(
				sprintf(
					// translators: %1$s is a time in minutes, %2$s is a list of products and quantities.
					_x( 'Stock hold of %1$s minutes applied to: %2$s', 'held stock note', 'woocommerce' ),
					$minutes,
					'<br>' . implode( '<br>', $held_stock_notes )
				),
				false,
				false,
				array(
					'note_group' => OrderNoteGroup::PRODUCT_STOCK,
				)
			);
		}
	}

	/**
	 * Release a temporary hold on stock for an order.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function release_stock_for_order( $order ) {
		global $wpdb;

		if ( ! $this->is_enabled() ) {
			return;
		}

		$wpdb->delete(
			$wpdb->wc_reserved_stock,
			array(
				'order_id' => $order->get_id(),
			)
		);
	}

	/**
	 * Reserve stock for a product by inserting rows into the DB.
	 *
	 * @throws ReserveStockException If a row cannot be inserted.
	 *
	 * @param int       $product_id     Product ID which is having stock reserved.
	 * @param int       $stock_quantity Stock amount to reserve.
	 * @param \WC_Order $order          Order object which contains the product.
	 * @param int       $minutes        How long to reserve stock in minutes.
	 */
	private function reserve_stock_for_product( $product_id, $stock_quantity, $order, $minutes ) {
		global $wpdb;

		$query_for_stock          = \WC_Data_Store::load( 'product' )->get_query_for_stock( $product_id );
		$query_for_reserved_stock = $this->get_query_for_reserved_stock( $product_id, $order->get_id() );

		// Performance note: this method uses pessimistic locking and requires InnoDB table types to function correctly.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"
			INSERT INTO {$wpdb->wc_reserved_stock} ( `order_id`, `product_id`, `stock_quantity`, `timestamp`, `expires` )
			SELECT %d, %d, %d, NOW(), ( NOW() + INTERVAL %d MINUTE ) FROM DUAL
			WHERE ( $query_for_stock FOR UPDATE ) - ( $query_for_reserved_stock LOCK IN SHARE MODE ) >= %d
			ON DUPLICATE KEY UPDATE `expires` = VALUES( `expires` ), `stock_quantity` = VALUES( `stock_quantity` )
			",
			$order->get_id(),
			$product_id,
			$stock_quantity,
			$minutes,
			$stock_quantity
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Retry operations in high-contention environments if error codes 1213, 1205, or 1020 occur. Since error
		// messages may be localized and we cannot reliably identify these codes, we use a generalized approach. Do
		// not remove this loop; it is required for the 'LOCK IN SHARE MODE' locking mode in the SQL above.
		for ( $attempt = 0; $attempt < 3; ++$attempt ) {
			$result = $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( false !== $result ) {
				break;
			}
		}

		// MySQL reports zero affected rows when a retry writes the values the order's row already has.
		if ( 0 === $result && $this->order_holds_stock_for_product( $order->get_id(), $product_id, $stock_quantity ) ) {
			return;
		}

		if ( ! $result ) {
			$product = wc_get_product( $product_id );
			throw new ReserveStockException(
				'woocommerce_product_not_enough_stock',
				sprintf(
					/* translators: %s: product name */
					__( 'Not enough units of %s are available in stock to fulfil this order.', 'woocommerce' ),
					$product ? $product->get_name() : '#' . $product_id
				),
				403
			);
		}
	}

	/**
	 * Tells whether a retry found its own hold row already in place.
	 *
	 * Only an unexpired row with the exact quantity counts.
	 *
	 * @param int $order_id       Order ID.
	 * @param int $product_id     Product ID whose stock is managed.
	 * @param int $stock_quantity Held quantity to match.
	 * @return bool
	 */
	private function order_holds_stock_for_product( $order_id, $product_id, $stock_quantity ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb.
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->wc_reserved_stock} WHERE `order_id` = %d AND `product_id` = %d AND `stock_quantity` = %d AND `expires` > NOW()",
				$order_id,
				$product_id,
				$stock_quantity
			)
		);
	}

	/**
	 * Returns query statement for getting reserved stock of a product.
	 *
	 * @param int $product_id       Product ID.
	 * @param int $exclude_order_id Order to exclude from the results.
	 * @return string               Query statement.
	 */
	private function get_query_for_reserved_stock( $product_id, $exclude_order_id ): string {
		global $wpdb;

		$pending = OrderInternalStatus::PENDING;
		$failed  = OrderInternalStatus::FAILED;
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$join         = "{$wpdb->prefix}wc_orders orders ON stock_table.`order_id` = orders.id";
			$where_status = "orders.status IN ( 'wc-checkout-draft', '$pending', '$failed' )";
		} else {
			$join         = "{$wpdb->posts} posts ON stock_table.`order_id` = posts.ID";
			$where_status = "posts.post_status IN ( 'wc-checkout-draft', '$pending', '$failed' )";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare(
			"
			SELECT COALESCE( SUM( stock_table.`stock_quantity` ), 0 ) FROM $wpdb->wc_reserved_stock stock_table
			LEFT JOIN $join
			WHERE $where_status
			AND stock_table.`expires` > NOW()
			AND stock_table.`product_id` = %d
			AND stock_table.`order_id` != %d
			",
			$product_id,
			$exclude_order_id
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$query .= $this->get_stale_own_holds_exclusion_clause( $product_id, $exclude_order_id );

		/**
		 * Filter: woocommerce_query_for_reserved_stock
		 * Allows to filter the query for getting reserved stock of a product.
		 *
		 * @since 4.5.0
		 * @param string $query            The query for getting reserved stock of a product.
		 * @param int    $product_id       Product ID.
		 * @param int    $exclude_order_id Order to exclude from the results.
		 */
		return apply_filters( 'woocommerce_query_for_reserved_stock', $query, $product_id, $exclude_order_id );
	}

	/**
	 * Builds a SQL clause excluding the current shopper's stale stock holds.
	 *
	 * @param int $product_id       Product whose reserved stock is being counted.
	 * @param int $exclude_order_id Order already excluded by the caller.
	 * @return string SQL clause, or an empty string when no holds qualify.
	 */
	private function get_stale_own_holds_exclusion_clause( $product_id, $exclude_order_id ): string {
		global $wpdb;

		/**
		 * Filters how many minutes must pass before a shopper's own unpaid stock hold
		 * stops blocking that same shopper.
		 *
		 * The exclusion is disabled by default. Enabling it can create multiple payable
		 * orders for the same stock and cause overselling if an older payment later
		 * completes. Return 0 to exclude the shopper's own holds immediately.
		 *
		 * @since 11.3.0
		 *
		 * @param int|false $threshold_minutes Threshold in minutes, or false to keep the exclusion disabled.
		 * @param int       $product_id        Product whose reserved stock is being counted.
		 * @param int       $exclude_order_id  Order already excluded by the caller.
		 */
		$threshold_minutes = apply_filters( 'woocommerce_own_stock_hold_exclusion_threshold_minutes', false, $product_id, $exclude_order_id );

		if ( false === $threshold_minutes || ! is_numeric( $threshold_minutes ) ) {
			return '';
		}

		$threshold_minutes = max( 0, (int) $threshold_minutes );

		$order_ids = array_values( array_diff( $this->get_current_shopper_stock_holding_order_ids(), array( absint( $exclude_order_id ) ) ) );

		if ( empty( $order_ids ) ) {
			return '';
		}

		$placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );

		// The `timestamp` column records when the hold was first placed, so it
		// measures the age of the shopper's attempt rather than of the last write.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The interpolated value contains only generated %d placeholders.
		return $wpdb->prepare(
			"
			AND NOT (
				stock_table.`order_id` IN ( $placeholders )
				AND stock_table.`timestamp` < ( NOW() - INTERVAL %d MINUTE )
			)
			",
			array_merge( $order_ids, array( $threshold_minutes ) )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Returns stock-holding order ids associated with the current shopper.
	 *
	 * Includes orders recorded by the current session and unpaid orders belonging
	 * to the signed-in customer. Ownership is never inferred from anything a
	 * shopper can type, such as the billing email.
	 *
	 * @return int[]
	 */
	private function get_current_shopper_stock_holding_order_ids(): array {
		$session = WC()->session;

		// In the admin, WP-CLI and cron there is no shopper and WC()->session is
		// not initialised, so nothing is ever treated as the requester's own hold.
		if ( ! $session instanceof \WC_Session ) {
			return array();
		}

		$order_ids = $this->get_session_stock_holding_order_ids( $session );

		$customer_id = get_current_user_id();

		if ( $customer_id ) {
			$order_ids = array_merge( $order_ids, $this->get_potential_stock_holding_order_ids_for_customer( $customer_id ) );
		}

		return array_values( array_filter( array_unique( $order_ids ) ) );
	}

	/**
	 * Returns the stock-holding order ids recorded against the current session.
	 *
	 * The list is only trusted if it was written under this session's customer id.
	 *
	 * @param \WC_Session $session Session of the shopper making this request.
	 * @return int[]
	 */
	private function get_session_stock_holding_order_ids( \WC_Session $session ): array {
		$remembered = $session->get( self::OWN_ORDERS_SESSION_KEY, array() );

		if ( ! is_array( $remembered ) ) {
			return array();
		}

		if ( ! isset( $remembered['customer'], $remembered['order_ids'] ) || ! is_array( $remembered['order_ids'] ) ) {
			return array();
		}

		// Core hands session data to a different shopper in two places (cart token
		// cloning and guest-to-user migration), and both change the session
		// customer id first. A mismatch means the list arrived from somebody
		// else's session, so it confers no ownership.
		if ( (string) $remembered['customer'] !== (string) $session->get_customer_id() ) {
			return array();
		}

		return array_slice( array_map( 'absint', $remembered['order_ids'] ), 0, self::MAX_OWN_ORDERS );
	}

	/**
	 * Records a stock-holding order against the current customer session.
	 *
	 * The session's customer id is stamped alongside the ids so the list can be
	 * discarded if it later turns up in somebody else's session. See
	 * get_session_stock_holding_order_ids().
	 *
	 * @param \WC_Order $order Order that now holds stock.
	 */
	private function remember_order_for_shopper( \WC_Order $order ): void {
		$session = WC()->session;

		// An order created in the admin or over WP-CLI has no session to record against.
		if ( ! $session instanceof \WC_Session ) {
			return;
		}

		$order_ids = $this->get_session_stock_holding_order_ids( $session );

		array_unshift( $order_ids, $order->get_id() );

		$order_ids = array_slice( array_values( array_unique( array_map( 'absint', $order_ids ) ) ), 0, self::MAX_OWN_ORDERS );

		$session->set(
			self::OWN_ORDERS_SESSION_KEY,
			array(
				'customer'  => (string) $session->get_customer_id(),
				'order_ids' => $order_ids,
			)
		);
	}

	/**
	 * Returns order ids of a signed-in customer that could be holding stock, newest first.
	 *
	 * Covers a shopper whose session pointers were lost (new device, cleared
	 * cookies, lapsed session). The statuses match the ones the reserved stock
	 * query counts.
	 *
	 * @param int $customer_id Customer ID.
	 * @return int[]
	 */
	private function get_potential_stock_holding_order_ids_for_customer( int $customer_id ): array {
		if ( isset( self::$potential_order_ids_by_customer[ $customer_id ] ) ) {
			return self::$potential_order_ids_by_customer[ $customer_id ];
		}

		/** @var int[] $order_ids */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
		$order_ids = wc_get_orders(
			array(
				'customer' => $customer_id,
				'status'   => array( OrderStatus::CHECKOUT_DRAFT, OrderStatus::PENDING ),
				'limit'    => self::MAX_OWN_ORDERS,
				'orderby'  => 'date',
				'order'    => 'DESC',
				'return'   => 'ids',
			)
		);

		$order_ids = array_map( 'absint', $order_ids );

		self::$potential_order_ids_by_customer[ $customer_id ] = $order_ids;

		return $order_ids;
	}
}
