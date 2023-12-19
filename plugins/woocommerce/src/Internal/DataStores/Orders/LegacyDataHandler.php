<?php
/**
 * LegacyDataHandler class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * This class provides functionality to clean up post data from the posts table when HPOS is authoritative.
 */
class LegacyDataHandler {

	/**
	 * Instance of the HPOS datastore.
	 *
	 * @var OrdersTableDataStore
	 */
	private OrdersTableDataStore $data_store;

	/**
	 * Instance of the DataSynchronizer class.
	 *
	 * @var DataSynchronizer
	 */
	private DataSynchronizer $data_synchronizer;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @param OrdersTableDataStore $data_store HPOS datastore instance to use.
	 * @param DataSynchronizer     $data_synchronizer DataSynchronizer instance to use.
	 *
	 * @internal
	 */
	final public function init( OrdersTableDataStore $data_store, DataSynchronizer $data_synchronizer ) {
		$this->data_store        = $data_store;
		$this->data_synchronizer = $data_synchronizer;
	}

	/**
	 * Returns the total number of orders for which legacy post data can be removed.
	 *
	 * @param array $order_ids If provided, total is computed only among IDs in this array, which can be either individual IDs or ranges like "100-200".
	 * @return int Number of orders.
	 */
	public function count_orders_for_cleanup( $order_ids = array() ) : int {
		global $wpdb;
		return (int) $wpdb->get_var( $this->build_sql_query_for_cleanup( $order_ids, 'count' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared in build_sql_query_for_cleanup().
	}

	/**
	 * Returns a set of orders for which legacy post data can be removed.
	 *
	 * @param array $order_ids If provided, result is a subset of the order IDs in this array, which can contain either individual order IDs or ranges like "100-200".
	 * @param int   $limit     Limit the number of results.
	 * @return array[int] Order IDs.
	 */
	public function get_orders_for_cleanup( $order_ids = array(), int $limit = 0 ): array {
		global $wpdb;

		return array_map(
			'absint',
			$wpdb->get_col( $this->build_sql_query_for_cleanup( $order_ids, 'ids', $limit ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared in build_sql_query_for_cleanup().
		);
	}

	/**
	 * Builds a SQL statement to either count or obtain IDs for orders in need of cleanup.
	 *
	 * @param array   $order_ids If provided, the query will only include orders in this set of order IDs or ID ranges (like "10-100").
	 * @param string  $result    Use 'count' to build a query that returns a count. Otherwise, the query will return order IDs.
	 * @param integer $limit     If provided, the query will be limited to this number of results. Does not apply when $result is 'count'.
	 * @return string SQL query.
	 */
	private function build_sql_query_for_cleanup( array $order_ids = array(), string $result = 'ids', int $limit = 0 ): string {
		global $wpdb;

		$sql_where = '';

		if ( $order_ids ) {
			// Expand ranges in $order_ids as needed to build the WHERE clause.
			$where_ids    = array();
			$where_ranges = array();

			foreach ( $order_ids as &$arg ) {
				if ( is_numeric( $arg ) ) {
					$where_ids[] = absint( $arg );
				} elseif ( preg_match( '/^(\d+)-(\d+)$/', $arg, $matches ) ) {
					$where_ranges[] = $wpdb->prepare( "({$wpdb->posts}.ID >= %d AND {$wpdb->posts}.ID <= %d)", absint( $matches[1] ), absint( $matches[2] ) );
				}
			}

			if ( $where_ids ) {
				$where_ranges[] = "{$wpdb->posts}.ID IN (" . implode( ',', $where_ids ) . ')';
			}

			if ( ! $where_ranges ) {
				$sql_where .= '1=0';
			} else {
				$sql_where .= '(' . implode( ' OR ', $where_ranges ) . ')';
			}
		}

		$sql_where .= $sql_where ? ' AND ' : '';

		// Post type handling.
		$sql_where .= '(';
		$sql_where .= "{$wpdb->posts}.post_type IN ('" . implode( "', '", esc_sql( wc_get_order_types( 'cot-migration' ) ) ) . "')";
		$sql_where .= $wpdb->prepare(
			" OR (post_type = %s AND EXISTS(SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID))",
			$this->data_synchronizer::PLACEHOLDER_ORDER_POST_TYPE
		);
		$sql_where .= ')';

		// Exclude 'auto-draft' since those go away on their own.
		$sql_where .= $wpdb->prepare( " AND {$wpdb->posts}.post_status != %s", 'auto-draft' );

		if ( 'count' === $result ) {
			$sql_fields = 'COUNT(*)';
			$sql_limit  = '';
		} else {
			$sql_fields = 'ID';
			$sql_limit  = $limit > 0 ? $wpdb->prepare( 'LIMIT %d', $limit ) : '';
		}

		return "SELECT {$sql_fields} FROM {$wpdb->posts} WHERE {$sql_where} {$sql_limit}";
	}

	/**
	 * Performs a cleanup of post data for a given order and also converts the post to the placeholder type in the backup table.
	 *
	 * @param int  $order_id    Order ID.
	 * @param bool $skip_checks Whether to skip the checks that happen before the order is cleaned up.
	 * @return void
	 * @throws \Exception When an error occurs.
	 */
	public function cleanup_post_data( int $order_id, bool $skip_checks = false ): void {
		global $wpdb;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			// translators: %d is an order ID.
			throw new \Exception( sprintf( __( '%d is not a valid order ID.', 'woocommerce' ), $order_id ) );
		}

		if ( ! $skip_checks && ! $this->is_order_newer_than_post( $order ) ) {
			throw new \Exception( sprintf( __( 'Data in posts table appears to be more recent than in HPOS tables.', 'woocommerce' ) ) );
		}

		$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d", $order->get_id() ) );
		foreach ( $meta_ids as $meta_id ) {
			delete_metadata_by_mid( 'post', $meta_id );
		}

		// wp_update_post() changes the post modified date, so we do this manually.
		// Also, we suspect using wp_update_post() could lead to integrations mistakenly updating the entity.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_type = %s, post_status = %s WHERE ID = %d",
				$this->data_synchronizer::PLACEHOLDER_ORDER_POST_TYPE,
				'draft',
				$order->get_id()
			)
		);

		clean_post_cache( $order->get_id() );
	}

	/**
	 * Checks whether an HPOS-backed order is newer than the corresponding post.
	 *
	 * @param int|\WC_Order $order An HPOS order.
	 * @return bool TRUE if the order is up to date with the corresponding post.
	 * @throws \Exception When the order is not an HPOS order.
	 */
	private function is_order_newer_than_post( $order ): bool {
		$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( absint( $order ) );

		if ( ! is_a( $order->get_data_store()->get_current_class_name(), OrdersTableDataStore::class, true ) ) {
			throw new \Exception( __( 'Order is not an HPOS order.', 'woocommerce' ) );
		}

		$post = get_post( $order->get_id() );
		if ( ! $post || $this->data_synchronizer::PLACEHOLDER_ORDER_POST_TYPE === $post->post_type ) {
			return true;
		}

		$order_modified_gmt = $order->get_date_modified() ?? $order->get_date_created();
		$order_modified_gmt = $order_modified_gmt ? $order_modified_gmt->getTimestamp() : 0;
		$post_modified_gmt  = $post->post_modified_gmt ?? $post->post_date_gmt;
		$post_modified_gmt  = ( $post_modified_gmt && '0000-00-00 00:00:00' !== $post_modified_gmt ) ? wc_string_to_timestamp( $post_modified_gmt ) : 0;

		return $order_modified_gmt >= $post_modified_gmt;
	}



}
