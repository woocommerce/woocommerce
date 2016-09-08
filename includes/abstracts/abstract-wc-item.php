<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Line Item
 *
 * A class which represents an item within an order or cart.
 * Uses ArrayAccess to be BW compatible with previously public properties.
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Item extends WC_Data implements ArrayAccess {

	/**
	 * Constructor.
	 * @param int|object|array $read ID to load from the DB (optional) or already queried data.
	 */
	public function __construct( $read = 0 ) {
		parent::__construct( $read );

		if ( $read instanceof WC_Item ) {
			if ( $this->is_type( $read->get_type() ) ) {
				$this->set_props( $read->get_data() );
			}
		} elseif ( is_array( $read ) ) {
			$this->set_props( $read );
		} else {
			$this->read( $read );
		}
	}

	/**
	 * Type checking
	 * @param  string|array  $Type
	 * @return boolean
	 */
	public function is_type( $type ) {
		return is_array( $type ) ? in_array( $this->get_type(), $type ) : $type === $this->get_type();
	}

	/**
	 * Get item type.
	 * @return string
	 */
	public function get_type() {
		return '';
	}

	/*
	|--------------------------------------------------------------------------
	| Array Access Methods
	|--------------------------------------------------------------------------
	|
	| For backwards compat with legacy arrays.
	|
	*/

	/**
	 * offsetSet for ArrayAccess
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		if ( 'item_meta_array' === $offset ) {
			foreach ( $value as $meta_id => $meta ) {
				$this->update_meta_data( $meta->key, $meta->value, $meta_id );
			}
			return;
		}

		if ( array_key_exists( $offset, $this->_data ) ) {
			$this->_data[ $offset ] = $value;
		}

		$this->update_meta_data( '_' . $offset, $value );
	}

	/**
	 * offsetUnset for ArrayAccess
	 * @param string $offset
	 */
	public function offsetUnset( $offset ) {
		if ( 'item_meta_array' === $offset || 'item_meta' === $offset ) {
			$this->_meta_data = array();
			return;
		}

		if ( array_key_exists( $offset, $this->_data ) ) {
			unset( $this->_data[ $offset ] );
		}

		$this->delete_meta_data( '_' . $offset );
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( 'item_meta_array' === $offset || 'item_meta' === $offset || array_key_exists( $offset, $this->_data ) ) {
			return true;
		}
		return array_key_exists( '_' . $offset, wp_list_pluck( $this->_meta_data, 'value', 'key' ) );
	}

	/**
	 * Get legacy item_meta_array.
	 * @return arrau
	 */
	private function get_item_meta_array() {
		$return = array();
		foreach ( $this->_meta_data as $meta ) {
			$return[ $meta->id ] = $meta;
		}
		return $return;
	}

	/**
	 * offsetGet for ArrayAccess.
	 * Item meta was expanded in previous versions, with prefixes removed. This maintains support.
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		$meta_values = wp_list_pluck( $this->_meta_data, 'value', 'key' );

		if ( 'item_meta_array' === $offset ) {
			return $this->get_item_meta_array();
		} elseif ( 'item_meta' === $offset ) {
			return $meta_values;
		} elseif ( array_key_exists( $offset, $this->_data ) ) {
			return $this->_data[ $offset ];
		} elseif ( array_key_exists( '_' . $offset, $meta_values ) ) {
			return $meta_values[ '_' . $offset ];
		}

		return null;
	}
}
