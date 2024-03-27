<?php
/**
 * CustomMetaDataStore class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores;

use Automattic\WooCommerce\Caching\WPCacheEngine;

/**
 * Implements functions similar to WP's add_metadata(), get_metadata(), and friends using a custom table.
 *
 * @see WC_Data_Store_WP For an implementation using WP's metadata functions and tables.
 */
abstract class CustomMetaDataStore {

	/**
	 * Returns the cache group to store cached data in.
	 * @return string
	 */
	abstract protected function get_cache_group();

	/**
	 * Returns the name of the table used for storage.
	 *
	 * @return string
	 */
	abstract protected function get_table_name();

	/**
	 * Returns the name of the field/column used for identifiying metadata entries.
	 *
	 * @return string
	 */
	protected function get_meta_id_field() {
		return 'id';
	}

	/**
	 * Returns the name of the field/column used for associating meta with objects.
	 *
	 * @return string
	 */
	protected function get_object_id_field() {
		return 'object_id';
	}

	/**
	 * Describes the structure of the metadata table.
	 *
	 * @return array Array elements: table, object_id_field, meta_id_field.
	 */
	protected function get_db_info() {
		return array(
			'table'           => $this->get_table_name(),
			'meta_id_field'   => $this->get_meta_id_field(),
			'object_id_field' => $this->get_object_id_field(),
		);
	}

	/**
	 * Returns an array of meta for an object.
	 *
	 * @param  \WC_Data $object WC_Data object.
	 * @return array
	 */
	public function read_meta( &$object ) {
		$raw_meta_data = $this->get_meta_data_for_object_ids( array( $object->get_id() ) );

		return $raw_meta_data[ $object->get_id() ] ?: array();
	}

	/**
	 * Deletes meta based on meta ID.
	 *
	 * @param  \WC_Data  $object WC_Data object.
	 * @param  \stdClass $meta (containing at least ->id).
	 *
	 * @return bool
	 */
	public function delete_meta( &$object, $meta ) : bool {
		global $wpdb;

		if ( ! isset( $meta->id ) ) {
			return false;
		}

		$db_info = $this->get_db_info();
		$meta_id = absint( $meta->id );

		$successful = (bool) $wpdb->delete( $db_info['table'], array( $db_info['meta_id_field'] => $meta_id, $db_info['object_id_field'] => $object->get_id() ) );
		if ( $successful ) {
			/** @var $cache_engine WPCacheEngine */
			$cache_engine = wc_get_container()->get( WPCacheEngine::class );
			$cache_engine->delete_cached_object( $object->get_id(), $this->get_cache_group() );
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
		global $wpdb;

		$db_info = $this->get_db_info();

		$object_id  = $object->get_id();
		$meta_key   = wp_unslash( wp_slash( $meta->key ) );
		$meta_value = maybe_serialize( is_string( $meta->value ) ? wp_unslash( wp_slash( $meta->value ) ) : $meta->value );

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$result = $wpdb->insert(
			$db_info['table'],
			array(
				$db_info['object_id_field'] => $object_id,
				'meta_key'                  => $meta_key,
				'meta_value'                => $meta_value,
			)
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		$insert_id = $result ? (int) $wpdb->insert_id : false;
		if ( $insert_id !== false ) {
			/** @var $cache_engine WPCacheEngine */
			$cache_engine = wc_get_container()->get( WPCacheEngine::class );
			$cache_engine->delete_cached_object( $object->get_id(), $this->get_cache_group() );
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
	public function update_meta( &$object, $meta ) : bool {
		global $wpdb;

		if ( ! isset( $meta->id ) || empty( $meta->key ) ) {
			return false;
		}

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$data = array(
			'meta_key'   => $meta->key,
			'meta_value' => maybe_serialize( $meta->value ),
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_value,WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		$result = $wpdb->update(
			$this->get_table_name(),
			$data,
			array( $this->get_meta_id_field() => $meta->id, $this->get_object_id_field() => $object->get_id() ),
			'%s',
			'%d'
		);

		$is_successful = 1 === $result;
		if ( $is_successful ) {
			/** @var $cache_engine WPCacheEngine */
			$cache_engine = wc_get_container()->get( WPCacheEngine::class );
			$cache_engine->delete_cached_object( $object->get_id(), $this->get_cache_group() );
		}
		return $is_successful;
	}

	/**
	 * Retrieves metadata by meta ID.
	 *
	 * @param int $meta_id Meta ID.
	 * @return object|bool Metadata object or FALSE if not found.
	 */
	public function get_metadata_by_id( $meta_id ) {
		global $wpdb;

		if ( ! is_numeric( $meta_id ) || floor( $meta_id ) != $meta_id ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			return false;
		}

		$db_info = $this->get_db_info();

		$meta_id = absint( $meta_id );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT {$db_info['meta_id_field']}, meta_key, meta_value, {$db_info['object_id_field']} FROM {$db_info['table']} WHERE {$db_info['meta_id_field']} = %d",
				$meta_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $meta ) ) {
			return false;
		}

		if ( isset( $meta->meta_value ) ) {
			$meta->meta_value = maybe_unserialize( $meta->meta_value );
		}

		return $meta;
	}

	/**
	 * Retrieves metadata by meta key.
	 *
	 * @param \WC_Abstract_Order $object Object ID.
	 * @param string             $meta_key Meta key.
	 *
	 * @return \stdClass|bool Metadata object or FALSE if not found.
	 */
	public function get_metadata_by_key( &$object, string $meta_key ) {
		global $wpdb;

		$db_info = $this->get_db_info();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$db_info['meta_id_field']}, meta_key, meta_value, {$db_info['object_id_field']} FROM {$db_info['table']} WHERE meta_key = %s AND {$db_info['object_id_field']} = %d",
				$meta_key,
				$object->get_id(),
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $meta ) ) {
			return false;
		}

		foreach ( $meta as $row ) {
			if ( isset( $row->meta_value ) ) {
				$row->meta_value = maybe_unserialize( $row->meta_value );
			}
		}

		return $meta;
	}

