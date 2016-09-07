<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collection of Cart Items.
 *
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Items {

	/**
	 * An array of items in the cart.
	 * @var WC_Cart_Item[]
	 */
	private $items = array();

	/**
	 * An array of items removed from the cart which can be restored.
	 * @var WC_Cart_Item[]
	 */
	private $removed_items = array();

	/**
	 * Generate a unique ID for the cart item being added.
	 *
	 * @param array $item Cart item.
	 * @return string cart item key
	 */
	public function generate_key( $item ) {
		unset( $item['quantity'] );
		return md5( json_encode( $item ) );
	}

	/**
	 * Get an item by it's key.
	 *
	 * @param  string $item_key
	 * @return WC_Cart_Item|bool
	 */
	public function get_item_by_key( $item_key ) {
		return isset( $this->items[ $item_key ] ) ? $this->items[ $item_key ] : false;
	}

	/**
	 * Get items.
	 * @return array
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Get removed items.
	 * @return array
	 */
	public function get_removed_items() {
		return $this->removed_items;
	}

	/**
	 * Set items.
	 * @param array $items
	 */
	public function set_items( $items ) {
		$this->items = array();
		$items       = array_filter( (array) $items );

		foreach ( $items as $key => $item ) {
			if ( ! is_a( $item, 'WC_Cart_Item' ) ) {
				$item = new WC_Cart_Item( $item );
			}
			$this->items[ $key ] = $item;
		}
	}

	/**
	 * Set items.
	 * @param array $items
	 */
	public function set_removed_items( $items ) {
		$this->removed_items = array();
		$items               = array_filter( (array) $items );

		foreach ( $items as $key => $item ) {
			if ( ! is_a( $item, 'WC_Cart_Item' ) ) {
				$item = new WC_Cart_Item( $item );
			}
			$this->removed_items[ $key ] = $item;
		}
	}

	/**
	 * Remove a cart item.
	 * @param  string $cart_item_key
	 * @return bool
	 */
	public function remove_item( $cart_item_key ) {
		if ( isset( $this->items[ $cart_item_key ] ) ) {
			do_action( 'woocommerce_remove_cart_item', $cart_item_key, WC()->cart );

			$this->removed_items[ $cart_item_key ] = $this->items[ $cart_item_key ];
			unset( $this->items[ $cart_item_key ] );

			do_action( 'woocommerce_cart_item_removed', $cart_item_key, WC()->cart );
			return true;
		}
		return false;
	}

	/**
	 * Restore a cart item.
	 * @param  string $cart_item_key
	 * @return bool
	 */
	public function restore_item( $cart_item_key ) {
		if ( isset( $this->removed_items[ $cart_item_key ] ) ) {
			do_action( 'woocommerce_restore_cart_item', $cart_item_key, WC()->cart );

			$this->items[ $cart_item_key ] = new WC_Cart_Item( $this->removed_items[ $cart_item_key ] );
			unset( $this->removed_items[ $cart_item_key ] );

			do_action( 'woocommerce_cart_item_restored', $cart_item_key, WC()->cart );
			return true;
		}
		return false;
	}

	/**
	 * Filter items needing shipping callback.
	 * @return bool
	 */
	protected function filter_items_needing_shipping( $item ) {
		$product = $item->get_product();
		return $product && $product->needs_shipping();
	}

	/**
	 * Get only items that need shipping.
	 * @return array
	 */
	public function get_items_needing_shipping() {
		return array_filter( $this->get_items(), array( $this, 'filter_items_needing_shipping' ) );
	}

	/**
	 * Check all cart items for errors.
	 */
	public function check_items() {
		try {
			foreach ( $this->get_items() as $cart_item_key => $item ) {
				$product = $item->get_product();

				if ( ! $product || ! $product->is_purchasable() ) {
					unset( $this->items[ $cart_item_key ] );
					wc_add_notice( __( 'An item which is no longer available was removed from your cart.', 'woocommerce' ), 'error' );
					continue;
				}

				wc_add_to_cart_validate_stock( $product );
			}
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
		}
	}

	/**
	 * Get number of items in the cart.
	 * @return int
	 */
	public function get_quantity() {
		return apply_filters( 'woocommerce_cart_contents_count', array_sum( wc_list_pluck( $this->items, 'get_quantity' ) ) );
	}

	/**
	 * Get all tax classes for items in the cart.
	 * @return array
	 */
	public function get_tax_classes() {
		return array_unique( array_values( wc_list_pluck( $this->items, 'get_tax_class' ) ) );
	}

	/**
	 * Get weight of items in the cart.
	 * @return int
	 */
	public function get_weight() {
		return apply_filters( 'woocommerce_cart_contents_weight', array_sum( wc_list_pluck( $this->items, 'get_weight' ) ) );
	}
}
