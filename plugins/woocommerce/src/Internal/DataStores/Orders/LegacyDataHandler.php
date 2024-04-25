<?php
/**
 * LegacyDataHandler class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Utilities\ArrayUtil;
use WC_Abstract_Order;

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
	 * Instance of the PostsToOrdersMigrationController.
	 *
	 * @var PostsToOrdersMigrationController
	 */
	private PostsToOrdersMigrationController $posts_to_cot_migrator;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @param OrdersTableDataStore             $data_store            HPOS datastore instance to use.
	 * @param DataSynchronizer                 $data_synchronizer     DataSynchronizer instance to use.
	 * @param PostsToOrdersMigrationController $posts_to_cot_migrator Posts to HPOS migration controller instance to use.
	 *
	 * @internal
	 */
	final public function init( OrdersTableDataStore $data_store, DataSynchronizer $data_synchronizer, PostsToOrdersMigrationController $posts_to_cot_migrator ) {
		$this->data_store            = $data_store;
		$this->data_synchronizer     = $data_synchronizer;
		$this->posts_to_cot_migrator = $posts_to_cot_migrator;
	}

	/**
	 * Returns the total number of orders for which legacy post data can be removed.
	 *
	 * @param array $order_ids If provided, total is computed only among IDs in this array, which can be either individual IDs or ranges like "100-200".
	 * @return int Number of orders.
	 */
	public function count_orders_for_cleanup( $order_ids = array() ): int {
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

		$post_is_placeholder = get_post_type( $order_id ) === $this->data_synchronizer::PLACEHOLDER_ORDER_POST_TYPE;
		if ( ! $post_is_placeholder ) {
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				// translators: %d is an order ID.
				throw new \Exception( esc_html( sprintf( __( '%d is not a valid order ID.', 'woocommerce' ), $order_id ) ) );
			}

			if ( ! $skip_checks && ! $this->is_order_newer_than_post( $order ) ) {
				// translators: %1 is an order ID.
				throw new \Exception( esc_html( sprintf( __( 'Data in posts table appears to be more recent than in HPOS tables. Compare order data with `wp wc hpos diff %1$d` and use `wp wc hpos backfill %1$d --from=posts --to=hpos` to fix.', 'woocommerce' ), $order_id ) ) );
			}
		}

		// Delete all metadata.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d",
				$order_id
			)
		);

		// wp_update_post() changes the post modified date, so we do this manually.
		// Also, we suspect using wp_update_post() could lead to integrations mistakenly updating the entity.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_type = %s, post_status = %s WHERE ID = %d",
				$this->data_synchronizer::PLACEHOLDER_ORDER_POST_TYPE,
				'draft',
				$order_id
			)
		);

		clean_post_cache( $order_id );
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
			throw new \Exception( esc_html__( 'Order is not an HPOS order.', 'woocommerce' ) );
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
		$cpt_order  = $this->get_order_from_datastore( $order_id, 'posts' );

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

			if ( $val1 != $val2 ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison,Universal.Operators.StrictComparisons.LooseNotEqual
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
	 * @param string $data_store_id Datastore to use. Should be either 'hpos' or 'posts'. Defaults to 'hpos'.
	 * @return \WC_Order Order instance.
	 * @throws \Exception When an error occurs.
	 */
	public function get_order_from_datastore( int $order_id, string $data_store_id = 'hpos' ) {
		$data_store = ( 'hpos' === $data_store_id ) ? $this->data_store : $this->data_store->get_cpt_data_store_instance();

		wp_cache_delete( \WC_Order::generate_meta_cache_key( $order_id, 'orders' ), 'orders' );

		// Prime caches if we can.
		if ( method_exists( $data_store, 'prime_caches_for_orders' ) ) {
			$data_store->prime_caches_for_orders( array( $order_id ), array() );
		}

		$order_type = wc_get_order_type( $data_store->get_order_type( $order_id ) );

		if ( ! $order_type ) {
			// translators: %d is an order ID.
			throw new \Exception( esc_html( sprintf( __( '%d is not an order or has an invalid order type.', 'woocommerce' ), $order_id ) ) );
		}

		$classname = $order_type['class_name'];
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
	 * Backfills an order from/to the CPT or HPOS datastore.
	 *
	 * @since 8.7.0
	 *
	 * @param int    $order_id               Order ID.
	 * @param string $source_data_store      Datastore to use as source. Should be either 'hpos' or 'posts'.
	 * @param string $destination_data_store Datastore to use as destination. Should be either 'hpos' or 'posts'.
	 * @param array  $fields                 List of metakeys or order properties to limit the backfill to.
	 * @return void
	 * @throws \Exception When an error occurs.
	 */
	public function backfill_order_to_datastore( int $order_id, string $source_data_store, string $destination_data_store, array $fields = array() ) {
		$valid_data_stores = array( 'posts', 'hpos' );

		if ( ! in_array( $source_data_store, $valid_data_stores, true ) || ! in_array( $destination_data_store, $valid_data_stores, true ) || $destination_data_store === $source_data_store ) {
			throw new \Exception( esc_html( sprintf( 'Invalid datastore arguments: %1$s -> %2$s.', $source_data_store, $destination_data_store ) ) );
		}

		$fields    = array_filter( $fields );
		$src_order = $this->get_order_from_datastore( $order_id, $source_data_store );

		// Backfill entire orders.
		if ( ! $fields ) {
			if ( 'posts' === $destination_data_store ) {
				$src_order->get_data_store()->backfill_post_record( $src_order );
			} elseif ( 'hpos' === $destination_data_store ) {
				$this->posts_to_cot_migrator->migrate_orders( array( $src_order->get_id() ) );
			}

			return;
		}

		$this->validate_backfill_fields( $fields, $src_order );

		$dest_order = $this->get_order_from_datastore( $src_order->get_id(), $destination_data_store );

		if ( 'posts' === $destination_data_store ) {
			$datastore = $this->data_store->get_cpt_data_store_instance();
		} elseif ( 'hpos' === $destination_data_store ) {
			$datastore = $this->data_store;
		}

		if ( ! $datastore || ! method_exists( $datastore, 'update_order_from_object' ) ) {
			throw new \Exception( esc_html__( 'The backup datastore does not support updating orders.', 'woocommerce' ) );
		}

		// Backfill meta.
		if ( ! empty( $fields['meta_keys'] ) ) {
			foreach ( $fields['meta_keys'] as $meta_key ) {
				$dest_order->delete_meta_data( $meta_key );

				foreach ( $src_order->get_meta( $meta_key, false, 'edit' ) as $meta ) {
					$dest_order->add_meta_data( $meta_key, $meta->value );
				}
			}
		}

		// Backfill props.
		if ( ! empty( $fields['props'] ) ) {
			$new_values = array_combine(
				$fields['props'],
				array_map(
					fn( $prop_name ) => $src_order->{"get_{$prop_name}"}(),
					$fields['props']
				)
			);

			$dest_order->set_props( $new_values );

			if ( 'hpos' === $destination_data_store ) {
				$dest_order->apply_changes();
				$limit_cb = function ( $rows, $order ) use ( $dest_order, $fields ) {
					if ( $dest_order->get_id() === $order->get_id() ) {
						$rows = $this->limit_hpos_update_to_props( $rows, $fields['props'] );
					}

					return $rows;
				};
				add_filter( 'woocommerce_orders_table_datastore_db_rows_for_order', $limit_cb, 10, 2 );
			}
		}

		$datastore->update_order_from_object( $dest_order );

		if ( 'hpos' === $destination_data_store && isset( $limit_cb ) ) {
			remove_filter( 'woocommerce_orders_table_datastore_db_rows_for_order', $limit_cb );
		}
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
		$base_props = array();

		foreach ( $this->data_store->get_all_order_column_mappings() as $mapping ) {
			$base_props = array_merge( $base_props, array_column( $mapping, 'name' ) );
		}

		return $base_props;
	}

	/**
	 * Filters a set of HPOS row updates to those matching a specific set of order properties.
	 * Called via the `woocommerce_orders_table_datastore_db_rows_for_order` filter in `backfill_order_to_datastore`.
	 *
	 * @param array    $rows  Details for the db update.
	 * @param string[] $props Order property names.
	 * @return array
	 * @see OrdersTableDataStore::get_db_rows_for_order()
	 */
	private function limit_hpos_update_to_props( array $rows, array $props ) {
		// Determine HPOS columns corresponding to the props in the $props array.
		$allowed_columns = array();
		foreach ( $this->data_store->get_all_order_column_mappings() as &$mapping ) {
			foreach ( $mapping as $column_name => &$column_data ) {
				if ( ! isset( $column_data['name'] ) || ! in_array( $column_data['name'], $props, true ) ) {
					continue;
				}

				$allowed_columns[ $column_data['name'] ] = $column_name;
			}
		}

		foreach ( $rows as $i => &$db_update ) {
			// Prevent accidental update of another prop by limiting columns to explicitly requested props.
			if ( ! array_intersect_key( $db_update['data'], array_flip( $allowed_columns ) ) ) {
				unset( $rows[ $i ] );
				continue;
			}

			$allowed_column_names_with_ids = array_merge(
				$allowed_columns,
				array( 'id', 'order_id', 'address_type' )
			);

			$db_update['data']   = array_intersect_key( $db_update['data'], array_flip( $allowed_column_names_with_ids ) );
			$db_update['format'] = array_intersect_key( $db_update['format'], array_flip( $allowed_column_names_with_ids ) );
		}

		return $rows;
	}

	/**
	 * Validates meta_keys and property names for a partial order backfill.
	 *
	 * @param array              $fields An array possibly having entries with index 'meta_keys' and/or 'props',
	 *                                   corresponding to an array of order meta keys and/or order properties.
	 * @param \WC_Abstract_Order $order  The order being validated.
	 * @throws \Exception When a validation error occurs.
	 * @return void
	 */
	private function validate_backfill_fields( array $fields, \WC_Abstract_Order $order ) {
		if ( ! $fields ) {
			return;
		}

		if ( ! empty( $fields['meta_keys'] ) ) {
			$internal_meta_keys = array_unique(
				array_merge(
					$this->data_store->get_internal_meta_keys(),
					$this->data_store->get_cpt_data_store_instance()->get_internal_meta_keys()
				)
			);

			$possibly_internal_keys = array_intersect( $internal_meta_keys, $fields['meta_keys'] );
			if ( ! empty( $possibly_internal_keys ) ) {
				throw new \Exception(
					esc_html(
						sprintf(
							// translators: %s is a comma separated list of metakey names.
							_n(
								'%s is an internal meta key. Use --props to set it.',
								'%s are internal meta keys. Use --props to set them.',
								count( $possibly_internal_keys ),
								'woocommerce'
							),
							implode( ', ', $possibly_internal_keys )
						)
					)
				);
			}
		}

		if ( ! empty( $fields['props'] ) ) {
			$invalid_props = array_filter(
				$fields['props'],
				function ( $prop_name ) use ( $order ) {
					return ! method_exists( $order, "get_{$prop_name}" );
				}
			);

			if ( ! empty( $invalid_props ) ) {
				throw new \Exception(
					esc_html(
						sprintf(
							// translators: %s is a list of order property names.
							_n(
								'%s is not a valid order property.',
								'%s are not valid order properties.',
								count( $invalid_props ),
								'woocommerce'
							),
							implode( ', ', $invalid_props )
						)
					)
				);
			}
		}
	}
}
