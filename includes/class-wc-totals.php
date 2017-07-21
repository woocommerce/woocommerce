<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order/cart totals calculation class.
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 * @version 3.2.0
 * @since   3.2.0
 */
class WC_Totals {

	/**
	 * Line items to calculate.
	 *
	 * @var array
	 */
	protected $items          = array();

	/**
	 * Coupons to calculate.
	 *
	 * @var array
	 */
	protected $coupons        = array();

	/**
	 * Fees to calculate.
	 *
	 * @var array
	 */
	protected $fees           = array();

	/**
	 * Shipping to calculate.
	 *
	 * @var array
	 */
	protected $shipping_lines = array();

	/**
	 * Stores totals.
	 *
	 * @var array
	 */
	protected $totals         = null;

	/**
	 * Precision so we can work in cents.
	 *
	 * @var int
	 */
	protected $precision = 1;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->precision = pow( 10, wc_get_price_decimals() );
		$this->set_totals( $this->get_default_totals() );
	}

	/**
	 * Remove precision from a price.
	 *
	 * @param  int $value
	 * @return float
	 */
	protected function remove_precision( $value ) {
		return wc_format_decimal( $value / $this->precision, wc_get_price_decimals() );
	}

	/**
	 * Get default totals.
	 * @return array
	 */
	protected function get_default_totals() {
		return array(
			'fees_total'         => 0,
			'fees_total_tax'     => 0,
			'items_subtotal'     => 0,
			'items_subtotal_tax' => 0,
			'items_total'        => 0,
			'items_total_tax'    => 0,
			'item_totals'        => array(),
			'total'              => 0,
			'taxes'              => array(),
			'tax_total'          => 0,
			'shipping_total'     => 0,
			'shipping_tax_total' => 0,
		);
	}

	/**
	 * Get default blank set of props used per item.
	 * @return array
	 */
	protected function get_default_item_props() {
		return (object) array(
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
	 * Get default blank set of props used per coupon.
	 * @return array
	 */
	protected function get_default_coupon_props() {
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
	protected function get_default_fee_props() {
		return (object) array(
			'total_tax' => 0,
			'taxes'     => array(),
		);
	}

	/**
	 * Get default blank set of props used per shipping row.
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
	 * Sort items by the subtotal.
	 *
	 * @param  object $a
	 * @param  object $b
	 * @return int
	 */
	protected function sort_items_callback( $a, $b ) {
		$b->subtotal = isset( $a->subtotal ) ? $a->subtotal : 0;
		$b->subtotal = isset( $b->subtotal ) ? $b->subtotal : 0;
		return $b->subtotal === $b->subtotal ? 0 : ( $b->subtotal < $b->subtotal ? 1 : -1 );
	}

	/**
	 * Only ran if woocommerce_adjust_non_base_location_prices is true.
	 *
	 * If the customer is outside of the base location, this removes the base
	 * taxes. This is off by default unless the filter is used.
	 */
	protected function adjust_non_base_location_price( $item ) {
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

	/*
	|--------------------------------------------------------------------------
	| Calculation methods.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Run all calculations methods on the given items.
	 */
	public function calculate() {
		$this->calculate_item_subtotals();
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
	 */
	protected function calculate_item_subtotals() {
		foreach ( $this->items as $item ) {
			$item->subtotal     = $item->price * $item->quantity;
			$item->subtotal_tax = 0;

			if ( $item->price_includes_tax && apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
				$item           = $this->adjust_non_base_location_price( $item );
				$item->subtotal = $item->price * $item->quantity;
			}

			if ( $this->wc_tax_enabled() && $item->product->is_taxable() ) {
				$item->subtotal_taxes = WC_Tax::calc_tax( $item->subtotal, $this->get_item_tax_rates( $item ), $item->price_includes_tax );
				$item->subtotal_tax      = array_sum( $item->subtotal_taxes );

				if ( $item->price_includes_tax ) {
					$item->subtotal = $item->subtotal - $item->subtotal_tax;
				}
			}
		}
		uasort( $this->items, array( $this, 'sort_items_callback' ) );
		$this->set_items_subtotal( array_sum( array_values( wp_list_pluck( $this->items, 'subtotal' ) ) ) );
		$this->set_items_subtotal_tax( array_sum( array_values( wp_list_pluck( $this->items, 'subtotal_tax' ) ) ) );
	}

	/**
	 * Totals are costs after discounts.
	 */
	public function calculate_item_totals() {
		foreach ( $this->items as $item ) {



			// ! @todo
			//$item->discounted_price = $this->get_discounted_price( $item );






			$item->total            = $item->discounted_price * $item->quantity;
			$item->total_tax        = 0;

			if ( $this->wc_tax_enabled() && $item->product->is_taxable() ) {
				$item->taxes     = WC_Tax::calc_tax( $item->total, $this->get_item_tax_rates( $item ), $item->price_includes_tax );
				$item->total_tax = array_sum( $item->taxes );

				if ( $item->price_includes_tax ) {
					$item->total = $item->total - $item->total_tax;
				} else {
					$item->total = $item->total;
				}
			}
		}
		$this->set_items_total( array_sum( array_values( wp_list_pluck( $this->items, 'total' ) ) ) );
		$this->set_items_total_tax( array_sum( array_values( wp_list_pluck( $this->items, 'total_tax' ) ) ) );
		//$this->set_coupon_totals( wp_list_pluck( $this->coupons, 'total' ) );
		//$this->set_coupon_tax_totals( wp_list_pluck( $this->coupons, 'total_tax' ) );
		//$this->set_coupon_counts( wp_list_pluck( $this->coupons, 'count' ) );
		//$this->set_item_totals( $this->items );
	}

	/**
	 * Calculate any fees taxes.
	 */
	protected function calculate_fee_totals() {
		if ( ! empty( $this->fees ) ) {
			foreach ( $this->fees as $fee_key => $fee ) {
				if ( $this->wc_tax_enabled() && $fee->taxable ) {
					$fee->taxes     = WC_Tax::calc_tax( $fee->total, $tax_rates, false );
					$fee->total_tax = array_sum( $fee->taxes );
				}
			}
		}
		$this->set_fees_total( array_sum( array_values( wp_list_pluck( $this->fees, 'total' ) ) ) );
		$this->set_fees_total_tax( array_sum( array_values( wp_list_pluck( $this->fees, 'total_tax' ) ) ) );
	}

	/**
	 * Calculate any shipping taxes.
	 */
	protected function calculate_shipping_totals() {

	}

	/**
	 * Main cart totals.
	 */
	public function calculate_totals() {

		$this->set_shipping_total( array_sum( array_values( wp_list_pluck( $this->shipping_lines, 'total' ) ) ) );
		$this->set_taxes( $this->get_merged_taxes() );

		// Total up/round taxes and shipping taxes
		if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
			$this->set_tax_total( WC_Tax::get_tax_total( wc_list_pluck( $this->get_taxes(), 'get_tax_total' ) ) );
			$this->set_shipping_tax_total( WC_Tax::get_tax_total( wc_list_pluck( $this->get_taxes(), 'get_shipping_tax_total' ) ) );
		} else {
			$this->set_tax_total( array_sum( wc_list_pluck( $this->get_taxes(), 'get_tax_total' ) ) );
			$this->set_shipping_tax_total( array_sum( wc_list_pluck( $this->get_taxes(), 'get_shipping_tax_total' ) ) );
		}

		// Allow plugins to hook and alter totals before final total is calculated
		do_action( 'woocommerce_calculate_totals', WC()->cart );

		// Grand Total - Discounted product prices, discounted tax, shipping cost + tax
		$this->set_total( apply_filters( 'woocommerce_calculated_total', round( $this->get_items_total() + $this->get_fees_total() + $this->get_shipping_total() + $this->get_tax_total() + $this->get_shipping_tax_total(), wc_get_price_decimals() ), WC()->cart ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set all totals.
	 * @param array $value
	 */
	public function set_totals( $value ) {
		$value = wp_parse_args( $value, $this->get_default_totals() );
		$this->totals = $value;
	}

	/**
	 * Set cart/order items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param array $raw_items List of raw cart or order items.
	 */
	public function set_items( $raw_items ) {
		$this->items           = array();

		if ( ! empty( $raw_items ) && is_array( $raw_items ) ) {
			foreach ( $raw_items as $raw_item ) {
				$item = (object) array(
					'price'    => 0, // Line price without discounts, in cents.
					'quantity' => 0, // Line qty.
					'product'  => false,
				);
				if ( is_a( $raw_item, 'WC_Cart_Item' ) ) {
					//$item->quantity   = $raw_item->get_quantity();
					//$item->price      = $raw_item->get_price() * $raw_item->get_quantity();
					//$item->is_taxable = $raw_item->is_taxable();
					//$item->tax_class  = $raw_item->get_tax_class();
					// @todo
				} elseif ( is_a( $raw_item, 'WC_Order_Item_Product' ) ) {
					$item->key      = $raw_item->get_id();
					$item->quantity = $raw_item->get_quantity();
					$item->price    = $raw_item->get_subtotal() * $this->precision;
					$item->product  = $raw_item->get_product();
				} else {
					$item->key      = $raw_item['key'];
					$item->quantity = $raw_item['quantity'];
					$item->price    = $raw_item['data']->get_price() * $this->precision * $raw_item['quantity'];
					$item->product  = $raw_item['data'];
				}
				$this->items[ $item->key ]     = $item;
			}
		}
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
	}

	/**
	 * Set fees.
	 * @param array $fees
	 */
	public function set_fees( $fees ) {
		foreach ( $fees as $fee_key => $fee_object ) {
			$fee                    = $this->get_default_fee_props();
			$fee->total             = $fee_object->amount;
			$fee->taxable           = $fee_object->taxable;
			$fee->tax_class         = $fee_object->tax_class;
			$this->fees[ $fee_key ] = $fee;
		}
	}

	/**
	 * Set shipping lines.
	 * @param array
	 */
	public function set_shipping( $shipping_objects ) {
		$this->shipping_lines = array();

		if ( is_array( $shipping_objects ) ) {
			foreach ( $shipping_objects as $key => $shipping_object ) {
				$shipping                     = $this->get_default_shipping_props();
				$shipping->total              = $shipping_object->cost;
				$shipping->taxes              = $shipping_object->taxes;
				$shipping->total_tax          = array_sum( $shipping_object->taxes );
				$this->shipping_lines[ $key ] = $shipping;
			}
		}
	}

	/**
	 * Set taxes.
	 * @param array $value
	 */
	protected function set_taxes( $value ) {
		$this->totals['taxes'] = $value;
	}

	/**
	 * Set tax total.
	 * @param float $value
	 */
	protected function set_tax_total( $value ) {
		$this->totals['tax_total'] = $value;
	}

	/**
	 * Set shipping total.
	 * @param float $value
	 */
	protected function set_shipping_total( $value ) {
		$this->totals['shipping_total'] = $value;
	}

	/**
	 * Set shipping tax total.
	 * @param float $value
	 */
	protected function set_shipping_tax_total( $value ) {
		$this->totals['shipping_tax_total'] = $value;
	}

	/**
	 * Set item totals.
	 * @param array $value
	 */
	protected function set_item_totals( $value ) {
		$this->totals['item_totals'] = $value;
	}

	/**
	 * Set items subtotal.
	 * @param float $value
	 */
	protected function set_items_subtotal( $value ) {
		$this->totals['items_subtotal'] = $value;
	}

	/**
	 * Set items subtotal tax.
	 * @param float $value
	 */
	protected function set_items_subtotal_tax( $value ) {
		$this->totals['items_subtotal_tax'] = $value;
	}

	/**
	 * Set items total.
	 * @param float $value
	 */
	protected function set_items_total( $value ) {
		$this->totals['items_total'] = $value;
	}

	/**
	 * Set items total tax.
	 * @param float $value
	 */
	protected function set_items_total_tax( $value ) {
		$this->totals['items_total_tax'] = $value;
	}

	/**
	 * Set fees total.
	 * @param float $value
	 */
	protected function set_fees_total( $value ) {
		$this->totals['fees_total'] = $value;
	}

	/**
	 * Set fees total tax.
	 * @param float $value
	 */
	protected function set_fees_total_tax( $value ) {
		$this->totals['fees_total_tax'] = $value;
	}

	/**
	 * Set total.
	 * @param float $value
	 */
	protected function set_total( $value ) {
		$this->totals['total'] = max( 0, $value );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get all totals.
	 * @return array.
	 */
	public function get_totals() {
		return $this->totals;
	}

	/**
	 * Get shipping and item taxes.
	 * @return array
	 */
	public function get_taxes() {
		return $this->totals['taxes'];
	}

	/**
	 * Get tax total.
	 * @return float
	 */
	public function get_tax_total() {
		return $this->totals['tax_total'];
	}

	/**
	 * Get shipping total.
	 * @return float
	 */
	public function get_shipping_total() {
		return $this->totals['shipping_total'];
	}

	/**
	 * Get shipping tax total.
	 * @return float
	 */
	public function get_shipping_tax_total() {
		return $this->totals['shipping_tax_total'];
	}

	/**
	 * Get the items subtotal.
	 * @return float
	 */
	public function get_items_subtotal() {
		return $this->totals['items_subtotal'];
	}

	/**
	 * Get the items subtotal tax.
	 * @return float
	 */
	public function get_items_subtotal_tax() {
		return $this->totals['items_subtotal_tax'];
	}

	/**
	 * Get the items total.
	 * @return float
	 */
	public function get_items_total() {
		return $this->totals['items_total'];
	}

	/**
	 * Get the items total tax.
	 * @return float
	 */
	public function get_items_total_tax() {
		return $this->totals['items_total_tax'];
	}

	/**
	 * Get the total fees amount.
	 * @return float
	 */
	public function get_fees_total() {
		return $this->totals['fees_total'];
	}

	/**
	 * Get the total fee tax amount.
	 * @return float
	 */
	public function get_fees_total_tax() {
		return $this->totals['fees_total_tax'];
	}

	/**
	 * Get the total.
	 * @return float
	 */
	public function get_total() {
		return $this->totals['total'];
	}

	/**
	 * Returns an array of item totals.
	 * @return array
	 */
	public function get_item_totals() {
		return $this->totals['item_totals'];
	}

	/**
	 * Get tax rates for an item. Caches rates in class to avoid multiple look ups.
	 * @param  object $item
	 * @return array of taxes
	 */
	protected function get_item_tax_rates( $item ) {
		$tax_class = $item->product->get_tax_class();
		return isset( $this->item_tax_rates[ $tax_class ] ) ? $this->item_tax_rates[ $tax_class ] : $this->item_tax_rates[ $tax_class ] = WC_Tax::get_rates( $item->product->get_tax_class() );
	}

	/**
	 * Get all tax rows for a set of items and shipping methods.
	 * @return array
	 */
	protected function get_merged_taxes() {
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
}
