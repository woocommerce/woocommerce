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
class WC_Item_Tax extends WC_Item {

	/**
	 * Data array. This is the core order data exposed in APIs since 2.7.0.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_data = array(
		'rate_code'          => '',
		'rate_id'            => 0,
		'label'              => '',
		'compound'           => false,
		'tax_total'          => 0,
		'shipping_tax_total' => 0,
	);

	/**
	 * Is this a compound tax rate?
	 * @return boolean
	 */
	public function is_compound() {
		return $this->get_compound();
	}

	/**
	 * Backwards compatibility for directly accessed props.
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'id' :
				return $this->get_id();
			case 'rate_id' :
				return $this->get_rate_id();
			case 'is_compound' :
				return $this->is_compound();
			case 'label' :
				return $this->get_label();
			case 'amount' :
				return wc_round_tax_total( $this->get_tax_total() + $this->get_shipping_tax_total() );
			case 'formatted_amount' :
				return wc_price( wc_round_tax_total( $this->get_tax_total() + $this->get_shipping_tax_total() ) );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set item name.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_rate_code( $value ) {
		$this->_data['rate_code'] = wc_clean( $value );
	}

	/**
	 * Set item name.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_label( $value ) {
		$this->_data['label'] = wc_clean( $value );
	}

	/**
	 * Set tax rate id.
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_rate_id( $value ) {
		$this->_data['rate_id'] = absint( $value );
	}

	/**
	 * Set tax total.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_tax_total( $value ) {
		$this->_data['tax_total'] = wc_format_decimal( $value );
	}

	/**
	 * Set shipping_tax_total
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_tax_total( $value ) {
		$this->_data['shipping_tax_total'] = wc_format_decimal( $value );
	}

	/**
	 * Set compound
	 * @param bool $value
	 * @throws WC_Data_Exception
	 */
	public function set_compound( $value ) {
		$this->_data['compound'] = (bool) $value;
	}

	/**
	 * Set properties based on passed in tax rate by ID.
	 * @param int $tax_rate_id
	 * @throws WC_Data_Exception
	 */
	public function set_rate( $tax_rate_id ) {
		$this->set_rate_id( $tax_rate_id );
		$this->set_rate_code( WC_Tax::get_rate_code( $tax_rate_id ) );
		$this->set_label( WC_Tax::get_rate_label( $tax_rate_id ) );
		$this->set_compound( WC_Tax::is_compound( $tax_rate_id ) );
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
	public function get_rate_code() {
		return $this->_data['rate_code'];
	}

	/**
	 * Get label.
	 * @return string
	 */
	public function get_label() {
		return $this->_data['label'] ? $this->_data['label'] : __( 'Tax', 'woocommerce');
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
