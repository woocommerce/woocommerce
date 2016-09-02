<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart Items.
 *
 * @class 		WC_Cart_Items
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

	public function __construct() {
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_items' ), 1 );
	}

	/**
	 * Check if product is in the cart and return cart item key.
	 *
	 * Cart item key will be unique based on the item and its properties, such as variations.
	 *
	 * @param mixed id of product to find in the cart
	 * @return string cart item key
	 */
	public function find_product_in_cart( $cart_id = false ) {
		if ( $cart_id !== false ) {
			if ( is_array( $this->cart_contents ) && isset( $this->cart_contents[ $cart_id ] ) ) {
				return $cart_id;
			}
		}
		return '';
	}

	/**
	 * Generate a unique ID for the cart item being added.
	 *
	 * @param int $product_id - id of the product the key is being generated for
	 * @param int $variation_id of the product the key is being generated for
	 * @param array $variation data for the cart item
	 * @param array $cart_item_data other cart item data passed which affects this items uniqueness in the cart
	 * @return string cart item key
	 */
	public function generate_cart_id( $product_id, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		return apply_filters( 'woocommerce_cart_id', md5( json_encode( array( $product_id, $variation_id, $variation, $cart_item_data ) ) ), $product_id, $variation_id, $variation, $cart_item_data );
	}

	/**
	 * Add a product to the cart.
	 *
	 * @param int $product_id contains the id of the product to add to the cart
	 * @param int $quantity contains the quantity of the item to add
	 * @param int $variation_id
	 * @param array $variation attribute values
	 * @param array $cart_item_data extra cart item data we want to pass into the item
	 * @return string|bool $cart_item_key
	 */
	public function add_to_cart( $product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		// Wrap in try catch so plugins can throw an exception to prevent adding to cart
		try {
			$product_id   = absint( $product_id );
			$variation_id = absint( $variation_id );

			// Ensure we don't add a variation to the cart directly by variation ID
			if ( 'product_variation' == get_post_type( $product_id ) ) {
				$variation_id = $product_id;
				$product_id   = wp_get_post_parent_id( $variation_id );
			}

			// Get the product
			$product_data = wc_get_product( $variation_id ? $variation_id : $product_id );

			// Sanity check
			if ( $quantity <= 0 || ! $product_data || 'trash' === $product_data->post->post_status  ) {
				return false;
			}

			// Load cart item data - may be added by other plugins
			$cart_item_data = (array) apply_filters( 'woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id );

			// Generate a ID based on product ID, variation ID, variation data, and other cart item data
			$cart_id        = $this->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );

			// Find the cart item key in the existing cart
			$cart_item_key  = $this->find_product_in_cart( $cart_id );

			// Force quantity to 1 if sold individually and check for existing item in cart
			if ( $product_data->is_sold_individually() ) {
				$quantity         = apply_filters( 'woocommerce_add_to_cart_sold_individually_quantity', 1, $quantity, $product_id, $variation_id, $cart_item_data );
				$in_cart_quantity = $cart_item_key ? $this->cart_contents[ $cart_item_key ]['quantity'] : 0;

				if ( $in_cart_quantity > 0 ) {
					throw new Exception( sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', wc_get_cart_url(), __( 'View Cart', 'woocommerce' ), sprintf( __( 'You cannot add another &quot;%s&quot; to your cart.', 'woocommerce' ), $product_data->get_title() ) ) );
				}
			}

			// Check product is_purchasable
			if ( ! $product_data->is_purchasable() ) {
				throw new Exception( __( 'Sorry, this product cannot be purchased.', 'woocommerce' ) );
			}

			// Stock check - only check if we're managing stock and backorders are not allowed
			if ( ! $product_data->is_in_stock() ) {
				throw new Exception( sprintf( __( 'You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'woocommerce' ), $product_data->get_title() ) );
			}

			if ( ! $product_data->has_enough_stock( $quantity ) ) {
				throw new Exception( sprintf(__( 'You cannot add that amount of &quot;%s&quot; to the cart because there is not enough stock (%s remaining).', 'woocommerce' ), $product_data->get_title(), $product_data->get_stock_quantity() ) );
			}

			// Stock check - this time accounting for whats already in-cart
			if ( $managing_stock = $product_data->managing_stock() ) {
				$products_qty_in_cart = $this->get_cart_item_quantities();

				if ( $product_data->is_type( 'variation' ) && true === $managing_stock ) {
					$check_qty = isset( $products_qty_in_cart[ $variation_id ] ) ? $products_qty_in_cart[ $variation_id ] : 0;
				} else {
					$check_qty = isset( $products_qty_in_cart[ $product_id ] ) ? $products_qty_in_cart[ $product_id ] : 0;
				}

				/**
				 * Check stock based on all items in the cart.
				 */
				if ( ! $product_data->has_enough_stock( $check_qty + $quantity ) ) {
					throw new Exception( sprintf(
						'<a href="%s" class="button wc-forward">%s</a> %s',
						wc_get_cart_url(),
						__( 'View Cart', 'woocommerce' ),
						sprintf( __( 'You cannot add that amount to the cart &mdash; we have %s in stock and you already have %s in your cart.', 'woocommerce' ), $product_data->get_stock_quantity(), $check_qty )
					) );
				}
			}

			// If cart_item_key is set, the item is already in the cart
			if ( $cart_item_key ) {
				$new_quantity = $quantity + $this->cart_contents[ $cart_item_key ]['quantity'];
				$this->set_quantity( $cart_item_key, $new_quantity, false );
			} else {
				$cart_item_key = $cart_id;

				// Add item after merging with $cart_item_data - hook to allow plugins to modify cart item
				$this->cart_contents[ $cart_item_key ] = apply_filters( 'woocommerce_add_cart_item', array_merge( $cart_item_data, array(
					'product_id'	=> $product_id,
					'variation_id'	=> $variation_id,
					'variation' 	=> $variation,
					'quantity' 		=> $quantity,
					'data'			=> $product_data,
				) ), $cart_item_key );
			}

			if ( did_action( 'wp' ) ) {
				$this->set_cart_cookies( ! $this->is_empty() );
			}

			do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );

			return $cart_item_key;

		} catch ( Exception $e ) {
			if ( $e->getMessage() ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
			return false;
		}
	}

	/**
	 * Remove a cart item.
	 *
	 * @since  2.3.0
	 * @param  string $cart_item_key
	 * @return bool
	 */
	public function remove_cart_item( $cart_item_key ) {
		if ( isset( $this->cart_contents[ $cart_item_key ] ) ) {
			$this->removed_cart_contents[ $cart_item_key ] = $this->cart_contents[ $cart_item_key ];
			unset( $this->removed_cart_contents[ $cart_item_key ]['data'] );

			do_action( 'woocommerce_remove_cart_item', $cart_item_key, $this );

			unset( $this->cart_contents[ $cart_item_key ] );

			do_action( 'woocommerce_cart_item_removed', $cart_item_key, $this );

			$this->calculate_totals();

			return true;
		}

		return false;
	}

	/**
	 * Restore a cart item.
	 *
	 * @param  string $cart_item_key
	 * @return bool
	 */
	public function restore_cart_item( $cart_item_key ) {
		if ( isset( $this->removed_cart_contents[ $cart_item_key ] ) ) {
			$this->cart_contents[ $cart_item_key ] = $this->removed_cart_contents[ $cart_item_key ];
			$this->cart_contents[ $cart_item_key ]['data'] = wc_get_product( $this->cart_contents[ $cart_item_key ]['variation_id'] ? $this->cart_contents[ $cart_item_key ]['variation_id'] : $this->cart_contents[ $cart_item_key ]['product_id'] );

			do_action( 'woocommerce_restore_cart_item', $cart_item_key, $this );

			unset( $this->removed_cart_contents[ $cart_item_key ] );

			do_action( 'woocommerce_cart_item_restored', $cart_item_key, $this );

			$this->calculate_totals();

			return true;
		}

		return false;
	}

	/**
	 * Set the quantity for an item in the cart.
	 *
	 * @param string	$cart_item_key	contains the id of the cart item
	 * @param int		$quantity		contains the quantity of the item
	 * @param bool      $refresh_totals	whether or not to calculate totals after setting the new qty
	 *
	 * @return bool
	 */
	public function set_quantity( $cart_item_key, $quantity = 1, $refresh_totals = true ) {
		if ( $quantity == 0 || $quantity < 0 ) {
			do_action( 'woocommerce_before_cart_item_quantity_zero', $cart_item_key );
			unset( $this->cart_contents[ $cart_item_key ] );
		} else {
			$old_quantity = $this->cart_contents[ $cart_item_key ]['quantity'];
			$this->cart_contents[ $cart_item_key ]['quantity'] = $quantity;
			do_action( 'woocommerce_after_cart_item_quantity_update', $cart_item_key, $quantity, $old_quantity );
		}

		if ( $refresh_totals ) {
			$this->calculate_totals();
		}

		return true;
	}

	/**
	 * Check all cart items for errors.
	 */
	public function check_cart_items() {

		// Result
		$return = true;

		// Check cart item validity
		$result = $this->check_cart_item_validity();

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );
			$return = false;
		}

		// Check item stock
		$result = $this->check_cart_item_stock();

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );
			$return = false;
		}

		return $return;

	}

	/**
	 * Looks through the cart to check each item is in stock. If not, add an error.
	 *
	 * @return bool|WP_Error
	 */
	public function check_cart_item_stock() {
		global $wpdb;

		$error               = new WP_Error();
		$product_qty_in_cart = $this->get_cart_item_quantities();

		// First stock check loop
		foreach ( $this->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];

			/**
			 * Check stock based on stock-status.
			 */
			if ( ! $_product->is_in_stock() ) {
				$error->add( 'out-of-stock', sprintf(__( 'Sorry, "%s" is not in stock. Please edit your cart and try again. We apologise for any inconvenience caused.', 'woocommerce' ), $_product->get_title() ) );
				return $error;
			}

			if ( ! $_product->managing_stock() ) {
				continue;
			}

			$check_qty = $_product->is_type( 'variation' ) && true === $_product->managing_stock() ? $product_qty_in_cart[ $values['variation_id'] ] : $product_qty_in_cart[ $values['product_id'] ];

			/**
			 * Check stock based on all items in the cart.
			 */
			if ( ! $_product->has_enough_stock( $check_qty ) ) {
				$error->add( 'out-of-stock', sprintf(__( 'Sorry, we do not have enough "%s" in stock to fulfill your order (%s in stock). Please edit your cart and try again. We apologise for any inconvenience caused.', 'woocommerce' ), $_product->get_title(), $_product->get_stock_quantity() ) );
				return $error;
			}

			/**
			 * Finally consider any held stock, from pending orders.
			 */
			if ( get_option( 'woocommerce_hold_stock_minutes' ) > 0 && ! $_product->backorders_allowed() ) {
				$order_id   = isset( WC()->session->order_awaiting_payment ) ? absint( WC()->session->order_awaiting_payment ) : 0;
				$held_stock = $wpdb->get_var(
					$wpdb->prepare( "
						SELECT SUM( order_item_meta.meta_value ) AS held_qty
						FROM {$wpdb->posts} AS posts
						LEFT JOIN {$wpdb->prefix}woocommerce_order_items as order_items ON posts.ID = order_items.order_id
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta2 ON order_items.order_item_id = order_item_meta2.order_item_id
						WHERE 	order_item_meta.meta_key   = '_qty'
						AND 	order_item_meta2.meta_key  = %s AND order_item_meta2.meta_value  = %d
						AND 	posts.post_type            IN ( '" . implode( "','", wc_get_order_types() ) . "' )
						AND 	posts.post_status          = 'wc-pending'
						AND		posts.ID                   != %d;",
						$_product->is_type( 'variation' ) && true === $_product->managing_stock() ? '_variation_id' : '_product_id',
						$_product->is_type( 'variation' ) && true === $_product->managing_stock() ? $values['variation_id'] : $values['product_id'],
						$order_id
					)
				);

				$not_enough_stock = false;

				if ( $_product->is_type( 'variation' ) && 'parent' === $_product->managing_stock() && $_product->parent->get_stock_quantity() < ( $held_stock + $check_qty ) ) {
					$not_enough_stock = true;
				} elseif ( $_product->get_stock_quantity() < ( $held_stock + $check_qty ) ) {
					$not_enough_stock = true;
				}
				if ( $not_enough_stock ) {
					$error->add( 'out-of-stock', sprintf(__( 'Sorry, we do not have enough "%s" in stock to fulfill your order right now. Please try again in %d minutes or edit your cart and try again. We apologise for any inconvenience caused.', 'woocommerce' ), $_product->get_title(), get_option( 'woocommerce_hold_stock_minutes' ) ) );
					return $error;
				}
			}
		}

		return true;
	}

}
