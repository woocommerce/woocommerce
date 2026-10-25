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

	// @phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.objectFound

	/**
	 * Deletes meta based on meta ID.
	 *
	 * @param  \WC_Data  $object WC_Data object.
	 * @param  \stdClass $meta (containing at least ->id).
	 *
	 * @return bool
	 */
	public function delete_meta( &$object, $meta ): bool {
		$successful = parent::delete_meta( $object, $meta );
		if ( $successful ) {
			$this->clear_cached_data( array( $object->get_id() ) );
		}

		return $successful;
	}

	/**
	 * Add new piece of meta.
	 *
	 * @param  \WC_Data  $object WC_Data object.
	 * @param  \stdClass $meta (containing ->key and ->value).
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

	// @phpcs:enable Universal.NamingConventions.NoReservedKeywordParameterNames.objectFound

	/**
	 * Return order meta data for multiple IDs. Results are cached.
	 *
	 * @param array $object_ids List of order IDs.
	 *
	 * @return \stdClass[][] An array, keyed by the object IDs, containing arrays of raw meta data for each object.
	 */
	public function get_meta_data_for_object_ids( array $object_ids ): array {
		if ( ! OrderUtil::custom_orders_table_datastore_cache_enabled() ) {
			return parent::get_meta_data_for_object_ids( $object_ids );
		}

		$meta_data  = $this->get_meta_data_for_object_ids_from_cache( $object_ids );
		$object_ids = array_diff( $object_ids, array_keys( $meta_data ) );

		if ( empty( $object_ids ) ) {
			return $meta_data;
		}

		$db_meta_data = parent::get_meta_data_for_object_ids( $object_ids );
		$this->set_meta_data_for_objects_in_cache( $db_meta_data );

		return $db_meta_data + $meta_data;
	}

	/**
	 * Retrieve raw object meta from cache for the given a set of IDs.
	 *
	 * @param int[] $object_ids List of object IDs.
	 *
	 * @return \stdClass[][] An array, keyed by the object IDs, containing arrays of raw meta data for each object.
	 */
	private function get_meta_data_for_object_ids_from_cache( array $object_ids ): array {
		$cache_engine = wc_get_container()->get( WPCacheEngine::class );
		$meta_data    = $cache_engine->get_cached_objects( $object_ids, $this->get_cache_group() );

		foreach ( $meta_data as $object_id => $object_meta ) {
			if ( null === $object_meta ) {
				unset( $meta_data[ $object_id ] );
				continue;
			}

			if ( $this->is_valid_cached_meta( $object_meta ) ) {
				continue;
			}

			/*
			 * A malformed cache entry - not an array, or an array whose elements are not complete
			 * meta rows - would fatal or silently load wrong values downstream
			 * (WC_Data_Store_WP::filter_raw_meta_data() reads $meta->meta_key; WC_Data::init_meta_data()
			 * reads meta_id/meta_key/meta_value). This can happen when a third-party persistent
			 * object cache returns a corrupt or cross-contaminated value. Invalidate the entry and
			 * exclude it from the cache hits so it is re-read from the database in this same
			 * request, and surface it for diagnosis.
			 */
			$cache_engine->delete_cached_object( $object_id, $this->get_cache_group() );
			wc_get_logger()->warning(
				sprintf(
					'Discarded a corrupt HPOS meta cache entry for order %1$d; it will be re-read from the database.',
					(int) $object_id
				),
				array( 'source' => 'hpos-data-cache' )
			);
			unset( $meta_data[ $object_id ] );
		}

		return $meta_data;
	}

	/**
	 * Determine whether a cached meta entry is a well-formed array of meta rows.
	 *
	 * An empty array is valid: it represents an order with no meta. Each non-empty element must be
	 * an object carrying the meta_id, meta_key and meta_value properties that database-backed rows
	 * always have (see CustomMetaDataStore::get_meta_data_for_object_ids()) and that downstream
	 * consumers read.
	 *
	 * @param mixed $object_meta The cached value to validate.
	 *
	 * @return bool True when the value is a usable array of meta rows.
	 */
	private function is_valid_cached_meta( $object_meta ): bool {
		if ( ! is_array( $object_meta ) ) {
			return false;
		}

		foreach ( $object_meta as $meta_row ) {
			if ( ! is_object( $meta_row )
				|| ! property_exists( $meta_row, 'meta_id' )
				|| ! property_exists( $meta_row, 'meta_key' )
				|| ! property_exists( $meta_row, 'meta_value' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Store the raw meta data for a set of objects in cache.
	 *
	 * @param \stdClass[][] $meta_data An array, keyed by the object IDs, containing arrays of raw meta data for each object.
	 *
	 * @return void
	 */
	private function set_meta_data_for_objects_in_cache( array $meta_data ) {
		$cache_engine = wc_get_container()->get( WPCacheEngine::class );
		$cache_engine->cache_objects( $meta_data, 0, $this->get_cache_group() );
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
		if ( ! OrderUtil::custom_orders_table_datastore_cache_enabled() ) {
			return array_fill_keys( $object_ids, true );
		}

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
		if ( ! OrderUtil::custom_orders_table_datastore_cache_enabled() ) {
			return true;
		}

		$cache_engine = wc_get_container()->get( WPCacheEngine::class );

		return $cache_engine->delete_cache_group( $this->get_cache_group() );
	}
}
