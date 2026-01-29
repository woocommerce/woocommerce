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
        add_action( 'woocommerce_review_order_after_cart_item_meta', array( __CLASS__, 'add_custom_content_after_cart_item_meta' ), 10, 3 );
        
        // Block checkout JavaScript support
        add_action( 'wp_footer', array( __CLASS__, 'add_block_checkout_support' ) );
    }

    /**
     * Add custom content after each individual cart item meta in classic checkout
     */
    public static function add_custom_content_after_cart_item_meta( $_product, $cart_item, $cart_item_key ) {
        echo '<div style="background: #f0f8ff; padding: 10px; margin: 10px 0; border-left: 3px solid #0073aa; font-size: 14px; font-weight: bold;">';
        echo 'This is custom block';
        echo '</div>';
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
                    // Check if custom block already exists for this item
                    const existingBlock = cartItem.querySelector('.custom-cart-item-meta-block');
                    if (existingBlock) {
                        return; // Skip if already added
                    }
                    
                    // Find the description element to add content after it
                    const description = cartItem.querySelector('.wc-block-components-order-summary-item__description');
                    if (description) {
                        // Create custom content div
                        const customContent = document.createElement('div');
                        customContent.className = 'custom-cart-item-meta-block';
                        customContent.style.cssText = 'background: #f0f8ff; padding: 10px; margin: 10px 0; border-left: 3px solid #0073aa; font-size: 14px; font-weight: bold;';
                        
                        customContent.innerHTML = 'This is custom block';
                        
                        // Insert after the description (which contains the item meta)
                        description.appendChild(customContent);
                    }
                });
                
                console.log('✅ Custom blocks added after each cart item meta');
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
