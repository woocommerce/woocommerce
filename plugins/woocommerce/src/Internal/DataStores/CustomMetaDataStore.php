<?php
/**
 * CustomMetaDataStore class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores;

/**
 * Implements functions similar to WP's add_metadata(), get_metadata(), and friends using a custom table.
 *
 * @see WC_Data_Store_WP For an implementation using WP's metadata functions and tables.
 */
abstract class CustomMetaDataStore {

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
		$object_id     = $object->get_id();
		$raw_meta_data = $this->get_meta_data_for_object_ids( array( $object_id ) );

		return isset( $raw_meta_data[ $object_id ] ) ? (array) $raw_meta_data[ $object_id ] : array();
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

		return (bool) $wpdb->delete(
			$db_info['table'],
			array(
				$db_info['meta_id_field']   => $meta_id,
				$db_info['object_id_field'] => $object->get_id(),
			),
			'%d'
		);
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

		$object_id = $object->get_id();
		if ( ! $object_id ) {
			return false;
		}

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

		return $result ? (int) $wpdb->insert_id : false;
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

		if ( ! isset( $meta->id ) || empty( $meta->key ) || ! $object->get_id() ) {
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
			array(
				$this->get_meta_id_field()   => $meta->id,
				$this->get_object_id_field() => $object->get_id(),
			),
			'%s',
			'%d'
		);

		return 1 === $result;
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
	 * @param \WC_Data $object Object ID.
	 * @param string   $meta_key Meta key.
	 *
	 * @return \stdClass|bool Metadata object or FALSE if not found.
	 */
	public function get_metadata_by_key( &$object, string $meta_key ) {
		global $wpdb;

		if ( ! $object->get_id() ) {
			return false;
		}

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
	 * @param int $limit Maximum number of meta keys to return. Defaults to 100.
	 * @return string[]
	 */
	public function get_meta_keys( int $limit = 100 ): array {
		global $wpdb;

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_key FROM %i WHERE meta_key != '' AND meta_key NOT BETWEEN '_' AND '_z' AND meta_key NOT LIKE %s ORDER BY meta_key ASC LIMIT %d",
				$this->get_db_info()['table'],
				$wpdb->esc_like( '_' ) . '%',
				$limit
			)
		);
	}

	/**
	 * Return order meta data for multiple IDs.
	 *
	 * @param array $object_ids List of object IDs.
	 *
	 * @return \stdClass[][] An array, keyed by object_ids, containing array of raw meta data records for each object. Objects with no meta data will have an empty array.
	 */
	public function get_meta_data_for_object_ids( array $object_ids ): array {
		global $wpdb;

		if ( empty( $object_ids ) ) {
			return array();
		}

		$id_placeholder   = implode( ', ', array_fill( 0, count( $object_ids ), '%d' ) );
		$meta_table       = $this->get_table_name();
		$object_id_column = $this->get_object_id_field();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $object_id_column and $meta_table is hardcoded. IDs are prepared above.
		$meta_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, $object_id_column as object_id, meta_key, meta_value FROM $meta_table WHERE $object_id_column in ( $id_placeholder )",
				$object_ids
			)
		);
		// phpcs:enable

		$meta_data = array_fill_keys( $object_ids, array() );
		foreach ( $meta_rows as $meta_row ) {
			if ( ! isset( $meta_data[ $meta_row->object_id ] ) ) {
				$meta_data[ $meta_row->object_id ] = array();
			}
			$meta_data[ $meta_row->object_id ][] = (object) array(
				'meta_id'    => $meta_row->id,
				'meta_key'   => $meta_row->meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $meta_row->meta_value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			);
		}

		return $meta_data;
	}

}
