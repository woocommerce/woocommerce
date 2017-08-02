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
 * @since 3.2.0
 */
final class WC_Cart_Totals {

	/**
	 * Reference to cart object.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $object;

	/**
	 * Reference to customer object.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $customer;

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
	 * Should taxes be calculated?
	 *
	 * @var boolean
	 */
	protected $calculate_tax = true;

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
		if ( is_a( $cart, 'WC_Cart' ) ) {
			$this->object        = $cart;
			$this->calculate_tax = wc_tax_enabled() && ! $cart->get_customer()->get_is_vat_exempt();
			$this->calculate();
		}
	}

	/**
	 * Run all calculations methods on the given items in sequence.
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
	 * Should we round at subtotal level only?
	 *
	 * @return bool
	 */
	protected function round_at_subtotal() {
		return 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
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
			$item->tax_rates               = $this->get_item_tax_rates( $item );
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

			if ( $this->calculate_tax && $fee->object->taxable ) {
				$fee->taxes     = WC_Tax::calc_tax( $fee->total, WC_Tax::get_rates( $fee->object->tax_class, $this->object->get_customer() ), false );
				$fee->total_tax = array_sum( $fee->taxes );

				if ( ! $this->round_at_subtotal() ) {
					$fee->total_tax = wc_round_tax_total( $fee->total_tax, 0 );
				}
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

			if ( ! $this->round_at_subtotal() ) {
				$shipping_line->total_tax = wc_round_tax_total( $shipping_line->total_tax, 0 );
			}

			$this->shipping[ $key ] = $shipping_line;
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

		if ( $item->tax_rates !== $base_tax_rates ) {
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
		$item = $this->items[ $item_key ];
		$price = $item->subtotal - $this->discount_totals[ $item_key ];
		if ( $item->price_includes_tax ) {
			$price += $item->subtotal_tax;
		}
		return $price;
	}

	/**
	 * Get tax rates for an item. Caches rates in class to avoid multiple look ups.
	 *
	 * @param  object $item Item to get tax rates for.
	 * @return array of taxes
	 */
	protected function get_item_tax_rates( $item ) {
		$tax_class = $item->product->get_tax_class();
		return isset( $this->item_tax_rates[ $tax_class ] ) ? $this->item_tax_rates[ $tax_class ] : $this->item_tax_rates[ $tax_class ] = WC_Tax::get_rates( $item->product->get_tax_class(), $this->object->get_customer() );
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

			if ( has_filter( 'woocommerce_get_discounted_price' ) ) {
				/**
				 * Allow plugins to filter this price like in the legacy cart class.
				 *
				 * This is legacy and should probably be deprecated in the future.
				 * $item->object is the cart item object.
				 * $this->object is the cart object.
				 */
				$item->total = wc_add_number_precision(
					apply_filters( 'woocommerce_get_discounted_price', wc_remove_number_precision( $item->total ), $item->object, $this->object )
				);
			}

			if ( $this->calculate_tax && $item->product->is_taxable() ) {
				$item->taxes     = WC_Tax::calc_tax( $item->total, $item->tax_rates, $item->price_includes_tax );
				$item->total_tax = array_sum( $item->taxes );

				if ( ! $this->round_at_subtotal() ) {
					$item->total_tax = wc_round_tax_total( $item->total_tax, 0 );
				}

				if ( $item->price_includes_tax ) {
					$item->total = $item->total - $item->total_tax;
				} else {
					$item->total = $item->total;
				}
			}

			$this->object->cart_contents[ $item_key ]['line_total'] = wc_remove_number_precision( $item->total );
			$this->object->cart_contents[ $item_key ]['line_tax']   = wc_remove_number_precision( $item->total_tax );
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
		foreach ( $this->items as $item_key => $item ) {
			if ( $item->price_includes_tax && apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
				$item           = $this->adjust_non_base_location_price( $item );
			}
			if ( $this->calculate_tax && $item->product->is_taxable() ) {
				$subtotal_taxes     = WC_Tax::calc_tax( $item->subtotal, $item->tax_rates, $item->price_includes_tax );
				$item->subtotal_tax = array_sum( $subtotal_taxes );

				if ( ! $this->round_at_subtotal() ) {
					$item->subtotal_tax = wc_round_tax_total( $item->subtotal_tax, 0 );
				}

				if ( $item->price_includes_tax ) {
					$item->subtotal = $item->subtotal - $item->subtotal_tax;
				}
			}

			$this->object->cart_contents[ $item_key ]['line_subtotal']     = wc_remove_number_precision( $item->subtotal );
			$this->object->cart_contents[ $item_key ]['line_subtotal_tax'] = wc_remove_number_precision( $item->subtotal_tax );
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

		$discounts = new WC_Discounts( $this->object );

		foreach ( $this->coupons as $coupon ) {
			$discounts->apply_discount( $coupon );
		}

		$this->discount_totals                 = $discounts->get_discounts_by_item( true );
		$this->totals['discounts_total']       = ! empty( $this->discount_totals ) ? array_sum( $this->discount_totals ) : 0;
		$this->object->coupon_discount_amounts = $discounts->get_discounts_by_coupon();

		// See how much tax was 'discounted' per item and per coupon.
		if ( $this->calculate_tax ) {
			$coupon_discount_tax_amounts = array();
			$item_taxes                  = 0;

			foreach ( $discounts->get_discounts( true ) as $coupon_code => $coupon_discounts ) {
				$coupon_discount_tax_amounts[ $coupon_code ] = 0;

				foreach ( $coupon_discounts as $item_key => $item_discount ) {
					$item = $this->items[ $item_key ];

					if ( $item->product->is_taxable() ) {
						$item_tax                                     = array_sum( WC_Tax::calc_tax( $item_discount, $item->tax_rates, false ) );
						$item_taxes                                  += $item_tax;
						$coupon_discount_tax_amounts[ $coupon_code ] += $item_tax;
					}
				}
			}

			$this->totals['discounts_tax_total']       = $item_taxes;
			$this->object->coupon_discount_tax_amounts = $coupon_discount_tax_amounts;
		}
	}

	/**
	 * Return discounted tax amount for an item.
	 *
	 * @param  object $item
	 * @param  int $discount_amount
	 * @return int
	 */
	protected function get_item_discount_tax( $item, $discount_amount ) {
		if ( $item->product->is_taxable() ) {
			$taxes = WC_Tax::calc_tax( $discount_amount, $item->tax_rates, false );
			return array_sum( $taxes );
		}
		return 0;
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

		// Add totals to cart object.
		$this->object->taxes               = wp_list_pluck( $this->get_total( 'taxes' ), 'shipping_tax_total' );
		$this->object->shipping_taxes      = wp_list_pluck( $this->get_total( 'taxes' ), 'tax_total' );
		$this->object->cart_contents_total = $this->get_total( 'items_total' );
		$this->object->tax_total           = $this->get_total( 'tax_total' );
		$this->object->total               = $this->get_total( 'total' );
		$this->object->discount_cart       = $this->get_total( 'discounts_total' );
		$this->object->discount_cart_tax   = $this->get_total( 'discounts_tax_total' );

		// Allow plugins to hook and alter totals before final total is calculated.
		if ( has_action( 'woocommerce_calculate_totals' ) ) {
			do_action( 'woocommerce_calculate_totals', $this->object );
		}

		// Allow plugins to filter the grand total, and sum the cart totals in case of modifications.
		$totals_to_sum       = wc_add_number_precision_deep( array( $this->object->cart_contents_total, $this->object->tax_total, $this->object->shipping_tax_total, $this->object->shipping_total, $this->object->fee_total ) );
		$this->object->total = max( 0, apply_filters( 'woocommerce_calculated_total', wc_remove_number_precision( round( array_sum( $totals_to_sum ) ) ), $this->object ) );
	}
}
