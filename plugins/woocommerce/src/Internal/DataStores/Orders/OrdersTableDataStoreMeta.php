<?php
/**
 * OrdersTableDataStoreMeta class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Caching\WPCacheEngine;
use Automattic\WooCommerce\Internal\DataStores\CustomMetaDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Mimics a WP metadata (i.e. add_metadata(), get_metadata() and friends) implementation using a custom table.
 */
class OrdersTableDataStoreMeta extends CustomMetaDataStore {

	/**
	 * Returns the cache group to store cached data in.
	 *
	 * @return string
	 */
	protected function get_cache_group() {
		return 'orders_meta';
	}

	/**
	 * Returns the name of the table used for storage.
	 *
	 * @return string
	 */
	protected function get_table_name() {
		return OrdersTableDataStore::get_meta_table_name();
	}

	/**
	 * Returns the name of the field/column used for associating meta with objects.
	 *
	 * @return string
	 */
	protected function get_object_id_field() {
		return 'order_id';
	}

	/**
	 * Returns an array of meta for an object.
	 *
	 * @param  \WC_Data $object WC_Data object.
	 * @return array
	 */
	public function read_meta( &$object ) {
		$raw_meta_data = $this->get_meta_data_for_object_ids( array( $object->get_id() ) );

		return isset( $raw_meta_data[ $object->get_id() ] ) ? (array) $raw_meta_data[ $object->get_id() ] : array();
	}

	/**
	 * Deletes meta based on meta ID.
	 *
	 * @param  \WC_Data  $object WC_Data object.
	 * @param  \stdClass $meta (containing at least ->id).
	 *
	 * @return bool
	 */
	public function delete_meta( &$object, $meta ): bool {
		global $wpdb;

		if ( ! isset( $meta->id ) ) {
			return false;
		}

		$db_info = $this->get_db_info();
		$meta_id = absint( $meta->id );

		$successful = (bool) $wpdb->delete(
			$db_info['table'],
			array(
				$db_info['meta_id_field']   => $meta_id,
				$db_info['object_id_field'] => $object->get_id(),
			)
		);
		if ( $successful ) {
			$this->clear_cached_data( array( $object->get_id() ) );
		}

		return $successful;
	}

	/**
	 * Add new piece of meta.
	 *
	 * @param  WC_Data  $object WC_Data object.
	 * @param  stdClass $meta (containing ->key and ->value).
	 *
	 * @return int|false meta ID
	 */
	public function add_meta( &$object, $meta ) {
		$insert_id = parent::add_meta( $object, $meta );
		if ( false !== $insert_id ) {
			$this->clear_cached_data( array( $object->get_id() ) );
		}

		return $insert_id;
	}

	/**
	 * Update meta.
	 *
	 * @param  \WC_Data  $object WC_Data object.
	 * @param  \stdClass $meta (containing ->id, ->key and ->value).
	 *
	 * @return bool
	 */
	public function update_meta( &$object, $meta ): bool {
		$is_successful = parent::update_meta( $object, $meta );
		if ( $is_successful ) {
			$this->clear_cached_data( array( $object->get_id() ) );
		}

		return $is_successful;
	}

	/**
	 * Return order meta data for multiple IDs. Results are cached.
	 *
	 * @param array $object_ids List of order IDs.
	 *
	 * @return \stdClass[] DB Order objects or error.
	 */
	public function get_meta_data_for_object_ids( array $object_ids ): array {
		if ( ! OrderUtil::custom_orders_table_datastore_cache_enabled() ) {
			return parent::get_meta_data_for_object_ids( $object_ids );
		}
		$cache_engine        = wc_get_container()->get( WPCacheEngine::class );
		$meta_data           = $cache_engine->get_cached_objects( $object_ids, $this->get_cache_group() );
		$meta_data           = array_filter( $meta_data );
		$uncached_object_ids = array_diff( $object_ids, array_keys( $meta_data ) );

		if ( empty( $uncached_order_ids ) ) {
			return $meta_data;
		}

		$uncached_meta_data = parent::get_meta_data_for_object_ids( $uncached_object_ids );
		$cache_engine->cache_objects( $uncached_meta_data, 0, $this->get_cache_group() );

		return $uncached_meta_data + $meta_data;
	}

	/**
	 * Delete cached meta data for the given object_ids.
	 *
	 * @internal This method should only be used by internally and in cases where the CRUD operations of this datastore
	 *           are bypassed for performance purposes. This interface is not guaranteed.
	 *
	 * @param array $object_ids The object_ids to delete cache for.
	 *
	 * @return bool[] Array of return values, grouped by the object_id. Each value is either true on success, or false
	 *                if the contents were not deleted.
	 */
	public function clear_cached_data( array $object_ids ): array {
		$cache_engine  = wc_get_container()->get( WPCacheEngine::class );
		$return_values = array();
		foreach ( $object_ids as $object_id ) {
			$return_values[ $object_id ] = $cache_engine->delete_cached_object( $object_id, $this->get_cache_group() );
		}
		return $return_values;
	}

	/**
	 * Invalidate all the cache used by this data store.
	 *
	 * @internal This method should only be used by internally and in cases where the CRUD operations of this datastore
	 *           are bypassed for performance purposes. This interface is not guaranteed.
	 *
	 * @return bool Whether the cache as fully invalidated.
	 */
	public function clear_all_cached_data(): bool {
		$cache_engine = wc_get_container()->get( WPCacheEngine::class );

		return $cache_engine->delete_cache_group( $this->get_cache_group() );
	}
}
