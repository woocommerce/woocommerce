<?php
/**
 * Order/cart totals calculation class.
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
 * WC_Totals class.
 *
 * @todo woocommerce_tax_round_at_subtotal option - how should we handle this with precision?
 * @todo Manual discounts.
 * @since   3.2.0
 */
abstract class WC_Totals {

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
	 * Precision so we can work in cents.
	 *
	 * @since 3.2.0
	 * @var int
	 */
	protected $precision = 1;

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
	 * @param object $object Cart or order object to calculate totals for.
	 */
	public function __construct( &$object = null ) {
		$this->precision = pow( 10, wc_get_price_decimals() );
		$this->object    = $object;
	}

	/**
	 * Handles a cart or order object passed in for calculation. Normalises data
	 * into the same format for use by this class.
	 *
	 * @since 3.2.0
	 */
	protected function set_items() {
		$this->items = array();
	}

	/**
	 * Get and normalise fees.
	 *
	 * @since  3.2.0
	 */
	protected function set_fees() {
		$this->fees = array();
	}

	/**
	 * Get shipping methods and normalise.
	 *
	 * @since  3.2.0
	 */
	protected function set_shipping() {
		$this->shipping = array();
	}

	/**
	 * Set array of coupon objects from the cart or an order.
	 *
	 * @since 3.2.0
	 */
	protected function set_coupons() {
		$this->coupons = array();
	}

	/**
	 * Add precision (deep) to a price.
	 *
	 * @since  3.2.0
	 * @param  int|array $value Value to remove precision from.
	 * @return float
	 */
	protected function add_precision( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $subvalue ) {
				$value[ $key ] = $this->add_precision( $subvalue );
			}
		} else {
			$value = $value * $this->precision;
		}
		return $value;
	}

	/**
	 * Remove precision (deep) from a price.
	 *
	 * @since  3.2.0
	 * @param  int|array $value Value to remove precision from.
	 * @return float
	 */
	protected function remove_precision( $value ) {
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
	protected function get_default_item_props() {
		return (object) array(
			'key'                => '',
			'object'             => null,
			'quantity'           => 0,
			'price'              => 0,
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
	protected function get_discounted_price_in_cents( $item ) {
		return $item->price - $this->discount_totals[ $item->key ];
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
		return $in_cents ? $this->totals : array_map( array( $this, 'remove_precision' ), $this->totals );
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
	 * Calculate item totals.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_item_totals() {
		$this->set_items();
		$this->calculate_item_subtotals();
		$this->calculate_discounts();

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

			$item->subtotal     = $item->price;
			$item->subtotal_tax = 0;

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
	}
}
