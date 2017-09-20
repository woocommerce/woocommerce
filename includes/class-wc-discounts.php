<?php
/**
 * Discount calculation
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 * @version 3.2.0
 * @since   3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discounts class.
 */
class WC_Discounts {

	/**
	 * Reference to cart or order object.
	 *
	 * @since 3.2.0
	 * @var WC_Cart|WC_Order|array
	 */
	protected $object;

	/**
	 * An array of items to discount.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * An array of discounts which have been applied to items.
	 *
	 * @var array[] Code => Item Key => Value
	 */
	protected $discounts = array();

	/**
	 * An array of all the coupon objects that have been applied.
	 *
	 * @var WC_Coupon[] Code => Coupon
	 */
	protected $coupons = array();

	/**
	 * A variable that holds a running total of all the discounts when
	 * WC option 'woocommerce_calc_discounts_sequentially' is set to yes.
	 *
	 * @var int
	 */
	protected $discount_total = 0;

	/**
	 * Constructor.
	 *
	 * @param WC_Cart|WC_Order|array $object Cart or order object.
	 */
	public function __construct( $object = array() ) {
		$this->object = $object;

		if ( is_a( $this->object, 'WC_Cart' ) ) {
			$this->set_items_from_cart( $this->object );
		} elseif ( is_a( $this->object, 'WC_Order' ) ) {
			$this->set_items_from_order( $this->object );
		}
	}

