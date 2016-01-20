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
		'order_item_type' => ''
	);

    /**
	 * Meta data array.
	 * @since 2.6.0
	 * @var array
	 */
    protected $meta_data = array();

    /**
	 * Constructor.
	 * @param int|object $order_item ID to load from the DB (optional) or already queried data.
	 */
    public function __construct( $item = 0 ) {
		if ( is_numeric( $item ) && ! empty( $item ) ) {
        	$this->read( $item );
		} elseif ( is_object( $item ) && ( empty( $this->get_order_item_type() ) || $this->is_type( $item->order_item_type ) ) ) {
            $this->set_order_id( $item->order_id );
			$this->set_order_item_id( $item->order_item_id );
			$this->set_order_item_name( $item->order_item_name );
            $this->set_order_item_type( $item->order_item_type );
			$this->read_order_item_meta();
		}
    }

    /**
     * Type checking
     * @param  string  $Type
     * @return boolean
     */
    public function is_type( $type ) {
        return $type === $this->get_order_item_type();
    }

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

    /**
	 * Get all class data in array format.
	 * @since 2.6.0
     * @access protected
	 * @return array
	 */
	protected function get_data() {
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

    /**
     * Get order item meta data.
     * @return array of date.
     */
    public function get_order_item_meta() {
        return $this->meta_data;
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
     * Add order item meta. @todo
     * @param string $value
     */
    public function add_meta_data( $key, $value ) {
        $this->meta_data[ $key ] = $value;
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
     * @access protected
     * @param array $data data to save
     */
    protected function create( $data ) {
        global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'woocommerce_order_items', $data );
		$this->set_item_id( $wpdb->insert_id );
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
			$this->read_order_item_meta();
		}
	}

    /**
     * Get item meta data from the database.
     */
    protected function read_order_item_meta() {
        if ( $this->get_order_item_id() ) {
            // @todo
        }
    }

    /**
     * Update data in the database.
	 * @since 2.6.0
	 * @access protected
     * @param array $data data to save
     */
    protected function update( $zone_data ) {
        global $wpdb;
		$wpdb->update( $wpdb->prefix . 'woocommerce_order_items', $zone_data, array( 'order_item_id' => $this->get_order_item_id() ) );
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
        //do_action()?
	}
}
