<?php
/**
 * Order Item
 *
 * A class which represents an item within an order and handles CRUD.
 *
 * @class 		WC_Order_Item
 * @version		2.6.0
 * @since       2.6.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
abstract class WC_Order_Item {

    /**
	 * Data array, with defaults.
	 * @since 2.6.0
	 * @var array
	 */
    protected $data = array(
		'order_id'        => 0,
		'order_item_id'   => 0,
		'order_item_name' => '',
		'order_item_type' => '',
        'meta_data'       => array(),
	);

    /**
	 * Constructor.
	 * @param int|object $order_item ID to load from the DB (optional) or already queried data.
	 */
    public function __construct( $item = 0 ) {
		if ( is_numeric( $item ) && ! empty( $item ) ) {
        	$this->read( $item );
		} elseif ( is_object( $item ) && $this->is_type( $item->get_order_item_type() ) ) {
            $this->set_all( $item );
		}
    }

    /**
     * Set data based on input item.
     * @access private
     */
    private function set_all( $item ) {
        foreach ( $item->get_data() as $key => $value ) {
            $this->data[ $key ] = $value;
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
        return $type === $this->get_order_item_type();
    }

    /**
	 * Get all item meta data in array format in the order it was saved. Does not group meta by key.
	 * @param mixed $order_item_id
	 * @return array of objects
	 */
	public function get_all_item_meta_data() {
		global $wpdb;

        $item_meta_array = array();

        if ( $this->get_order_item_id() ) {
    		// Get cache key - uses get_cache_prefix to invalidate when needed
    		$cache_key       = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'all_item_meta_' . $this->get_order_item_id();
    		$item_meta_array = wp_cache_get( $cache_key, 'orders' );

    		if ( false === $item_meta_array ) {
    			$metadata        = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d ORDER BY meta_id", $this->get_order_item_id() ) );
    			foreach ( $metadata as $metadata_row ) {
    				$item_meta_array[ $metadata_row->meta_key ] = $metadata_row->meta_value;
    			}
    			wp_cache_set( $cache_key, $item_meta_array, 'orders' );
    		}
        }

		return $item_meta_array;
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
		return $this->data;
	}

    /**
     * Get order ID this meta belongs to.
     * @return int
     */
    public function get_order_id() {
        return absint( $this->data['order_id'] );
    }

    /**
     * Get order item ID.
     * @return int
     */
    public function get_order_item_id() {
        return absint( $this->data['order_item_id'] );
    }

    /**
     * Get order item name.
     * @return string
     */
    public function get_order_item_name() {
        return $this->data['order_item_name'];
    }

    /**
     * Get order item type.
     * @return string
     */
    public function get_order_item_type() {
        return $this->data['order_item_type'];
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
        $this->data['order_id'] = absint( $value );
    }

    /**
     * Set order item ID.
     * @param int $value
     */
    public function set_order_item_id( $value ) {
        $this->data['order_item_id'] = absint( $value );
    }

    /**
     * Set order item name.
     * @param string $value
     */
    public function set_order_item_name( $value ) {
        $this->data['order_item_name'] = wc_clean( $value );
    }

    /**
     * Set order item type.
     * @param string $value
     */
    public function set_order_item_type( $value ) {
        $this->data['order_item_type'] = wc_clean( $value );

    }

    /**
     * Set meta data.
     * @param array $data Key/Value pairs
     */
    public function set_meta_data( $data ) {
        foreach ( $data as $key => $value ) {
            $this->data['meta_data'][ $key ] = $value;
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
     * @access private
     * @param array $data data to save
     */
    private function create( $data ) {
        global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'woocommerce_order_items', $data );
		$this->set_item_id( $wpdb->insert_id );
	}

    /**
     * Update data in the database.
	 * @since 2.6.0
	 * @access private
     * @param array $data data to save
     */
    private function update( $zone_data ) {
        global $wpdb;
		$wpdb->update( $wpdb->prefix . 'woocommerce_order_items', $zone_data, array( 'order_item_id' => $this->get_order_item_id() ) );
    }

	/**
     * Read from the database.
	 * @since 2.6.0
     * @access protected
     * @param int $id ID of object to read.
     */
    protected function read( $id ) {
		global $wpdb;
		if ( $data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $id ) ) ) {
			$this->set_order_id( $data->order_id );
			$this->set_order_item_id( $data->order_item_id );
			$this->set_order_item_name( $data->order_item_name );
            $this->set_order_item_type( $data->order_item_type );
		}
	}

    /**
     * Save data to the database.
	 * @since 2.6.0
     * @access protected
     */
    protected function save() {
        if ( ! $this->get_order_item_id() ) {
			$this->create();
        } else {
            $this->update();
        }
	}

    /**
     * Delte data from the database.
	 * @since 2.6.0
	 * @access protected
     */
    protected function delete() {
        global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_order_items', array( 'order_item_id' => $this->get_order_item_id() ) );
    }
}
