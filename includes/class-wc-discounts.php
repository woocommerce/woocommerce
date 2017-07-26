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
	 *
	 * @param array $items Items to discount.
	 */
	public function __construct( $items = array() ) {
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
	 * @param  string $key name of discount row to return.
	 * @return array
	 */
	public function get_discount( $key ) {
		return isset( $this->discounts[ $key ] ) ? $this->remove_precision( $this->discounts[ $key ] ) : 0;
	}

	/**
	 * Get all discount totals with precision.
	 *
	 * @since  3.2.0
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_discounts( $in_cents = false ) {
		return $in_cents ? $this->discounts : array_map( array( $this, 'remove_precision' ), $this->discounts );
	}

	/**
	 * Get discounted price of an item without precision.
	 *
	 * @since  3.2.0
	 * @param  object $item Get data for this item.
	 * @return float
	 */
	public function get_discounted_price( $item ) {
		return $this->remove_precision( $this->get_discounted_price_in_cents( $item ) );
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
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon Coupon object being applied to the items.
	 * @return bool|WP_Error True if applied or WP_Error instance in failure.
	 */
	public function apply_coupon( $coupon ) {
		if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
			return false;
		}

		if ( ! isset( $this->applied_coupons[ $coupon->get_code() ] ) ) {
			$this->applied_coupons[ $coupon->get_code() ] = 0;
		}

		$is_coupon_valid = $this->is_coupon_valid( $coupon );
		if ( is_wp_error( $is_coupon_valid ) ) {
			return $is_coupon_valid;
		}

		// @todo how can we support the old woocommerce_coupon_get_discount_amount filter?
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
	 * Sort by price.
	 *
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
	 * @param  object $item Get data for this item.
	 * @return bool
	 */
	protected function filter_products_with_price( $item ) {
		return $this->get_discounted_price_in_cents( $item ) > 0;
	}

	/**
	 * Get items which the coupon should be applied to.
	 *
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

		$cart_items = $this->get_cart_items_backwards_compatibility();

		foreach ( $this->items as $item ) {
			if ( 0 === $this->get_discounted_price_in_cents( $item ) ) {
				continue;
			}
			if ( ! $coupon->is_valid_for_product( $item->product, $cart_items[ $item->key ] ) && ! $coupon->is_valid_for_cart() ) { // @todo is this enough?
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
	 * @param  object $item Get data for this item.
	 * @param  int    $discount Amount of discount.
	 * @return int    Amount discounted.
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
	 * @param  array $items_to_apply Array of items to apply the coupon to.
	 * @param  int   $amount Amount of discount.
	 * @return int   total discounted in cents
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
	 * @param  int   $discount Amount of discout.
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
	 * @param  int   $cart_discount Fixed discount amount to apply.
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
		} elseif ( $cart_discount > 0 ) {
			/**
			 * Deal with remaining fractional discounts by splitting it over items
			 * until the amount is expired, discounting 1 cent at a time.
			 */
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

			$cart_items = $this->get_cart_items_backwards_compatibility();
			foreach ( $this->items as $item ) {
				if ( $item->product && $coupon->is_valid_for_product( $item->product, $cart_items[ $item->key ] ) ) {
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
			$error_message = $e->getMessage();
			$error_code    = $e->getCode();

			/**
			 * Coupon error message.
			 *
			 * Codes:
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
			 * @param string    $error_message Error message.
			 * @param int       $error_code    Error code.
			 * @param WC_Coupon $coupon        Coupon data.
			 */
			$message = apply_filters( 'woocommerce_coupon_error', $error_message, $error_code, $coupon );

			return new WP_Error( 'invalid_coupon', $message, array(
				'status' => 400,
			) );
		} // End try().

		return true;
	}

	/**
	 * Backwards compatibility method to get cart items.
	 *
	 * @return array
	 */
	protected function get_cart_items_backwards_compatibility() {
		$items = array();

		foreach ( $this->items as $item ) {
			$is_variable = $item->product->is_type( 'variation' );
			$items[ $item->key ] = array(
				'key'          => $item->key,
				'product_id'   => $is_variable ? $item->product->get_parent_id() : $item->product->get_id(),
				'variation_id' => $is_variable ? $item->product->get_id() : 0,
				'variation'    => $is_variable ? $item->product->get_variation_attributes() : array(),
				'quantity'     => $item->quantity,
				'data'         => $item->product,
			);
		}

		return $items;
	}
}
