<?php
/**
 * Discount calculation
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 * @version 3.2.0
 * @since   3.2.0
 */

/**
 * Discounts class.
 *
 * @todo this class will need to be called instead get_discounted_price, in the cart?
 */
class WC_Discounts {

	/**
	 * An array of items to discount.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * An array of discounts which have been applied to items.
	 *
	 * @var array
	 */
	protected $discounts = array();

	/**
	 * An array of applied coupons codes and total discount.
	 *
	 * @var array
	 */
	protected $applied_coupons = array();

	/**
	 * Precision so we can work in cents.
	 *
	 * @var int
	 */
	protected $precision = 1;

	/**
	 * Constructor.
	 */
	public function __construct( $items ) {
		$this->precision = pow( 10, wc_get_price_decimals() );
		$this->set_items( $items );
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
	 * Get discount by key without precision.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_discount( $key ) {
		return isset( $this->discounts[ $key ] ) ? $this->remove_precision( $this->discounts[ $key ] ) : 0;
	}

	/**
	 * Get all discount totals with precision.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_discounts() {
		return $this->discounts;
	}

	/**
	 * Get discounted price of an item without precision.
	 *
	 * @since  3.2.0
	 * @param  object $item
	 * @return float
	 */
	public function get_discounted_price( $item ) {
		return $this->remove_precision( $this->get_discounted_price_in_cents( $item ) );
	}

	/**
	 * Get discounted price of an item to precision (in cents).
	 *
	 * @since  3.2.0
	 * @param  object $item
	 * @return float
	 */
	public function get_discounted_price_in_cents( $item ) {
		return $item->price - $this->discounts[ $item->key ];
	}

	/**
	 * Returns a list of applied coupons with name value pairs - name being
	 * the coupon code, and value being the total amount disounted.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_applied_coupons() {
		return array_map( array( $this, 'remove_precision' ), $this->applied_coupons );
	}

	/**
	 * Set cart/order items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param array $items List items, normailised, by WC_Totals.
	 */
	public function set_items( $items ) {
		$this->items           = array();
		$this->discounts       = array();
		$this->applied_coupons = array();

		if ( ! empty( $items ) && is_array( $items ) ) {
			$this->items     = $items;
			$this->discounts = array_fill_keys( array_keys( $items ), 0 );
		}

		uasort( $this->items, array( $this, 'sort_by_price' ) );
	}

	/**
	 * Apply a discount to all items using a coupon.
	 *
	 * @todo Coupon class has lots of WC()->cart calls and needs decoupling. This makes 'is valid' hard to use here.
	 * @todo is_valid_for_product accepts values - how can we deal with that?
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon
	 * @return bool True if applied.
	 */
	public function apply_coupon( $coupon ) {
		if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
			return false;
		}

		if ( ! isset( $this->applied_coupons[ $coupon->get_code() ] ) ) {
			$this->applied_coupons[ $coupon->get_code() ] = 0;
		}

		// @todo how can we support the old woocommerce_coupon_get_discount_amount filter?
		// @todo is valid for product - filter items here and pass to function?
		$items_to_apply = $this->get_items_to_apply_coupon( $coupon );

		switch ( $coupon->get_discount_type() ) {
			case 'percent' :
				$this->applied_coupons[ $coupon->get_code() ] += $this->apply_percentage_discount( $items_to_apply, $coupon->get_amount() );
				break;
			case 'fixed_product' :
				$this->applied_coupons[ $coupon->get_code() ] += $this->apply_fixed_product_discount( $items_to_apply, $coupon->get_amount() * $this->precision );
				break;
			case 'fixed_cart' :
				$this->applied_coupons[ $coupon->get_code() ] += $this->apply_fixed_cart_discount( $items_to_apply, $coupon->get_amount() * $this->precision );
				break;
		}
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
	 * Sort by price.
	 *
	 * @param  array $a
	 * @param  array $b
	 * @return int
	 */
	protected function sort_by_price( $a, $b ) {
		$price_1 = $a->price * $a->quantity;
		$price_2 = $b->price * $b->quantity;;
		if ( $price_1 === $price_2 ) {
			return 0;
		}
		return ( $price_1 < $price_2 ) ? 1 : -1;
	}

	/**
	 * Filter out all products which have been fully discounted to 0.
	 * Used as array_filter callback.
	 *
	 * @param  object $item
	 * @return bool
	 */
	protected function filter_products_with_price( $item ) {
		return $this->get_discounted_price_in_cents( $item ) > 0;
	}

	/**
	 * Get items which the coupon should be applied to.
	 *
	 * @param  object $coupon
	 * @return array
	 */
	protected function get_items_to_apply_coupon( $coupon ) {
		$items_to_apply  = array();
		$limit_usage_qty = 0;
		$applied_count   = 0;

		if ( null !== $coupon->get_limit_usage_to_x_items() ) {
			$limit_usage_qty = $coupon->get_limit_usage_to_x_items();
		}

		foreach ( $this->items as $item ) {
			if ( 0 === $this->get_discounted_price_in_cents( $item ) ) {
				continue;
			}
			if ( ! $coupon->is_valid_for_product( $item->product ) && ! $coupon->is_valid_for_cart() ) { // @todo is this enough?
				continue;
			}
			if ( $limit_usage_qty && $applied_count > $limit_usage_qty ) {
				break;
			}
			if ( $limit_usage_qty && $item->quantity > ( $limit_usage_qty - $applied_count ) ) {
				$limit_to_qty   = absint( $limit_usage_qty - $applied_count );
				$item->price    = ( $item->price / $item->quantity ) * $limit_to_qty;
				$item->quantity = $limit_to_qty; // Lower the qty so the discount is applied less.
			}
			if ( 0 >= $item->quantity ) {
				continue;
			}
			$items_to_apply[] = $item;
			$applied_count   += $item->quantity;
		}
		return $items_to_apply;
	}

	/**
	 * Apply a discount amount to an item and ensure it does not go negative.
	 *
	 * @since  3.2.0
	 * @param  object $item
	 * @param  int $discount
	 * @return int Amount discounted.
	 */
	protected function add_item_discount( &$item, $discount ) {
		$discounted_price              = $this->get_discounted_price_in_cents( $item );
		$discount                      = $discount > $discounted_price ? $discounted_price : $discount;
		$this->discounts[ $item->key ] = $this->discounts[ $item->key ] + $discount;
		return $discount;
	}

	/**
	 * Apply percent discount to items.
	 *
	 * @since  3.2.0
	 * @param array $items_to_apply Array of items to apply the coupon to.
	 * @param  int $amount
	 * @return int total discounted in cents
	 */
	protected function apply_percentage_discount( $items_to_apply, $amount ) {
		$total_discounted = 0;

		foreach ( $items_to_apply as $item ) {
			$total_discounted += $this->add_item_discount( $item, $amount * ( $this->get_discounted_price_in_cents( $item ) / 100 ) );
		}

		return $total_discounted;
	}

	/**
	 * Apply fixed product discount to items.
	 *
	 * @since  3.2.0
	 * @param  array $items_to_apply Array of items to apply the coupon to.
	 * @param  int $amount
	 * @return int total discounted in cents
	 */
	protected function apply_fixed_product_discount( $items_to_apply, $discount ) {
		$total_discounted = 0;

		foreach ( $items_to_apply as $item ) {
			$total_discounted += $this->add_item_discount( $item, $discount * $item->quantity );
		}

		return $total_discounted;
	}

	/**
	 * Apply fixed cart discount to items.
	 *
	 * @since  3.2.0
	 * @param  array $items_to_apply Array of items to apply the coupon to.
	 * @param  int $cart_discount
	 * @return int total discounted in cents
	 */
	protected function apply_fixed_cart_discount( $items_to_apply, $cart_discount ) {
		$items_to_apply   = array_filter( $items_to_apply, array( $this, 'filter_products_with_price' ) );

		if ( ! $item_count = array_sum( wp_list_pluck( $items_to_apply, 'quantity' ) ) ) {
			return 0;
		}

		$per_item_discount = floor( $cart_discount / $item_count ); // round it down to the nearest cent number.
		$amount_discounted = 0;

		if ( $per_item_discount > 0 ) {
			foreach ( $items_to_apply as $item ) {
				$amount_discounted += $this->add_item_discount( $item, $per_item_discount * $item->quantity );
			}

			/**
			 * If there is still discount remaining, repeat the process.
			 */
			if ( $amount_discounted > 0 && $amount_discounted < $cart_discount ) {
				$amount_discounted += $this->apply_fixed_cart_discount( $items_to_apply, $cart_discount - $amount_discounted );
			}

		/**
		 * Deal with remaining fractional discounts by splitting it over items
		 * until the amount is expired, discounting 1 cent at a time.
		 */
	 	} elseif ( $cart_discount > 0 ) {
			foreach ( $items_to_apply as $item ) {
				for ( $i = 0; $i < $item->quantity; $i ++ ) {
					$amount_discounted += $this->add_item_discount( $item, 1 );

					if ( $amount_discounted >= $cart_discount ) {
						break 2;
					}
				}
				if ( $amount_discounted >= $cart_discount ) {
					break;
				}
			}
		}

		return $amount_discounted;
	}
}
