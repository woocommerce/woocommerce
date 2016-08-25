<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item (shipping).
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Order_Item_Shipping extends WC_Order_Item {

	/**
	 * Default data values.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_default_data = array(
		'order_id'     => 0,
		'id'           => 0,
		'method_title' => '',
		'method_id'    => '',
		'total'        => 0,
		'total_tax'    => 0,
		'taxes'        => array(
			'total' => array()
		),
	);

	/**
	 * Order Data array. This is the core order data exposed in APIs since 2.7.0.
	 * This is set the _defaults on load.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_data = array();

	/**
	 * offsetGet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( 'cost' === $offset ) {
			$offset = 'total';
		}
		return parent::offsetGet( $offset );
	}

	/**
	 * offsetSet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		if ( 'cost' === $offset ) {
			$offset = 'total';
		}
		parent::offsetSet( $offset, $value );
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( in_array( $offset, array( 'cost' ) ) ) {
			return true;
		}
		return parent::offsetExists( $offset );
	}

	/**
	 * Read/populate data properties specific to this order item.
	 */
	public function read( $id ) {
		parent::read( $id );

		if ( ! $this->get_id() ) {
			return;
		}

		$this->set_props( array(
			'method_id' => get_metadata( 'order_item', $this->get_id(), 'method_id', true ),
			'total'     => get_metadata( 'order_item', $this->get_id(), 'cost', true ),
			'taxes'     => get_metadata( 'order_item', $this->get_id(), 'taxes', true ),
		) );
	}

	/**
	 * Save properties specific to this order item.
	 * @return int Item ID
	 */
	public function save() {
		parent::save();
		if ( $this->get_id() ) {
			wc_update_order_item_meta( $this->get_id(), 'method_id', $this->get_method_id() );
			wc_update_order_item_meta( $this->get_id(), 'cost', $this->get_total() );
			wc_update_order_item_meta( $this->get_id(), 'total_tax', $this->get_total_tax() );
			wc_update_order_item_meta( $this->get_id(), 'taxes', $this->get_taxes() );
		}

		return $this->get_id();
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data.
	 * @return array()
	 */
	protected function get_internal_meta_keys() {
		return array( 'method_id', 'cost', 'total_tax', 'taxes' );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order item name.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_name( $value ) {
		return $this->set_method_title( $value );
	}

	/**
	 * Set code.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_method_title( $value ) {
		$this->set_prop( 'method_title', wc_clean( $value ) );
	}

	/**
	 * Set shipping method id.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_method_id( $value ) {
		$this->set_prop( 'method_id', wc_clean( $value ) );
	}

	/**
	 * Set total.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_total( $value ) {
		$this->set_prop( 'total', wc_format_decimal( $value ) );
	}

	/**
	 * Set total tax.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	protected function set_total_tax( $value ) {
		$this->set_prop( 'total_tax', wc_format_decimal( $value ) );
	}

	/**
	 * Set taxes.
	 *
	 * This is an array of tax ID keys with total amount values.
	 * @param array $raw_tax_data
	 * @throws WC_Data_Exception
	 */
	public function set_taxes( $raw_tax_data ) {
		$raw_tax_data = maybe_unserialize( $raw_tax_data );
		$tax_data     = array(
			'total'    => array()
		);
		if ( ! empty( $raw_tax_data['total'] ) ) {
			$tax_data['total']    = array_map( 'wc_format_decimal', $raw_tax_data['total'] );
		}
		$this->set_prop( 'taxes', $tax_data );
		$this->set_total_tax( array_sum( $tax_data['total'] ) );
	}

	/**
	 * Set properties based on passed in shipping rate object.
	 * @param WC_Shipping_Rate $tax_rate_id
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_rate( $shipping_rate ) {
		$this->set_method_title( $shipping_rate->label );
		$this->set_method_id( $shipping_rate->id );
		$this->set_total( $shipping_rate->cost );
		$this->set_taxes( $shipping_rate->taxes );
		$this->set_meta_data( $shipping_rate->get_meta_data() );
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
		return 'shipping';
	}

	/**
	 * Get order item name.
	 * @return string
	 */
	public function get_name() {
		return $this->get_method_title();
	}

	/**
	 * Get title.
	 * @return string
	 */
	public function get_method_title() {
		return $this->get_prop( 'method_title' ) ? $this->get_prop( 'method_title' ) : __( 'Shipping', 'woocommerce' );
	}

	/**
	 * Get method ID.
	 * @return string
	 */
	public function get_method_id() {
		return $this->get_prop( 'method_id' );
	}

	/**
	 * Get total cost.
	 * @return string
	 */
	public function get_total() {
		return wc_format_decimal( $this->get_prop( 'total' ) );
	}

	/**
	 * Get total tax.
	 * @return string
	 */
	public function get_total_tax() {
		return wc_format_decimal( $this->get_prop( 'total_tax' ) );
	}

	/**
	 * Get taxes.
	 * @return array
	 */
	public function get_taxes() {
		return $this->get_prop( 'taxes' );
	}
}
