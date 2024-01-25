<?php
/**
 * LegacyDataHandler class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Utilities\ArrayUtil;

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
	 * @param \WC_Abstract_Order $order An HPOS order.
	 * @return bool TRUE if the order is up to date with the corresponding post.
	 * @throws \Exception When the order is not an HPOS order.
	 */
	private function is_order_newer_than_post( \WC_Abstract_Order $order ): bool {
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

	/**
	 * Builds an array with properties and metadata for which HPOS and post record have different values.
	 * Given it's mostly informative nature, it doesn't perform any deep or recursive searches and operates only on top-level properties/metadata.
	 *
	 * @since 8.6.0
	 *
	 * @param int $order_id Order ID.
	 * @return array Array of [HPOS value, post value] keyed by property, for all properties where HPOS and post value differ.
	 */
	public function get_diff_for_order( int $order_id ): array {
		$diff = array();

		$hpos_order = $this->get_order_from_datastore( $order_id, 'hpos' );
		$cpt_order  = $this->get_order_from_datastore( $order_id, 'cpt' );

		if ( $hpos_order->get_type() !== $cpt_order->get_type() ) {
			$diff['type'] = array( $hpos_order->get_type(), $cpt_order->get_type() );
		}

		$hpos_meta = $this->order_meta_to_array( $hpos_order );
		$cpt_meta  = $this->order_meta_to_array( $cpt_order );

		// Consider only keys for which we actually have a corresponding HPOS column or are meta.
		$all_keys = array_unique(
			array_diff(
				array_merge(
					$this->get_order_base_props(),
					array_keys( $hpos_meta ),
					array_keys( $cpt_meta )
				),
				$this->data_synchronizer->get_ignored_order_props()
			)
		);

		foreach ( $all_keys as $key ) {
			$val1 = in_array( $key, $this->get_order_base_props(), true ) ? $hpos_order->{"get_$key"}() : ( $hpos_meta[ $key ] ?? null );
			$val2 = in_array( $key, $this->get_order_base_props(), true ) ? $cpt_order->{"get_$key"}() : ( $cpt_meta[ $key ] ?? null );

			// Workaround for https://github.com/woocommerce/woocommerce/issues/43126.
			if ( ! $val2 && in_array( $key, array( '_billing_address_index', '_shipping_address_index' ), true ) ) {
				$val2 = get_post_meta( $order_id, $key, true );
			}

			if ( $val1 != $val2 ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				$diff[ $key ] = array( $val1, $val2 );
			}
		}

		return $diff;
	}

	/**
	 * Returns an order object as seen by either the HPOS or CPT datastores.
	 *
	 * @since 8.6.0
	 *
	 * @param int    $order_id      Order ID.
	 * @param string $data_store_id Datastore to use. Should be either 'hpos' or 'cpt'. Defaults to 'hpos'.
	 * @return \WC_Order Order instance.
	 */
	public function get_order_from_datastore( int $order_id, string $data_store_id = 'hpos' ) {
		$data_store = ( 'hpos' === $data_store_id ) ? $this->data_store : $this->data_store->get_cpt_data_store_instance();

		wp_cache_delete( \WC_Order::generate_meta_cache_key( $order_id, 'orders' ), 'orders' );

		// Prime caches if we can.
		if ( method_exists( $data_store, 'prime_caches_for_orders' ) ) {
			$data_store->prime_caches_for_orders( array( $order_id ), array() );
		}

		$classname = wc_get_order_type( $data_store->get_order_type( $order_id ) )['class_name'];
		$order     = new $classname();
		$order->set_id( $order_id );

		// Switch datastore if necessary.
		$update_data_store_func = function ( $data_store ) {
			// Each order object contains a reference to its data store, but this reference is itself
			// held inside of an instance of WC_Data_Store, so we create that first.
			$data_store_wrapper = \WC_Data_Store::load( 'order' );

			// Bind $data_store to our WC_Data_Store.
			( function ( $data_store ) {
				$this->current_class_name = get_class( $data_store );
				$this->instance           = $data_store;
			} )->call( $data_store_wrapper, $data_store );

			// Finally, update the $order object with our WC_Data_Store( $data_store ) instance.
			$this->data_store = $data_store_wrapper;
		};
		$update_data_store_func->call( $order, $data_store );

		// Read order.
		$data_store->read( $order );

		return $order;
	}

	/**
	 * Returns all metadata in an order object as an array.
	 *
	 * @param \WC_Order $order Order instance.
	 * @return array Array of metadata grouped by meta key.
	 */
	private function order_meta_to_array( \WC_Order &$order ): array {
		$result = array();

		foreach ( ArrayUtil::select( $order->get_meta_data(), 'get_data', ArrayUtil::SELECT_BY_OBJECT_METHOD ) as &$meta ) {
			if ( array_key_exists( $meta['key'], $result ) ) {
				$result[ $meta['key'] ]   = array( $result[ $meta['key'] ] );
				$result[ $meta['key'] ][] = $meta['value'];
			} else {
				$result[ $meta['key'] ] = $meta['value'];
			}
		}

		return $result;
	}

	/**
	 * Returns names of all order base properties supported by HPOS.
	 *
	 * @return string[] Property names.
	 */
	private function get_order_base_props(): array {
		return array_column(
			call_user_func_array(
				'array_merge',
				array_values( $this->data_store->get_all_order_column_mappings() )
			),
			'name'
		);
	}

}
