<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* Abstract WC Data Class
*
* Implemented by classes using the same CRUD(s) pattern.
*
* @version  2.6.0
* @package  WooCommerce/Abstracts
* @category Abstract Class
* @author   WooThemes
*/
abstract class WC_Data {

	/**
	 * Core data for this object, name value pairs (name + default value).
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $_cache_group = '';

	/**
	 * Meta type. This should match up with
	 * the types avaiable at https://codex.wordpress.org/Function_Reference/add_metadata.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 */
	protected $_meta_type = 'post';

	/**
	 * This only needs set if you are using a custom metadata type (for example payment tokens.
	 * This should be the name of the field your table uses for associating meta with objects.
	 * For example, in payment_tokenmeta, this would be payment_token_id.
	 * @var string
	 */
	protected $object_id_field_for_meta = '';

	/**
	 * Stores additonal meta data.
	 * @var array
	 */
	protected $_meta_data = array();

	/**
	 * Internal meta keys we don't want exposed for the object.
	 * @var array
	 */
	protected $_internal_meta_keys = array();

	/**
	 * Returns the unique ID for this object.
	 * @return int
	 */
	abstract public function get_id();

	/**
	 * Creates new object in the database.
	 */
	abstract public function create();

	/**
	 * Read object from the database.
	 * @param int ID of the object to load.
	 */
	abstract public function read( $id );

	/**
	 * Updates object data in the database.
	 */
	abstract public function update();

	/**
	 * Updates object data in the database.
	 */
	abstract public function delete();

	/**
	 * Save should create or update based on object existance.
	 */
	abstract public function save();

	/**
	 * Change data to JSON format.
	 * @return string Data in JSON format.
	 */
	public function __toString() {
		return json_encode( $this->get_data() );
	}

	/**
	 * Returns all data for this object.
	 * @return array
	 */
	public function get_data() {
		return array_merge( $this->_data, array( 'meta_data' => $this->get_meta_data() ) );
	}

