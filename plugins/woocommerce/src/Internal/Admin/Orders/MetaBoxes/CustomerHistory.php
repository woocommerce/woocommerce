<?php

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

/**
 * Class CustomerHistory
 *
 * @since 8.5.0
 */
class CustomerHistory {

	/**
	 * Output the customer history template for the order.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	public function output( WC_Order $order ): void {
		// No history when adding a new order.
		if ( 'auto-draft' === $order->get_status() ) {
			return;
		}

		$customer_id   = $order->get_customer_id();
		$billing_email = $order->get_billing_email();

		$customer_history = $this->get_customer_history_from_orders( $customer_id, $billing_email );

		wc_get_template( 'order/customer-history.php', $customer_history );
	}

	/**
	 * Get the order history for the customer by querying actual order data.
	 *
	 * @param int    $customer_id   The customer user ID (0 for guests).
	 * @param string $billing_email The billing email address (used for guest lookup).
	 *
	 * @return array Order count, total spend, and average order value.
	 */
	private function get_customer_history_from_orders( int $customer_id, string $billing_email ): array {
		$result = OrderUtil::custom_orders_table_usage_is_enabled()
			? $this->query_hpos( $customer_id, $billing_email )
			: $this->query_cpt( $customer_id, $billing_email );

		$orders_count = (int) ( $result->orders_count ?? 0 );
		$total_spend  = (float) ( $result->total_spend ?? 0 );

		return array(
			'orders_count'    => $orders_count,
			'total_spend'     => $total_spend,
			'avg_order_value' => $orders_count > 0 ? $total_spend / $orders_count : 0,
		);
	}

