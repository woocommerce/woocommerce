<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item (fee).
 *
 * Fee is an amount of money charged for a particular piece of work
 * or for a particular right or service, and not supposed to be negative.
 *
 * @version     3.0.0
 * @since       3.0.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Order_Item_Fee extends WC_Order_Item {

	/**
	 * Order Data array. This is the core order data exposed in APIs since 3.0.0.
	 * @since 3.0.0
	 * @var array
	 */
	protected $extra_data = array(
		'tax_class'  => '',
		'tax_status' => 'taxable',
		'total'      => '',
		'total_tax'  => '',
		'taxes'      => array(
			'total' => array(),
		),
	);

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set tax class.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_tax_class( $value ) {
		if ( $value && ! in_array( $value, WC_Tax::get_tax_class_slugs() ) ) {
			$this->error( 'order_item_fee_invalid_tax_class', __( 'Invalid tax class', 'woocommerce' ) );
		}
		$this->set_prop( 'tax_class', $value );
	}

	/**
	 * Set tax_status.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_tax_status( $value ) {
		if ( in_array( $value, array( 'taxable', 'none' ) ) ) {
			$this->set_prop( 'tax_status', $value );
		} else {
			$this->set_prop( 'tax_status', 'taxable' );
		}
	}

	/**
	 * Set total.
	 *
	 * @param string $amount Fee amount (do not enter negative amounts).
	 * @throws WC_Data_Exception
	 */
	public function set_total( $amount ) {
		$this->set_prop( 'total', wc_format_decimal( $amount ) );
	}

	/**
	 * Set total tax.
	 *
	 * @param string $amount
	 * @throws WC_Data_Exception
	 */
	public function set_total_tax( $amount ) {
		$this->set_prop( 'total_tax', wc_format_decimal( $amount ) );
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
			'total' => array(),
		);
		if ( ! empty( $raw_tax_data['total'] ) ) {
			$tax_data['total'] = array_map( 'wc_format_decimal', $raw_tax_data['total'] );
		}
		$this->set_prop( 'taxes', $tax_data );
		$this->set_total_tax( array_sum( $tax_data['total'] ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order item name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		$name = $this->get_prop( 'name', $context );
		if ( 'view' === $context ) {
			return $name ? $name : __( 'Fee', 'woocommerce' );
		} else {
			return $name;
		}
	}

	/**
	 * Get order item type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'fee';
	}

	/**
	 * Get tax class.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_tax_class( $context = 'view' ) {
		return $this->get_prop( 'tax_class', $context );
	}

	/**
	 * Get tax status.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_tax_status( $context = 'view' ) {
		return $this->get_prop( 'tax_status', $context );
	}

	/**
	 * Get total fee.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	/**
	 * Get total tax.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_total_tax( $context = 'view' ) {
		return $this->get_prop( 'total_tax', $context );
	}

	/**
	 * Get fee taxes.
	 *
	 * @param  string $context
	 * @return array
	 */
	public function get_taxes( $context = 'view' ) {
		return $this->get_prop( 'taxes', $context );
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
	 * offsetGet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( 'line_total' === $offset ) {
			$offset = 'total';
		} elseif ( 'line_tax' === $offset ) {
			$offset = 'total_tax';
		} elseif ( 'line_tax_data' === $offset ) {
			$offset = 'taxes';
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
		if ( 'line_total' === $offset ) {
			$offset = 'total';
		} elseif ( 'line_tax' === $offset ) {
			$offset = 'total_tax';
		} elseif ( 'line_tax_data' === $offset ) {
			$offset = 'taxes';
		}
		parent::offsetSet( $offset, $value );
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( in_array( $offset, array( 'line_total', 'line_tax', 'line_tax_data' ) ) ) {
			return true;
		}
		return parent::offsetExists( $offset );
	}
}
