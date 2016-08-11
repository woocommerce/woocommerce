<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Cart Item Totals
 *
 * Handles total/subtotal calculation for a group of items.
 *
 * @class 		WC_Cart_Item_Totals
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Item_Totals {

	private $items          = array();
	private $item_tax_rates = array();

	/**
	 * Constructor expects $items array
	 */
	public function __construct( $items = array() ) {
		$this->set_items( $items );
		$this->get_calculated_totals();
	}

	/**
	 * Sets items and adds precision which lets us work with integers.
	 * @param array $items
	 */
	public function set_items( $items ) {
		foreach ( $items as $item_key => $maybe_item ) {
			$item = (object) array(
				'product'              => false,
				'price_includes_tax'   => wc_prices_include_tax(),
				'price'                => 0,
				'quantity'             => 0,
				'subtotal'             => 0,
				'subtotal_tax'         => 0,
				'subtotal_tax_data'    => array(),
				'total'                => 0,
				'tax'                  => 0,
				'tax_data'             => array(),
				'discount_amounts'     => array(),
				'discount_tax_amounts' => array(),
				'discounted_price'     => 0,
			);

			if ( is_array( $maybe_item ) && isset( $maybe_item['data'], $maybe_item['quantity'] ) ) {
				$item->product  = $maybe_item['data'];
				$item->quantity = $maybe_item['quantity'];
				$item->price    = $this->add_precision( $maybe_item['data']->get_price() ); // Work with cents
			}

			$this->items[ $item_key ] = $item;
		}
	}

	/**
	 * Removes precision and returns items.
	 * @return array
	 */
	public function get_items() {
		$props = array( 'subtotal', 'total', 'tax', 'subtotal_tax', 'tax_data', 'discount_amounts', 'discount_tax_amounts', 'price', 'discounted_price', 'subtotal_tax_data' );
		$items = $this->items;

		foreach ( $items as $key => $item ) {
			foreach ( $props as $prop ) {
				$items[ $key ]->$prop = $this->remove_precision( $item->$prop );
			}
		}

		return $this->items;
	}

	/**
	 * Calculate and get totals.
	 * @return array
	 */
	public function get_calculated_totals() {
		$this->calculate_item_subtotals();
		$this->calculate_item_totals();
	}

	public function get_subtotal( $inc_tax = true ) {
		$totals = $this->remove_precision( array_values( wp_list_pluck( $this->items, 'subtotal' ) ) );

		if ( $inc_tax ) {
			$totals = array_merge( $totals, $this->remove_precision( array_values( wp_list_pluck( $this->items, 'subtotal_tax' ) ) ) );
		}

		return array_sum( $totals );
	}

	public function get_total( $inc_tax = true ) {
		$totals = $this->remove_precision( array_values( wp_list_pluck( $this->items, 'total' ) ) );

		if ( $inc_tax ) {
			$totals = array_merge( $totals, $this->remove_precision( array_values( wp_list_pluck( $this->items, 'tax' ) ) ) );
		}

		return array_sum( $totals );
	}

	public function get_tax_data() {
		$tax_data = array();

		foreach ( $this->items as $item ) {
			foreach ( array_keys( $tax_data + $item->tax_data ) as $key ) {
				$tax_data[ $key ] = ( isset( $item->tax_data[ $key ] ) ? $this->remove_precision( $item->tax_data[ $key ] ) : 0 ) + ( isset( $tax_data[ $key ] ) ? $tax_data[ $key ] : 0 );
			}
		}

		return $tax_data;
	}

	/**
	 * Multiply costs by pow precision. Lets us work with cent values.
	 * @param  float $price
	 * @return int
	 */
	private function add_precision( $price ) {
		if ( is_array( $price ) ) {
			foreach ( $price as $key => $value ) {
			 	$price[ $key ] = $this->add_precision( $value );
			}
		} else {
			$price = $price * ( pow( 10, wc_get_rounding_precision() ) );
		}
		return $price;
	}

	/**
	 * Divide costs by pow precision. Lets us work with cent values.
	 * @param  int $price
	 * @return float
	 */
	private function remove_precision( $price ) {
		if ( is_array( $price ) ) {
			foreach ( $price as $key => $value ) {
				$price[ $key ] = $this->remove_precision( $value );
			}
		} else {
			$price = round( $price ) / ( pow( 10, wc_get_rounding_precision() ) );
		}
		return $price;
	}

	/**
	 * Sort items by the subtotal.
	 */
	private function sort_items_by_subtotal( $a, $b ) {
		$first_item_subtotal  = isset( $a->subtotal ) ? $a->subtotal : 0;
		$second_item_subtotal = isset( $b->subtotal ) ? $b->subtotal : 0;
		if ( $first_item_subtotal === $second_item_subtotal ) {
			return 0;
		}
		return ( $first_item_subtotal < $second_item_subtotal ) ? 1 : -1;
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
			$taxes                         = WC_Tax::calc_tax( $item->line_price, $base_tax_rates, true, true );

			// Now we have a new item price (excluding TAX)
			$item->price              = $item->price - array_sum( $taxes );
			$item->price_includes_tax = false;
		}

		return $item;
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

			if ( wc_tax_enabled() && $item->product->is_taxable() ) {
			 	if ( $item->price_includes_tax && apply_filters( 'woocommerce_adjust_non_base_location_prices', false ) ) {
					$item           = $this->adjust_non_base_location_price( $item );
					$item->subtotal = $item->price * $item->quantity;
				}

				$item->subtotal_tax_data = WC_Tax::calc_tax( $item->subtotal, $this->get_item_tax_rates( $item ), $item->price_includes_tax );
				$item->subtotal_tax      = array_sum( $item->subtotal_tax_data );

				if ( $item->price_includes_tax ) {
					$item->subtotal = $item->subtotal - $item->subtotal_tax;
				}
			}
		}

		uasort( $this->items, array( $this, 'sort_items_by_subtotal' ) );
	}


	public function get_discounted_price( $item ) {
		if ( $item->price_includes_tax ) {
			$undiscounted_price = ( $item->subtotal + $item->subtotal_tax ) / $item->quantity;
		} else {
			$undiscounted_price = $item->subtotal / $item->quantity;
		}

		$price = $undiscounted_price;

		if ( ! empty( WC()->cart->get_coupons() ) ) {
			foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
				if ( $coupon->is_valid_for_product( $item->product ) || $coupon->is_valid_for_cart() ) {
					$price_to_discount = 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ? $price : $undiscounted_price;

					if ( $coupon->is_type( 'fixed_product' ) ) {
						$discount = min( $this->add_precision( $coupon->get_amount() ), $price_to_discount );

					} elseif ( $coupon->is_type( array( 'percent_product', 'percent' ) ) ) {
						$discount = $coupon->get_amount() * ( $price_to_discount / 100 );

					/**
					 * This is the most complex discount - we need to divide the discount between rows based on their price in
					 * proportion to the subtotal. This is so rows with different tax rates get a fair discount, and so rows
					 * with no price (free) don't get discounted. Get item discount by dividing item cost by subtotal to get a %.
					 *
					 * Uses price inc tax if prices include tax to work around https://github.com/woothemes/woocommerce/issues/7669 and https://github.com/woothemes/woocommerce/issues/8074.
					 */
					} elseif ( $coupon->is_type( 'fixed_cart' ) ) {
						$discount_percent = ( $item->subtotal + $item->subtotal_tax ) / array_sum( array_merge( array_values( wp_list_pluck( $this->items, 'subtotal' ) ), array_values( wp_list_pluck( $this->items, 'subtotal_tax' ) ) ) );
						$discount         = ( $this->add_precision( $coupon->get_amount() ) * $discount_percent ) / $item->quantity;
					}
				}

				$discount_amount = min( $price_to_discount, $discount );
				$price           = max( $price - $discount_amount, 0 );

				// Store how much each coupon discounts @todo
				$total_discount = $discount_amount * $item->quantity;

				if ( wc_tax_enabled() ) {
					$tax_rates          = WC_Tax::get_rates( $item->product->get_tax_class() );
					$taxes              = WC_Tax::calc_tax( $discount_amount, $tax_rates, $item->price_includes_tax );
					$total_discount_tax = WC_Tax::get_tax_total( $taxes ) * $item->quantity;
					$total_discount     = $item->price_includes_tax ? $total_discount - $total_discount_tax : $total_discount;
					$item->discount_tax_amounts[ $code ] = $total_discount_tax;
				}

				$item->discount_amounts[ $code ] = $total_discount;

				// If the price is 0, we can stop going through coupons because there is nothing more to discount for this product.
				if ( 0 >= $price ) {
					break;
				}
			}
		}

		return $price;
	}

	/**
	 * Apply coupon based discounts to an item.
	 * @param  object $item
	 * @return object
	 */
	private function apply_item_discount( $item ) {
		$item->discounted_price = $this->get_discounted_price( $item );
		return $item;
	}

	/**
	 * Totals are costs after discounts.
	 */
	private function calculate_item_totals() {
		foreach ( $this->items as $item ) {
			$item = $this->apply_item_discount( $item );
			$item->total = $item->discounted_price * $item->quantity;
			$item->tax   = 0;

			if ( wc_tax_enabled() && $item->product->is_taxable() ) {
				$item->tax_data = WC_Tax::calc_tax( $item->total, $this->get_item_tax_rates( $item ), $item->price_includes_tax );
				$item->tax      = array_sum( $item->tax_data );

				if ( $item->price_includes_tax ) {
					$item->total = $item->total - $item->tax;
				} else {
					$item->total = $item->total;
				}
			}
		}
	}
}
