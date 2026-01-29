<?php
/**
 * WooCommerce Checkout Hook Implementation
 *
 * @package WooCommerce
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Checkout_Hook Class.
 */
class WC_Checkout_Hook {

	/**
	 * Initialize the hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_review_order_after_cart_item_meta', array( __CLASS__, 'cart_item_meta_hook' ), 10, 3 );
		add_action( 'wp_footer', array( __CLASS__, 'block_checkout_support' ) );
	}

	/**
	 * Cart item meta hook for classic checkout.
	 *
	 * @param WC_Product $_product     Product object.
	 * @param array        $cart_item  Cart item.
	 * @param string       $cart_item_key Cart item key.
	 */
	public static function cart_item_meta_hook( $_product, $cart_item, $cart_item_key ) {
		do_action( 'woocommerce_review_order_after_cart_item_meta', $_product, $cart_item, $cart_item_key );
	}

	/**
	 * Block checkout support.
	 */
	public static function block_checkout_support() {
		?>
		<script>
		document.addEventListener( 'DOMContentLoaded', function() {
			function initBlockCheckoutHooks() {
				var cartItems = document.querySelectorAll( '.wc-block-components-order-summary-item' );
				
				cartItems.forEach( function( cartItem ) {
					if ( cartItem.querySelector( '.wc-block-cart-item-meta-hook' ) ) {
						return;
					}
					
					var description = cartItem.querySelector( '.wc-block-components-order-summary-item__description' );
					if ( description ) {
						var hookContainer = document.createElement( 'div' );
						hookContainer.className = 'wc-block-cart-item-meta-hook';
						
						description.appendChild( hookContainer );
						
						// Check if user has hooked into this action
						if ( window.wc && window.wc.blocks && window.wc.blocks.cartItemMetaHooks ) {
							window.wc.blocks.cartItemMetaHooks.forEach( function( callback ) {
								if ( typeof callback === 'function' ) {
									callback( hookContainer, cartItem );
								}
							} );
						}
					}
				} );
			}
			
			initBlockCheckoutHooks();
			setTimeout( initBlockCheckoutHooks, 1000 );
			setTimeout( initBlockCheckoutHooks, 2000 );
			
			var observer = new MutationObserver( function( mutations ) {
				mutations.forEach( function( mutation ) {
					if ( mutation.addedNodes.length ) {
						setTimeout( initBlockCheckoutHooks, 500 );
					}
				} );
			} );
			
			observer.observe( document.body, {
				childList: true,
				subtree: true
			} );
		} );
		</script>
		<?php
	}
}

// Initialize the hooks.
WC_Checkout_Hook::init();
