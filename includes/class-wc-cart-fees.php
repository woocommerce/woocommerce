<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart Fees API.
 *
 * Fees are additional costs added to orders.
 *
 * @class 		WC_Cart_Fees
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Fees {

	/**
	 * An array of fee objects.
	 * @var object[]
	 */
	private $fees = array();

	/**
	 * Get fees.
	 * @return array
	 */
	public function get() {
		return array_filter( (array) $this->fees );
	}

	public function add( $name, $amount, $taxable = false, $tax_class = '' ) {
		$new_fee_id = sanitize_title( $name );

		// Only add each fee once
		foreach ( $this->fees as $fee ) {
			if ( $fee->id == $new_fee_id ) {
				return;
			}
		}

		$new_fee            = new stdClass();
		$new_fee->id        = $new_fee_id;
		$new_fee->name      = esc_attr( $name );
		$new_fee->amount    = (float) esc_attr( $amount );
		$new_fee->tax_class = $tax_class;
		$new_fee->taxable   = $taxable ? true : false;
		$new_fee->tax       = 0;
		$new_fee->tax_data  = array();
		$this->fees[]       = $new_fee;
	}

	public function calculate() {
		// Reset fees before calculation
		$this->fee_total = 0;
		$this->fees      = array();

		// Fire an action where developers can add their fees
		do_action( 'woocommerce_cart_calculate_fees', $this );

		// If fees were added, total them and calculate tax
		if ( ! empty( $this->fees ) ) {
			foreach ( $this->fees as $fee_key => $fee ) {
				$this->fee_total += $fee->amount;

				if ( $fee->taxable ) {
					// Get tax rates
					$tax_rates = WC_Tax::get_rates( $fee->tax_class );
					$fee_taxes = WC_Tax::calc_tax( $fee->amount, $tax_rates, false );

					if ( ! empty( $fee_taxes ) ) {
						// Set the tax total for this fee
						$this->fees[ $fee_key ]->tax = array_sum( $fee_taxes );

						// Set tax data - Since 2.2
						$this->fees[ $fee_key ]->tax_data = $fee_taxes;

						// Tax rows - merge the totals we just got
						foreach ( array_keys( $this->taxes + $fee_taxes ) as $key ) {
							$this->taxes[ $key ] = ( isset( $fee_taxes[ $key ] ) ? $fee_taxes[ $key ] : 0 ) + ( isset( $this->taxes[ $key ] ) ? $this->taxes[ $key ] : 0 );
						}
					}
				}
			}
		}
	}

}
