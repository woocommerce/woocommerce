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
	 * Constructor.
	 *
	 * @param array $items Items to discount.
	 */
	public function __construct( $items = array() ) {
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
	 * @param  string $key name of discount row to return.
	 * @return array
	 */
	public function get_discount( $key ) {
		return isset( $this->discounts[ $key ] ) ? wc_remove_number_precision_deep( $this->discounts[ $key ] ) : 0;
	}

	/**
	 * Get all discount totals with precision.
	 *
	 * @since  3.2.0
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_discounts( $in_cents = false ) {
		return $in_cents ? $this->discounts : wc_remove_number_precision_deep ( $this->discounts );
	}

	/**
	 * Get discounted price of an item without precision.
	 *
	 * @since  3.2.0
	 * @param  object $item Get data for this item.
	 * @return float
	 */
	public function get_discounted_price( $item ) {
		return wc_remove_number_precision_deep( $this->get_discounted_price_in_cents( $item ) );
	}

	/**
	 * Get discounted price of an item to precision (in cents).
	 *
	 * @since  3.2.0
	 * @param  object $item Get data for this item.
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
		return wc_remove_number_precision_deep( $this->applied_coupons );
	}

	/**
	 * Set cart/order items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param array $items List items.
	 */
	public function set_items( $items ) {
		$this->items           = array();
		$this->discounts       = array();
		$this->applied_coupons = array();

		if ( ! empty( $items ) && is_array( $items ) ) {
			foreach ( $items as $key => $item ) {
				$this->items[ $key ]        = $item;
				$this->items[ $key ]->key   = $key;
				$this->items[ $key ]->price = $item->subtotal;
			}
			$this->discounts = array_fill_keys( array_keys( $items ), 0 );
		}

		uasort( $this->items, array( $this, 'sort_by_price' ) );
	}

	/**
	 * Apply a discount to all items using a coupon.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon Coupon object being applied to the items.
	 * @return bool|WP_Error True if applied or WP_Error instance in failure.
	 */
	public function apply_coupon( $coupon ) {
		if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
			return false;
		}

		$is_coupon_valid = $this->is_coupon_valid( $coupon );

		if ( is_wp_error( $is_coupon_valid ) ) {
			return $is_coupon_valid;
		}

		if ( ! isset( $this->applied_coupons[ $coupon->get_code() ] ) ) {
			$this->applied_coupons[ $coupon->get_code() ] = array(
				'discount'     => 0,
				'discount_tax' => 0,
			);
		}

		$items_to_apply = $this->get_items_to_apply_coupon( $coupon );
		$coupon_type    = $coupon->get_discount_type();

		// Core discounts are handled here as of 3.2.
		switch ( $coupon->get_discount_type() ) {
			case 'percent' :
				$this->apply_percentage_discount( $items_to_apply, $coupon->get_amount(), $coupon );
				break;
			case 'fixed_product' :
				$this->apply_fixed_product_discount( $items_to_apply, wc_add_number_precision( $coupon->get_amount() ), $coupon );
				break;
			case 'fixed_cart' :
				$this->apply_fixed_cart_discount( $items_to_apply, wc_add_number_precision( $coupon->get_amount() ), $coupon );
				break;
			default :
				if ( has_action( 'woocommerce_discounts_apply_coupon_' . $coupon_type ) ) {
					// Allow custom coupon types to control this in their class per item, unless the new action is used.
					do_action( 'woocommerce_discounts_apply_coupon_' . $coupon_type, $coupon, $items_to_apply, $this );
				} else {
					// Fallback to old coupon-logic.
					foreach ( $items_to_apply as $item ) {
						$discounted_price  = $this->get_discounted_price_in_cents( $item );
						$price_to_discount = ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $item->price : $discounted_price;
						$discount          = min( $discounted_price, $coupon->get_discount_amount( $price_to_discount, $item->object ) );
						$discount_tax      = $this->get_item_discount_tax( $item, $discount );

						// Store totals.
						$this->discounts[ $item->key ]                                += $discount;
						if ( $coupon ) {
							$this->applied_coupons[ $coupon->get_code() ]['discount']     += $discount;
							$this->applied_coupons[ $coupon->get_code() ]['discount_tax'] += $discount_tax;
						}
					}
				}
				break;
		}
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
	 * @param  object $coupon Coupon object.
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
			if ( ! $coupon->is_valid_for_product( $item->product, $item->object ) && ! $coupon->is_valid_for_cart() ) {
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
	 * Apply percent discount to items and return an array of discounts granted.
	 *
	 * @since  3.2.0
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @param  int       $amount Amount of discount.
	 * @param  WC_Coupon $coupon Coupon object if appliable. Passed through filters.
	 * @return int Total discounted.
	 */
	protected function apply_percentage_discount( $items_to_apply, $amount, $coupon = null ) {
		$total_discount = 0;

		foreach ( $items_to_apply as $item ) {
			// Find out how much price is available to discount for the item.
			$discounted_price  = $this->get_discounted_price_in_cents( $item );

			// Get the price we actually want to discount, based on settings.
			$price_to_discount = ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $item->price: $discounted_price;

			// Run coupon calculations.
			$discount          = $amount * ( $price_to_discount / 100 );
			$discount          = min( $discounted_price, apply_filters( 'woocommerce_coupon_get_discount_amount', $discount, $price_to_discount, $item->object, false, $coupon ) );
			$discount_tax      = $this->get_item_discount_tax( $item, $discount );

			// Store totals.
			$total_discount                                               += $discount;
			$this->discounts[ $item->key ]                                += $discount;
			if ( $coupon ) {
				$this->applied_coupons[ $coupon->get_code() ]['discount']     += $discount;
				$this->applied_coupons[ $coupon->get_code() ]['discount_tax'] += $discount_tax;
			}
		}
		return $total_discount;
	}

	/**
	 * Apply fixed product discount to items.
	 *
	 * @since  3.2.0
	 * @param  array $items_to_apply Array of items to apply the coupon to.
	 * @param  int   $amount Amount of discount.
	 * @param  WC_Coupon $coupon Coupon object if appliable. Passed through filters.
	 * @return int Total discounted.
	 */
	protected function apply_fixed_product_discount( $items_to_apply, $amount, $coupon = null ) {
		$total_discount = 0;

		foreach ( $items_to_apply as $item ) {
			// Find out how much price is available to discount for the item.
			$discounted_price  = $this->get_discounted_price_in_cents( $item );

			// Get the price we actually want to discount, based on settings.
			$price_to_discount = ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $item->price: $discounted_price;

			// Run coupon calculations.
			$discount          = $amount * $item->quantity;
			$discount          = min( $discounted_price, apply_filters( 'woocommerce_coupon_get_discount_amount', $discount, $price_to_discount, $item->object, false, $coupon ) );
			$discount_tax      = $this->get_item_discount_tax( $item, $discount );

			// Store totals.
			$total_discount                                               += $discount;
			$this->discounts[ $item->key ]                                += $discount;
			if ( $coupon ) {
				$this->applied_coupons[ $coupon->get_code() ]['discount']     += $discount;
				$this->applied_coupons[ $coupon->get_code() ]['discount_tax'] += $discount_tax;
			}
		}
		return $total_discount;
	}

	/**
	 * Apply fixed cart discount to items.
	 *
	 * @since  3.2.0
	 * @param  array $items_to_apply Array of items to apply the coupon to.
	 * @param  int   $cart_discount Fixed discount amount to apply.
	 * @param  WC_Coupon $coupon Coupon object if appliable. Passed through filters.
	 * @return int Total discounted.
	 */
	protected function apply_fixed_cart_discount( $items_to_apply, $cart_discount, $coupon = null ) {
		$total_discount = 0;
		$items_to_apply = array_filter( $items_to_apply, array( $this, 'filter_products_with_price' ) );

		if ( ! $item_count = array_sum( wp_list_pluck( $items_to_apply, 'quantity' ) ) ) {
			return $total_discount;
		}

		$per_item_discount = floor( $cart_discount / $item_count ); // round it down to the nearest cent.

		if ( $per_item_discount > 0 ) {
			$total_discounted = $this->apply_fixed_product_discount( $items_to_apply, $per_item_discount, $coupon );

			/**
			 * If there is still discount remaining, repeat the process.
			 */
			if ( $total_discounted > 0 && $total_discounted < $cart_discount ) {
				$total_discounted = $total_discounted + $this->apply_fixed_cart_discount( $items_to_apply, $cart_discount - $total_discounted );
			}

		} elseif ( $cart_discount > 0 ) {
			$total_discounted = $this->apply_fixed_cart_discount_remainder( $items_to_apply, $cart_discount, $coupon );
		}
		return $total_discount;
	}

	/**
	 * Deal with remaining fractional discounts by splitting it over items
	 * until the amount is expired, discounting 1 cent at a time.
	 *
	 * @since 3.2.0
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @param  int       $cart_discount Fixed discount amount to apply.
	 * @param  WC_Coupon $coupon Coupon object if appliable. Passed through filters.
	 * @return int Total discounted.
	 */
	protected function apply_fixed_cart_discount_remainder( $items_to_apply, $remaining_discount, $coupon = null ) {
		$total_discount = 0;

		foreach ( $items_to_apply as $item ) {
			for ( $i = 0; $i < $item->quantity; $i ++ ) {
				// Find out how much price is available to discount for the item.
				$discounted_price  = $this->get_discounted_price_in_cents( $item );

				// Get the price we actually want to discount, based on settings.
				$price_to_discount = ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $item->price: $discounted_price;

				// Run coupon calculations.
				$discount     = min( $discounted_price, 1 );
				$discount_tax = $this->get_item_discount_tax( $item, $discount );

				// Store totals.
				$total_discount                                               += $discount;
				$this->discounts[ $item->key ]                                += $discount;
				if ( $coupon ) {
					$this->applied_coupons[ $coupon->get_code() ]['discount']     += $discount;
					$this->applied_coupons[ $coupon->get_code() ]['discount_tax'] += $discount_tax;
				}

				if ( $total_discount >= $remaining_discount ) {
					break 2;
				}
			}
			if ( $total_discount >= $remaining_discount ) {
				break;
			}
		}
		return $total_discount;
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
		if ( ! $coupon->get_id() ) {
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

		if ( $coupon->get_usage_limit_per_user() > 0 && is_user_logged_in() && $coupon->get_id() && $coupon->get_data_store() ) {
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
		if ( ! $this->items && $coupon->is_type( wc_get_product_coupon_types() ) ) {
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

			if ( ! apply_filters( 'woocommerce_discount_is_coupon_valid', true, $coupon, $this ) ) {
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
			$message = apply_filters( 'woocommerce_coupon_error', $e->getMessage(), $e->getCode(), $coupon );

			return new WP_Error( 'invalid_coupon', $message, array(
				'status' => 400,
			) );
		}
		return true;
	}
}
