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
        // Classic checkout hook
        add_action( 'woocommerce_review_order_after_cart_contents', array( __CLASS__, 'add_custom_content_after_cart_contents' ) );
        
        // Block checkout JavaScript support
        add_action( 'wp_footer', array( __CLASS__, 'add_block_checkout_support' ) );
    }

    /**
     * Add custom content after cart contents in classic checkout
     */
    public static function add_custom_content_after_cart_contents() {
        echo '<tr><td colspan="2">';
        echo '<div style="background: #f0f8ff; padding: 15px; margin: 15px 0; border-left: 3px solid #0073aa; font-size: 14px; font-weight: bold; text-align: center;">';
        echo 'This is custom block';
        echo '</div>';
        echo '</td></tr>';
    }

    /**
     * Add JavaScript support for WooCommerce Blocks checkout
     */
    public static function add_block_checkout_support() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function addBlockCheckoutContent() {
                const cartItemsContainer = document.querySelector('.wc-block-components-order-summary__content');
                
                if (!cartItemsContainer) {
                    return;
                }
                
                const existingBlock = cartItemsContainer.querySelector('.custom-cart-item-meta-block');
                if (existingBlock) {
                    return;
                }
                
                const customContent = document.createElement('div');
                customContent.className = 'custom-cart-item-meta-block';
                customContent.style.cssText = 'background: #f0f8ff; padding: 15px; margin: 15px 0; border-left: 3px solid #0073aa; font-size: 14px; font-weight: bold; text-align: center;';
                
                customContent.innerHTML = 'This is custom block';
                
                const couponForm = cartItemsContainer.querySelector('.wp-block-woocommerce-checkout-order-summary-coupon-form-block');
                if (couponForm) {
                    cartItemsContainer.insertBefore(customContent, couponForm);
                } else {
                    const totalsBlock = cartItemsContainer.querySelector('.wp-block-woocommerce-checkout-order-summary-totals-block');
                    if (totalsBlock) {
                        cartItemsContainer.insertBefore(customContent, totalsBlock);
                    } else {
                        cartItemsContainer.appendChild(customContent);
                    }
                }
                
                console.log('✅ Custom block added after all cart items');
            }
            
            addBlockCheckoutContent();
            setTimeout(addBlockCheckoutContent, 1000);
            setTimeout(addBlockCheckoutContent, 2000);
            
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
