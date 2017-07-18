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
 */
class WC_Discounts {

	/**
	 * An array of items to discount.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * An array of coupons which have been applied.
	 *
	 * @var array
	 */
	protected $coupons = array();

	/**
	 * An array of discounts which have been applied.
	 *
	 * @var array
	 */
	protected $discounts = array();

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
	 * Set cart/order items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param array $raw_items List of raw cart or order items.
	 */
	public function set_items( $raw_items ) {
		$this->items = array();

		if ( ! empty( $raw_items ) && is_array( $raw_items ) ) {
			foreach ( $raw_items as $raw_item ) {
				$item = (object) array(
					'price'            => 0, // Unit price without discounts.
					'discounted_price' => 0, // Unit price with discounts.
					'quantity'         => 0, // Line qty.
				);

				if ( is_a( $raw_item, 'WC_Cart_Item' ) ) {
					$item->quantity = $raw_item->get_quantity();
					$item->price    = $raw_item->get_price();
				} elseif ( is_a( $raw_item, 'WC_Order_Item_Product' ) ) {
					$item->quantity = $raw_item->get_quantity();
					$item->price    = $raw_item->get_subtotal();
				} else {
					// @todo remove when we implement WC_Cart_Item. This is the old cart item schema.
					$item->quantity = $raw_item['quantity'];
					$item->price    = $raw_item['data']->get_price();
				}

				$item->discounted_price = $item->price;
				$this->items[] = $item;
			}
		}
	}

	public function get_discounted_totals() {}

	public function get_applied_discounts() {}

	public function get_applied_coupons() {}

	/**
	 * Get all discount totals.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_discounts() {
		return array(
			'items',
			'discount_totals' => array(
				// 'code' => 'amount'
			)
		);
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
		$items  = $this->get_items();
		$type   = $coupon->get_discount_type();
		$amount = $coupon->get_amount();

		switch ( $type ) {
			case 'percent' :
				$this->apply_percentage_discount( $amount );
				break;
			case 'fixed_product' :
				$this->apply_fixed_product_discount( $amount );
				break;
			case 'fixed_cart' :
				$this->apply_fixed_cart_discount( $amount );
				break;
		}
	}

	/**
	 * Apply percent discount to items.
	 *
	 * @param  float $amount
	 */
	private function apply_percentage_discount( $amount ) {
		foreach ( $this->items as $item ) {
			$this->apply_discount_to_item( $item, (float) $amount * ( $item->discounted_price / 100 ) );
		}
	}

	/**
	 * Apply fixed product discount to items.
	 *
	 * @param  float $amount
	 */
	private function apply_fixed_product_discount( $discount ) {
		foreach ( $this->items as $item ) {
			$this->apply_discount_to_item( $item, $discount );
		}
	}

	/**
	 * Apply fixed cart discount to items.
	 *
	 * @param  float $amount
	 */
	private function apply_fixed_cart_discount( $total_amount ) {
		// Fixed amount needs to be divided equally between items.
		$item_count = array_sum( wp_list_pluck( $this->items, 'quantity' ) );
		$discount   = floor( $total_amount / $item_count );
		$discounted = 0; // Keep track of what actually gets discounted, since some products may be cheaper than the discount.

		if ( 0 < $discount ) {
			foreach ( $this->items as $item ) {
				$discounted += $this->apply_discount_to_item( $item, $discount );
			}

			// Anything left?
			if ( $discounted && ( $remaining_discount = $total_amount - $discounted ) ) {
				$this->apply_fixed_cart_discount( $remaining_discount );
			}

		// Amount is too small to divide equally.
		} elseif ( $total_amount ) {
			$discount = $total_amount;

			while ( $discount > 0 ) {
				$did_discount = false;

				foreach ( $this->items as $item ) {
					if ( $this->apply_discount_to_item( $item, 0.1 ) ) {
						$discount     -= 0.1;
						$did_discount = true;
					}
				}

				if ( ! $did_discount ) {
					break;
				}
			}
		}
	}

	/**
	 * Apply a discount amount to an item and ensure it does not go negative.
	 *
	 * @param  object $item
	 * @param  float $discount
	 * @return float
	 */
	private function apply_discount_to_item( &$item, $discount ) {
		if ( $discount > $item->discounted_price ) {
			$discount = $item->discounted_price;
		}
		$item->discounted_price = $item->discounted_price - $discount;
		return $discount;
	}
}
