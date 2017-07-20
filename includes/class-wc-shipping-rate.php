<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Shipping Rate Class.
 *
 * Simple Class for storing rates.
 *
 * @class    WC_Shipping_Rate
 * @package  WooCommerce/Classes/Shipping
 * @category Class
 * @author   Automattic
 */
class WC_Shipping_Rate {

	/**
	 * Stores data for this rate.
	 *
	 * @since 3.2.0
	 * @var   array
	 */
	protected $data = array(
		'id'          => '',
		'method_id'   => '',
		'instance_id' => 0,
		'label'       => '',
		'cost'        => 0,
		'taxes'       => array(),
	);

	/**
	 * Stores meta data for this rate.
	 *
	 * @since 2.6.0
	 * @var   array
	 */
	protected $meta_data = array();

	/**
	 * Constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param integer $cost
	 * @param array $taxes
	 * @param string $method_id
	 * @param int $instance_id
	 */
	public function __construct( $id = '', $label = '', $cost = 0, $taxes = array(), $method_id = '', $instance_id = 0 ) {
		$this->set_id( $id );
		$this->set_label( $label );
		$this->set_cost( $cost );
		$this->set_taxes( $taxes );
		$this->set_method_id( $method_id );
		$this->set_instance_id( $instance_id );
	}

	/**
	 * Magic methods to support direct access to props.
	 *
	 * @since 3.2.0
	 * @param string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->data[ $key ] );
	}

	/**
	 * Magic methods to support direct access to props.
	 *
	 * @since 3.2.0
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( is_callable( array( $this, "get_{$key}" ) ) ) {
			return $this->{"get_{$key}"}();
		} elseif ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		} else {
			return '';
		}
	}

	/**
	 * Magic methods to support direct access to props.
	 *
	 * @since 3.2.0
	 * @param string $key
	 * @param mixed  $value
	 */
	public function __set( $key, $value ) {
		if ( is_callable( array( $this, "set_{$key}" ) ) ) {
			$this->{"set_{$key}"}( $value );
		} else {
			$this->data[ $key ] = $value;
		}
	}

	/**
	 * Set ID for the rate. This is usually a combination of the method and instance IDs.
	 *
	 * @since 3.2.0
	 * @param string $id
	 */
	public function set_id( $id ) {
		$this->data['id'] = (string) $id;
	}

	/**
	 * Set shipping method ID the rate belongs to.
	 *
	 * @since 3.2.0
	 * @param string $method_id
	 */
	public function set_method_id( $method_id ) {
		$this->data['method_id'] = (string) $method_id;
	}

	/**
	 * Set instance ID the rate belongs to.
	 *
	 * @since 3.2.0
	 * @param int $instance_id
	 */
	public function set_instance_id( $instance_id ) {
		$this->data['instance_id'] = absint( $instance_id );
	}

	/**
	 * Set rate label.
	 *
	 * @since 3.2.0
	 * @param string $method_id
	 */
	public function set_label( $label ) {
		$this->data['label'] = (string) $label;
	}

	/**
	 * Set rate cost.
	 *
	 * @since 3.2.0
	 * @param string $cost
	 */
	public function set_cost( $cost ) {
		$this->data['cost'] = $cost;
	}

	/**
	 * Set rate taxes.
	 *
	 * @since 3.2.0
	 * @param array $taxes
	 */
	public function set_taxes( $taxes ) {
		$this->data['taxes'] = ! empty( $taxes ) && is_array( $taxes ) ? $taxes : array();
	}

	/**
	 * Set ID for the rate. This is usually a combination of the method and instance IDs.
	 *
	 * @since 3.2.0
	 * @return string
	 */
	public function get_id() {
		return apply_filters( 'woocommerce_shipping_rate_id', $this->data['id'], $this );
	}

	/**
	 * Set shipping method ID the rate belongs to.
	 *
	 * @since 3.2.0
	 * @return string
	 */
	public function get_method_id() {
		return apply_filters( 'woocommerce_shipping_rate_method_id', $this->data['method_id'], $this );
	}

	/**
	 * Set instance ID the rate belongs to.
	 *
	 * @since 3.2.0
	 * @return int
	 */
	public function get_instance_id() {
		return apply_filters( 'woocommerce_shipping_rate_instance_id', $this->data['instance_id'], $this );
	}

	/**
	 * Set rate label.
	 *
	 * @return string
	 */
	public function get_label() {
		return apply_filters( 'woocommerce_shipping_rate_label', $this->data['label'], $this );
	}

	/**
	 * Set rate cost.
	 *
	 * @since 3.2.0
	 * @return string
	 */
	public function get_cost() {
		return apply_filters( 'woocommerce_shipping_rate_cost', $this->data['cost'], $this );
	}

	/**
	 * Set rate taxes.
	 *
	 * @since 3.2.0
	 * @return array
	 */
	public function get_taxes() {
		return apply_filters( 'woocommerce_shipping_rate_taxes', $this->data['taxes'], $this );
	}

	/**
	 * Get shipping tax.
	 *
	 * @return array
	 */
	public function get_shipping_tax() {
		return apply_filters( 'woocommerce_get_shipping_tax', sizeof( $this->taxes ) > 0 && ! WC()->customer->get_is_vat_exempt() ? array_sum( $this->taxes ) : 0, $this );
	}

	/**
	 * Add some meta data for this rate.
	 *
	 * @since 2.6.0
	 * @param string $key
	 * @param string $value
	 */
	public function add_meta_data( $key, $value ) {
		$this->meta_data[ wc_clean( $key ) ] = wc_clean( $value );
	}

	/**
	 * Get all meta data for this rate.
	 *
	 * @since 2.6.0
	 * @return array
	 */
	public function get_meta_data() {
		return $this->meta_data;
	}
}