	/**
	 * Returns distinct meta keys in use.
	 *
	 * @since 8.8.0
	 *
	 * @param int    $limit           Maximum number of meta keys to return. Defaults to 100.
	 * @param string $order           Order to use for the results. Either 'ASC' or 'DESC'. Defaults to 'ASC'.
	 * @param bool   $include_private Whether to include private meta keys in the results. Defaults to FALSE.
	 * @return string[]
	 */
	public function get_meta_keys( $limit = 100, $order = 'ASC', $include_private = false ) {
		global $wpdb;

		$db_info = $this->get_db_info();

		$query = "SELECT DISTINCT meta_key FROM {$db_info['table']} ";

		if ( ! $include_private ) {
			$query .= $wpdb->prepare( 'WHERE meta_key NOT LIKE %s ', $wpdb->esc_like( '_' ) . '%' );
		}

		$order  = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
		$query .= 'ORDER BY meta_key ' . $order . ' ';

		if ( $limit ) {
			$query .= $wpdb->prepare( 'LIMIT %d ', $limit );
		}

		return $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
	}


	public function get_meta_data_for_object_ids( array $ids ): array {
		global $wpdb;

		/** @var $cache_engine WPCacheEngine */
		$cache_engine       = wc_get_container()->get( WPCacheEngine::class );
		$meta_data = $cache_engine->get_cached_objects( $ids, $this->get_cache_group() );
		$meta_data = array_filter( $meta_data );
		$uncached_order_ids = array_diff( $ids, array_keys( $meta_data ) );

		if ( empty( $uncached_order_ids ) ) {
			return $meta_data;
		}

		$id_placeholder   = implode( ', ', array_fill( 0, count( $uncached_order_ids ), '%d' ) );
		$meta_table = $this->get_table_name();
		$object_id_column = $this->get_object_id_field();
		$meta_rows        = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, $object_id_column as object_id, meta_key, meta_value FROM $meta_table WHERE $object_id_column in ( $id_placeholder )",
				$ids
			)
		);

		foreach ( $meta_rows as $meta_row ) {
			if ( ! isset( $meta_data[ $meta_row->object_id ] ) ) {
				$meta_data[ $meta_row->object_id ] = array();
			}
			$meta_data[ $meta_row->object_id ][] = (object) array(
				'meta_id'    => $meta_row->id,
				'meta_key'   => $meta_row->meta_key,
				'meta_value' => $meta_row->meta_value,
			);
		}
		$cache_engine->cache_objects( $meta_data, 0, $this->get_cache_group() );

		return $meta_data;
	}

}
