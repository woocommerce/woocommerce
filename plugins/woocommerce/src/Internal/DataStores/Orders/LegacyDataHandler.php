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
		return (int) $wpdb->get_var( $this->build_sql_query_for_cleanup( $order_ids, 'count' ) );
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
			$wpdb->get_col( $this->build_sql_query_for_cleanup( $order_ids, 'ids', $limit ) )
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

		$order_types                 = wc_get_order_types( 'cot-migration' );
		$sql_fields                  = 'count' === $result ? 'COUNT(*)' : 'ID as id';
		$sql_order_types_placeholder = implode( ', ', array_fill( 0, count( $order_types ), '%s' ) );
		$sql_limit_placeholder       = ( 'count' !== $result && $limit ) ? 'LIMIT %d' : '';

		if ( $order_ids ) {
			// Expand ranges in $order_ids as needed to build the WHERE clause.
			$sql_ids    = '';
			$sql_ranges = array();

			foreach ( $order_ids as &$arg ) {
				if ( is_numeric( $arg ) ) {
					$sql_ids .= $wpdb->prepare( '%d,', absint( $arg ) );
				} elseif ( preg_match( '/^(\d+)-(\d+)$/', $arg, $matches ) ) {
					$sql_ranges[] = $wpdb->prepare( '(id >= %d AND id <= %d)', array( absint( $matches[1] ), absint( $matches[2] ) ) );
				}
			}

			if ( $sql_ids ) {
				$sql_ids = substr( $sql_ids, 0, -1 );
				$sql_ids = "(id IN ({$sql_ids}))";
			}

			if ( ! $sql_ids && ! $sql_ranges ) {
				$sql_where = '1=0';
			} else {
				$sql_where = implode( ' OR ', array_filter( array_merge( array( $sql_ids ), $sql_ranges ) ) );
			}
		} else {
			$sql_where = '1=1';
		}

		$sql = $wpdb->prepare(
			"SELECT {$sql_fields} FROM {$wpdb->posts} WHERE post_type IN ({$sql_order_types_placeholder}) AND ({$sql_where}) {$sql_limit_placeholder}",
			array_merge( $order_types, $limit ? array( $limit ) : array() )
		);

		return $sql;
	}
}
