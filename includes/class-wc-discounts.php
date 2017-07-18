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
	 * An array of discounts which have been applied.
	 *
	 * @var array
	 */
	protected $discounts = array();

	/**
	 * Reset items and discounts to 0.
	 */
	protected function reset() {
		$this->items     = array();
		$this->discounts = array( 'cart' => 0 );
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
					'price'    => 0, // Line price without discounts.
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
					$item->product  = $raw_item->get_product();
				} else {
					$item->key      = $raw_item['key'];
					// @todo remove when we implement WC_Cart_Item. This is the old cart item schema.
					$item->quantity = $raw_item['quantity'];
					$item->price    = $raw_item['data']->get_price() * $raw_item['quantity'];
					$item->product  = $raw_item['data'];
				}
				$this->items[ $item->key ]     = $item;
				$this->discounts[ $item->key ] = 0;
			}
		}
	}

	/**
	 * Get all discount totals.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_discounts() {
		return $this->discounts;
	}

	/**
	 * Get discount by key.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_discount( $key ) {
		return isset( $this->discounts[ $key ] ) ? $this->discounts[ $key ] : 0;
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
		switch ( $coupon->get_discount_type() ) {
			case 'percent' :
				$this->apply_percentage_discount( $coupon->get_amount() );
				break;
			case 'fixed_product' :
				$this->apply_fixed_product_discount( $coupon->get_amount() );
				break;
			case 'fixed_cart' :
				$this->apply_fixed_cart_discount( $coupon->get_amount() );
				break;
		}
	}

	/**
	 * Get discounted price of an item.
	 *
	 * @since  3.2.0
	 * @param  object $item
	 * @return float
	 */
	public function get_discounted_price( $item ) {
		return $item->price - $this->discounts[ $item->key ];
	}

	/**
	 * Apply a discount amount to an item and ensure it does not go negative.
	 *
	 * @since  3.2.0
	 * @param  object $item
	 * @param  float $discount
	 * @return float
	 */
	protected function apply_discount_to_item( &$item, $discount ) {
		$discounted_price              = $this->get_discounted_price( $item );
		$discount                      = $discount > $discounted_price ? $discounted_price : $discount;
		$this->discounts[ $item->key ] = $this->discounts[ $item->key ] + $discount;
		return $discount;
	}

	/**
	 * Apply a discount amount to the cart.
	 *
	 * @since  3.2.0
	 * @param  object $item
	 * @param  float $discount
	 */
	protected function apply_discount_to_cart( $discount ) {
		$this->discounts['cart'] += $discount;
	}

	/**
	 * Apply percent discount to items.
	 *
	 * @since  3.2.0
	 * @param  float $amount
	 */
	protected function apply_percentage_discount( $amount ) {
		foreach ( $this->items as $item ) {
			$this->apply_discount_to_item( $item, (float) $amount * ( $this->get_discounted_price( $item ) / 100 ) );
		}
	}

	/**
	 * Apply fixed product discount to items.
	 *
	 * @since  3.2.0
	 * @param  float $amount
	 */
	protected function apply_fixed_product_discount( $discount ) {
		foreach ( $this->items as $item ) {
			$this->apply_discount_to_item( $item, $discount * $item->quantity );
		}
	}

	/*protected function filter_taxable_items( $item ) {
		return $item->is_taxable;
	}*/

	/**
	 * Apply fixed cart discount to items. Cart discounts will be stored and
	 * displayed separately to items, and taxes will be apportioned.
	 *
	 * @since  3.2.0
	 * @param float $cart_discount
	 */
	protected function apply_fixed_cart_discount( $cart_discount ) {
		// @todo Fixed cart discounts will be apportioned based on tax class.
		/*$tax_class_counts    = array_count_values( wp_list_pluck( array_filter( $this->items, array( $this, 'filter_taxable_items' ) ), 'tax_class' ) );
		$item_count          = array_sum( wp_list_pluck( $this->items, 'quantity' ) );
		$cart_discount_taxes = array();

		foreach ( $tax_class_counts as $tax_class => $tax_class_count ) {
			$proportion                        = $tax_class_count / $item_count;
			$cart_discount_proportion          = $cart_discount * $proportion;
			$tax_rates                         = WC_Tax::get_rates( $tax_class );
			$cart_discount_taxes[ $tax_class ] = WC_Tax::calc_tax( $cart_discount_proportion, $tax_rates );
		}

		var_dump($cart_discount_taxes);*/
		$this->apply_discount_to_cart( $cart_discount );
	}
}
