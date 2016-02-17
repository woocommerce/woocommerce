<?php
/**
 * Order Item
 *
 * A class which represents an item within an order and handles CRUD.
 * Uses ArrayAccess to be BW compatible with WC_Orders::get_items().
 *
 * @class 		WC_Order_Item
 * @version		2.6.0
 * @since       2.6.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Order_Item implements ArrayAccess, WC_Data {

    /**
	 * Data array, with defaults.
	 * @since 2.6.0
	 * @var array
	 */
    protected $_data = array(
		'order_id'      => 0,
		'order_item_id' => 0,
		'name'          => '',
		'type'          => '',
        'meta_data'     => array(), // @todo handle save
	);

    /**
	 * Constructor.
	 * @param int|object $order_item ID to load from the DB (optional) or already queried data.
	 */
    public function __construct( $item = 0 ) {
		if ( $item instanceof WC_Order_Item ) {
            if ( $this->is_type( $item->get_type() ) ) {
                $this->set_all( $item->get_data() );
            }
		} else {
            $this->read( $item );
        }
    }

    /**
     * Set all data based on input array.
     * @param array $data
     * @access private
     */
    private function set_all( $data ) {
        foreach ( $data as $key => $value ) {
            if ( is_callable( array( $this, "set_$key" ) ) ) {
                $this->{"set_$key"}( $value );
            } else {
                $this->_data[ $key ] = $value;
            }
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
     * Type checking
     * @param  string  $Type
     * @return boolean
     */
    public function is_type( $type ) {
        return $type === $this->get_type();
    }

    /**
     * Internal meta keys we don't want exposed as part of meta_data.
     * @return array()
     */
    protected function get_internal_meta_keys() {
        return array();
    }

    /**
     * Get qty.
     * @return int
     */
    public function get_qty() {
        return 1;
    }

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

    /**
	 * Get all class data in array format.
	 * @since 2.6.0
	 * @return array
	 */
	public function get_data() {
		return $this->_data;
	}

    /**
     * Get order item ID.
     * @return int
     */
    public function get_id() {
        return $this->get_order_item_id();
    }

    /**
     * Get order ID this meta belongs to.
     * @return int
     */
    public function get_order_id() {
        return absint( $this->_data['order_id'] );
    }

    /**
     * Get order ID this meta belongs to.
     * @return int
     */
    public function get_order_item_id() {
        return absint( $this->_data['order_item_id'] );
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

    /**
     * Get meta data
     * @return array
     */
    public function get_meta_data() {
        return $this->_data['meta_data'];
    }

    /*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

    /**
     * Set order ID.
     * @param int $value
     */
    public function set_order_id( $value ) {
        $this->_data['order_id'] = absint( $value );
    }

    /**
     * Set order item ID.
     * @param int $value
     */
    public function set_order_item_id( $value ) {
        $this->_data['order_item_id'] = absint( $value );
    }

    /**
     * Set order item name.
     * @param string $value
     */
    public function set_name( $value ) {
        $this->_data['name'] = wc_clean( $value );
    }

    /**
     * Set order item type.
     * @param string $value
     */
    public function set_type( $value ) {
        $this->_data['type'] = wc_clean( $value );

    }

    /**
     * Set meta data.
     * @param array $data Key/Value pairs
     */
    public function set_meta_data( $data ) {
        foreach ( $data as $key => $value ) {
            $this->_data['meta_data'][ $key ] = $value;
        }
    }

    /**
     * Set meta data.
     * @param array $data Key/Value pairs
     */
    public function add_meta_data( $key, $value, $unique = false ) {
        if ( $unique ) {
            $meta_ids = array_keys( wp_list_pluck( $this->_data['meta_data'], 'key' ), $key );
            $this->_data['meta_data'] = array_diff_key( $this->_data['meta_data'], array_fill_keys( $meta_ids, '' ) );
        }
        $this->_data['meta_data'][] = array(
            'key'   => $key,
            'value' => $value
        );
    }

    /**
     * Update meta data by key or ID, if provided.
     * @param  string $key
     * @param  string $value
     * @param  int $meta_id
     */
    public function update_meta_data( $key, $value, $meta_id = '' ) {
        if ( $meta_id && isset( $this->_data['meta_data'][ $meta_id ] ) ) {
            $this->_data['meta_data'][ $meta_id ] = array(
                'key'   => $key,
                'value' => $value
            );
        } else {
            $this->add_meta_data( $key, $value, true );
        }
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
	 * @since 2.6.0
     */
    public function create() {
        global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'woocommerce_order_items', array(
            'order_item_name' => $this->get_name(),
            'order_item_type' => $this->get_type(),
            'order_id'        => $this->get_order_id()
        ) );
		$this->set_order_item_id( $wpdb->insert_id );
	}

    /**
     * Update data in the database.
	 * @since 2.6.0
     */
    public function update() {
        global $wpdb;
		$wpdb->update( $wpdb->prefix . 'woocommerce_order_items', array(
            'order_item_name' => $this->get_name(),
            'order_item_type' => $this->get_type(),
            'order_id'        => $this->get_order_id()
        ), array( 'order_item_id' => $this->get_id() ) );
    }

	/**
     * Read from the database.
	 * @since 2.6.0
     * @param int|object $item ID of object to read, or already queried object.
     */
    public function read( $item ) {
		global $wpdb;

        if ( is_numeric( $item ) && ! empty( $item ) ) {
            $data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item ) );
        } elseif ( ! empty( $item->order_item_id ) ) {
            $data = $item;
        } else {
            $data = false;
        }

		if ( $data ) {
			$this->set_order_id( $data->order_id );
			$this->set_order_item_id( $data->order_item_id );
			$this->set_name( $data->order_item_name );
            $this->set_type( $data->order_item_type );
            $this->read_meta_data();
		}
	}

    /**
     * Read Meta Data from the database. Ignore any internal properties.
     */
    protected function read_meta_data() {
        $this->_data['meta_data'] = array();

        if ( $this->get_id() ) {
            // Get cache key - uses get_cache_prefix to invalidate when needed
            $cache_key       = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'meta_data_' . $this->get_id();
            $item_meta_array = wp_cache_get( $cache_key, 'orders' );

            if ( false === $item_meta_array ) {
                global $wpdb;

                $metadata = $wpdb->get_results( $wpdb->prepare( "SELECT meta_id, meta_key, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d ORDER BY meta_id", $this->get_id() ) );
                foreach ( $metadata as $metadata_row ) {
                    if ( in_array( $metadata_row->meta_key, $this->get_internal_meta_keys() ) ) {
                        continue;
                    }
                    $this->_data['meta_data'][ $metadata_row->meta_id ] = (object) array( 'key' => $metadata_row->meta_key, 'value' => $metadata_row->meta_value );
                }
                wp_cache_set( $cache_key, $item_meta_array, 'orders' );
            }
        }
    }

    /**
     * Save data to the database.
	 * @since 2.6.0
     */
    public function save() {
        if ( ! $this->get_id() ) {
			$this->create();
        } else {
            $this->update();
        }
	}

    /**
     * Delete data from the database.
	 * @since 2.6.0
     */
    public function delete() {
        global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_order_items', array( 'order_item_id' => $this->get_id() ) );
    }

    /**
     * offsetSet for ArrayAccess
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet( $offset, $value ) {
        if ( 'item_meta_array' === $offset ) {
            $offset = 'meta_data';
        }
        $this->_data[ $offset ] = $value;
    }

    /**
     * offsetUnset for ArrayAccess
     * @param string $offset
     */
    public function offsetUnset( $offset ) {
        if ( 'item_meta_array' === $offset || 'item_meta' === $offset ) {
            $this->_data['meta_data'] = array();
        }
        unset( $this->_data[ $offset ] );
    }

    /**
     * offsetExists for ArrayAccess
     * @param string $offset
     * @return bool
     */
    public function offsetExists( $offset ) {
        if ( 'item_meta_array' === $offset || 'item_meta' === $offset ) {
            return true;
        }
        return isset( $this->_data[ $offset ] );
    }

    /**
     * offsetGet for ArrayAccess
     * @param string $offset
     * @return mixed
     */
    public function offsetGet( $offset ) {
        if( 'item_meta_array' === $offset ) {
            $offset = 'meta_data';
        }
        elseif( 'item_meta' === $offset ) {
            $meta_values = wp_list_pluck( $this->_data['meta_data'], 'value', 'key' );
            return $meta_values;
        }
        return isset( $this->_data[ $offset ] ) ? $this->_data[ $offset ] : null;
    }
}
