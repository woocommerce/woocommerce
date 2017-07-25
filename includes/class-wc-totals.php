<?php
/**
 * Order/cart totals calculation class.
 *
 * Methods are private and class is final to keep this as an internal API.
 * May be opened in the future once structure is stable.
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Totals class.
 *
 * @todo consider extending this for cart vs orders if lots of conditonal logic is needed.
 * @todo Instead of setting cart totals from here, do it from a subclass.
 * @todo woocommerce_tax_round_at_subtotal option - how should we handle this with precision?
 * @todo woocommerce_calculate_totals action for carts.
 * @todo woocommerce_calculated_total filter for carts.
 * @since   3.2.0
 */
class WC_Totals {

	/**
	 * Reference to cart or order object.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	private $object;

	/**
	 * Line items to calculate.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	private $items = array();

	/**
	 * Fees to calculate.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	private $fees = array();

	/**
	 * Discount amounts in cents after calculation for the cart.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	private $discount_totals = array();

	/**
	 * Precision so we can work in cents.
	 *
	 * @since 3.2.0
	 * @var int
	 */
	private $precision = 1;

	/**
	 * Stores totals.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	private $totals = array(
		'fees_total'          => 0,
		'fees_total_tax'      => 0,
		'items_subtotal'      => 0,
		'items_subtotal_tax'  => 0,
		'items_total'         => 0,
		'items_total_tax'     => 0,
		'total'               => 0,
		'taxes'               => array(),
		'tax_total'           => 0,
		'shipping_total'      => 0,
		'shipping_tax_total'  => 0,
		'discounts_total'     => 0,
		'discounts_tax_total' => 0,
	);

	/**
	 * Sets up the items provided, and calculate totals.
	 *
	 * @since 3.2.0
	 * @param object $cart Cart or order object to calculate totals for.
	 */
	public function __construct( &$cart = null ) {
		$this->precision = pow( 10, wc_get_price_decimals() );
		$this->object    = $cart;
		$this->set_items();
		$this->calculate();
	}

	/**
	 * Handles a cart or order object passed in for calculation. Normalises data
	 * into the same format for use by this class.
	 *
	 * @since 3.2.0
	 */
	private function set_items() {
		if ( is_a( $this->object, 'WC_Cart' ) ) {
			foreach ( $this->object->get_cart() as $cart_item_key => $cart_item ) {
				$item                          = $this->get_default_item_props();
				$item->key                     = $cart_item_key;
				$item->quantity                = $cart_item['quantity'];
				$item->price                   = $cart_item['data']->get_price() * $this->precision * $cart_item['quantity'];
				$item->product                 = $cart_item['data'];
				$this->items[ $cart_item_key ] = $item;
			}
		}
	}

