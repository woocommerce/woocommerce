<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item (tax).
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Order_Item_Tax extends WC_Item_Tax {

	/**
	 * Order Data array. This is the core order data exposed in APIs since 2.7.0.
	 * @since 2.7.0
	 * @var array
	 */
	protected $data = array(
		'order_id'           => 0,
		'rate_code'          => '',
		'rate_id'            => 0,
		'label'              => '',
		'compound'           => false,
		'tax_total'          => 0,
		'shipping_tax_total' => 0,
	);

	/**
	 * May store an order to prevent retriving it multiple times.
	 * @var object
	 */
	protected $order;

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $cache_group = 'order_itemmeta';

	/**
	 * Meta type. This should match up with
	 * the types avaiable at https://codex.wordpress.org/Function_Reference/add_metadata.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 */
	protected $meta_type = 'order_item';

	/**
	 * Internal meta keys we don't want exposed as part of meta_data.
	 * @return array()
	 */
	protected function get_internal_meta_keys() {
		return array( 'rate_id', 'label', 'compound', 'tax_amount', 'shipping_tax_amount' );
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
	 * @return object
	 */
	public function get_order() {
		return $this->order ? $this->order : $this->order = wc_get_order( $this->get_order_id() );
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
	 * Get data either from the passed object, or the DB, or return false;
	 * @param  mixed $item
	 * @return object|bool
	 */
	private function get_item_data( $item ) {
		global $wpdb;

		if ( is_numeric( $item ) && ! empty( $item ) ) {
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d LIMIT 1;", $item ) );
		} elseif ( ! empty( $item->order_item_id ) ) {
			$data = $item;
		} else {
			$data = false;
		}

		return $data;
	}

	/**
	 * Read from the database.
	 * @since 2.7.0
	 * @param int|object $item ID of object to read, or already queried object.
	 */
	public function read( $item ) {
		$this->set_defaults();

		if ( ! $data = $this->get_item_data( $item ) ) {
			return;
		}

		$this->set_id( $data->order_item_id );
		$this->read_meta_data();
		$this->set_props( array(
			'order_id'           => $data->order_id,
			'rate_code'          => $data->order_item_name,
			'rate_id'            => get_metadata( 'order_item', $this->get_id(), 'rate_id', true ),
			'label'              => get_metadata( 'order_item', $this->get_id(), 'label', true ),
			'compound'           => get_metadata( 'order_item', $this->get_id(), 'compound', true ),
			'tax_total'          => get_metadata( 'order_item', $this->get_id(), 'tax_amount', true ),
			'shipping_tax_total' => get_metadata( 'order_item', $this->get_id(), 'shipping_tax_amount', true ),
		) );
	}

	/**
	 * Save data to the database.
	 * @since 2.7.0
	 * @return int Item ID
	 */
	public function save() {
		$this->get_id() ? $this->update() : $this->create();
		$this->save_meta_data();
		wc_update_order_item_meta( $this->get_id(), 'rate_id', $this->get_rate_id() );
		wc_update_order_item_meta( $this->get_id(), 'label', $this->get_label() );
		wc_update_order_item_meta( $this->get_id(), 'compound', $this->get_compound() );
		wc_update_order_item_meta( $this->get_id(), 'tax_amount', $this->get_tax_total() );
		wc_update_order_item_meta( $this->get_id(), 'shipping_tax_amount', $this->get_shipping_tax_total() );
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
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order ID.
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_order_id( $value ) {
		$this->data['order_id'] = absint( $value );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get label.
	 * @return string
	 */
	public function get_label() {
		return $this->data['label'] ? $this->data['label'] : __( 'Tax', 'woocommerce' );
	}

	/**
	 * Get order ID this meta belongs to.
	 * @return int
	 */
	public function get_order_id() {
		return $this->data['order_id'];
	}
}
