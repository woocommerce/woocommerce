<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Item
 *
 * A class which represents an item within an order and handles CRUD.
 * Uses ArrayAccess to be BW compatible with WC_Orders::get_items().
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Order_Item extends WC_Data implements ArrayAccess {

	/**
	 * Order Data array. This is the core order data exposed in APIs since 2.7.0.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_data = array(
		'order_id' => 0,
		'id'       => 0, // order_item_id
		'name'     => '',
		'type'     => '',
	);

	/**
	 * May store an order to prevent retriving it multiple times.
	 * @var object
	 */
	protected $_order;

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $_cache_group = 'order_itemmeta';

	/**
	 * Meta type. This should match up with
	 * the types avaiable at https://codex.wordpress.org/Function_Reference/add_metadata.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 */
	protected $_meta_type = 'order_item';

	/**
	 * Constructor.
	 * @param int|object|array $read ID to load from the DB (optional) or already queried data.
	 */
	public function __construct( $read = 0 ) {
		parent::__construct( $read );

		if ( $read instanceof WC_Order_Item ) {
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
	 * Get quantity.
	 * @return int
	 */
	public function get_quantity() {
		return 1;
	}

	/**
	 * Get parent order object.
	 * @return int
	 */
	public function get_order() {
		if ( ! $this->_order ) {
		 	$this->_order = wc_get_order( $this->get_order_id() );
		}
		return $this->_order;
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order item ID.
	 * @return int
	 */
	public function get_id() {
		return $this->_data['id'];
	}

	/**
	 * Get order ID this meta belongs to.
	 * @return int
	 */
	public function get_order_id() {
		return $this->_data['order_id'];
	}

	/**
	 * Get order item name.
	 * @return string
	 */
	public function get_name() {
		return $this->_data['name'];
	}

	/**
	 * Get order item type.
	 * @return string
	 */
	public function get_type() {
		return $this->_data['type'];
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set ID
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_id( $value ) {
		$this->_data['id'] = absint( $value );
	}

	/**
	 * Set order ID.
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_order_id( $value ) {
		$this->_data['order_id'] = absint( $value );
	}

	/**
	 * Set order item name.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_name( $value ) {
		$this->_data['name'] = wc_clean( $value );
	}

	/**
	 * Set order item type.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	protected function set_type( $value ) {
		$this->_data['type'] = wc_clean( $value );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete data from the database.
	|
	*/

	/**
	 * Insert data into the database.
	 * @since 2.7.0
	 */
	public function create() {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'woocommerce_order_items', array(
			'order_item_name' => $this->get_name(),
			'order_item_type' => $this->get_type(),
			'order_id'        => $this->get_order_id(),
		) );
		$this->set_id( $wpdb->insert_id );

		do_action( 'woocommerce_new_order_item', $this->get_id(), $this, $this->get_order_id() );
	}

	/**
	 * Update data in the database.
	 * @since 2.7.0
	 */
	public function update() {
		global $wpdb;

		$wpdb->update( $wpdb->prefix . 'woocommerce_order_items', array(
			'order_item_name' => $this->get_name(),
			'order_item_type' => $this->get_type(),
			'order_id'        => $this->get_order_id(),
		), array( 'order_item_id' => $this->get_id() ) );

		do_action( 'woocommerce_update_order_item', $this->get_id(), $this, $this->get_order_id() );
	}

	/**
	 * Read from the database.
	 * @since 2.7.0
	 * @param int|object $item ID of object to read, or already queried object.
	 */
	public function read( $item ) {
		global $wpdb;

		$this->set_defaults();

		if ( is_numeric( $item ) && ! empty( $item ) ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item ) );
		} elseif ( ! empty( $item->order_item_id ) ) {
			$data = $item;
		} else {
			$data = false;
		}

		if ( ! $data ) {
			return;
		}

		$this->set_props( array(
			'order_id' => $data->order_id,
			'id'       => $data->order_item_id,
			'name'     => $data->order_item_name,
			'type'     => $data->order_item_type,
		) );
		$this->read_meta_data();
	}

	/**
	 * Save data to the database.
	 * @since 2.7.0
	 * @return int Item ID
	 */
	public function save() {
		if ( ! $this->get_id() ) {
			$this->create();
		} else {
			$this->update();
		}
		$this->save_meta_data();

		return $this->get_id();
	}

	/**
	 * Delete data from the database.
	 * @since 2.7.0
	 */
	public function delete() {
		if ( $this->get_id() ) {
			global $wpdb;
			do_action( 'woocommerce_before_delete_order_item', $this->get_id() );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_items', array( 'order_item_id' => $this->get_id() ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_order_itemmeta', array( 'order_item_id' => $this->get_id() ) );
			do_action( 'woocommerce_delete_order_item', $this->get_id() );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Meta Data Handling
	|--------------------------------------------------------------------------
	*/

	/**
	 * Expands things like term slugs before return.
	 * @param string $hideprefix (default: _)
	 * @return array
	 */
	public function get_formatted_meta_data( $hideprefix = '_' ) {
		$formatted_meta = array();
		$meta_data      = $this->get_meta_data();

		foreach ( $meta_data as $meta ) {
			if ( "" === $meta->value || is_serialized( $meta->value ) || ( ! empty( $hideprefix ) && substr( $meta->key, 0, 1 ) === $hideprefix ) ) {
				continue;
			}

			$meta->key     = rawurldecode( $meta->key );
			$meta->value   = rawurldecode( $meta->value );
			$attribute_key = str_replace( 'attribute_', '', $meta->key );
			$display_key   = wc_attribute_label( $attribute_key, is_callable( array( $this, 'get_product' ) ) ? $this->get_product() : false );
			$display_value = $meta->value;

			if ( taxonomy_exists( $attribute_key ) ) {
				$term = get_term_by( 'slug', $meta->value, $attribute_key );
				if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
					$display_value = $term->name;
				}
			}

			$formatted_meta[ $meta->id ] = (object) array(
				'key'           => $meta->key,
				'value'         => $meta->value,
				'display_key'   => apply_filters( 'woocommerce_order_item_display_meta_key', $display_key ),
				'display_value' => apply_filters( 'woocommerce_order_item_display_meta_value', wpautop( make_clickable( $display_value ) ) ),
			);
		}

		return $formatted_meta;
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
	 * offsetGet for ArrayAccess
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( 'item_meta_array' === $offset ) {
			$return = array();

			foreach ( $this->_meta_data as $meta ) {
				$return[ $meta->id ] = $meta;
			}

			return $return;
		}

		$meta_values = wp_list_pluck( $this->_meta_data, 'value', 'key' );

		if ( 'item_meta' === $offset ) {
			return $meta_values;
		} elseif ( array_key_exists( $offset, $this->_data ) ) {
			return $this->_data[ $offset ];
		} elseif ( array_key_exists( '_' . $offset, $meta_values ) ) {
			// Item meta was expanded in previous versions, with prefixes removed. This maintains support.
			return $meta_values[ '_' . $offset ];
		}

		return null;
	}
}
