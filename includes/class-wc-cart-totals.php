<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles cart totals calculations.
 *
 * @class 		WC_Cart_Totals
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Totals {

	protected $calculate_tax  = true;
	protected $totals         = null;
	protected $coupons        = array();
	protected $items          = array();
	protected $fees           = array();
	protected $shipping_lines = array();

	/**
	 * Get default blank set of props used per item.
	 * @return array
	 */
	private function get_default_item_props() {
		return (object) array(
			'price_includes_tax' => wc_prices_include_tax(),
			'subtotal'           => 0,
			'subtotal_tax'       => 0,
			'subtotal_taxes'     => array(),
			'total'              => 0,
			'tax'                => 0,
			'taxes'              => array(),
			'discounted_price'   => 0,
		);
	}

	/**
	 * Get default blank set of props used per coupon.
	 * @return array
	 */
	private function get_default_coupon_props() {
		return (object) array(
			'count'     => 0,
			'total'     => 0,
			'total_tax' => 0,
		);
	}

	/**
	 * Get default blank set of props used per fee.
	 * @return array
	 */
	private function get_default_fee_props() {
		return (object) array(
			'tax'   => 0,
			'taxes' => array(),
		);
	}

	/**
	 * Get default blank set of props used per shipping row.
	 * @return array
	 */
	private function get_default_shipping_props() {
		return (object) array(
			'total'     => 0,
			'total_tax' => 0,
			'taxes'     => array(),
		);
	}

	/**
	 * Sets items and adds precision which lets us work with integers.
	 * @param array $items
	 */
	public function set_items( $items ) {
		foreach ( $items as $item_key => $maybe_item ) {
			if ( ! is_a( $maybe_item, 'WC_Cart_Item' ) ) {
				continue;
			}
			$item                     = $this->get_default_item_props();
			$item->product            = $maybe_item->get_product();
			$item->values             = $maybe_item;
			$item->quantity           = $maybe_item->get_quantity();
			$item->price              = $item->product->get_price();
			$this->items[ $item_key ] = $item;
		}
		$this->totals = null;
	}

	/**
	 * Set coupons.
	 * @param array $coupons
	 */
	public function set_coupons( $coupons ) {
		foreach ( $coupons as $code => $coupon_object ) {
			$coupon                 = $this->get_default_coupon_props();
			$coupon->coupon         = $coupon_object;
			$this->coupons[ $code ] = $coupon;
		}
		$this->totals = null;
	}


		/**
		 * Get fees.
		 * @return array
		 */
		public function get_coupons() {
			return $this->coupons;
		}

	/**
	 * Set fees.
	 * @param array $fees
	 */
	public function set_fees( $fees ) {
		foreach ( $fees as $fee_key => $fee_object ) {
			$fee                    = $this->get_default_fee_props();
			$fee->amount            = $fee_object->amount;
			$fee->taxable           = $fee_object->taxable;
			$fee->tax_class         = $fee_object->tax_class;
			$this->fees[ $fee_key ] = $fee;
		}
		$this->totals = null;
	}

	/**
	 * Should we calc tax?
	 * @param bool
	 */
	public function set_calculate_tax( $value ) {
		$this->calculate_tax = (bool) $value;
	}

	/**
	 * Should we calc tax?
	 * @param bool
	 */
	public function get_calculate_tax() {
		return wc_tax_enabled() && $this->calculate_tax;
	}

	/**
	 * Subtotals are costs before discounts.
	  * Prices include tax. @todo
	  *
	  * To prevent rounding issues we need to work with the inclusive price where possible.
	  * otherwise we'll see errors such as when working with a 9.99 inc price, 20% VAT which would.
	  * be 8.325 leading to totals being 1p off.
	  *
	  * Pre tax coupons come off the price the customer thinks they are paying - tax is calculated.
	  * afterwards.
	  *
	  * e.g. $100 bike with $10 coupon = customer pays $90 and tax worked backwards from that.
	  */
	private function calculate_item_subtotals() {
		foreach ( $this->items as $item ) {
			$item->subtotal     = $item->price * $item->quantity;
			$item->subtotal_tax = 0;

			if ( $item->price_includes_tax && apply_filters( 'woocommerce_adjust_non_base_location_prices', false ) ) {
				$item           = $this->adjust_non_base_location_price( $item );
				$item->subtotal = $item->price * $item->quantity;
			}

			if ( $this->get_calculate_tax() && $item->product->is_taxable() ) {
				$item->subtotal_taxes = WC_Tax::calc_tax( $item->subtotal, $this->get_item_tax_rates( $item ), $item->price_includes_tax );
				$item->subtotal_tax      = array_sum( $item->subtotal_taxes );

				if ( $item->price_includes_tax ) {
					$item->subtotal = $item->subtotal - $item->subtotal_tax;
				}
			}
		}
	}

	/**
	 * Totals are costs after discounts.
	 */
	public function calculate_item_totals() {
		$this->calculate_item_subtotals();
		uasort( $this->items, array( $this, 'sort_items_callback' ) );

		foreach ( $this->items as $item ) {
			$item->discounted_price = $this->get_discounted_price( $item );
			$item->total            = $item->discounted_price * $item->quantity;
			$item->tax              = 0;

			if ( $this->get_calculate_tax() && $item->product->is_taxable() ) {
				$item->taxes = WC_Tax::calc_tax( $item->total, $this->get_item_tax_rates( $item ), $item->price_includes_tax );
				$item->tax      = array_sum( $item->taxes );

				if ( $item->price_includes_tax ) {
					$item->total = $item->total - $item->tax;
				} else {
					$item->total = $item->total;
				}
			}
		}
	}

	/**
	 * Calculate any fees taxes.
	 */
	private function calculate_fee_totals() {
		if ( ! empty( $this->fees ) ) {
			foreach ( $this->fees as $fee_key => $fee ) {
				if ( $this->get_calculate_tax() && $fee->taxable ) {
					$fee->taxes = WC_Tax::calc_tax( $fee->amount, $tax_rates, false );
					$fee->tax      = array_sum( $fee->taxes );
				}
			}
		}
	}

	/**
	 * Main cart totals.
	 */
	public function calculate_totals() {
		$this->calculate_fee_totals();
		$this->totals        = new stdClass();
		$this->totals->taxes = $this->get_merged_taxes();

		// Total up/round taxes and shipping taxes
		if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
			$this->totals->tax_total          = WC_Tax::get_tax_total( wc_list_pluck( $this->totals->taxes, 'get_tax_total' ) );
			$this->totals->shipping_tax_total = WC_Tax::get_tax_total( wc_list_pluck( $this->totals->taxes, 'get_shipping_tax_total' ) );
		} else {
			$this->totals->tax_total          = array_sum( wc_list_pluck( $this->totals->taxes, 'get_tax_total' ) );
			$this->totals->shipping_tax_total = array_sum( wc_list_pluck( $this->totals->taxes, 'get_shipping_tax_total' ) );
		}

		// Allow plugins to hook and alter totals before final total is calculated
		do_action( 'woocommerce_calculate_totals', WC()->cart );

		// Grand Total - Discounted product prices, discounted tax, shipping cost + tax
		$this->totals->total = max( 0, apply_filters( 'woocommerce_calculated_total', round( $this->get_items_total( false ) + $this->get_fees_total( false ) + $this->get_shipping_total( false ) + $this->totals->tax_total + $this->totals->shipping_tax_total, wc_get_price_decimals() ), WC()->cart ) );
	}

	/**
	 * Returns items and their totals.
	 * @return array
	 */
	public function get_item_totals() {
		return $this->items;
	}

	/**
	 * Get tax rates for an item. Caches rates in class to avoid multiple look ups.
	 * @param  object $item
	 * @return array of taxes
	 */
	private function get_item_tax_rates( $item ) {
		$tax_class = $item->product->get_tax_class();
		return isset( $this->item_tax_rates[ $tax_class ] ) ? $this->item_tax_rates[ $tax_class ] : $this->item_tax_rates[ $tax_class ] = WC_Tax::get_rates( $item->product->get_tax_class() );
	}

	/**
	 * Sort items by the subtotal.
	 */
	private function sort_items_callback( $a, $b ) {
		$first_item_subtotal  = isset( $a->subtotal ) ? $a->subtotal : 0;
		$second_item_subtotal = isset( $b->subtotal ) ? $b->subtotal : 0;
		if ( $first_item_subtotal === $second_item_subtotal ) {
			return 0;
		}
		return ( $first_item_subtotal < $second_item_subtotal ) ? 1 : -1;
	}

	/**
	 * Get the subtotal for all items.
	 *
	 * @param  boolean $inc_tax Should tax also be included in the subtotal?
	 * @return float
	 */
	public function get_items_subtotal( $inc_tax = true ) {
		$totals = array_values( wp_list_pluck( $this->items, 'subtotal' ) );

		if ( $inc_tax ) {
			$totals = array_merge( $totals, array_values( wp_list_pluck( $this->items, 'subtotal_tax' ) ) );
		}

		return array_sum( $totals );
	}

	/**
	 * Get the total for all items.
	 *
	 * @param  boolean $inc_tax Should tax also be included in the subtotal?
	 * @return float
	 */
	public function get_items_total( $inc_tax = true ) {
		$totals = array_values( wp_list_pluck( $this->items, 'total' ) );

		if ( $inc_tax ) {
			$totals = array_merge( $totals, array_values( wp_list_pluck( $this->items, 'tax' ) ) );
		}

		return array_sum( $totals );
	}

	/**
	 * Get the total discount amount.
	 * @return float
	 */
	public function get_discount_total() {
		return wc_cart_round_discount( array_sum( array_values( wp_list_pluck( $this->coupons, 'total' ) ) ), wc_get_price_decimals() );
	}

	/**
	 * Get the total discount amount.
	 * @return float
	 */
	public function get_discount_total_tax() {
		return wc_cart_round_discount( array_sum( array_values( wp_list_pluck( $this->coupons, 'total_tax' ) ) ), wc_get_price_decimals() );
	}

	/**
	 * Should discounts be applied sequentially?
	 * @return bool
	 */
	private function calc_discounts_sequentially() {
		return 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' );
	}

	/**
	 * Get an items price to discount (undiscounted price).
	 * @param  object $item
	 * @return float
	 */
	private function get_undiscounted_price( $item ) {
		if ( $item->price_includes_tax ) {
			return ( $item->subtotal + $item->subtotal_tax ) / $item->quantity;
		} else {
			return $item->subtotal / $item->quantity;
		}
	}

	/**
	 * Get an individual items price after discounts are applied.
	 * @param  object $item
	 * @return float
	 */
	public function get_discounted_price( $item ) {
		$price = $this->get_undiscounted_price( $item );

		foreach ( $this->coupons as $code => $coupon ) {
			if ( $coupon->coupon->is_valid_for_product( $item->product ) || $coupon->coupon->is_valid_for_cart() ) {
				$price_to_discount = $this->calc_discounts_sequentially() ? $price : $this->get_undiscounted_price( $item );

				if ( $coupon->coupon->is_type( 'fixed_product' ) ) {
					$discount = min( $coupon->coupon->get_amount(), $price_to_discount );

				} elseif ( $coupon->coupon->is_type( array( 'percent_product', 'percent' ) ) ) {
					$discount = $coupon->coupon->get_amount() * ( $price_to_discount / 100 );

				/**
				 * This is the most complex discount - we need to divide the discount between rows based on their price in
				 * proportion to the subtotal. This is so rows with different tax rates get a fair discount, and so rows
				 * with no price (free) don't get discounted. Get item discount by dividing item cost by subtotal to get a %.
				 *
				 * Uses price inc tax if prices include tax to work around https://github.com/woothemes/woocommerce/issues/7669 and https://github.com/woothemes/woocommerce/issues/8074.
				 */
				} elseif ( $coupon->coupon->is_type( 'fixed_cart' ) ) {
					$discount_percent = ( $item->subtotal + $item->subtotal_tax ) / array_sum( array_merge( array_values( wp_list_pluck( $this->items, 'subtotal' ) ), array_values( wp_list_pluck( $this->items, 'subtotal_tax' ) ) ) );
					$discount         = ( $coupon->coupon->get_amount() * $discount_percent ) / $item->quantity;
				}

				// Discount cannot be greater than the price we are discounting.
				$discount_amount = min( $price_to_discount, $discount );

				// Reduce the price so the next coupon discounts the new amount.
				$price           = max( $price - $discount_amount, 0 );

				// Store how much each coupon has discounted in total.
				$coupon->count += $item->quantity;
				$coupon->total += $discount_amount * $item->quantity;

				// If taxes are enabled, we should also note how much tax would have been paid if it was not discounted.
				if ( $this->get_calculate_tax() ) {
					$tax_amount    = WC_Tax::get_tax_total( WC_Tax::calc_tax( $discount_amount, $this->get_item_tax_rates( $item ), $item->price_includes_tax ) );
					$coupon->total_tax += $tax_amount * $item->quantity;
					$coupon->total = $item->price_includes_tax ? $coupon->total - ( $tax_amount * $item->quantity ) : $coupon->total;
				}
			}

			// If the price is 0, we can stop going through coupons because there is nothing more to discount for this product.
			if ( 0 >= $price ) {
				break;
			}
		}

		/**
		 * woocommerce_get_discounted_price filter.
		 * @param float $price the price to return.
		 * @param array $item->values Cart item values. Used in legacy cart class function.
		 * @param object WC()->cart. Used in legacy cart class function.
		 */
		return apply_filters( 'woocommerce_get_discounted_price', $price, $item->values, WC()->cart );
	}

	/**
	 * Only ran if woocommerce_adjust_non_base_location_prices is true.
	 *
	 * If the customer is outside of the base location, this removes the base taxes.
	 *
	 * Pre 2.7, this was on by default. From 2.7 onwards this is off by default meaning
	 * that if a product costs 10 including tax, all users will pay 10 regardless of location and taxes. This was experiemental from 2.4.7.
	 */
	private function adjust_non_base_location_price( $item ) {
		$base_tax_rates = WC_Tax::get_base_tax_rates( $item->product->tax_class );
		$item_tax_rates = $this->get_item_tax_rates( $item );

		if ( $item_tax_rates !== $base_tax_rates ) {
			// Work out a new base price without the shop's base tax
			$taxes                    = WC_Tax::calc_tax( $item->price, $base_tax_rates, true, true );

			// Now we have a new item price (excluding TAX)
			$item->price              = $item->price - array_sum( $taxes );
			$item->price_includes_tax = false;
		}

		return $item;
	}

	/**
	 * Get the total for all items.
	 *
	 * @param  boolean $inc_tax Should tax also be included in the subtotal?
	 * @return float
	 */
	public function get_fees_total( $inc_tax = true ) {
		$totals = array_values( wp_list_pluck( $this->fees, 'amount' ) );

		if ( $inc_tax ) {
			$totals = array_merge( $totals, array_values( wp_list_pluck( $this->fees, 'tax' ) ) );
		}

		return array_sum( $totals );
	}

	/**
	 * Get all tax rows for a set of items and shipping methods.
	 * @return array
	 */
	public function get_merged_taxes() {
		$taxes = array();

		foreach ( $this->items as $item ) {
			foreach ( $item->taxes as $rate_id => $rate ) {
				if ( ! isset( $taxes[ $rate_id ] ) ) {
					$taxes[ $rate_id ] = new WC_Item_Tax();
				}
				$taxes[ $rate_id ]->set_rate( $rate_id );
				$taxes[ $rate_id ]->set_tax_total( $taxes[ $rate_id ]->get_tax_total() + $rate );
			}
		}

		foreach ( $this->shipping_lines as $item ) {
			foreach ( $item->taxes as $rate_id => $rate ) {
				if ( ! isset( $taxes[ $rate_id ] ) ) {
					$taxes[ $rate_id ] = new WC_Item_Tax();
				}
				$taxes[ $rate_id ]->set_rate( $rate_id );
				$taxes[ $rate_id ]->set_shipping_tax_total( $taxes[ $rate_id ]->get_shipping_tax_total() + $rate );
			}
		}

		return $taxes;
	}

	/**
	 * Get taxes.
	 * @return array
	 */
	public function get_taxes() {
		return $this->totals->taxes;
	}

	/**
	 * Get tax total.
	 * @return float
	 */
	public function get_tax_total() {
		return $this->totals->tax_total;
	}

	/**
	 * Get shipping tax total.
	 * @return float
	 */
	public function get_shipping_tax_total() {
		return $this->totals->shipping_tax_total;
	}

	/**
	 * Get grand total.
	 * @return float
	 */
	public function get_total() {
		return $this->totals->total;
	}

	/**
	 * Get the total for all items.
	 *
	 * @param  boolean $inc_tax Should tax also be included in the subtotal?
	 * @return float
	 */
	public function get_shipping_total( $inc_tax = true ) {
		$totals = array_values( wp_list_pluck( $this->shipping_lines, 'total' ) );

		if ( $inc_tax ) {
			$totals = array_merge( $totals, array_values( wp_list_pluck( $this->shipping_lines, 'total_tax' ) ) );
		}

		return array_sum( $totals );
	}

	public function set_shipping( $shipping_objects ) {
		$this->shipping_lines = array();

		if ( is_array( $shipping_objects ) ) {
			foreach ( $shipping_objects as $key => $shipping_object ) {
				$shipping                     = $this->get_default_shipping_props();
				$shipping->total              = $shipping_object->cost;
				$shipping->taxes           = $shipping_object->taxes;
				$shipping->total_tax          = array_sum( $shipping_object->taxes );
				$this->shipping_lines[ $key ] = $shipping;
			}
		}

		$this->totals = null;
	}
}
