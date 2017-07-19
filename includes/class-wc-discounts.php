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
	 * An array of discounts which have been applied.
	 *
	 * @var array
	 */
	protected $discounts = array();

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
	 * Reset items and discounts to 0.
	 */
	protected function reset() {
		$this->items     = array();
		$this->discounts = array();
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
	 * Get all discount totals without precision.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_discounts() {
		return array_map( array( $this, 'remove_precision' ), $this->discounts );
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
	 * Set cart/order items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param array $raw_items List of raw cart or order items.
	 */
	public function set_items( $raw_items ) {
		$this->reset();

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
				$this->discounts[ $item->key ] = 0;
			}
		}
	}

	/**
	 * Apply a discount to all items using a coupon.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon
	 * @return bool True if applied.
	 */
	public function apply_coupon( $coupon ) {
		if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
			return false;
		}


		/* @todo add this logic here somewhere
		if ( null === $this->get_limit_usage_to_x_items() ) {
			$limit_usage_qty = $cart_item_qty;
		} else {
			$limit_usage_qty = min( $this->get_limit_usage_to_x_items(), $cart_item_qty );

			$this->set_limit_usage_to_x_items( max( 0, ( $this->get_limit_usage_to_x_items() - $limit_usage_qty ) ) );
		}
		if ( $single ) {
			$discount = ( $discount * $limit_usage_qty ) / $cart_item_qty;
		} else {
			$discount = ( $discount / $cart_item_qty ) * $limit_usage_qty;
		}*/

		// @todo how can we support the old woocommerce_coupon_get_discount_amount filter?




		switch ( $coupon->get_discount_type() ) {
			case 'percent' :
				$this->apply_percentage_discount( $coupon->get_amount() );
				break;
			case 'fixed_product' :
				$this->apply_fixed_product_discount( $coupon->get_amount() * $this->precision );
				break;
			case 'fixed_cart' :
				$this->apply_fixed_cart_discount( $coupon->get_amount() * $this->precision );
				break;
		}
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
	 * @param  float $amount
	 */
	protected function apply_percentage_discount( $amount ) {
		foreach ( $this->items as $item ) {
			$this->add_item_discount( $item, $amount * ( $this->get_discounted_price_in_cents( $item ) / 100 ) );
		}
	}

	/**
	 * Apply fixed product discount to items.
	 *
	 * @since  3.2.0
	 * @param  int $amount
	 */
	protected function apply_fixed_product_discount( $discount ) {
		foreach ( $this->items as $item ) {
			$this->add_item_discount( $item, $discount * $item->quantity );
		}
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
	 * Apply fixed cart discount to items.
	 *
	 * @since 3.2.0
	 * @param int $cart_discount
	 */
	protected function apply_fixed_cart_discount( $cart_discount ) {
		$items_to_discount = array_filter( $this->items, array( $this, 'filter_products_with_price' ) );

		if ( ! $item_count = array_sum( wp_list_pluck( $items_to_discount, 'quantity' ) ) ) {
			return;
		}

		$per_item_discount = floor( $cart_discount / $item_count ); // round it down to the nearest cent number.
		$amount_discounted = 0;

		if ( $per_item_discount > 0 ) {
			foreach ( $items_to_discount as $item ) {
				$amount_discounted += $this->add_item_discount( $item, $per_item_discount * $item->quantity );
			}

			/**
			 * If there is still discount remaining, repeat the process.
			 */
			if ( $amount_discounted > 0 && $amount_discounted < $cart_discount ) {
				$this->apply_fixed_cart_discount( $cart_discount - $amount_discounted );
			}
			return;
		}

		/**
		 * Deal with remaining fractional discounts by splitting it over items
		 * until the amount is expired, discounting 1 cent at a time.
		 */
		if ( $cart_discount > 0 ) {
			foreach ( $items_to_discount as $item ) {
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
	}
}
