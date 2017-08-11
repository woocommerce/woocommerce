<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item (discount).
 *
 * @version     3.2.0
 * @since       3.2.0
 * @package     WooCommerce/Classes
 * @author      WooCommerce
 */
class WC_Order_Item_Discount extends WC_Order_Item {

	/**
	 * Data array.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'amount'        => 0, // Discount amount.
		'discount_type' => 'fixed', // Fixed or percent type.
		'total'         => '',
		'total_tax'     => '',
		'taxes'         => array(
			'total' => array(),
		),
	);

	/**
	 * Calculate item taxes.
	 *
	 * @since  3.2.0
	 * @param  array $calculate_tax_for Location data to get taxes for. Required.
	 * @return bool  True if taxes were calculated.
	 */
	public function calculate_taxes( $calculate_tax_for = array() ) {
		if ( ! isset( $calculate_tax_for['country'], $calculate_tax_for['state'], $calculate_tax_for['postcode'], $calculate_tax_for['city'] ) ) {
			return false;
		}
		if ( wc_tax_enabled() && ( $order = $this->get_order() ) ) {
			// Apportion taxes to order items, shipping, and fees.
			$order            = $this->get_order();
			$tax_class_counts = $order->get_tax_class_counts_for_items( array( 'line_item', 'fee', 'shipping' ) );
			$item_count       = $order->get_item_count( array( 'line_item', 'fee', 'shipping' ) );
			$discount_taxes   = array();

			foreach ( $tax_class_counts as $tax_class => $tax_class_count ) {
				$proportion               = $tax_class_count / $item_count;
				$cart_discount_proportion = $this->get_total() * $proportion;
				$discount_taxes           = wc_array_merge_recursive_numeric( $discount_taxes, WC_Tax::calc_tax( $cart_discount_proportion, WC_Tax::get_rates( $tax_class ) ) );
			}

			$this->set_taxes( array( 'total' => $discount_taxes ) );
		} else {
			$this->set_taxes( false );
		}
		return true;
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set amount.
	 *
	 * @param string $value Value to set.
	 */
	public function set_amount( $value ) {
		$this->set_prop( 'amount', $value );
	}

	/**
	 * Set discount_type.
	 *
	 * @param string $value Value to set.
	 */
	public function set_discount_type( $value ) {
		$this->set_prop( 'discount_type', $value );
	}

	/**
	 * Set total.
	 *
	 * @param string $value Value to set.
	 */
	public function set_total( $value ) {
		$this->set_prop( 'total', wc_format_decimal( $value ) );
	}

	/**
	 * Set total tax.
	 *
	 * @param string $value Value to set.
	 */
	public function set_total_tax( $value ) {
		$this->set_prop( 'total_tax', wc_format_decimal( $value ) );
	}

	/**
	 * Set taxes.
	 *
	 * This is an array of tax ID keys with total amount values.
	 *
	 * @param array $raw_tax_data Array of taxes.
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
	 * Get order item type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'discount';
	}

	/**
	 * Get amount.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_amount( $context = 'view' ) {
		return $this->get_prop( 'amount', $context );
	}

	/**
	 * Get discount_type.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_discount_type( $context = 'view' ) {
		return $this->get_prop( 'discount_type', $context );
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
}