	/**
	 * Normalise cart items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param WC_Cart $cart Cart object.
	 */
	public function set_items_from_cart( $cart ) {
		$this->items = $this->discounts = array();

		if ( ! is_a( $cart, 'WC_Cart' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $key => $cart_item ) {
			$item                = new stdClass();
			$item->key           = $key;
			$item->object        = $cart_item;
			$item->product       = $cart_item['data'];
			$item->quantity      = $cart_item['quantity'];
			$item->price         = wc_add_number_precision_deep( $item->product->get_price() );
			$item->prices_include_tax = 'incl' === $cart->tax_display_cart;
			$this->items[ $key ] = $item;
		}

		// Default to sorting by price.
		uasort( $this->items, array( $this, 'sort_by_price' ) );

		/**
		 * Filters the items that are added to the WC_Discount class.
		 *
		 * This filter adds the ability to manipulate what items the WC_Discount
		 * class operates on. An example of wanting to do this is to always sort
		 * them in a particular order so calculations are consistent no matter
		 * what order items are added.
		 *
		 * @since 3.2.0
		 *
		 * @param array $items {
		 *     The array of items to filter.
		 *
		 *     @type string $item_key The key of the item.
		 *     @type object $item     The actual item object.
		 * }
		 * @param string $add_method The way in which the items were added.
		 */
		$this->items = apply_filters( 'woocommerce_discounts_items', $this->items, 'cart' );
	}

	/**
	 * Normalise order items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param WC_Order $order Order object.
	 */
	public function set_items_from_order( $order ) {
		$this->items     = $this->discounts      = array();

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		foreach ( $order->get_items() as $order_item ) {
			/* @var $order_item WC_Order_Item_Product */
			$item                = new stdClass();
			$item->key           = $order_item->get_id();
			$item->object        = $order_item;
			$item->product       = $order_item->get_product();
			$item->quantity      = $order_item->get_quantity();
//			$item->price         = wc_add_number_precision( $item->product->get_price() );
			$item->price         = wc_add_number_precision( $order_item->get_total() / $item->quantity );
			$item->prices_include_tax = $order->get_prices_include_tax();

			// Order item total doesn't include tax, so we need to add it if prices are supposed to include tax.
			if ( $order->get_prices_include_tax() ) {
				$item->price += wc_add_number_precision( $order_item->get_total_tax() / $item->quantity );
			}

			$this->items[ $order_item->get_id() ] = $item;
		}

		// Default to sorting by price.
		uasort( $this->items, array( $this, 'sort_by_price' ) );

		/** This filter is documented above in set_items_from_cart(). */
		$this->items = apply_filters( 'woocommerce_discounts_items', $this->items, 'order' );
	}

	/**
	 * Get items.
	 *
	 * @since  3.2.0
	 * @return object[]
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Retrieve the current discount for the given item key. If this is called before
	 * all the discounts are applied, this will not represent the correct total.
	 *
	 * @param string $item_key The key of the item for which to get the discount sum.
	 * @param bool   $in_cents True to return the value in cents.
	 *
	 * @return float|int The current discount amount for the given item.
	 */
	public function get_discount( $item_key, $in_cents = false ) {
		$discount_sum = 0;
		foreach ( $this->discounts as $code => $discount ) {
			if ( isset( $discount[ $item_key ] ) ) {
				$discount_sum += $discount[ $item_key ];
			}
		}
		return ( $in_cents ) ? $discount_sum : wc_remove_number_precision( $discount_sum );
	}

	/**
	 * Get all discount totals.
	 *
	 * @since  3.2.0
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_discounts( $in_cents = false ) {
		$discounts = $this->discounts;
		return $in_cents ? $discounts : wc_remove_number_precision_deep( $discounts );
	}

	/**
	 * Get all discount totals per item.
	 *
	 * @since  3.2.0
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_discounts_by_item( $in_cents = false ) {
		$discounts            = $this->discounts;
		$item_discount_totals = (array) array_shift( $discounts );

		foreach ( $discounts as $item_discounts ) {
			foreach ( $item_discounts as $item_key => $item_discount ) {
				$item_discount_totals[ $item_key ] += $item_discount;
			}
		}

		return $in_cents ? $item_discount_totals : wc_remove_number_precision_deep( $item_discount_totals );
	}

	/**
	 * Get all discount totals per coupon.
	 *
	 * @since  3.2.0
	 * @param  bool   $in_cents Should the totals be returned in cents, or without precision.
	 * @param  string $coupon_type (Optional) The type of coupon for which to get the discounts.
	 *                             If left null or not a recognized coupon type, return all coupons.
	 * @return array Array of discounts in format Coupon_Code => Discount_Amount.
	 */
	public function get_discounts_by_coupon( $in_cents = false, $coupon_type = null ) {
		$coupon_discount_totals = array_map( 'array_sum', $this->discounts );

		if ( ! empty( $coupon_type ) && in_array( $coupon_type, array_keys( wc_get_coupon_types() ) ) ) {
			$filter_function = function ( $coupon ) use ( $coupon_type ) {
				/* @var $coupon WC_Coupon */
				return $coupon->is_type( $coupon_type );
			};

			// Get an array of coupons that fit the requested type.
			$filtered_coupons = array_filter( $this->coupons, $filter_function );

			// Get an array containing just the coupon discounts for the requested type.
			$coupon_discount_totals = array_intersect_key( $coupon_discount_totals, $filtered_coupons );
		}

		return $in_cents ? $coupon_discount_totals : wc_remove_number_precision_deep( $coupon_discount_totals );
	}

	/**
	 * Get discounted price of an item.
	 *
	 * @param string|object $item Pass a string to look up an item by its key. Pass an object to
	 *                            check the discounted price based on the given object.
	 * @param bool          $in_cents True to return the price in cents.
	 *
	 * @return float|WP_Error The discounted price for the item. WP_Error when the given item
	 *                        is invalid.
	 */
	public function get_discounted_price( $item, $in_cents = false ) {
		// Assume we were given an object.
		$item_obj = $item;

		// The passed object doesn't contain the proper properties, so assume a leftover price of 0.
		if ( is_object( $item_obj ) && ( ! property_exists( $item_obj, 'price' ) || ! property_exists( $item_obj, 'quantity') ) ) {
			return new WP_Error( 'invalid_item', __( 'Invalid item object', 'woocommerce' ) );
		} elseif ( is_string( $item ) ) {
			// If the given key doesn't exist, assume there's no price available.
			if ( ! isset( $this->items[ $item ] ) ) {
				return new WP_Error( 'invalid_item', __( 'Invalid item key', 'woocommerce' ) );
			}

			// Initialize our local variable with the item from the class global $items array.
			$item_obj = $this->items[ $item ];
		}

		$item_discount_sum = $this->get_discount( $item->key, true );
		$discounted_price = max( ( $item_obj->price * $item_obj->quantity ) - $item_discount_sum, 0 );

		return ( $in_cents ) ? $discounted_price : wc_remove_number_precision( $discounted_price );
	}

	/**
	 * Get discounted price of an item to precision (in cents).
	 *
	 * @param string|object $item Pass a string to look up an item by its key. Pass an object to
	 *                            check the discounted price based on the given object.
	 *
	 * @return float|WP_Error The discounted price for the item. WP_Error when the given item
	 *                        is invalid.
	 */
	public function get_discounted_price_in_cents( $item ) {
		return $this->get_discounted_price( $item, true );
	}

	/**
	 * Add multiple coupons at once.
	 *
	 * @param WC_Coupon[] $coupons An array of coupons to add.
	 * @param bool        $validate Set to false to skip coupon validation for
	 *                              the coupons.
	 */
	public function apply_coupons( $coupons, $validate = true ) {
		foreach ( $coupons as $coupon ) {
			$this->apply_coupon( $coupon, $validate, false );
		}

		$this->calculate_discounts();
	}

	/**
	 * Apply a discount to all items using a coupon.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon Coupon object being applied to the items.
	 * @param  bool      $validate Set to false to skip coupon validation.
	 * @param  bool      $run_calculations True to calculate discounts after
	 *                                     adding the coupon. False to just add
	 *                                     coupon but not calculate discount.
	 * @return bool|WP_Error True if applied and calculated. False if added but not calculated.
	 *                       WP_Error instance in failure.
	 */
	public function apply_coupon( $coupon, $validate = true, $run_calculations = true ) {
		if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
			return new WP_Error( 'invalid_coupon', __( 'Invalid coupon', 'woocommerce' ) );
		}

		$is_coupon_valid = $validate ? $this->is_coupon_valid( $coupon ) : true;

		if ( is_wp_error( $is_coupon_valid ) ) {
			// Return the error.
			return $is_coupon_valid;
		}

		// Add the coupon to the list of coupons.
		if ( ! isset( $this->coupons[ $coupon->get_code()] ) ) {
			/**
			 * Filters the coupons that are added to the WC_Discount class.
			 *
			 * This filter adds the ability to manipulate what coupons the WC_Discount
			 * class operates on. An example of wanting to do this is to always sort
			 * them in a particular order so calculations are consistent no matter
			 * what order items are added.
			 *
			 * @since 3.2.0
			 *
			 * @param WC_Coupon[] $coupons {
			 *     The array of items to filter.
			 *
			 *     @type string    $coupon_key The coupon code.
			 *     @type WC_Coupon $coupon     The actual coupon object.
			 * }
			 */
			$this->coupons = apply_filters( 'woocommerce_discounts_coupons', array_merge( $this->coupons, array( $coupon->get_code() => $coupon ) ), $this->get_items() );
		}

		if ( $run_calculations ) {
			$this->calculate_discounts();

			// Coupon applied and calculated.
			return true;
		}

		// Coupon added but not calculated.
		return false;
	}

