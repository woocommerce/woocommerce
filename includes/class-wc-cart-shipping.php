<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart Shipping.
 *
 * @class 		WC_Cart_Shipping
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Shipping {

	/**
	 * Uses the shipping class to calculate shipping then gets the totals when its finished.
	 */
	public function calculate_shipping() {
		if ( $this->needs_shipping() && $this->show_shipping() ) {
			WC()->shipping->calculate_shipping( $this->get_shipping_packages() );
		} else {
			WC()->shipping->reset_shipping();
		}

		// Get totals for the chosen shipping method
		$this->shipping_total 		= WC()->shipping->shipping_total;	// Shipping Total
		$this->shipping_taxes		= WC()->shipping->shipping_taxes;	// Shipping Taxes
	}

	/**
	 * Get packages to calculate shipping for.
	 *
	 * This lets us calculate costs for carts that are shipped to multiple locations.
	 *
	 * Shipping methods are responsible for looping through these packages.
	 *
	 * By default we pass the cart itself as a package - plugins can change this.
	 * through the filter and break it up.
	 *
	 * @since 1.5.4
	 * @return array of cart items
	 */
	public function get_shipping_packages() {
		// Packages array for storing 'carts'
		$packages = array();

		$packages[0]['contents']                 = $this->get_cart();		// Items in the package
		$packages[0]['contents_cost']            = 0;						// Cost of items in the package, set below
		$packages[0]['applied_coupons']          = $this->applied_coupons;
		$packages[0]['user']['ID']               = get_current_user_id();
		$packages[0]['destination']['country']   = WC()->customer->get_shipping_country();
		$packages[0]['destination']['state']     = WC()->customer->get_shipping_state();
		$packages[0]['destination']['postcode']  = WC()->customer->get_shipping_postcode();
		$packages[0]['destination']['city']      = WC()->customer->get_shipping_city();
		$packages[0]['destination']['address']   = WC()->customer->get_shipping_address();
		$packages[0]['destination']['address_2'] = WC()->customer->get_shipping_address_2();

		foreach ( $this->get_cart() as $item ) {
			if ( $item['data']->needs_shipping() ) {
				if ( isset( $item['line_total'] ) ) {
					$packages[0]['contents_cost'] += $item['line_total'];
				}
			}
		}

		return apply_filters( 'woocommerce_cart_shipping_packages', $packages );
	}

	/**
	 * Looks through the cart to see if shipping is actually required.
	 *
	 * @return bool whether or not the cart needs shipping
	 */
	public function needs_shipping() {
		// If shipping is disabled or not yet configured, we can skip this.
		if ( ! wc_shipping_enabled() || 0 === wc_get_shipping_method_count( true ) ) {
			return false;
		}

		$needs_shipping = false;

		if ( ! empty( $this->cart_contents ) ) {
			foreach ( $this->cart_contents as $cart_item_key => $values ) {
				$_product = $values['data'];
				if ( $_product->needs_shipping() ) {
					$needs_shipping = true;
				}
			}
		}

		return apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );
	}

	/**
	 * Should the shipping address form be shown.
	 *
	 * @return bool
	 */
	public function needs_shipping_address() {

		$needs_shipping_address = false;

		if ( $this->needs_shipping() === true && ! wc_ship_to_billing_address_only() ) {
			$needs_shipping_address = true;
		}

		return apply_filters( 'woocommerce_cart_needs_shipping_address', $needs_shipping_address );
	}

	/**
	 * Sees if the customer has entered enough data to calc the shipping yet.
	 *
	 * @return bool
	 */
	public function show_shipping() {
		if ( ! wc_shipping_enabled() || ! is_array( $this->cart_contents ) )
			return false;

		if ( 'yes' === get_option( 'woocommerce_shipping_cost_requires_address' ) ) {
			if ( ! WC()->customer->has_calculated_shipping() ) {
				if ( ! WC()->customer->get_shipping_country() || ( ! WC()->customer->get_shipping_state() && ! WC()->customer->get_shipping_postcode() ) ) {
					return false;
				}
			}
		}

		return apply_filters( 'woocommerce_cart_ready_to_calc_shipping', true );
	}

	/**
	 * Gets the shipping total (after calculation).
	 *
	 * @return string price or string for the shipping total
	 */
	public function get_cart_shipping_total() {
		if ( isset( $this->shipping_total ) ) {
			if ( $this->shipping_total > 0 ) {

				// Display varies depending on settings
				if ( $this->tax_display_cart == 'excl' ) {

					$return = wc_price( $this->shipping_total );

					if ( $this->shipping_tax_total > 0 && $this->prices_include_tax ) {
						$return .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}

					return $return;

				} else {

					$return = wc_price( $this->shipping_total + $this->shipping_tax_total );

					if ( $this->shipping_tax_total > 0 && ! $this->prices_include_tax ) {
						$return .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}

					return $return;

				}
			} else {
				return __( 'Free!', 'woocommerce' );
			}
		}
		return '';
	}

}
