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
	 * @param  WC_Data $object WC_Data object.
	 * @return array
	 */
	public function read_meta( &$object ) {
		global $wpdb;

		$db_info = $this->get_db_info();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$raw_meta_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$db_info['meta_id_field']} AS meta_id, meta_key, meta_value FROM {$db_info['table']} WHERE {$db_info['object_id_field']} = %d ORDER BY meta_id",
				$object->get_id()
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $raw_meta_data;
	}

	/**
	 * Deletes meta based on meta ID.
	 *
	 * @param  WC_Data  $object WC_Data object.
	 * @param  stdClass $meta (containing at least ->id).
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

		return (bool) $wpdb->delete( $db_info['table'], array( $db_info['meta_id_field'] => $meta_id ) );
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

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update meta.
	 *
	 * @param  WC_Data  $object WC_Data object.
	 * @param  stdClass $meta (containing ->id, ->key and ->value).
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

		$db_info = $this->get_db_info();

		$result = $wpdb->update(
			$db_info['table'],
			$data,
			array( $db_info['meta_id_field'] => $meta->id ),
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
			$query .= $wpdb->prepare( "WHERE meta_key !='' AND meta_key NOT BETWEEN '_' AND '_z' AND meta_key NOT LIKE %s ", $wpdb->esc_like( '_' ) . '%' );
		} else {
			$query .= "WHERE meta_key != '' ";
		}

		$order  = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
		$query .= 'ORDER BY meta_key ' . $order . ' ';

		if ( $limit ) {
			$query .= $wpdb->prepare( 'LIMIT %d ', $limit );
		}

		return $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is prepared.
	}

}
