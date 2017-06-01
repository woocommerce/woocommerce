<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wraps an array (meta data for now) and tells if there was any changes.
 *
 * The main idea behind this class is to avoid doing unneeded
 * SQL updates if nothing changed.
 *
 * @version 3.0.x
 * @package WooCommerce
 * @category Class
 * @author crodas
 */
class WC_Meta_Data {
	/**
	 * Current data for metadata
	 *
	 * @since 3.1.0
	 * @var arrray
	 */
	protected $current_data;

	/**
	 * Metadata data
	 *
	 * @since 3.1.0
	 * @var arrray
	 */
	protected $data;

	/**
	 * Default constructor
	 *
	 * @param Array	meta data to wrap behind this function
	 */
	public function __construct( Array $meta ) {
		$this->current_data = $meta;
		$this->apply_changes();
	}

	/**
	 * Merge changes with data and clear.
	 */
	public function apply_changes() {
		$this->data = $this->current_data;
	}

	/**
	 * Creates or updates a property in the metadata object.
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 */
	public function __set( $key, $value ) {
		$this->current_data[ $key ] = $value;
	}

	/**
	 * Checks if a given key exists in our data. This is called internally
	 * by `empty` and `isset`.
	 *
	 * @param string $key
	 */
	public function __isset( $key ) {
		return array_key_exists( $key, $this->current_data );
	}

	/**
	 * Returns the value of any property.
	 *
	 * @param string $key
	 *
	 * @return mixed Property value or NULL if it does not exists
	 */
	public function __get( $key ) {
		if ( array_key_exists( $key, $this->current_data ) ) {
			return $this->current_data[ $key ];
		}
		return null;
	}

	/**
	 * Return data changes only.
	 *
	 * @return array
	 */
	public function get_changes() {
		$changes = array();
		foreach ( $this->current_data as $id => $value) {
			if ( ! array_key_exists( $id, $this->data ) || $value !== $this->data[ $id ] ) {
				$changes[ $id ] = $value;
			}
		}

		return $changes;
	}

}