	/**
	 * Query customer order stats from HPOS tables.
	 *
	 * @param int    $customer_id   The customer user ID.
	 * @param string $billing_email The billing email address.
	 *
	 * @return object Object with orders_count and total_spend properties.
	 */
	private function query_hpos( int $customer_id, string $billing_email ): object {
		global $wpdb;

		$excluded_statuses_sql = $this->get_excluded_statuses_sql();
		$orders_table          = OrdersTableDataStore::get_orders_table_name();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $customer_id > 0 ) {
			$sql = $wpdb->prepare(
				"SELECT COUNT(*) AS orders_count,
					COALESCE( SUM( filtered.total_amount ), 0 ) + COALESCE( SUM( r.refund_total ), 0 ) AS total_spend
				FROM (
					SELECT id, total_amount
					FROM %i
					WHERE customer_id = %d AND type = 'shop_order' AND status NOT IN $excluded_statuses_sql
				) AS filtered
				LEFT JOIN (
					SELECT parent_order_id, SUM( total_amount ) AS refund_total
					FROM %i
					WHERE type = 'shop_order_refund'
						AND parent_order_id IN (
							SELECT id FROM %i WHERE customer_id = %d AND type = 'shop_order' AND status NOT IN $excluded_statuses_sql
						)
					GROUP BY parent_order_id
				) AS r ON filtered.id = r.parent_order_id",
				$orders_table,
				$customer_id,
				$orders_table,
				$orders_table,
				$customer_id
			);
		} elseif ( '' !== $billing_email ) {
			$addresses_table = OrdersTableDataStore::get_addresses_table_name();
			$sql             = $wpdb->prepare(
				"SELECT COUNT(*) AS orders_count,
					COALESCE( SUM( filtered.total_amount ), 0 ) + COALESCE( SUM( r.refund_total ), 0 ) AS total_spend
				FROM (
					SELECT o.id, o.total_amount
					FROM %i AS o
					INNER JOIN %i AS a ON o.id = a.order_id AND a.address_type = 'billing'
					WHERE o.customer_id = 0 AND a.email = %s AND o.type = 'shop_order' AND o.status NOT IN $excluded_statuses_sql
				) AS filtered
				LEFT JOIN (
					SELECT parent_order_id, SUM( total_amount ) AS refund_total
					FROM %i
					WHERE type = 'shop_order_refund'
						AND parent_order_id IN (
							SELECT o2.id FROM %i AS o2
							INNER JOIN %i AS a2 ON o2.id = a2.order_id AND a2.address_type = 'billing'
							WHERE o2.customer_id = 0 AND a2.email = %s AND o2.type = 'shop_order' AND o2.status NOT IN $excluded_statuses_sql
						)
					GROUP BY parent_order_id
				) AS r ON filtered.id = r.parent_order_id",
				$orders_table,
				$addresses_table,
				$billing_email,
				$orders_table,
				$orders_table,
				$addresses_table,
				$billing_email
			);
		} else {
			return (object) array(
				'orders_count' => 0,
				'total_spend'  => 0,
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared above.
		$row = $wpdb->get_row( $sql );

		if ( $wpdb->last_error ) {
			wc_get_logger()->error(
				sprintf( 'CustomerHistory: Failed to query HPOS order stats. DB error: %s', $wpdb->last_error ),
				array( 'source' => 'customer-history' )
			);
		}

		return $row ?? (object) array(
			'orders_count' => 0,
			'total_spend'  => 0,
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Query customer order stats from CPT tables.
	 *
	 * @param int    $customer_id   The customer user ID.
	 * @param string $billing_email The billing email address.
	 *
	 * @return object Object with orders_count and total_spend properties.
	 */
	private function query_cpt( int $customer_id, string $billing_email ): object {
		global $wpdb;

		$excluded_statuses_sql = $this->get_excluded_statuses_sql();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $customer_id > 0 ) {
			$sql = $wpdb->prepare(
				"SELECT COUNT(*) AS orders_count,
					COALESCE( SUM( filtered.order_total ), 0 ) - COALESCE( SUM( r.refund_total ), 0 ) AS total_spend
				FROM (
					SELECT p.ID, meta_total.meta_value AS order_total
					FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->postmeta} AS meta_customer ON p.ID = meta_customer.post_id
					INNER JOIN {$wpdb->postmeta} AS meta_total ON p.ID = meta_total.post_id
					WHERE meta_customer.meta_key = '_customer_user' AND meta_customer.meta_value = %s
					AND meta_total.meta_key = '_order_total'
					AND p.post_type = 'shop_order' AND p.post_status NOT IN $excluded_statuses_sql
				) AS filtered
				LEFT JOIN (
					SELECT rp.post_parent, SUM( rm.meta_value ) AS refund_total
					FROM {$wpdb->posts} AS rp
					INNER JOIN {$wpdb->postmeta} AS rm ON rp.ID = rm.post_id AND rm.meta_key = '_refund_amount'
					WHERE rp.post_type = 'shop_order_refund'
						AND rp.post_parent IN (
							SELECT p2.ID FROM {$wpdb->posts} AS p2
							INNER JOIN {$wpdb->postmeta} AS mc2 ON p2.ID = mc2.post_id
							WHERE mc2.meta_key = '_customer_user' AND mc2.meta_value = %s
							AND p2.post_type = 'shop_order' AND p2.post_status NOT IN $excluded_statuses_sql
						)
					GROUP BY rp.post_parent
				) AS r ON filtered.ID = r.post_parent",
				(string) $customer_id,
				(string) $customer_id
			);
		} elseif ( '' !== $billing_email ) {
			$sql = $wpdb->prepare(
				"SELECT COUNT(*) AS orders_count,
					COALESCE( SUM( filtered.order_total ), 0 ) - COALESCE( SUM( r.refund_total ), 0 ) AS total_spend
				FROM (
					SELECT p.ID, meta_total.meta_value AS order_total
					FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->postmeta} AS meta_email ON p.ID = meta_email.post_id
					INNER JOIN {$wpdb->postmeta} AS meta_total ON p.ID = meta_total.post_id
					INNER JOIN {$wpdb->postmeta} AS meta_customer ON p.ID = meta_customer.post_id
					WHERE meta_email.meta_key = '_billing_email' AND meta_email.meta_value = %s
					AND meta_customer.meta_key = '_customer_user' AND meta_customer.meta_value = '0'
					AND meta_total.meta_key = '_order_total'
					AND p.post_type = 'shop_order' AND p.post_status NOT IN $excluded_statuses_sql
				) AS filtered
				LEFT JOIN (
					SELECT rp.post_parent, SUM( rm.meta_value ) AS refund_total
					FROM {$wpdb->posts} AS rp
					INNER JOIN {$wpdb->postmeta} AS rm ON rp.ID = rm.post_id AND rm.meta_key = '_refund_amount'
					WHERE rp.post_type = 'shop_order_refund'
						AND rp.post_parent IN (
							SELECT p2.ID FROM {$wpdb->posts} AS p2
							INNER JOIN {$wpdb->postmeta} AS me2 ON p2.ID = me2.post_id
							INNER JOIN {$wpdb->postmeta} AS mc2 ON p2.ID = mc2.post_id
							WHERE me2.meta_key = '_billing_email' AND me2.meta_value = %s
							AND mc2.meta_key = '_customer_user' AND mc2.meta_value = '0'
							AND p2.post_type = 'shop_order' AND p2.post_status NOT IN $excluded_statuses_sql
						)
					GROUP BY rp.post_parent
				) AS r ON filtered.ID = r.post_parent",
				$billing_email,
				$billing_email
			);
		} else {
			return (object) array(
				'orders_count' => 0,
				'total_spend'  => 0,
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is prepared above.
		$row = $wpdb->get_row( $sql );

		if ( $wpdb->last_error ) {
			wc_get_logger()->error(
				sprintf( 'CustomerHistory: Failed to query CPT order stats. DB error: %s', $wpdb->last_error ),
				array( 'source' => 'customer-history' )
			);
		}

		return $row ?? (object) array(
			'orders_count' => 0,
			'total_spend'  => 0,
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get the SQL fragment for excluded order statuses.
	 *
	 * @return string SQL IN clause contents, e.g. ( 'wc-pending', 'wc-failed', ... ).
	 */
	private function get_excluded_statuses_sql(): string {
		$excluded_statuses = get_option( 'woocommerce_excluded_report_order_statuses', array( 'pending', 'failed', 'cancelled' ) );
		if ( ! is_array( $excluded_statuses ) ) {
			$excluded_statuses = array( 'pending', 'failed', 'cancelled' );
		}
		$excluded_statuses = array_merge( array( 'auto-draft', 'trash' ), $excluded_statuses );

		/**
		 * Filter the list of excluded order statuses for analytics reports.
		 *
		 * @since 4.0.0
		 * @param array $excluded_statuses Order statuses to exclude.
		 */
		$excluded_statuses = apply_filters( 'woocommerce_analytics_excluded_order_statuses', $excluded_statuses );
		if ( ! is_array( $excluded_statuses ) ) {
			$excluded_statuses = array( 'auto-draft', 'trash', 'pending', 'failed', 'cancelled' );
		}

		$prefixed = array_map(
			function ( $status ) {
				$status = sanitize_title( $status );
				return 'auto-draft' === $status || 'trash' === $status ? $status : 'wc-' . $status;
			},
			$excluded_statuses
		);

		return "( '" . implode( "','", array_map( 'esc_sql', $prefixed ) ) . "' )";
	}
}
