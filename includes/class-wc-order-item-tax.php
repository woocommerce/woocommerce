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
class WC_Order_Item_Tax extends WC_Order_Item {

	/**
	 * Data properties of this order item object.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_data = array(
		'order_id'           => 0,
		'order_item_id'      => 0,
		'rate_code'          => '',
		'rate_id'            => 0,
		'label'              => '',
		'compound'           => false,
		'tax_total'          => 0,
		'shipping_tax_total' => 0
	);

	/**
	 * Read/populate data properties specific to this order item.
	 */
	public function read( $id ) {
		parent::read( $id );
		if ( $this->get_id() ) {
			$this->set_rate_id( get_metadata( 'order_item', $this->get_id(), 'rate_id', true ) );
			$this->set_label( get_metadata( 'order_item', $this->get_id(), 'label', true ) );
			$this->set_compound( get_metadata( 'order_item', $this->get_id(), 'compound', true ) );
			$this->set_tax_total( get_metadata( 'order_item', $this->get_id(), 'tax_amount', true ) );
			$this->set_shipping_tax_total( get_metadata( 'order_item', $this->get_id(), 'shipping_tax_amount', true ) );
		}
	}

	/**
	 * Save properties specific to this order item.
	 * @return int Item ID
	 */
	public function save() {
		parent::save();
		if ( $this->get_id() ) {
			wc_update_order_item_meta( $this->get_id(), 'rate_id', $this->get_rate_id() );
			wc_update_order_item_meta( $this->get_id(), 'label', $this->get_label() );
			wc_update_order_item_meta( $this->get_id(), 'compound', $this->get_compound() );
			wc_update_order_item_meta( $this->get_id(), 'tax_amount', $this->get_tax_total() );
			wc_update_order_item_meta( $this->get_id(), 'shipping_tax_amount', $this->get_shipping_tax_total() );
		}

		return $this->get_id();
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data.
	 * @return array()
	 */
	protected function get_internal_meta_keys() {
		return array( 'rate_id', 'label', 'compound', 'tax_amount', 'shipping_tax_amount' );
	}

	/**
	 * Is this a compound tax rate?
	 * @return boolean
	 */
	public function is_compound() {
		return $this->get_compound();
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order item name.
	 * @param string $value
	 */
	public function set_name( $value ) {
		$this->set_rate_code( $value );
	}

	/**
	 * Set item name.
	 * @param string $value
	 */
	public function set_rate_code( $value ) {
		$this->_data['rate_code'] = wc_clean( $value );
	}

	/**
	 * Set item name.
	 * @param string $value
	 */
	public function set_label( $value ) {
		$this->_data['label'] = wc_clean( $value );
	}

	/**
	 * Set tax rate id.
	 * @param int $value
	 */
	public function set_rate_id( $value ) {
		$this->_data['rate_id'] = absint( $value );
	}

	/**
	 * Set tax total.
	 * @param string $value
	 */
	public function set_tax_total( $value ) {
		$this->_data['tax_total'] = wc_format_decimal( $value );
	}

	/**
	 * Set shipping_tax_total
	 * @param string $value
	 */
	public function set_shipping_tax_total( $value ) {
		$this->_data['shipping_tax_total'] = wc_format_decimal( $value );
	}

	/**
	 * Set compound
	 * @param bool $value
	 */
	public function set_compound( $value ) {
		$this->_data['compound'] = (bool) $value;
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order item type.
	 * @return string
	 */
	public function get_type() {
		return 'tax';
	}

	/**
	 * Get rate code/name.
	 * @return string
	 */
	public function get_name() {
		return $this->get_rate_code();
	}

	/**
	 * Get rate code/name.
	 * @return string
	 */
	public function get_rate_code() {
		return $this->_data['rate_code'];
	}

	/**
	 * Get label.
	 * @return string
	 */
	public function get_label() {
		return $this->_data['label'] ? $this->_data['label'] : __( 'Tax', 'woocommerce' );
	}

	/**
	 * Get tax rate ID.
	 * @return int
	 */
	public function get_rate_id() {
		return absint( $this->_data['rate_id'] );
	}

	/**
	 * Get tax_total
	 * @return string
	 */
	public function get_tax_total() {
		return wc_format_decimal( $this->_data['tax_total'] );
	}

	/**
	 * Get shipping_tax_total
	 * @return string
	 */
	public function get_shipping_tax_total() {
		return wc_format_decimal( $this->_data['shipping_tax_total'] );
	}

	/**
	 * Get compound.
	 * @return bool
	 */
	public function get_compound() {
		return (bool) $this->_data['compound'];
	}
}
