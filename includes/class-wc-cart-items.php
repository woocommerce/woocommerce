<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart Items.
 *
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Items {

	/**
	 * An array of items in the cart.
	 * @var object[]
	 */
	private $items = array();

	/**
	 * An array of items removed from the cart which can be restored.
	 * @var object[]
	 */
	private $removed_items = array();

	/**
	 * When data has been added, this is set to true so totals can be recalculated.
	 * @var boolean
	 */
	private $dirty = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_items' ), 1 );
	}

	/**
 	 * Get default blank set of props used per item.
 	 * @return array
 	 */
 	private function get_default_item_props() {
 		return (object) array(
			'product'            => false,
 			'quantity'           => 0,
			'price'              => 0,
			'weight'             => 0,
			'tax_class'          => '',
			'price_includes_tax' => wc_prices_include_tax(),
 			'subtotal'           => 0,
 			'subtotal_tax'       => 0,
 			'subtotal_tax_data'  => array(),
 			'total'              => 0,
 			'tax'                => 0,
 			'tax_data'           => array(),
 			'discounted_price'   => 0,
 		);
 	}

	/**
	 * Set items array.
	 * @param array $items
	 */
	public function set_items( $items = array() ) {
		foreach ( $items as $item_key => $maybe_item ) {
			$this->add_item( $maybe_item, $item_key );
		}
		$this->dirty = true;
	}

	/**
	 * Add an item.
	 */
	public function add_item( $maybe_item, $item_key = '' ) {
		if ( ! is_array( $maybe_item ) || ! isset( $maybe_item['data'], $maybe_item['quantity'] ) ) {
			return;
		}
		if ( ! $item_key ) {
			$item_key = $this->generate_id( $maybe_item );
		}
		$item = $this->get_default_item_props();

		$item->values    = $maybe_item;
		$item->product   = $maybe_item['data'];
		$item->quantity  = $maybe_item['quantity'];
		$item->price     = $maybe_item['data']->get_price();
		$item->weight    = $maybe_item['data']->get_weight();
		$item->tax_class = $maybe_item['data']->get_tax_class();

		$this->items[ $item_key ] = $item;
	}

	/**
	 * Get all items.
	 * @return array
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Get an item by it's key.
	 *
	 * @param  string $item_key
	 * @return array
	 */
	public function get_item_by_key( $item_key ) {
		return isset( $this->items[ $item_key ] ) ? $this->items[ $item_key ] : array();
	}

	/**
	 * Generate a unique ID for the cart item being added.
	 *
	 * @param array $item Cart item.
	 * @return string cart item key
	 */
	public function generate_id( $item ) {
		unset( $item['quantity'], $item['data'] );
		return md5( json_encode( $item ) );
	}

	/**
	 * Validate a product before adding to cart.
	 * @param  WC_Product $product
	 * @throws Exception
	 */
	public function validate_item( $product, $adding_quantity = 0 ) {
		$products_qty_in_cart  = $this->get_item_quantities();
		$managing_stock        = $product->managing_stock();
		$check_variation_stock = $product->is_type( 'variation' ) && true === $managing_stock;

		if ( empty( $product ) || ! $product->exists() || ! $product->is_purchasable() || 'trash' === $product->post->post_status ) {
			throw new Exception( __( 'Sorry, this product cannot be purchased.', 'woocommerce' ) );
		}

		if ( ! $product->is_in_stock() ) {
			throw new Exception( sprintf( __( 'You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'woocommerce' ), $product->get_title() ) );
		}

		// Force quantity to 1 if sold individually and check for existing item in cart
		if ( $product->is_sold_individually() ) {
			$in_cart_quantity = $product->variation_id ? $products_qty_in_cart[ $product->variation_id ] : $products_qty_in_cart[ $product->id ];

			if ( $in_cart_quantity > 0 ) {
				throw new Exception( sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', wc_get_cart_url(), __( 'View Cart', 'woocommerce' ), sprintf( __( 'You cannot add another &quot;%s&quot; to your cart.', 'woocommerce' ), $product->get_title() ) ) );
			}
		}

		if ( ! $product->has_enough_stock( $adding_quantity ) ) {
			throw new Exception( sprintf(__( 'You cannot add that amount of &quot;%s&quot; to the cart because there is not enough stock (%s remaining).', 'woocommerce' ), $product->get_title(), $product->get_stock_quantity() ) );
		}

		// Stock check - this time accounting for whats already in-cart
		$check_id  = $check_variation_stock ? $product->variation_id : $product->id;
		$check_qty = isset( $products_qty_in_cart[ $check_id ] ) ? $products_qty_in_cart[ $check_id ] : 0;

		/**
		 * Check stock based on all items in the cart.
		 */
		if ( ! $product->has_enough_stock( $check_qty + $adding_quantity ) ) {
			throw new Exception( '<a href="' . esc_url( wc_get_cart_url() ) . '" class="button wc-forward">' . __( 'View Cart', 'woocommerce' ) . '</a> ' . sprintf( __( 'You cannot add that amount to the cart &mdash; we have %s in stock and you already have %s in your cart.', 'woocommerce' ), $product->get_stock_quantity(), $check_qty ) );
		}

		/**
		 * Finally consider any held stock, from pending orders.
		 */
		if ( ! $product->has_enough_stock( $check_qty + $adding_quantity + wc_get_held_stock_count( $product ) ) ) {
			throw new Exception( '<a href="' . esc_url( wc_get_cart_url() ) . '" class="button wc-forward">' . __( 'View Cart', 'woocommerce' ) . '</a> ' . sprintf(__( 'Sorry, we do not have enough "%s" in stock to fulfill your order right now. Please try again in %d minutes or edit your cart and try again. We apologise for any inconvenience caused.', 'woocommerce' ), $product->get_title(), get_option( 'woocommerce_hold_stock_minutes' ) ) );
		}
	}

	/**
	 * Set the quantity for an item in the cart.
	 *
	 * @param string	$cart_item_key	contains the id of the cart item
	 * @param int		$quantity		contains the quantity of the item
	 */
	public function set_quantity( $cart_item_key, $quantity = 1 ) {
		if ( $quantity <= 0 ) {
			$this->remove_item( $cart_item_key );
		} else {
			$old_quantity = $this->items[ $cart_item_key ]['quantity'];
			$this->items[ $cart_item_key ]['quantity'] = $quantity;
			$this->dirty = true;
			do_action( 'woocommerce_after_cart_item_quantity_update', $cart_item_key, $quantity, $old_quantity );
		}
	}

	/**
	 * Remove a cart item.
	 * @param  string $cart_item_key
	 * @return bool
	 */
	public function remove_item( $cart_item_key ) {
		if ( isset( $this->items[ $cart_item_key ] ) ) {
			do_action( 'woocommerce_remove_cart_item', $cart_item_key, $this );

			$this->removed_items[ $cart_item_key ] = $this->items[ $cart_item_key ];
			$this->dirty = true;
			unset( $this->removed_items[ $cart_item_key ]['data'] );
			unset( $this->items[ $cart_item_key ] );

			do_action( 'woocommerce_cart_item_removed', $cart_item_key, $this );
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
			do_action( 'woocommerce_restore_cart_item', $cart_item_key, $this );

			$this->items[ $cart_item_key ] = $this->removed_items[ $cart_item_key ];
			$this->items[ $cart_item_key ]['data'] = wc_get_product( $this->items[ $cart_item_key ]['variation_id'] ? $this->items[ $cart_item_key ]['variation_id'] : $this->items[ $cart_item_key ]['product_id'] );
			$this->dirty = true;
			unset( $this->removed_items[ $cart_item_key ] );

			do_action( 'woocommerce_cart_item_restored', $cart_item_key, $this );
			return true;
		}
		return false;
	}

	/**
	 * Check all cart items for errors.
	 */
	public function check_items() {
		try {
			foreach ( $this->get_items() as $item ) {
				$this->validate_item( $item['data'] );
			}
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
		}

	}

	/**
	 * Get cart items quantities - merged so we can do accurate stock checks on items across multiple lines.
	 * @return array
	 */
	public function get_item_quantities() {
		$quantities = array();

		foreach ( $this->get_items() as $cart_item_key => $values ) {
			$product           = $values['data'];
			$id                = $values[ $product->is_type( 'variation' ) && true === $product->managing_stock() ? 'variation_id' : 'product_id' ];
			$quantities[ $id ] = isset( $quantities[ $id ] ) ? $quantities[ $id ] + $values['quantity'] : $values['quantity'];
		}

		return $quantities;
	}

	/**
	 * Get number of items in the cart.
	 * @return int
	 */
	public function get_item_count() {
		return apply_filters( 'woocommerce_cart_contents_count', array_sum( wp_list_pluck( $this->items, 'quantity' ) ) );
	}

	/**
	 * Get all tax classes for items in the cart.
	 * @return array
	 */
	public function get_item_tax_classes() {
		return array_unique( wp_list_pluck( $this->items, 'tax_class' ) );
	}

	/**
	 * Get weight of items in the cart.
	 * @return int
	 */
	public function get_item_weight() {
		return apply_filters( 'woocommerce_cart_contents_weight', array_sum( wp_list_pluck( $this->items, 'weight' ) ) );
	}
}