	/**
	 * Sort by price.
	 *
	 * @since  3.2.0
	 * @param  array $a First element.
	 * @param  array $b Second element.
	 * @return int
	 */
	protected function sort_by_price( $a, $b ) {
		$price_1 = $a->price * $a->quantity;
		$price_2 = $b->price * $b->quantity;
		if ( $price_1 === $price_2 ) {
			return 0;
		}
		return ( $price_1 < $price_2 ) ? 1 : -1;
	}

	/**
	 * Filter out all products which have been fully discounted to 0.
	 * Used as array_filter callback.
	 *
	 * @since  3.2.0
	 * @param  object $item Get data for this item.
	 * @return bool
	 */
	protected function filter_products_with_price( $item ) {
		return $this->get_discounted_price_in_cents( $item ) > 0;
	}

	/**
	 * Get items which the coupon should be applied to.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon Coupon object.
	 * @return array
	 */
	protected function get_items_to_apply_coupon( $coupon ) {
		$items_to_apply  = array();
		$limit_usage_qty = 0;
		$applied_count   = 0;

		// Make a copy of the items so we can manipulate them for just this coupon.
		$items = array_map( function ( $object ) { return clone $object; }, $this->items );

		if ( null !== $coupon->get_limit_usage_to_x_items() ) {
			$limit_usage_qty = $coupon->get_limit_usage_to_x_items();
		}

		foreach ( $items as $item ) {
			// Check if this coupon even applies to this item and works with the whole cart.
			if ( ! $coupon->is_valid_for_product( $item->product, $item->object ) && ! $coupon->is_valid_for_cart() ) {
				continue;
			}

			// Make sure we don't let this coupon work for more items than allowed by limit_usage_to_x_items.
			if ( $limit_usage_qty && $applied_count >= $limit_usage_qty ) {
				break;
			}

			// Handle case where there is a limit on usage and an item has more quantity than leftover usage.
			if ( $limit_usage_qty && $item->quantity > ( $limit_usage_qty - $applied_count ) ) {
				$limit_to_qty   = absint( $limit_usage_qty - $applied_count );
				$item->quantity = $limit_to_qty; // Lower the qty so the discount is applied less.
			}
			$items_to_apply[] = $item;
			$applied_count   += $item->quantity;
		}
		return $items_to_apply;
	}

