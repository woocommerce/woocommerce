<?php
/**
 * Handle product stock reservation during checkout.
 */

namespace Automattic\WooCommerce\Checkout\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Stock Reservation class.
 */
final class ReserveStock {

	/**
	 * Is stock reservation enabled?
	 *
	 * @var boolean
	 */
	private $enabled = true;

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
	 * Returns the amount of minutes to use for reserving stock stock.
	 *
	 * @param  integer $minutes amount of minutes to reserve stock.
	 * @return integer $minutes filtered minutes.
	 */
	public function reservation_minutes( $minutes = 0 ) {

		$minutes = $minutes ? $minutes : (int) get_option( 'woocommerce_hold_stock_minutes', 60 );

		/**
		 * Filter can also be false to bypass stock reservation later.
		 */
		$minutes = apply_filters( 'woocommerce_reserve_stock_minutes', $minutes );

		return $minutes;
	}

	/**
	 * Query for any existing holds on stock for this item.
	 *
	 * @param \WC_Product $product Product to get reserved stock for.
	 * @param integer     $exclude_order_id Optional order to exclude from the results.
	 *
	 * @return integer Amount of stock already reserved.
	 */
	public function get_reserved_stock( $product, $exclude_order_id = 0 ) {

		$order = $exclude_order_id ? wc_get_order( $exclude_order_id ) : 0;

		$exclude_customer_id = $order instanceof WC_Order ? $order->get_customer_id() : 0;

		return $this->get_reserved_stock_by_customer_id( $product, $exclude_customer_id );
	}

	/**
	 * Query for any existing holds on stock for this item.
	 *
	 * @param \WC_Product $product Product to get reserved stock for.
	 * @param integer     $exclude_customer_id Optional customer_id to exclude from the results.
	 *
	 * @return integer Amount of stock already reserved.
	 */
	public function get_reserved_stock_by_customer_id( $product, $exclude_customer_id = 0 ) {
		global $wpdb;

		if ( ! $this->is_enabled() ) {
			return 0;
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $this->get_query_for_reserved_stock( $product->get_stock_managed_by_id(), $exclude_customer_id ) );
	}

	/**
	 * Put a temporary hold on stock for an order if enough is available.
	 *
	 * @throws ReserveStockException If stock cannot be reserved.
	 *
	 * @param \WC_Order $order Order object.
	 * @param int       $minutes How long to reserve stock in minutes. Defaults to woocommerce_hold_stock_minutes.
	 */
	public function reserve_stock_for_order( $order, $minutes = 0 ) {
		// Testing with reserving stock for customer ID instead of orders.
		$customer_id = false; // We should get the customer ID from the order.

		return $this->reserve_stock_for_customer( $customer_id, $minutes );
	}

	/**
	 * Put a temporary hold on stock for a customer using their ID.
	 *
	 * @throws ReserveStockException If stock cannot be reserved.

	 * @param string $customer_id can be set on the manually, fallback to current customer session.
	 * @param int    $minutes How long to reserve stock in minutes. Defaults to woocommerce_hold_stock_minutes.
	 */
	public function reserve_stock_for_customer( $customer_id = 0, $minutes = 0 ) {
		$minutes = $this->reservation_minutes( $minutes );

		$customer_id = $customer_id ? $customer_id : WC()->session->get_customer_id();

		if ( ! $customer_id || ! $minutes || ! $this->is_enabled() ) {
			return;
		}

		try {
			$items = WC()->cart->get_cart();

			$rows = array();

			foreach ( $items as $item ) {
				$product = $item['data'];

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

				$item_quantity = $item['quantity'];

				$rows[ $managed_by_id ] = isset( $rows[ $managed_by_id ] ) ? $rows[ $managed_by_id ] + $item_quantity : $item_quantity;
			}

			if ( ! empty( $rows ) ) {
				foreach ( $rows as $product_id => $quantity ) {
					$this->reserve_stock_for_product( $product_id, $quantity, $customer_id, $minutes );
				}
			}
		} catch ( ReserveStockException $e ) {
			$this->release_stock_for_customer( $customer_id );
			throw $e;
		}
	}

	/**
	 * Release a temporary hold on stock for an order.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function release_stock_for_order( $order ) {
		$this->release_stock_for_customer( $order->get_customer_id() );
	}

	/**
	 * Release a temporary hold on stock for an order.
	 *
	 * @param string $customer_id ID of the customer.
	 */
	public function release_stock_for_customer( $customer_id ) {
		global $wpdb;

		if ( ! $customer_id && ! $this->is_enabled() ) {
			return;
		}

		$wpdb->delete(
			$wpdb->wc_reserved_stock,
			array(
				'customer_id' => $customer_id,
			)
		);
	}

	/**
	 * Reserve stock for a product by inserting rows into the DB.
	 *
	 * @throws ReserveStockException If a row cannot be inserted.
	 *
	 * @param int    $product_id Product ID which is having stock reserved.
	 * @param int    $stock_quantity Stock amount to reserve.
	 * @param string $customer_id customer ID.
	 * @param int    $minutes How long to reserve stock in minutes.
	 */
	private function reserve_stock_for_product( $product_id, $stock_quantity, $customer_id, $minutes ) {
		global $wpdb;

		if ( ! $customer_id || ! $minutes ) {
			return;
		}

		$product_data_store       = \WC_Data_Store::load( 'product' );
		$query_for_stock          = $product_data_store->get_query_for_stock( $product_id );
		$query_for_reserved_stock = $this->get_query_for_reserved_stock( $product_id, $customer_id );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				"
				INSERT INTO {$wpdb->wc_reserved_stock} ( `customer_id`, `product_id`, `stock_quantity`, `timestamp`, `expires` )
				SELECT %s, %d, %d, NOW(), ( NOW() + INTERVAL %d MINUTE ) FROM DUAL
				WHERE ( $query_for_stock FOR UPDATE ) - ( $query_for_reserved_stock FOR UPDATE ) >= %d
				ON DUPLICATE KEY UPDATE `expires` = VALUES( `expires` ), `stock_quantity` = VALUES( `stock_quantity` )
				",
				$customer_id,
				$product_id,
				$stock_quantity,
				$minutes,
				$stock_quantity
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

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
	 * @param  int     $product_id Product ID.
	 * @param  integer $exclude_customer_id Optional customer_id to exclude from the results.
	 * @return string|void Query statement.
	 */
	private function get_query_for_reserved_stock( $product_id, $exclude_customer_id = 0 ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT COALESCE( SUM( stock_table.`stock_quantity` ), 0 ) FROM $wpdb->wc_reserved_stock stock_table
			WHERE stock_table.`product_id` = %d
			AND stock_table.`expires` > NOW()
			AND stock_table.`customer_id` != %s
			",
			$product_id,
			$exclude_customer_id
		);

		/**
		 * Filter: woocommerce_query_for_reserved_stock
		 * Allows to filter the query for getting reserved stock of a product.
		 *
		 * @since 4.5.0
		 * @param string $query            The query for getting reserved stock of a product.
		 * @param int    $product_id       Product ID.
		 * @param int    $exclude_customer_id customer_id to exclude from the results.
		 */
		return apply_filters( 'woocommerce_query_for_reserved_stock', $query, $product_id, $exclude_customer_id );
	}
}
