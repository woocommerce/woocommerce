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
	 * Most of the current shopper's own orders to consider when discounting their
	 * own stale holds. Keeps the generated IN clause bounded.
	 */
	private const MAX_OWN_ORDERS = 10;

	/**
	 * Is stock reservation enabled?
	 *
	 * @var boolean
	 */
	private $enabled = true;

	/**
	 * Request scoped memo of a signed in customer's unpaid order ids.
	 *
	 * A new ReserveStock is constructed for each wc_get_held_stock_quantity()
	 * call, and the cart makes one per stock managed item, so the memo cannot
	 * live on the instance.
	 *
	 * @var array<int, int[]>
	 */
	private static $unpaid_order_ids_by_customer = array();

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
	 * Returns query statement for getting reserved stock of a product.
	 *
	 * @param int $product_id       Product ID.
	 * @param int $exclude_order_id Order to exclude from the results.
	 * @return string               Query statement.
	 */
	private function get_query_for_reserved_stock( $product_id, $exclude_order_id ): string {
		global $wpdb;

		$pending = OrderInternalStatus::PENDING;
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$join         = "{$wpdb->prefix}wc_orders orders ON stock_table.`order_id` = orders.id";
			$where_status = "orders.status IN ( 'wc-checkout-draft', '$pending' )";
		} else {
			$join         = "{$wpdb->posts} posts ON stock_table.`order_id` = posts.ID";
			$where_status = "posts.post_status IN ( 'wc-checkout-draft', '$pending' )";
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

		$query .= $this->get_clause_to_discount_stale_own_holds( $exclude_order_id );

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
	 * Returns a clause discounting holds the current shopper placed themselves and
	 * then left unpaid for longer than a grace window.
	 *
	 * Only one order is excluded by $exclude_order_id, so a shopper who abandons a
	 * checkout and starts another is blocked by their own earlier hold for the
	 * whole of woocommerce_hold_stock_minutes, which stores accepting slow payment
	 * methods set to days. Inside the window their hold still counts, so a genuine
	 * in flight payment is protected across a bank app switch or a one time code.
	 * Past it, that shopper stops being blocked by their own abandoned attempt.
	 * Holds belonging to anybody else are unaffected either way.
	 *
	 * @param int $exclude_order_id Order already excluded by the caller.
	 * @return string Clause to append, or an empty string when nothing qualifies.
	 */
	private function get_clause_to_discount_stale_own_holds( $exclude_order_id ): string {
		global $wpdb;

		/**
		 * Filters how long a shopper's own unpaid hold keeps blocking that same shopper.
		 *
		 * The hold is only ever discounted for the shopper who placed it, and only
		 * once it is older than this. Return 0 to keep such holds blocking for the
		 * full reservation window, which is how WooCommerce behaved before this
		 * filter existed.
		 *
		 * @since 11.2.0
		 *
		 * @param int $minutes Grace window in minutes.
		 */
		$grace_minutes = (int) apply_filters( 'woocommerce_own_reserved_stock_grace_minutes', 10 );

		if ( $grace_minutes < 1 ) {
			return '';
		}

		$order_ids = array_values( array_diff( $this->get_current_shopper_order_ids(), array( absint( $exclude_order_id ) ) ) );

		if ( empty( $order_ids ) ) {
			return '';
		}

		$placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );

		// The `timestamp` column records when the hold was first placed and is not
		// touched by the ON DUPLICATE KEY UPDATE in reserve_stock_for_product(), so
		// it measures the age of the shopper's attempt rather than of the last write.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->prepare(
			"
			AND NOT (
				stock_table.`order_id` IN ( $placeholders )
				AND stock_table.`timestamp` < ( NOW() - INTERVAL %d MINUTE )
			)
			",
			array_merge( $order_ids, array( $grace_minutes ) )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Returns order ids that demonstrably belong to the shopper making this request.
	 *
	 * Two sources. The list this class records as it takes holds, which is bound to
	 * the session that recorded it, and the account when the shopper is signed in.
	 * Ownership is never taken from anything a shopper can type: an unauthenticated
	 * field such as the billing email would let one shopper claim another's in
	 * flight hold, which is the oversell the grace window exists to bound.
	 *
	 * order_awaiting_payment and store_api_draft_order are deliberately NOT read
	 * here. Session contents are not by themselves a proof of identity, because
	 * core hands them to a different shopper in two places: clone_session_data()
	 * copies everything except `customer` into a freshly minted session when a cart
	 * token is presented, and migrate_guest_session_to_user_session() moves a guest
	 * session wholesale onto whichever account next signs in on that browser. Those
	 * two keys carry no record of who wrote them, so they cannot be checked. They
	 * also add nothing: an order can only hold stock by passing through
	 * reserve_stock_for_order(), which is where the bound list is written.
	 *
	 * This runs inside get_query_for_reserved_stock(), which wc_get_held_stock_quantity()
	 * also reaches from the admin, WP-CLI and cron. There is no shopper in those
	 * contexts and WC()->session is not initialised, so there is no ownership and
	 * the query is returned unchanged.
	 *
	 * @return int[]
	 */
	private function get_current_shopper_order_ids(): array {
		$session = $this->get_shopper_session();

		if ( ! $session instanceof \WC_Session ) {
			return array();
		}

		$order_ids = $this->get_remembered_order_ids( $session );

		$customer_id = get_current_user_id();

		if ( $customer_id ) {
			$order_ids = array_merge( $order_ids, $this->get_unpaid_order_ids_for_customer( $customer_id ) );
		}

		return array_values( array_filter( array_unique( $order_ids ) ) );
	}

	/**
	 * Returns the recorded order ids, but only if this is still the session that
	 * recorded them.
	 *
	 * The customer id is stamped alongside the ids when they are written, and both
	 * of the ways core moves session data between shoppers change it first:
	 * init_session_from_request() mints a new customer id before cloning, and
	 * migrate_guest_session_to_user_session() swaps the guest id for the account id.
	 * A mismatch therefore means the data arrived from somebody else's session, and
	 * the list is ignored rather than trusted.
	 *
	 * @param \WC_Session $session Session of the shopper making this request.
	 * @return int[]
	 */
	private function get_remembered_order_ids( \WC_Session $session ): array {
		$remembered = $session->get( self::OWN_ORDERS_SESSION_KEY, array() );

		if ( ! is_array( $remembered ) || ! isset( $remembered['customer'], $remembered['order_ids'] ) || ! is_array( $remembered['order_ids'] ) ) {
			return array();
		}

		if ( (string) $remembered['customer'] !== (string) $session->get_customer_id() ) {
			return array();
		}

		return array_map( 'absint', $remembered['order_ids'] );
	}

	/**
	 * Returns the session of the shopper making this request, if there is one.
	 *
	 * @return \WC_Session|null
	 */
	private function get_shopper_session() {
		$session = function_exists( 'WC' ) && WC() ? WC()->session : null;

		return $session instanceof \WC_Session ? $session : null;
	}

	/**
	 * Records an order against the shopper's session so that a later request can
	 * still recognise the hold as theirs.
	 *
	 * Neither existing pointer survives an abandoned attempt. The Store API
	 * repoints store_api_draft_order at a new draft as soon as the previous one
	 * leaves checkout-draft, and order_awaiting_payment is only ever written by the
	 * classic checkout. This list keeps the ids for the life of the session.
	 *
	 * The session's customer id is stamped alongside them so that the list can be
	 * discarded if it later turns up in somebody else's session. See
	 * get_remembered_order_ids().
	 *
	 * Skipped entirely where there is no session, which is how an order created in
	 * the admin or over WP-CLI leaves nothing behind.
	 *
	 * @param \WC_Order $order Order that now holds stock.
	 */
	private function remember_order_for_shopper( $order ): void {
		$session = $this->get_shopper_session();

		if ( ! $session instanceof \WC_Session ) {
			return;
		}

		$order_ids = $this->get_remembered_order_ids( $session );

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
	 * Returns unpaid order ids belonging to a signed in customer, newest first.
	 *
	 * Session pointers are lost when the shopper switches device, clears cookies or
	 * lets the session lapse, which is when they come back and meet their own hold.
	 * The account covers that case for shoppers who are signed in.
	 *
	 * The statuses match the ones the reserved stock query counts, so an order that
	 * cannot be holding stock is never fetched.
	 *
	 * @param int $customer_id Customer ID.
	 * @return int[]
	 */
	private function get_unpaid_order_ids_for_customer( int $customer_id ): array {
		if ( isset( self::$unpaid_order_ids_by_customer[ $customer_id ] ) ) {
			return self::$unpaid_order_ids_by_customer[ $customer_id ];
		}

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

		self::$unpaid_order_ids_by_customer[ $customer_id ] = is_array( $order_ids ) ? array_map( 'absint', $order_ids ) : array();

		return self::$unpaid_order_ids_by_customer[ $customer_id ];
	}
}