	/**
	 * Apply a percent coupon in such a way that it applies it per item instead of off the
	 * average of all the items to which it applies.
	 *
	 * @since 3.2.0
	 * @param WC_Coupon $coupon The coupon being applied.
	 * @param array     $items_to_apply Items that fit the coupon's criteria.
	 *
	 * @return float The total discount amount for this coupon.
	 */
	protected function apply_coupon_percent_by_each_item( $coupon, $items_to_apply ) {
		$total_discount = 0;
		foreach ( $items_to_apply as $item ) {
			// Grab the discounts across all coupons so far for this item.
			$discounts_so_far = ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ?
				$this->get_discount( $item->key, true ) : 0;

			// Determine how much the coupon should be applied against for the current item.
			$total_to_discount = max( ( $item->price * $item->quantity ) - $discounts_so_far, 0 );

			// Run coupon calculation.
			$coupon_discount = floor( $total_to_discount * ( $this->get_coupon_amount( $coupon ) / 100 ) );

			/**
			 * Filters the percent coupon discount calculated for an item when applying
			 * discounts per item.
			 *
			 * This only fires when percent discounts are applied per item instead
			 * of off an average of all the items.
			 *
			 * @since 3.2.0
			 *
			 * @param float $discount The amount of discount calculated for the item.
			 * @param WC_Coupon $coupon The coupon being applied to the item.
			 * @param object $item The item for which the amount was calculated.
			 * @param float $discount_sum The sum of discounts already calculated for
			 *                            the given item.
			 */
			$coupon_discount = wc_add_number_precision( apply_filters( 'woocommerce_coupon_get_percent_discount_amount_by_item', wc_remove_number_precision( $coupon_discount ), $coupon, $item, wc_remove_number_precision( $discounts_so_far ) ) );

			$this->discounts[ $coupon->get_code() ][ $item->key ] += $coupon_discount;
			$total_discount += $coupon_discount;
		}

		return $total_discount;
	}

	/**
	 * Apply percent coupon across an average of all the items to which it applies.
	 *
	 * @since 3.2.0
	 * @param WC_Coupon $coupon The coupon being applied.
	 * @param array     $items_to_apply Items that fit the coupon's criteria.
	 *
	 * @return float The total discount amount for this coupon.
	 */
	protected function apply_coupon_percent_by_each_item_average( $coupon, $items_to_apply ) {
		$discounts_so_far = ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $this->discount_total : 0;

		$total_to_discount = 0;
		$sum_of_quantities = 0;

		$used_items = array();
		foreach ( $items_to_apply as $item ) {
			$composite_price = $item->price * $item->quantity;

			// Don't process items with a zero price so they don't count in the per-item average.
			if ( 0 == $composite_price ) {
				continue;
			}

			$total_to_discount += $composite_price;
			$sum_of_quantities += $item->quantity;
			$used_items[] = $item;
		}

		// If there are no items for this coupon, don't discount anything.
		if ( 0 == $sum_of_quantities ) {
			return 0;
		}

		// Make sure we won't be applying the coupon to a negative amount.
		$total_to_discount = max ( $total_to_discount - $discounts_so_far, 0 );

		// Determine average price per item on which we'll apply the coupon discount.
		$price_per_item = $total_to_discount / $sum_of_quantities;

		$coupon_discount = floor( $price_per_item * ( $this->get_coupon_amount( $coupon ) / 100 ) * $sum_of_quantities );

		if ( is_a( $this->object, 'WC_Cart' ) && has_filter( 'woocommerce_coupon_get_discount_amount' ) ) {
			// Send through the legacy filter, but not as cents.
			$coupon_discount = wc_add_number_precision( apply_filters( 'woocommerce_coupon_get_discount_amount', wc_remove_number_precision( $coupon_discount ), wc_remove_number_precision( $total_to_discount ), $item->object, false, $coupon ) );
		}

		$this->discount_total += $coupon_discount;

		// Now save the discount distribution for later use.
		foreach ( $used_items as $item ) {
			// Get the total discount that this coupon gives for the given item.
			$item_discount = ( $coupon_discount / $sum_of_quantities ) * $item->quantity;
			$this->discounts[ $coupon->get_code() ][ $item->key ] += $item_discount;
		}

		// Work out how much discount would have been given to the cart as a whole and compare to what was discounted on all line items.
		$cart_total_discount = wc_cart_round_discount( $total_to_discount * ( $this->get_coupon_amount( $coupon ) / 100 ), 0 );

		if ( $coupon_discount < $cart_total_discount ) {
			$coupon_discount += $this->apply_coupon_remainder( $coupon, $used_items, $cart_total_discount - $coupon_discount );
		}

		return $coupon_discount;
	}

