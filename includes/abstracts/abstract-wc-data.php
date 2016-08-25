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
	 * Core data for this object. Name value pairs (name + default value).
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Set to _data on construct so we can track and reset data if needed.
	 * @var array
	 */
	protected $_default_data = array();

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
	 * Default constructor.
	 * @param int|object|array $read ID to load from the DB (optional) or already queried data.
	 */
	public function __construct( $read = 0 ) {
		$this->_default_data = $this->_data;
	}

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
		return array_filter( $this->_meta_data, array( $this, 'filter_null_meta' ) );
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
		$array_keys = array_keys( wp_list_pluck( $this->get_meta_data(), 'key' ), $key );
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
				if ( isset( $meta['key'], $meta['value'], $meta['id'] ) ) {
					$this->_meta_data[] = (object) array(
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
			$array_key = array_keys( wp_list_pluck( $this->_meta_data, 'id' ), $meta_id );
		}
		if ( $array_key ) {
			$this->_meta_data[ current( $array_key ) ] = (object) array(
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
		$array_keys = array_keys( wp_list_pluck( $this->_meta_data, 'key' ), $key );
		if ( $array_keys ) {
			foreach ( $array_keys as $array_key ) {
				$this->_meta_data[ $array_key ]->value = null;
			}
		}
	}

	/**
	 * Delete meta data.
	 * @since 2.6.0
	 * @param int $mid Meta ID
	 */
	public function delete_meta_data_by_mid( $mid ) {
		$array_keys         = array_keys( wp_list_pluck( $this->_meta_data, 'id' ), $mid );
		if ( $array_keys ) {
			foreach ( $array_keys as $array_key ) {
				$this->_meta_data[ $array_key ]->value = null;
			}
		}
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

		if ( ! empty( $this->_cache_group ) ) {
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
				WHERE " . $db_info['object_id_field'] . "=%d AND meta_key NOT LIKE 'wp\_%%' ORDER BY " . $db_info['meta_id_field'] . "
			", $this->get_id() ) );

			if ( $raw_meta_data ) {
				foreach ( $raw_meta_data as $meta ) {
					if ( in_array( $meta->meta_key, $this->get_internal_meta_keys() ) ) {
						continue;
					}
					$this->_meta_data[] = (object) array(
						'id'    => (int) $meta->{ $db_info['meta_id_field'] },
						'key'   => $meta->meta_key,
						'value' => maybe_unserialize( $meta->meta_value ),
					);
				}
			}

			if ( ! empty( $this->_cache_group ) ) {
				wp_cache_set( $cache_key, $this->_meta_data, $this->_cache_group );
			}
		}
	}

	/**
	 * Update Meta Data in the database.
	 * @since 2.6.0
	 */
	protected function save_meta_data() {
		foreach ( $this->_meta_data as $array_key => $meta ) {
			if ( is_null( $meta->value ) ) {
				if ( ! empty( $meta->id ) ) {
					delete_metadata_by_mid( $this->_meta_type, $meta->id );
				}
			} elseif ( empty( $meta->id ) ) {
				$new_meta_id = add_metadata( $this->_meta_type, $this->get_id(), $meta->key, $meta->value, false );
				$this->_meta_data[ $array_key ]->id = $new_meta_id;
			} else {
				update_metadata_by_mid( $this->_meta_type, $meta->id, $meta->value, $meta->key );
			}
		}

		if ( ! empty( $this->_cache_group ) ) {
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

	/**
	 * Set all props to default values.
	 */
	protected function set_defaults() {
		$this->_data = $this->_default_data;
	}

	/**
	 * Get internal data prop (raw).
	 * @param string ...$param Prop keys to retrieve. Supports multiple keys to get nested values.
	 * @return mixed
	 */
	protected function get_prop() {
		$args = func_get_args();
		$prop = &$this->_data;

		foreach ( $args as $arg ) {
			if ( ! isset( $prop[ $arg ] ) ) {
				return false;
			}
			$prop = &$prop[ $arg ];
		}

		return $prop;
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
				$setter = "set_$prop";
				if ( ! is_null( $value ) && is_callable( array( $this, $setter ) ) ) {
					$this->{$setter}( wc_clean( wp_unslash( $value ) ) );
				}
			} catch ( WC_Data_Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		return sizeof( $errors->get_error_codes() ) ? $errors : true;
	}

	/**
	 * Set internal data prop to specified value.
	 * @param int ...$param Prop keys followed by value to set.
	 * @throws WC_Data_Exception
	 */
	protected function set_prop() {
		if ( func_num_args() < 2 ) {
			$this->error( 'invalid_value', __( 'set_prop() requires at least 2 parameters', 'woocommerce' ) );
		}

		$args  = func_get_args();
		$value = array_pop( $args );
		$prop  = &$this->_data;

		foreach ( $args as $arg ) {
			$prop = &$prop[ $arg ];
		}

		$prop = $value;
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
