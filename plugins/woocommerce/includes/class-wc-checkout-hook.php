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
 * WC_Checkout_Hook Class
 */
class WC_Checkout_Hook {

    /**
     * Initialize the hooks
     */
    public static function init() {
        // Hook after each individual cart item meta (as requested in GitHub issue #36379)
        // Users will implement their own functionality using this hook
        add_action( 'woocommerce_review_order_after_cart_item_meta', array( __CLASS__, 'add_custom_content_after_cart_item_meta' ), 10, 3 );
        
        // Block checkout JavaScript support
        add_action( 'wp_footer', array( __CLASS__, 'add_block_checkout_support' ) );
    }

    /**
     * Hook implementation - users will add their own functionality
     * This is just a placeholder to demonstrate the hook works
     */
    public static function add_custom_content_after_cart_item_meta( $_product, $cart_item, $cart_item_key ) {
        // This hook is now available for users to implement their own functionality
        // Example: do_action( 'woocommerce_review_order_after_cart_item_meta', $_product, $cart_item, $cart_item_key );
        // Users can add their own functions like:
        // add_action( 'woocommerce_review_order_after_cart_item_meta', 'my_custom_function', 10, 3 );
    }

    /**
     * Add JavaScript support for WooCommerce Blocks checkout
     */
    public static function add_block_checkout_support() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function addBlockCheckoutContent() {
                // Find all cart items in block checkout
                const cartItems = document.querySelectorAll('.wc-block-components-order-summary-item');
                
                cartItems.forEach(function(cartItem) {
                    // Check if custom content already exists for this item
                    const existingContent = cartItem.querySelector('.wc-block-cart-item-meta-hook');
                    if (existingContent) {
                        return; // Skip if already added
                    }
                    
                    // Find the description element to add hook container after it
                    const description = cartItem.querySelector('.wc-block-components-order-summary-item__description');
                    if (description) {
                        // Create hook container div for users to target
                        const hookContainer = document.createElement('div');
                        hookContainer.className = 'wc-block-cart-item-meta-hook';
                        hookContainer.setAttribute('data-hook', 'woocommerce_review_order_after_cart_item_meta');
                        
                        // Insert after the description (which contains the item meta)
                        description.appendChild(hookContainer);
                        
                        // Dispatch custom event for users to listen to
                        const event = new CustomEvent('woocommerce_review_order_after_cart_item_meta', {
                            detail: {
                                container: hookContainer,
                                cartItem: cartItem
                            }
                        });
                        document.dispatchEvent(event);
                    }
                });
                
                console.log('✅ Hook containers added after each cart item meta for block checkout');
            }
            
            // Try immediately
            addBlockCheckoutContent();
            
            // Also try after a short delay (for dynamic loading)
            setTimeout(addBlockCheckoutContent, 1000);
            
            // Also try after 2 seconds (for slower loading)
            setTimeout(addBlockCheckoutContent, 2000);
            
            // Also try when DOM changes (for AJAX updates)
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        setTimeout(addBlockCheckoutContent, 500);
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
        </script>
        <?php
    }
}

// Initialize the hooks
WC_Checkout_Hook::init();