	/**
	 * Apply percent discount to items and return an array of discounts granted.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon Coupon object. Passed through filters.
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @return int Total discounted.
	 */
	protected function apply_coupon_percent( $coupon, $items_to_apply ) {
		$total_discounted = 0;

		/**
		 * Filters the method by which to calculate the discount from a percent
		 * coupon.
		 *
		 * @since 3.2.0
		 *
		 * @param string $calc_method The method to use when calculating. Valid
		 *                            options are 'by_item_average' and 'by_item'.
		 * @param WC_Coupon $coupon The coupon being calculated.
		 * @param array $items_to_apply {
		 *     The items to which the coupon will be applied.
		 *
		 *     @type string $item_key The key of the item.
		 *     @type object $item     The actual item object.
		 * }
		 */
		$coupon_calc_method = apply_filters( 'woocommerce_coupon_percent_calc_method', 'by_item_average', $coupon, $items_to_apply );

		switch ( $coupon_calc_method ) {
			case 'by_item':
				$total_discounted = $this->apply_coupon_percent_by_each_item( $coupon, $items_to_apply );
				break;
			case 'by_item_average':
			default:
				$total_discounted = $this->apply_coupon_percent_by_each_item_average( $coupon, $items_to_apply );
		}

		return $total_discounted;
	}

	/**
	 * Run the actual calculations. No discounts will done before this is run.
	 *
	 * @since 3.2.0
	 */
	public function calculate_discounts() {
		// Reset the discounts as we'll regenerate it.
		$this->discounts = array();

		// A running total of discounts.
		$this->discount_total = 0;

		foreach ( $this->coupons as $coupon_code => $coupon) {
			// Initialize the discounts array for this coupon.
			if ( ! isset( $this->discounts[ $coupon->get_code() ] ) ) {
				$this->discounts[ $coupon->get_code() ] = array_fill_keys( array_keys( $this->items ), 0 );
			}

			$items_to_apply = $this->get_items_to_apply_coupon( $coupon );

			// Core discounts are handled here as of 3.2.
			switch ( $coupon->get_discount_type() ) {
				case 'percent' :
					$this->apply_coupon_percent( $coupon, $items_to_apply );
					break;
				case 'fixed_product' :
					$this->apply_coupon_fixed_product( $coupon, $items_to_apply );
					break;
				case 'fixed_cart' :
					$this->apply_coupon_fixed_cart( $coupon, $items_to_apply );
					break;
				default :
					foreach ( $items_to_apply as $item ) {
						$discounted_price  = $this->get_discounted_price_in_cents( $item );
						$price_to_discount = wc_remove_number_precision( ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $discounted_price : $item->price );
						$discount          = wc_add_number_precision( $coupon->get_discount_amount( $price_to_discount, $item->object ) ) * $item->quantity;
						$discount          = min( $discounted_price, $discount );

						// Store code and discount amount per item.
						$this->discounts[ $coupon->get_code() ][ $item->key ] += $discount;
					}
					break;
			}
		}
	}

	/**
	 * Apply fixed product discount to items.
	 *
	 * @since  3.2.0
	 *
	 * @param  WC_Coupon $coupon Coupon object. Passed through filters.
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @param  int       $discount_amount Fixed discount amount to apply in cents. Leave blank to pull from coupon.
	 *
	 * @return float     Total discounted.
	 */
	protected function apply_coupon_fixed_product( $coupon, $items_to_apply, $discount_amount = null ) {
		$total_discount = 0;

		if ( null === $discount_amount ) {
			// Because get_amount() could return an empty string, let's be sure to set our local variable to a known good value.
			$discount_amount = wc_add_number_precision( $this->get_coupon_amount( $coupon ) );
		}

		foreach ( $items_to_apply as $item ) {
			$total_item_price = $item->price * $item->quantity;
			$coupon_discount  = $discount_amount * $item->quantity;

			// Don't allow a discount more than the total of the item prices.
			if ( $coupon_discount > $total_item_price ) {
				$coupon_discount = $total_item_price;
			}

			if ( is_a( $this->object, 'WC_Cart' ) && has_filter( 'woocommerce_coupon_get_discount_amount' ) ) {
				// Send through the legacy filter, but not as cents.
				$coupon_discount = wc_add_number_precision( apply_filters( 'woocommerce_coupon_get_discount_amount', wc_remove_number_precision( $coupon_discount ), wc_remove_number_precision( $total_item_price ), $item->object, false, $coupon ) );
			}

			$this->discounts[ $coupon->get_code() ][ $item->key ] += $coupon_discount;
			$total_discount += $coupon_discount;
		}

		return $total_discount;
	}

