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
	 * ID for this object.
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Core data for this object. Name value pairs (name + default value).
	 * @var array
	 */
	protected $data = array();

	/**
	 * Extra data for this object. Name value pairs (name + default value).
	 * Used as a standard way for sub classes (like product types) to add
	 * additional information to an inherited class.
	 * @var array
	 */
	protected $extra_data = array();

	/**
	 * Set to _data on construct so we can track and reset data if needed.
	 * @var array
	 */
	protected $default_data = array();

	/**
	 * Contains a reference to the data store for this class.
	 * @var object
	 */
	protected $data_store;

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $cache_group = '';

	/**
	 * Meta type. This should match up with
	 * the types avaiable at https://codex.wordpress.org/Function_Reference/add_metadata.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 */
	protected $meta_type = 'post';

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
	protected $meta_data = array();

	/**
	 * Internal meta keys we don't want exposed for the object.
	 * @var array
	 */
	protected $internal_meta_keys = array();

	/**
	 * Default constructor.
	 * @param int|object|array $read ID to load from the DB (optional) or already queried data.
	 */
	public function __construct( $read = 0 ) {
		$this->default_data = $this->data;
	}

	/**
	 * Returns the unique ID for this object.
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Updates object data in the database.
	 */
	public function delete( $force_delete = false ) {
		if ( $this->data_store ) {
			$this->data_store->delete( $this, $force_delete );
		}
	}

	/**
	 * Save should create or update based on object existance.
	 */
	public function save() {
		if ( $this->data_store ) {
			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}
			return $this->get_id();
		}
	}

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
		return array_merge( array( 'id' => $this->get_id() ), $this->data, array( 'meta_data' => $this->get_meta_data() ) );
	}

	/**
	 * Returns array of expected data keys for this object.
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_data_keys() {
		return array_keys( $this->data );
	}

	/**
	 * Returns all "extra" data keys for an object (for sub objects like product types).
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_extra_data_keys() {
		return array_keys( $this->extra_data );
	}

	/**
	 * Filter null meta values from array.
	 * @return bool
	 */
	protected function filter_null_meta( $meta ) {
		return ! is_null( $meta->value );
	}

	/**
	 * Get All Meta Data.
	 * @since 2.6.0
	 * @return array
	 */
	public function get_meta_data() {
		return array_filter( $this->meta_data, array( $this, 'filter_null_meta' ) );
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
		return array_merge( array_map( array( $this, 'prefix_key' ), array_keys( $this->data ) ), $this->internal_meta_keys );
	}

	/**
	 * Get Meta Data by Key.
	 * @since 2.6.0
	 * @param  string $key
	 * @param  bool $single return first found meta with key, or all with $key
	 * @return mixed
	 */
	public function get_meta( $key = '', $single = true ) {
		$array_keys = array_keys( wp_list_pluck( $this->get_meta_data(), 'key' ), $key );
		$value    = '';

		if ( ! empty( $array_keys ) ) {
			if ( $single ) {
				$value = $this->meta_data[ current( $array_keys ) ]->value;
			} else {
				$value = array_intersect_key( $this->meta_data, array_flip( $array_keys ) );
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
				if ( isset( $meta['key'], $meta['value'], $meta['id'] ) ) {
					$this->meta_data[] = (object) array(
						'id'    => $meta['id'],
						'key'   => $meta['key'],
						'value' => $meta['value'],
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
			$this->delete_meta_data( $key );
		}
		$this->meta_data[] = (object) array(
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
			$array_key = array_keys( wp_list_pluck( $this->meta_data, 'id' ), $meta_id );
		}
		if ( $array_key ) {
			$this->meta_data[ current( $array_key ) ] = (object) array(
				'id'    => $meta_id,
				'key'   => $key,
				'value' => $value,
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
		$array_keys = array_keys( wp_list_pluck( $this->meta_data, 'key' ), $key );
		if ( $array_keys ) {
			foreach ( $array_keys as $array_key ) {
				$this->meta_data[ $array_key ]->value = null;
			}
		}
	}

	/**
	 * Delete meta data.
	 * @since 2.6.0
	 * @param int $mid Meta ID
	 */
	public function delete_meta_data_by_mid( $mid ) {
		$array_keys         = array_keys( wp_list_pluck( $this->meta_data, 'id' ), $mid );
		if ( $array_keys ) {
			foreach ( $array_keys as $array_key ) {
				$this->meta_data[ $array_key ]->value = null;
			}
		}
	}

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta
	 * @return bool
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		return ! in_array( $meta->meta_key, $this->get_internal_meta_keys() );
	}

	/**
	 * Read Meta Data from the database. Ignore any internal properties.
	 * @since 2.6.0
	 */
	protected function read_meta_data() {
		$this->meta_data = array();
		$cache_loaded     = false;

		if ( ! $this->get_id() ) {
			return;
		}

		if ( ! empty( $this->cache_group ) ) {
			$cache_key   = WC_Cache_Helper::get_cache_prefix( $this->cache_group ) . $this->get_id();
			$cached_meta = wp_cache_get( $cache_key, $this->cache_group );

			if ( false !== $cached_meta ) {
				$this->meta_data = $cached_meta;
				$cache_loaded = true;
			}
		}

		if ( ! $cache_loaded ) {
			global $wpdb;
			$db_info = $this->get_db_info();
			$raw_meta_data = $wpdb->get_results( $wpdb->prepare( "
				SELECT " . $db_info['meta_id_field'] . ", meta_key, meta_value
				FROM " . $db_info['table'] . "
				WHERE " . $db_info['object_id_field'] . "=%d AND meta_key NOT LIKE 'wp\_%%' ORDER BY " . $db_info['meta_id_field'] . "
			", $this->get_id() ) );

			if ( $raw_meta_data ) {
				$raw_meta_data = array_filter( $raw_meta_data, array( $this, 'exclude_internal_meta_keys' ) );
				foreach ( $raw_meta_data as $meta ) {
					$this->meta_data[] = (object) array(
						'id'    => (int) $meta->{ $db_info['meta_id_field'] },
						'key'   => $meta->meta_key,
						'value' => maybe_unserialize( $meta->meta_value ),
					);
				}
			}

			if ( ! empty( $this->cache_group ) ) {
				wp_cache_set( $cache_key, $this->meta_data, $this->cache_group );
			}
		}
	}

	/**
	 * Update Meta Data in the database.
	 * @since 2.6.0
	 */
	protected function save_meta_data() {
		foreach ( $this->meta_data as $array_key => $meta ) {
			if ( is_null( $meta->value ) ) {
				if ( ! empty( $meta->id ) ) {
					delete_metadata_by_mid( $this->meta_type, $meta->id );
				}
			} elseif ( empty( $meta->id ) ) {
				$new_meta_id = add_metadata( $this->meta_type, $this->get_id(), $meta->key, $meta->value, false );
				$this->meta_data[ $array_key ]->id = $new_meta_id;
			} else {
				update_metadata_by_mid( $this->meta_type, $meta->id, $meta->value, $meta->key );
			}
		}

		if ( ! empty( $this->cache_group ) ) {
			WC_Cache_Helper::incr_cache_prefix( $this->cache_group );
		}
		$this->read_meta_data();
	}

	/**
	 * Table structure is slightly different between meta types, this function will return what we need to know.
	 * @since 2.6.0
	 * @return array Array elements: table, object_id_field, meta_id_field
	 */
	protected function get_db_info() {
		global $wpdb;

		$meta_id_field   = 'meta_id'; // for some reason users calls this umeta_id so we need to track this as well.
		$table           = $wpdb->prefix;

		// If we are dealing with a type of metadata that is not a core type, the table should be prefixed.
		if ( ! in_array( $this->meta_type, array( 'post', 'user', 'comment', 'term' ) ) ) {
			$table .= 'woocommerce_';
		}

		$table .= $this->meta_type . 'meta';
		$object_id_field = $this->meta_type . '_id';

		// Figure out our field names.
		if ( 'user' === $this->meta_type ) {
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

	/**
	 * Set ID.
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	/**
	 * Set all props to default values.
	 */
	public function set_defaults() {
		$this->data = $this->default_data;
	}

	/**
	 * Set a collection of props in one go, collect any errors, and return the result.
	 * @param array $props Key value pairs to set. Key is the prop and should map to a setter function name.
	 * @return WP_Error|bool
	 */
	public function set_props( $props ) {
		$errors = new WP_Error();

		foreach ( $props as $prop => $value ) {
			try {
				if ( 'meta_data' === $prop ) {
					continue;
				}
				$setter = "set_$prop";
				if ( ! is_null( $value ) && is_callable( array( $this, $setter ) ) ) {
					$this->{$setter}( $value );
				}
			} catch ( WC_Data_Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		return sizeof( $errors->get_error_codes() ) ? $errors : true;
	}

	/**
	 * When invalid data is found, throw an exception unless reading from the DB.
	 * @param string $error_code Error code.
	 * @param string $error_message Error message.
	 * @throws WC_Data_Exception
	 */
	protected function error( $error_code, $error_message ) {
		throw new WC_Data_Exception( $error_code, $error_message );
	}
}