	/**
	 * Remove precision (deep) from a price.
	 *
	 * @since  3.2.0
	 * @param  int|array $value Value to remove precision from.
	 * @return float
	 */
	private function remove_precision( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $subvalue ) {
				$value[ $key ] = $this->remove_precision( $subvalue );
			}
		} else {
			$value = wc_format_decimal( $value / $this->precision, wc_get_price_decimals() );
		}
		return $value;
	}

	/**
	 * Get default blank set of props used per item.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	private function get_default_item_props() {
		return (object) array(
			'key'                => '',
			'quantity'           => 0,
			'price'              => 0,
			'product'            => false,
			'price_includes_tax' => wc_prices_include_tax(),
			'subtotal'           => 0,
			'subtotal_tax'       => 0,
			'subtotal_taxes'     => array(),
			'total'              => 0,
			'total_tax'          => 0,
			'taxes'              => array(),
			'discounted_price'   => 0,
		);
	}

	/**
	 * Get default blank set of props used per fee.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	private function get_default_fee_props() {
		return (object) array(
			'total_tax' => 0,
			'taxes'     => array(),
		);
	}

	/**
	 * Only ran if woocommerce_adjust_non_base_location_prices is true.
	 *
	 * If the customer is outside of the base location, this removes the base
	 * taxes. This is off by default unless the filter is used.
	 *
	 * @since 3.2.0
	 * @param object $item Item to adjust the prices of.
	 * @return object
	 */
	private function adjust_non_base_location_price( $item ) {
		$base_tax_rates = WC_Tax::get_base_tax_rates( $item->product->tax_class );
		$item_tax_rates = $this->get_item_tax_rates( $item );

		if ( $item_tax_rates !== $base_tax_rates ) {
			// Work out a new base price without the shop's base tax.
			$taxes                    = WC_Tax::calc_tax( $item->price, $base_tax_rates, true, true );

			// Now we have a new item price (excluding TAX).
			$item->price              = $item->price - array_sum( $taxes );
			$item->price_includes_tax = false;
		}
		return $item;
	}

	/**
	 * Get discounted price of an item with precision (in cents).
	 *
	 * @since  3.2.0
	 * @param  object $item Item to get the price of.
	 * @return int
	 */
	private function get_discounted_price_in_cents( $item ) {
		return $item->price - $this->discount_totals[ $item->key ];
	}

	/**
	 * Get tax rates for an item. Caches rates in class to avoid multiple look ups.
	 *
	 * @param  object $item Item to get tax rates for.
	 * @return array of taxes
	 */
	private function get_item_tax_rates( $item ) {
		$tax_class = $item->product->get_tax_class();
		return isset( $this->item_tax_rates[ $tax_class ] ) ? $this->item_tax_rates[ $tax_class ] : $this->item_tax_rates[ $tax_class ] = WC_Tax::get_rates( $item->product->get_tax_class() );
	}

	/**
	 * Return array of coupon objects from the cart or an order.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	private function get_coupons() {
		if ( is_a( $this->object, 'WC_Cart' ) ) {
			return $this->object->get_coupons();
		}
	}

	/**
	 * Return array of shipping costs.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	private function get_shipping() {
		// @todo get this somehow. Where does calc occur?
		return array();
	}

	/**
	 * Get discounts.
	 *
	 * @return array
	 */
	private function get_discounts() {
		// @todo fee style API for discounts in cart/checkout.
		return array();
	}

	/**
	 * Get a single total with or without precision (in cents).
	 *
	 * @since  3.2.0
	 * @param  string $key Total to get.
	 * @param  bool   $in_cents Should the totals be returned in cents, or without precision.
	 * @return int|float
	 */
	public function get_total( $key = 'total', $in_cents = false ) {
		$totals = $this->get_totals( $in_cents );
		return isset( $totals[ $key ] ) ? $totals[ $key ] : 0;
	}

	/**
	 * Set a single total.
	 *
	 * @since  3.2.0
	 * @param string $key Total name you want to set.
	 * @param int    $total Total to set.
	 */
	private function set_total( $key = 'total', $total ) {
		$this->totals[ $key ] = $total;
	}

	/**
	 * Get all totals with or without precision (in cents).
	 *
	 * @since  3.2.0
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array.
	 */
	public function get_totals( $in_cents = false ) {
		return $in_cents ? $this->totals : array_map( array( $this, 'remove_precision' ), $this->totals );
	}

	/**
	 * Get all tax rows from items (including shipping and product line items).
	 *
	 * @todo consider an item object instead of array here
	 *
	 * @since  3.2.0
	 * @return array
	 */
	private function get_merged_taxes() {
		$taxes = array();

		foreach ( array_merge( $this->items, $this->fees, $this->get_shipping() ) as $item ) {
			foreach ( $item->taxes as $rate_id => $rate ) {
				$taxes[ $rate_id ] = array( 'tax_total' => 0, 'shipping_tax_total' => 0 );
			}
		}

		foreach ( $this->items as $item ) {
			foreach ( $item->taxes as $rate_id => $rate ) {
				$taxes[ $rate_id ]['tax_total'] = $taxes[ $rate_id ]['tax_total'] + $rate;
			}
		}

		foreach ( $this->fees as $fee ) {
			foreach ( $fee->taxes as $rate_id => $rate ) {
				$taxes[ $rate_id ]['tax_total'] = $taxes[ $rate_id ]['tax_total'] + $rate;
			}
		}

		foreach ( $this->get_shipping() as $item ) {
			foreach ( $item->taxes as $rate_id => $rate ) {
				$taxes[ $rate_id ]['shipping_tax_total'] = $taxes[ $rate_id ]['shipping_tax_total'] + $rate;
			}
		}
		return $taxes;
	}

	/*
	|--------------------------------------------------------------------------
	| Calculation methods.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Run all calculations methods on the given items in sequence.
	 *
	 * @since 3.2.0
	 */
	private function calculate() {
		$this->calculate_item_subtotals();
		$this->calculate_discounts();
		$this->calculate_item_totals();
		$this->calculate_fee_totals();
		$this->calculate_shipping_totals();
		$this->calculate_totals();
	}

	/**
	 * Subtotals are costs before discounts.
	 *
	 * To prevent rounding issues we need to work with the inclusive price where possible.
	 * otherwise we'll see errors such as when working with a 9.99 inc price, 20% VAT which would.
	 * be 8.325 leading to totals being 1p off.
	 *
	 * Pre tax coupons come off the price the customer thinks they are paying - tax is calculated.
	 * afterwards.
	 *
	 * e.g. $100 bike with $10 coupon = customer pays $90 and tax worked backwards from that.
	 *
	 * @since 3.2.0
	 */
	private function calculate_item_subtotals() {
		foreach ( $this->items as $item ) {
			if ( $item->price_includes_tax && apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
				$item           = $this->adjust_non_base_location_price( $item );
			}

			$item->subtotal     = $item->price;
			$item->subtotal_tax = 0;

			if ( wc_tax_enabled() && $item->product->is_taxable() ) {
				$item->subtotal_taxes = WC_Tax::calc_tax( $item->subtotal, $this->get_item_tax_rates( $item ), $item->price_includes_tax );
				$item->subtotal_tax      = array_sum( $item->subtotal_taxes );

				if ( $item->price_includes_tax ) {
					$item->subtotal = $item->subtotal - $item->subtotal_tax;
				}
			}
		}
		$this->set_total( 'items_subtotal', array_sum( array_values( wp_list_pluck( $this->items, 'subtotal' ) ) ) );
		$this->set_total( 'items_subtotal_tax', array_sum( array_values( wp_list_pluck( $this->items, 'subtotal_tax' ) ) ) );
	}

	/**
	 * Calculate all discount and coupon amounts.
	 *
	 * @todo Manual discounts.
	 *
	 * @since 3.2.0
	 * @uses  WC_Discounts class.
	 */
	private function calculate_discounts() {
		$discounts = new WC_Discounts( $this->items );

		foreach ( $this->get_coupons() as $coupon ) {
			$discounts->apply_coupon( $coupon );
		}

		$this->discount_totals           = $discounts->get_discounts();
		$this->totals['discounts_total'] = array_sum( $this->discount_totals );
		// @todo $this->totals['discounts_tax_total'] = $value;

		/*$this->set_coupon_totals( wp_list_pluck( $this->coupons, 'total' ) );
		//$this->set_coupon_tax_totals( wp_list_pluck( $this->coupons, 'total_tax' ) );
		//$this->set_coupon_counts( wp_list_pluck( $this->coupons, 'count' ) );*/
	}

	/**
	 * Totals are costs after discounts. @todo move cart specific setters to subclass?
	 *
	 * @since 3.2.0
	 */
	private function calculate_item_totals() {
		foreach ( $this->items as $item ) {
			$item->total     = $this->get_discounted_price_in_cents( $item );
			$item->total_tax = 0;

			if ( wc_tax_enabled() && $item->product->is_taxable() ) {
				$item->taxes     = WC_Tax::calc_tax( $item->total, $this->get_item_tax_rates( $item ), $item->price_includes_tax );
				$item->total_tax = array_sum( $item->taxes );

				if ( $item->price_includes_tax ) {
					$item->total = $item->total - $item->total_tax;
				} else {
					$item->total = $item->total;
				}
			}
		}
		$this->set_total( 'items_total', array_sum( array_values( wp_list_pluck( $this->items, 'total' ) ) ) );
		$this->set_total( 'items_total_tax', array_sum( array_values( wp_list_pluck( $this->items, 'total_tax' ) ) ) );

		$this->object->subtotal = array_sum( wp_list_pluck( $this->items, 'total' ) ) + array_sum( wp_list_pluck( $this->items, 'total_tax' ) );
		$this->object->subtotal_ex_tax = array_sum( wp_list_pluck( $this->items, 'total' ) );
	}

	/**
	 * Triggers the cart fees API, grabs the list of fees, and calculates taxes.
	 *
	 * Note: This class sets the totals for the 'object' as they are calculated. This is so that APIs like the fees API can see these totals if needed.
	 *
	 * @since 3.2.0
	 * @todo logic is unqiue to carts.
	 */
	private function calculate_fee_totals() {
		$this->object->calculate_fees();

		foreach ( $this->object->get_fees() as $fee_key => $fee_object ) {
			$fee         = $this->get_default_fee_props();
			$fee->object = $fee_object;
			$fee->total  = $fee->object->amount * $this->precision;

			if ( wc_tax_enabled() && $fee->object->taxable ) {
				$fee->taxes     = WC_Tax::calc_tax( $fee->total, WC_Tax::get_rates( $fee->object->tax_class ), false );
				$fee->total_tax = array_sum( $fee->taxes );
			}

			$this->fees[ $fee_key ] = $fee;
		}

		// Store totals to self.
		$this->set_total( 'fees_total', array_sum( wp_list_pluck( $this->fees, 'total' ) ) );
		$this->set_total( 'fees_total_tax', array_sum( wp_list_pluck( $this->fees, 'total_tax' ) ) );

		// Transfer totals to the cart.
		foreach ( $this->fees as $fee_key => $fee ) {
			$this->object->fees[ $fee_key ]->tax      = $this->remove_precision( $fee->total_tax );
			$this->object->fees[ $fee_key ]->tax_data = $this->remove_precision( $fee->taxes );
		}
		$this->object->fee_total = $this->remove_precision( array_sum( wp_list_pluck( $this->fees, 'total' ) ) );
	}

	/**
	 * Calculate any shipping taxes.
	 *
	 * @since 3.2.0
	 */
	private function calculate_shipping_totals() {
		//$this->set_shipping_total( array_sum( array_values( wp_list_pluck( $this->shipping, 'total' ) ) ) );
	}

	/**
	 * Main cart totals.
	 *
	 * @since 3.2.0
	 */
	private function calculate_totals() {
		$this->set_total( 'taxes', $this->get_merged_taxes() );
		$this->set_total( 'tax_total', array_sum( wp_list_pluck( $this->get_total( 'taxes', true ), 'tax_total' ) ) );
		$this->set_total( 'shipping_tax_total', array_sum( wp_list_pluck( $this->get_total( 'taxes', true ), 'shipping_tax_total' ) ) );
		$this->set_total( 'total', round( $this->get_total( 'items_total', true ) + $this->get_total( 'fees_total', true ) + $this->get_total( 'shipping_total', true ) + $this->get_total( 'tax_total', true ) + $this->get_total( 'shipping_tax_total', true ) ) );
	}
}