	/**
	 * Apply fixed cart discount to items.
	 *
	 * @since  3.2.0
	 *
	 * @param  WC_Coupon $coupon Coupon object. Passed through filters.
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @param  int       $discount_amount Fixed discount amount to apply in cents. Leave blank to pull from coupon.
	 *
	 * @return int Total discounted.
	 */
	protected function apply_coupon_fixed_cart( $coupon, $items_to_apply, $discount_amount = null ) {
		$total_discount  = 0;

		if ( null === $discount_amount ) {
			$discount_amount = wc_add_number_precision( $this->get_coupon_amount( $coupon ) );
		}

		// Find items that still have something to discount.
		$items_to_apply = array_filter( $items_to_apply, array( $this, 'filter_products_with_price' ) );

		// If no items with a quantity exist, there's no discount to apply.
		if ( ! $item_count = array_sum( wp_list_pluck( $items_to_apply, 'quantity' ) ) ) {
			return $total_discount;
		}

		if ( ! $discount_amount ) {
			// If there is no amount we still send it through so filters are fired.
			$total_discount = $this->apply_coupon_fixed_product( $coupon, $items_to_apply, 0 );
		} else {
			$per_item_discount = absint( $discount_amount / $item_count ); // round it down to the nearest cent.

			if ( $per_item_discount > 0 ) {
				// Apply the discounts across the products using the method for a fixed_product coupon.
				$total_discount = $this->apply_coupon_fixed_product( $coupon, $items_to_apply, $per_item_discount );

				/**
				 * If there is still discount remaining, repeat the process.
				 */
				if ( $total_discount > 0 && $total_discount < $discount_amount ) {
					$total_discount += $this->apply_coupon_fixed_cart( $coupon, $items_to_apply, $discount_amount - $total_discount );
				}
			} elseif ( $discount_amount > 0 ) {
				$total_discount += $this->apply_coupon_remainder( $coupon, $items_to_apply, $discount_amount );
			}
		}
		return $total_discount;
	}

	/**
	 * Deal with remaining fractional discounts by splitting it over items
	 * until the amount is expired, discounting 1 cent at a time.
	 *
	 * @since 3.2.0
	 * @param  WC_Coupon $coupon Coupon object if appliable. Passed through filters.
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @param  int       $amount Fixed discount amount to apply.
	 * @return int Total discounted.
	 */
	protected function apply_coupon_remainder( $coupon, $items_to_apply, $amount ) {
		$total_discount = 0;

		foreach ( $items_to_apply as $item ) {
			for ( $i = 0; $i < $item->quantity; $i ++ ) {
				$leftover_price = $this->get_discounted_price( $item, true );

				// In the case that the leftover price is greater than zero but less than one cent.
				$discount = min( $leftover_price, 1 );

				$total_discount += $discount;

				$this->discounts[ $coupon->get_code() ][ $item->key ] += $discount;

				if ( $total_discount >= $amount ) {
					break 2;
				}
			}
			if ( $total_discount >= $amount ) {
				break;
			}
		}
		return $total_discount;
	}

	/**
	 * Helper function to make sure we get a number from the coupon amount.
	 *
	 * @since 3.2.0
	 * @param WC_Coupon $coupon The coupon from which to get the amount.
	 *
	 * @return float The amount of the coupon. Zero if coupon amount is empty.
	 */
	private function get_coupon_amount( $coupon ) {
		return ( '' == $coupon->get_amount() ) ? 0 : $coupon->get_amount();
	}

	/*
	 |--------------------------------------------------------------------------
	 | Validation & Error Handling
	 |--------------------------------------------------------------------------
	 */