	/**
	 * Get All Meta Data
	 * @since 2.6.0
	 * @return array
	 */
	public function get_meta_data() {
		return $this->_meta_data;
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data. This is in
	 * addition to all data props with _ prefix.
	 * @since 2.6.0
	 * @return array
	 */
	protected function prefix_key( $key ) {
		return '_' === substr( $key, 0, 1 ) ? $key : '_' . $key;
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data. This is in
	 * addition to all data props with _ prefix.
	 * @since 2.6.0
	 * @return array
	 */
	protected function get_internal_meta_keys() {
		return array_merge( array_map( array( $this, 'prefix_key' ), array_keys( $this->_data ) ), $this->_internal_meta_keys );
	}

	/**
	 * Get Meta Data by Key.
	 * @since 2.6.0
	 * @param  string $key
	 * @param  bool $single return first found meta with key, or all with $key
	 * @return mixed
	 */
	public function get_meta( $key = '', $single = true ) {
		$array_keys = array_keys( wp_list_pluck( $this->_meta_data, 'key' ), $key );
		$value    = '';

		if ( ! empty( $array_keys ) ) {
			if ( $single ) {
				$value = $this->_meta_data[ current( $array_keys ) ]->value;
			} else {
				$value = array_intersect_key( $this->_meta_data, array_flip( $array_keys ) );
			}
		}

		return $value;
	}

	/**
	 * Set all meta data from array.
	 * @since 2.6.0
	 * @param array $data Key/Value pairs
	 */
	public function set_meta_data( $data ) {
		if ( ! empty( $data ) && is_array( $data ) ) {
			foreach ( $data as $meta ) {
				$meta = (array) $meta;
				if ( isset( $meta['key'], $meta['value'], $meta['meta_id'] ) ) {
					$this->_meta_data[] = (object) array(
						'key'     => $meta['key'],
						'value'   => $meta['value'],
						'meta_id' => $meta['meta_id'],
					);
				}
			}
		}
	}

	/**
	 * Add meta data.
	 * @since 2.6.0
	 * @param string $key Meta key
	 * @param string $value Meta value
	 * @param bool $unique Should this be a unique key?
	 */
	public function add_meta_data( $key, $value, $unique = false ) {
		if ( $unique ) {
			$array_keys       = array_keys( wp_list_pluck( $this->_meta_data, 'key' ), $key );
			$this->_meta_data = array_diff_key( $this->_meta_data, array_fill_keys( $array_keys, '' ) );
		}
		$this->_meta_data[] = (object) array(
			'key'   => $key,
			'value' => $value,
		);
	}

	/**
	 * Update meta data by key or ID, if provided.
	 * @since 2.6.0
	 * @param  string $key
	 * @param  string $value
	 * @param  int $meta_id
	 */
	public function update_meta_data( $key, $value, $meta_id = '' ) {
		$array_key = '';
		if ( $meta_id ) {
			$array_key = array_keys( wp_list_pluck( $this->_meta_data, 'meta_id' ), $meta_id );
		}
		if ( $array_key ) {
			$this->_meta_data[ current( $array_key ) ] = (object) array(
				'key'     => $key,
				'value'   => $value,
				'meta_id' => $meta_id,
			);
		} else {
			$this->add_meta_data( $key, $value, true );
		}
	}

	/**
	 * Delete meta data.
	 * @since 2.6.0
	 * @param array $key Meta key
	 */
	public function delete_meta_data( $key ) {
		$array_keys         = array_keys( wp_list_pluck( $this->_meta_data, 'key' ), $key );
		$this->_meta_data   = array_diff_key( $this->_meta_data, array_fill_keys( $array_keys, '' ) );
	}

	/**
	 * Read Meta Data from the database. Ignore any internal properties.
	 * @since 2.6.0
	 */
	protected function read_meta_data() {
		$this->_meta_data = array();
		$cache_loaded     = false;

		if ( ! $this->get_id() ) {
			return;
		}

		if ( ! empty ( $this->_cache_group ) ) {
			$cache_key   = WC_Cache_Helper::get_cache_prefix( $this->_cache_group ) . $this->get_id();
			$cached_meta = wp_cache_get( $cache_key, $this->_cache_group );

			if ( false !== $cached_meta ) {
				$this->_meta_data = $cached_meta;
				$cache_loaded = true;
			}
		}

		if ( ! $cache_loaded ) {
			global $wpdb;
			$db_info = $this->_get_db_info();
			$raw_meta_data = $wpdb->get_results( $wpdb->prepare( "
				SELECT " . $db_info['meta_id_field'] . ", meta_key, meta_value
				FROM " . $db_info['table'] . "
				WHERE " . $db_info['object_id_field'] . " = %d ORDER BY " . $db_info['meta_id_field'] . "
			", $this->get_id() ) );

			foreach ( $raw_meta_data as $meta ) {
				if ( in_array( $meta->meta_key, $this->get_internal_meta_keys() ) ) {
					continue;
				}
				$this->_meta_data[] = (object) array(
					'key'     => $meta->meta_key,
					'value'   => $meta->meta_value,
					'meta_id' => $meta->{ $db_info['meta_id_field'] },
				);
			}

			if ( ! empty ( $this->_cache_group ) ) {
				wp_cache_set( $cache_key, $this->_meta_data, $this->_cache_group );
			}
		}
	}

	/**
	 * Update Meta Data in the database.
	 * @since 2.6.0
	 */
	protected function save_meta_data() {
		global $wpdb;
		$db_info = $this->_get_db_info();
		$all_meta_ids = array_map( 'absint', $wpdb->get_col( $wpdb->prepare( "
			SELECT " . $db_info['meta_id_field'] . " FROM " . $db_info['table'] . "
			WHERE " . $db_info['object_id_field'] . " = %d", $this->get_id() ) . "
			AND meta_key NOT IN ('" . implode( "','", array_map( 'esc_sql', $this->get_internal_meta_keys() ) ) . "');
		" ) );
		$set_meta_ids = array();

		foreach ( $this->_meta_data as $array_key => $meta ) {
			if ( empty( $meta->meta_id ) ) {
				$new_meta_id    = add_metadata( $this->_meta_type, $this->get_id(), $meta->key, $meta->value, false );
				$set_meta_ids[] = $new_meta_id;
				$this->_meta_data[ $array_key ]->meta_id = $new_meta_id;
			} else {
				update_metadata_by_mid( $this->_meta_type, $meta->meta_id, $meta->value, $meta->key );
				$set_meta_ids[] = absint( $meta->meta_id );
			}
		}

		// Delete no longer set meta data
		$delete_meta_ids = array_diff( $all_meta_ids, $set_meta_ids );

		foreach ( $delete_meta_ids as $meta_id ) {
			delete_metadata_by_mid( $this->_meta_type, $meta_id );
		}

		if ( ! empty ( $this->_cache_group ) ) {
			WC_Cache_Helper::incr_cache_prefix( $this->_cache_group );
		}
		$this->read_meta_data();
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 * @since 2.6.0
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function _get_db_info() {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->prefix;

		// If we are dealing with a type of metadata that is not a core type, the table should be prefixed.
		if ( ! in_array( $this->_meta_type, array( 'post', 'user', 'comment', 'term' ) ) ) {
			$table .= 'woocommerce_';
		}

		$table .= $this->_meta_type . 'meta';
		$object_id_field = $this->_meta_type . '_id';

		// Figure out our field names.
		if ( 'user' === $this->_meta_type ) {
			$meta_id_field   = 'umeta_id';
		}

		if ( ! empty( $this->object_id_field_for_meta ) ) {
			$object_id_field = $this->object_id_field_for_meta;
		}

		return array(
			'table'           => $table,
			'object_id_field' => $object_id_field,
			'meta_id_field'   => $meta_id_field,
		);
	}

}
