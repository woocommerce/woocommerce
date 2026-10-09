<?php
/**
 * OrderModifiedDateUpdater class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Updates the modified date of an order without saving the whole order.
 *
 * Used when a change to related data (such as deleting a refund) modifies the order,
 * but a full save would fire order update hooks for a change that isn't an order edit.
 *
 * @since 11.3.0
 */
class OrderModifiedDateUpdater {

	/**
	 * The data synchronizer.
	 *
	 * @var DataSynchronizer
	 */
	private $data_synchronizer;

	/**
	 * The orders table data store.
	 *
	 * @var OrdersTableDataStore
	 */
	private $orders_table_data_store;

	/**
	 * The order cache.
	 *
	 * @var OrderCache
	 */
	private $order_cache;

	/**
	 * Initialize the class dependencies.
	 *
	 * @internal
	 *
	 * @param DataSynchronizer     $data_synchronizer       The data synchronizer.
	 * @param OrdersTableDataStore $orders_table_data_store The orders table data store.
	 * @param OrderCache           $order_cache             The order cache.
	 */
	final public function init( DataSynchronizer $data_synchronizer, OrdersTableDataStore $orders_table_data_store, OrderCache $order_cache ): void {
		$this->data_synchronizer       = $data_synchronizer;
		$this->orders_table_data_store = $orders_table_data_store;
		$this->order_cache             = $order_cache;
	}

	/**
	 * Set the modified date of an order to the current time.
	 *
	 * When sync is enabled, both order tables get the same value so the order isn't flagged as out of sync.
	 *
	 * @since 11.3.0
	 *
	 * @param int $order_id The order ID.
	 */
	public function update_modified_date( int $order_id ): void {
		global $wpdb;

		if ( $order_id <= 0 ) {
			return;
		}

		$now_gmt            = current_time( 'mysql', true );
		$hpos_authoritative = OrderUtil::custom_orders_table_usage_is_enabled();
		$sync_enabled       = $this->data_synchronizer->data_sync_is_enabled();

		if ( $hpos_authoritative || $sync_enabled ) {
			$wpdb->update(
				OrdersTableDataStore::get_orders_table_name(),
				array( 'date_updated_gmt' => $now_gmt ),
				array( 'id' => $order_id ),
				array( '%s' ),
				array( '%d' )
			);
			$this->orders_table_data_store->clear_cached_data( array( $order_id ) );
		}

		if ( ! $hpos_authoritative || $sync_enabled ) {
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_modified'     => get_date_from_gmt( $now_gmt ),
					'post_modified_gmt' => $now_gmt,
				),
				array( 'ID' => $order_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			clean_post_cache( $order_id );
		}

		$this->order_cache->remove( $order_id );
	}
}