	/**
	 * Ensure coupon exists or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_exists( $coupon ) {
		if ( ! $coupon->get_id() && ! $coupon->get_virtual() ) {
			/* translators: %s: coupon code */
			throw new Exception( sprintf( __( 'Coupon "%s" does not exist!', 'woocommerce' ), $coupon->get_code() ), 105 );
		}

		return true;
	}

	/**
	 * Ensure coupon usage limit is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_usage_limit( $coupon ) {
		if ( $coupon->get_usage_limit() > 0 && $coupon->get_usage_count() >= $coupon->get_usage_limit() ) {
			throw new Exception( __( 'Coupon usage limit has been reached.', 'woocommerce' ), 106 );
		}

		return true;
	}

	/**
	 * Ensure coupon user usage limit is valid or throw exception.
	 *
	 * Per user usage limit - check here if user is logged in (against user IDs).
	 * Checked again for emails later on in WC_Cart::check_customer_coupons().
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon  Coupon data.
	 * @param  int       $user_id User ID.
	 * @return bool
	 */
	protected function validate_coupon_user_usage_limit( $coupon, $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( $coupon && $user_id && $coupon->get_usage_limit_per_user() > 0 && $coupon->get_id() && $coupon->get_data_store() ) {
			$date_store  = $coupon->get_data_store();
			$usage_count = $date_store->get_usage_by_user_id( $coupon, $user_id );
			if ( $usage_count >= $coupon->get_usage_limit_per_user() ) {
				throw new Exception( __( 'Coupon usage limit has been reached.', 'woocommerce' ), 106 );
			}
		}

		return true;
	}

	/**
	 * Ensure coupon date is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_expiry_date( $coupon ) {
		if ( $coupon->get_date_expires() && current_time( 'timestamp', true ) > $coupon->get_date_expires()->getTimestamp() ) {
			throw new Exception( __( 'This coupon has expired.', 'woocommerce' ), 107 );
		}

		return true;
	}

	/**
	 * Ensure coupon amount is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon   Coupon data.
	 * @param  float     $subtotal Items subtotal.
	 * @return bool
	 */
	protected function validate_coupon_minimum_amount( $coupon, $subtotal = 0 ) {
		if ( $coupon->get_minimum_amount() > 0 && apply_filters( 'woocommerce_coupon_validate_minimum_amount', $coupon->get_minimum_amount() > $subtotal, $coupon, $subtotal ) ) {
			/* translators: %s: coupon minimum amount */
			throw new Exception( sprintf( __( 'The minimum spend for this coupon is %s.', 'woocommerce' ), wc_price( $coupon->get_minimum_amount() ) ), 108 );
		}

		return true;
	}

	/**
	 * Ensure coupon amount is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon   Coupon data.
	 * @param  float     $subtotal Items subtotal.
	 * @return bool
	 */
	protected function validate_coupon_maximum_amount( $coupon, $subtotal = 0 ) {
		if ( $coupon->get_maximum_amount() > 0 && apply_filters( 'woocommerce_coupon_validate_maximum_amount', $coupon->get_maximum_amount() < $subtotal, $coupon ) ) {
			/* translators: %s: coupon maximum amount */
			throw new Exception( sprintf( __( 'The maximum spend for this coupon is %s.', 'woocommerce' ), wc_price( $coupon->get_maximum_amount() ) ), 112 );
		}

		return true;
	}

	/**
	 * Ensure coupon is valid for products in the list is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_product_ids( $coupon ) {
		if ( count( $coupon->get_product_ids() ) > 0 ) {
			$valid = false;

			foreach ( $this->items as $item ) {
				if ( $item->product && in_array( $item->product->get_id(), $coupon->get_product_ids(), true ) || in_array( $item->product->get_parent_id(), $coupon->get_product_ids(), true ) ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( __( 'Sorry, this coupon is not applicable to selected products.', 'woocommerce' ), 109 );
			}
		}

		return true;
	}

	/**
	 * Ensure coupon is valid for product categories in the list is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_product_categories( $coupon ) {
		if ( count( $coupon->get_product_categories() ) > 0 ) {
			$valid = false;

			foreach ( $this->items as $item ) {
				if ( $coupon->get_exclude_sale_items() && $item->product && $item->product->is_on_sale() ) {
					continue;
				}

				$product_cats = wc_get_product_cat_ids( $item->product->get_id() );

				// If we find an item with a cat in our allowed cat list, the coupon is valid.
				if ( count( array_intersect( $product_cats, $coupon->get_product_categories() ) ) > 0 ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( __( 'Sorry, this coupon is not applicable to selected products.', 'woocommerce' ), 109 );
			}
		}

		return true;
	}

	/**
	 * Ensure coupon is valid for sale items in the list is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_sale_items( $coupon ) {
		if ( $coupon->get_exclude_sale_items() ) {
			$valid = false;

			foreach ( $this->items as $item ) {
				if ( $item->product && ! $item->product->is_on_sale() ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( __( 'Sorry, this coupon is not valid for sale items.', 'woocommerce' ), 110 );
			}
		}

		return true;
	}

	/**
	 * All exclusion rules must pass at the same time for a product coupon to be valid.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_excluded_items( $coupon ) {
		if ( ! empty( $this->items ) && $coupon->is_type( wc_get_product_coupon_types() ) ) {
			$valid = false;

			foreach ( $this->items as $item ) {
				if ( $item->product && $coupon->is_valid_for_product( $item->product, $item->object ) ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( __( 'Sorry, this coupon is not applicable to selected products.', 'woocommerce' ), 109 );
			}
		}

		return true;
	}

	/**
	 * Cart discounts cannot be added if non-eligible product is found.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_eligible_items( $coupon ) {
		if ( ! $coupon->is_type( wc_get_product_coupon_types() ) ) {
			$this->validate_coupon_excluded_product_ids( $coupon );
			$this->validate_coupon_excluded_product_categories( $coupon );
		}

		return true;
	}

	/**
	 * Exclude products.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_excluded_product_ids( $coupon ) {
		// Exclude Products.
		if ( count( $coupon->get_excluded_product_ids() ) > 0 ) {
			$products = array();

			foreach ( $this->items as $item ) {
				if ( $item->product && in_array( $item->product->get_id(), $coupon->get_excluded_product_ids(), true ) || in_array( $item->product->get_parent_id(), $coupon->get_excluded_product_ids(), true ) ) {
					$products[] = $item->product->get_name();
				}
			}

			if ( ! empty( $products ) ) {
				/* translators: %s: products list */
				throw new Exception( sprintf( __( 'Sorry, this coupon is not applicable to the products: %s.', 'woocommerce' ), implode( ', ', $products ) ), 113 );
			}
		}

		return true;
	}

	/**
	 * Exclude categories from product list.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_excluded_product_categories( $coupon ) {
		if ( count( $coupon->get_excluded_product_categories() ) > 0 ) {
			$categories = array();

			foreach ( $this->items as $item ) {
				if ( $coupon->get_exclude_sale_items() && $item->product && $item->product->is_on_sale() ) {
					continue;
				}

				$product_cats = wc_get_product_cat_ids( $item->product->get_id() );
				$cat_id_list  = array_intersect( $product_cats, $coupon->get_excluded_product_categories() );
				if ( count( $cat_id_list ) > 0 ) {
					foreach ( $cat_id_list as $cat_id ) {
						$cat          = get_term( $cat_id, 'product_cat' );
						$categories[] = $cat->name;
					}
				}
			}

			if ( ! empty( $categories ) ) {
				/* translators: %s: categories list */
				throw new Exception( sprintf( __( 'Sorry, this coupon is not applicable to the categories: %s.', 'woocommerce' ), implode( ', ', array_unique( $categories ) ) ), 114 );
			}
		}

		return true;
	}

	/**
	 * Check if a coupon is valid.
	 *
	 * Error Codes:
	 * - 100: Invalid filtered.
	 * - 101: Invalid removed.
	 * - 102: Not yours removed.
	 * - 103: Already applied.
	 * - 104: Individual use only.
	 * - 105: Not exists.
	 * - 106: Usage limit reached.
	 * - 107: Expired.
	 * - 108: Minimum spend limit not met.
	 * - 109: Not applicable.
	 * - 110: Not valid for sale items.
	 * - 111: Missing coupon code.
	 * - 112: Maximum spend limit met.
	 * - 113: Excluded products.
	 * - 114: Excluded categories.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool|WP_Error
	 */
	public function is_coupon_valid( $coupon ) {
		try {
			$this->validate_coupon_exists( $coupon );
			$this->validate_coupon_usage_limit( $coupon );
			$this->validate_coupon_user_usage_limit( $coupon );
			$this->validate_coupon_expiry_date( $coupon );
			$this->validate_coupon_minimum_amount( $coupon );
			$this->validate_coupon_maximum_amount( $coupon );
			$this->validate_coupon_product_ids( $coupon );
			$this->validate_coupon_product_categories( $coupon );
			$this->validate_coupon_sale_items( $coupon );
			$this->validate_coupon_excluded_items( $coupon );
			$this->validate_coupon_eligible_items( $coupon );

			if ( ! apply_filters( 'woocommerce_coupon_is_valid', true, $coupon, $this ) ) {
				throw new Exception( __( 'Coupon is not valid.', 'woocommerce' ), 100 );
			}
		} catch ( Exception $e ) {
			/**
			 * Filter the coupon error message.
			 *
			 * @param string    $error_message Error message.
			 * @param int       $error_code    Error code.
			 * @param WC_Coupon $coupon        Coupon data.
			 */
			$message = apply_filters( 'woocommerce_coupon_error', is_numeric( $e->getMessage() ) ? $coupon->get_coupon_error( $e->getMessage() ) : $e->getMessage(), $e->getCode(), $coupon );

			return new WP_Error( 'invalid_coupon', $message, array(
				'status' => 400,
			) );
		}
		return true;
	}
}
