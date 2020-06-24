<?php
/**
 * Glorified key-value store with some helper methods to manage cache objects.
 *
 * @package Automattic\WooCommerce\Models
 */

namespace Automattic\WooCommerce\Models;

/**
 * Class CacheHydration. Used for storing data used for priming caches later.
 */
class CacheHydration {

	/**
	 * Internal object for storing singular data for different objects and keys.
	 *
	 * @var array
	 */
	protected $data_hydration = array();

	/**
	 * Internal object for storing collections for different objects and keys.
	 *
	 * @var array
	 */
	protected $collection_hydration = array();

	/**
	 * Set singular data for an object.
	 *
	 * @param string     $key       Name to identify data.
	 * @param string|int $object_id ID of object.
	 * @param mixed      $value     Value to store for object ID.
	 */
	public function set_data_for_object( $key, $object_id, $value ) {
		if ( ! $this->has_data( $key ) ) {
			$this->data_hydration[ $key ] = array();
		}
		$this->data_hydration[ $key ][ $object_id ] = $value;
	}

	/**
	 * Returns data for key, object ID.
	 *
	 * @param string     $key       Name to identify data.
	 * @param string|int $object_id ID of object.
	 *
	 * @return mixed|null Value to store for object ID. Null if not present.
	 */
	public function get_data_for_object( $key, $object_id ) {
		if ( ! isset( $this->data_hydration[ $key ] ) ) {
			return null;
		}

		if ( ! isset( $this->data_hydration[ $key ][ $object_id ] ) ) {
			return null;
		}

		return $this->data_hydration[ $key ][ $object_id ];
	}

	/**
	 * Check if data exists for key, object ID.
	 *
	 * @param string          $key       Name to identify data.
	 * @param string|int|null $object_id ID of object. If null then only key will be checked.
	 *
	 * @return bool|null True if value stored for key or object ID. Null if not present.
	 * */
	public function has_data( $key, $object_id = null ) {
		if ( null === $object_id ) {
			return key_exists( $key, $this->data_hydration );
		}
		return key_exists( $key, $this->data_hydration ) && isset( $this->data_hydration[ $key ][ $object_id ] );
	}

	/**
	 * Check if collection exists for key, object ID.
	 *
	 * @param string          $key       Name to identify collection.
	 * @param string|int|null $object_id ID of object. If null then only key will be checked.
	 *
	 * @return bool|null True if value stored for key or object ID. Null if not present.
	 * */
	public function has_collection( $key, $object_id = null ) {
		if ( null === $object_id ) {
			return key_exists( $key, $this->collection_hydration );
		}
		return key_exists( $key, $this->collection_hydration ) && isset( $this->collection_hydration[ $key ][ $object_id ] );
	}

	/**
	 * Adds rows to a collection.
	 *
	 * @param array  $data      Actual collection data. Should be an array of std objects.
	 * @param string $key       Name to identify collection.
	 * @param string $index_key Name of the index key.
	 * */
	public function add_to_collection( $data, $key, $index_key ) {
		foreach ( $data as $row ) {
			$index = $row->$index_key;
			$this->append_collection_for_object( $key, $index, $row );
		}
	}

	/**
	 * Set row to a collection.
	 *
	 * @param string     $key       Name to identify collection.
	 * @param string|int $object_id ID of object.
	 * @param object     $row       std object for row.
	 * */
	public function set_collection_for_object( $key, $object_id, $row ) {
		if ( ! isset( $this->collection_hydration[ $key ] ) ) {
			$this->collection_hydration[ $key ] = array();
		}

		$this->collection_hydration[ $key ][ $object_id ] = array( $row );
	}

	/**
	 * Append row to a collection.
	 *
	 * @param string     $key       Name to identify collection.
	 * @param string|int $object_id ID of object.
	 * @param object     $row       std object for row.
	 * */
	public function append_collection_for_object( $key, $object_id, $row ) {
		if ( ! isset( $this->collection_hydration[ $key ] ) ) {
			$this->collection_hydration[ $key ] = array();
		}

		if ( ! isset( $this->collection_hydration[ $key ][ $object_id ] ) ) {
			$this->collection_hydration[ $key ][ $object_id ] = array();
		}

		$this->collection_hydration[ $key ][ $object_id ][] = $row;
	}

}
