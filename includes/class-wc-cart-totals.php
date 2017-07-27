<?php
/**
 * Cart totals calculation class.
 *
 * Methods are protected and class is final to keep this as an internal API.
 * May be opened in the future once structure is stable.
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Cart_Totals class.
 *
 * @todo woocommerce_calculate_totals action for carts.
 * @todo woocommerce_calculated_total filter for carts.
 * @todo record coupon totals and counts for cart.
 * @todo woocommerce_tax_round_at_subtotal option - how should we handle this with precision?
 * @todo Manual discounts.
 *
 * @since 3.2.0
 */
final class WC_Cart_Totals {

	/**
	 * Reference to cart or order object.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $object;

	/**
	 * Line items to calculate.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $items = array();

	/**
	 * Fees to calculate.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $fees = array();

	/**
	 * Shipping costs.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $shipping = array();

	/**
	 * Applied coupon objects.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $coupons = array();

	/**
	 * Discount amounts in cents after calculation for the cart.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $discount_totals = array();

	/**
	 * Stores totals.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $totals = array(
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
	 * @param object $cart Cart object to calculate totals for.
	 */
	public function __construct( &$cart = null ) {
		$this->object = $cart;

		if ( is_a( $cart, 'WC_Cart' ) ) {
			$this->calculate();
		}
	}

	/**
	 * Run all calculations methods on the given items in sequence. @todo More documentation, and add other calculation methods for taxes and totals only?
	 *
	 * @since 3.2.0
	 */
	protected function calculate() {
		$this->calculate_item_totals();
		$this->calculate_fee_totals();
		$this->calculate_shipping_totals();
		$this->calculate_totals();
	}

	/**
	 * Get default blank set of props used per item.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	protected function get_default_item_props() {
		return (object) array(
			'object'             => null,
			'quantity'           => 0,
			'product'            => false,
			'price_includes_tax' => false,
			'subtotal'           => 0,
			'subtotal_tax'       => 0,
			'total'              => 0,
			'total_tax'          => 0,
			'taxes'              => array(),
		);
	}

	/**
	 * Get default blank set of props used per fee.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	protected function get_default_fee_props() {
		return (object) array(
			'total_tax' => 0,
			'taxes'     => array(),
		);
	}

	/**
	 * Get default blank set of props used per shipping row.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	protected function get_default_shipping_props() {
		return (object) array(
			'total'     => 0,
			'total_tax' => 0,
			'taxes'     => array(),
		);
	}

	/**
	 * Handles a cart or order object passed in for calculation. Normalises data
	 * into the same format for use by this class.
	 *
	 * Each item is made up of the following props, in addition to those returned by get_default_item_props() for totals.
	 * 	- key: An identifier for the item (cart item key or line item ID).
	 *  - cart_item: For carts, the cart item from the cart which may include custom data.
	 *  - quantity: The qty for this line.
	 *  - price: The line price in cents.
	 *  - product: The product object this cart item is for.
	 *
	 * @since 3.2.0
	 */
	protected function set_items() {
		$this->items = array();

		foreach ( $this->object->get_cart() as $cart_item_key => $cart_item ) {
			$item                          = $this->get_default_item_props();
			$item->object                  = $cart_item;
			$item->price_includes_tax      = wc_prices_include_tax();
			$item->quantity                = $cart_item['quantity'];
			$item->subtotal                = wc_add_number_precision_deep( $cart_item['data']->get_price() ) * $cart_item['quantity'];
			$item->product                 = $cart_item['data'];
			$this->items[ $cart_item_key ] = $item;
		}
	}

	/**
	 * Get fee objects from the cart. Normalises data
	 * into the same format for use by this class.
	 *
	 * @since 3.2.0
	 */
	protected function set_fees() {
		$this->fees = array();
		$this->object->calculate_fees();

		foreach ( $this->object->get_fees() as $fee_key => $fee_object ) {
			$fee         = $this->get_default_fee_props();
			$fee->object = $fee_object;
			$fee->total  = wc_add_number_precision_deep( $fee->object->amount );

			if ( wc_tax_enabled() && $fee->object->taxable ) {
				$fee->taxes     = WC_Tax::calc_tax( $fee->total, WC_Tax::get_rates( $fee->object->tax_class ), false );
				$fee->total_tax = array_sum( $fee->taxes );
			}

			$this->fees[ $fee_key ] = $fee;
		}
	}

	/**
	 * Get shipping methods from the cart and normalise.
	 *
	 * @since 3.2.0
	 */
	protected function set_shipping() {
		$this->shipping = array();

		foreach ( $this->object->calculate_shipping() as $key => $shipping_object ) {
			$shipping_line            = $this->get_default_shipping_props();
			$shipping_line->object    = $shipping_object;
			$shipping_line->total     = wc_add_number_precision_deep( $shipping_object->cost );
			$shipping_line->taxes     = wc_add_number_precision_deep( $shipping_object->taxes );
			$shipping_line->total_tax = wc_add_number_precision_deep( array_sum( $shipping_object->taxes ) );
			$this->shipping[ $key ]   = $shipping_line;
		}
	}

	/**
	 * Return array of coupon objects from the cart. Normalises data
	 * into the same format for use by this class.
	 *
	 * @since  3.2.0
	 */
	protected function set_coupons() {
		$this->coupons = $this->object->get_coupons();
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
	protected function adjust_non_base_location_price( $item ) {
		$base_tax_rates = WC_Tax::get_base_tax_rates( $item->product->tax_class );
		$item_tax_rates = $this->get_item_tax_rates( $item );

		if ( $item_tax_rates !== $base_tax_rates ) {
			// Work out a new base price without the shop's base tax.
			$taxes                    = WC_Tax::calc_tax( $item->subtotal, $base_tax_rates, true, true );

			// Now we have a new item price (excluding TAX).
			$item->subtotal           = $item->subtotal - array_sum( $taxes );
			$item->price_includes_tax = false;
		}
		return $item;
	}

	/**
	 * Get discounted price of an item with precision (in cents).
	 *
	 * @since  3.2.0
	 * @param  object $item_key Item to get the price of.
	 * @return int
	 */
	protected function get_discounted_price_in_cents( $item_key ) {
		return $this->items[ $item_key ]->subtotal - $this->discount_totals[ $item_key ];
	}

	/**
	 * Get tax rates for an item. Caches rates in class to avoid multiple look ups.
	 *
	 * @param  object $item Item to get tax rates for.
	 * @return array of taxes
	 */
	protected function get_item_tax_rates( $item ) {
		$tax_class = $item->product->get_tax_class();
		return isset( $this->item_tax_rates[ $tax_class ] ) ? $this->item_tax_rates[ $tax_class ] : $this->item_tax_rates[ $tax_class ] = WC_Tax::get_rates( $item->product->get_tax_class() );
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
	protected function set_total( $key = 'total', $total ) {
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
		return $in_cents ? $this->totals : wc_remove_number_precision_deep( $this->totals );
	}

	/**
	 * Get all tax rows from items (including shipping and product line items).
	 *
	 * @since  3.2.0
	 * @return array
	 */
	protected function get_merged_taxes() {
		$taxes = array();

		foreach ( array_merge( $this->items, $this->fees, $this->shipping ) as $item ) {
			foreach ( $item->taxes as $rate_id => $rate ) {
				$taxes[ $rate_id ] = array( 'tax_total' => 0, 'shipping_tax_total' => 0 );
			}
		}

		foreach ( $this->items + $this->fees as $item ) {
			foreach ( $item->taxes as $rate_id => $rate ) {
				$taxes[ $rate_id ]['tax_total'] = $taxes[ $rate_id ]['tax_total'] + $rate;
			}
		}

		foreach ( $this->shipping as $item ) {
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
	 * Calculate item totals.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_item_totals() {
		$this->set_items();
		$this->calculate_item_subtotals();
		$this->calculate_discounts();

		foreach ( $this->items as $item_key => $item ) {
			$item->total     = $this->get_discounted_price_in_cents( $item_key );
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

		$this->object->subtotal        = $this->get_total( 'items_total' ) + $this->get_total( 'items_total_tax' );
		$this->object->subtotal_ex_tax = $this->get_total( 'items_total' );
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
	protected function calculate_item_subtotals() {
		foreach ( $this->items as $item ) {
			if ( $item->price_includes_tax && apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
				$item           = $this->adjust_non_base_location_price( $item );
			}
			if ( wc_tax_enabled() && $item->product->is_taxable() ) {
				$subtotal_taxes     = WC_Tax::calc_tax( $item->subtotal, $this->get_item_tax_rates( $item ), $item->price_includes_tax );
				$item->subtotal_tax = array_sum( $subtotal_taxes );

				if ( $item->price_includes_tax ) {
					$item->subtotal = $item->subtotal - $item->subtotal_tax;
				}
			}
		}
		$this->set_total( 'items_subtotal', array_sum( array_values( wp_list_pluck( $this->items, 'subtotal' ) ) ) );
		$this->set_total( 'items_subtotal_tax', array_sum( array_values( wp_list_pluck( $this->items, 'subtotal_tax' ) ) ) );

		$this->object->subtotal        = $this->get_total( 'items_total' ) + $this->get_total( 'items_total_tax' );
		$this->object->subtotal_ex_tax = $this->get_total( 'items_total' );
	}

	/**
	 * Calculate all discount and coupon amounts.
	 *
	 * @since 3.2.0
	 * @uses  WC_Discounts class.
	 */
	protected function calculate_discounts() {
		$this->set_coupons();

		$discounts = new WC_Discounts( $this->items );

		foreach ( $this->coupons as $coupon ) {
			$discounts->apply_coupon( $coupon );
		}

		$this->discount_totals           = $discounts->get_discounts( true );
		$this->totals['discounts_total'] = array_sum( $this->discount_totals );

		// See how much tax was 'discounted'.
		if ( wc_tax_enabled() ) {
			foreach ( $this->discount_totals as $cart_item_key => $discount ) {
				$item = $this->items[ $cart_item_key ];
				if ( $item->product->is_taxable() ) {
					$tax_rates                            = $this->get_item_tax_rates( $item );
					$taxes                                = WC_Tax::calc_tax( $discount, $tax_rates, false );
					$this->totals['discounts_tax_total'] += array_sum( $taxes );
				}
			}
		}
	}

	/**
	 * Triggers the cart fees API, grabs the list of fees, and calculates taxes.
	 *
	 * Note: This class sets the totals for the 'object' as they are calculated. This is so that APIs like the fees API can see these totals if needed.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_fee_totals() {
		$this->set_fees();
		$this->set_total( 'fees_total', array_sum( wp_list_pluck( $this->fees, 'total' ) ) );
		$this->set_total( 'fees_total_tax', array_sum( wp_list_pluck( $this->fees, 'total_tax' ) ) );

		foreach ( $this->fees as $fee_key => $fee ) {
			$this->object->fees[ $fee_key ]->tax      = wc_remove_number_precision_deep( $fee->total_tax );
			$this->object->fees[ $fee_key ]->tax_data = wc_remove_number_precision_deep( $fee->taxes );
		}
		$this->object->fee_total = wc_remove_number_precision_deep( array_sum( wp_list_pluck( $this->fees, 'total' ) ) );
	}

	/**
	 * Calculate any shipping taxes.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_shipping_totals() {
		$this->set_shipping();
		$this->set_total( 'shipping_total', array_sum( wp_list_pluck( $this->shipping, 'total' ) ) );
		$this->set_total( 'shipping_tax_total', array_sum( wp_list_pluck( $this->shipping, 'total_tax' ) ) );

		$this->object->shipping_total     = $this->get_total( 'shipping_total' );
		$this->object->shipping_tax_total = $this->get_total( 'shipping_tax_total' );
	}

	/**
	 * Main cart totals.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_totals() {
		$this->set_total( 'taxes', $this->get_merged_taxes() );
		$this->set_total( 'tax_total', array_sum( wp_list_pluck( $this->get_total( 'taxes', true ), 'tax_total' ) ) );
		$this->set_total( 'total', round( $this->get_total( 'items_total', true ) + $this->get_total( 'fees_total', true ) + $this->get_total( 'shipping_total', true ) + $this->get_total( 'tax_total', true ) + $this->get_total( 'shipping_tax_total', true ) ) );

		$this->object->tax_total = $this->get_total( 'tax_total' );
		$this->object->total     = $this->get_total( 'total' );
	}
}
