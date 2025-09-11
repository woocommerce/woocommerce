#!/usr/bin/env node
/**
 * Creates necessary pages for checkout blocks testing
 * This mimics the page creation from WooCommerce Core's test suite
 */

const { execSync } = require('child_process');

// Helper to run WP-CLI commands
function wp(command) {
    try {
        const result = execSync(`wp ${command}`, { encoding: 'utf8' });
        return result.trim();
    } catch (error) {
        console.error(`Error running: wp ${command}`);
        console.error(error.message);
        return null;
    }
}

// Check if a page exists by slug
function pageExists(slug) {
    const result = wp(`post list --post_type=page --name="${slug}" --format=count`);
    return result && parseInt(result) > 0;
}

// Create checkout blocks page
function createBlocksCheckoutPage() {
    if (!pageExists('checkout')) {
        console.log('[setup] Creating Blocks Checkout page...');
        const pageId = wp(`post create --post_type=page --post_title="Checkout" --post_name="checkout" --post_status="publish" --post_content='<!-- wp:woocommerce/checkout -->
<div class="wp-block-woocommerce-checkout wc-block-checkout">
</div>
<!-- /wp:woocommerce/checkout -->' --porcelain`);
        
        if (pageId) {
            // Set as WooCommerce checkout page
            wp(`option update woocommerce_checkout_page_id ${pageId}`);
            console.log(`[setup] Created Blocks Checkout page (ID: ${pageId})`);
        }
    } else {
        console.log('[setup] Blocks Checkout page already exists');
    }
}

// Create cart blocks page
function createBlocksCartPage() {
    if (!pageExists('cart')) {
        console.log('[setup] Creating Blocks Cart page...');
        const pageId = wp(`post create --post_type=page --post_title="Cart" --post_name="cart" --post_status="publish" --post_content='<!-- wp:woocommerce/cart -->
<div class="wp-block-woocommerce-cart wc-block-cart">
</div>
<!-- /wp:woocommerce/cart -->' --porcelain`);
        
        if (pageId) {
            // Set as WooCommerce cart page
            wp(`option update woocommerce_cart_page_id ${pageId}`);
            console.log(`[setup] Created Blocks Cart page (ID: ${pageId})`);
        }
    } else {
        console.log('[setup] Blocks Cart page already exists');
    }
}

// Create classic checkout page for comparison tests
function createClassicCheckoutPage() {
    if (!pageExists('classic-checkout')) {
        console.log('[setup] Creating Classic Checkout page...');
        const pageId = wp(`post create --post_type=page --post_title="Classic Checkout" --post_name="classic-checkout" --post_status="publish" --post_content='<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->' --porcelain`);
        
        if (pageId) {
            console.log(`[setup] Created Classic Checkout page (ID: ${pageId})`);
        }
    } else {
        console.log('[setup] Classic Checkout page already exists');
    }
}

// Create classic cart page
function createClassicCartPage() {
    if (!pageExists('classic-cart')) {
        console.log('[setup] Creating Classic Cart page...');
        const pageId = wp(`post create --post_type=page --post_title="Classic Cart" --post_name="classic-cart" --post_status="publish" --post_content='<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->' --porcelain`);
        
        if (pageId) {
            console.log(`[setup] Created Classic Cart page (ID: ${pageId})`);
        }
    } else {
        console.log('[setup] Classic Cart page already exists');
    }
}

// Create My Account page
function createMyAccountPage() {
    if (!pageExists('my-account')) {
        console.log('[setup] Creating My Account page...');
        const pageId = wp(`post create --post_type=page --post_title="My Account" --post_name="my-account" --post_status="publish" --post_content='<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->' --porcelain`);
        
        if (pageId) {
            // Set as WooCommerce my account page
            wp(`option update woocommerce_myaccount_page_id ${pageId}`);
            console.log(`[setup] Created My Account page (ID: ${pageId})`);
        }
    } else {
        console.log('[setup] My Account page already exists');
    }
}

// Create shop page
function createShopPage() {
    if (!pageExists('shop')) {
        console.log('[setup] Creating Shop page...');
        const pageId = wp(`post create --post_type=page --post_title="Shop" --post_name="shop" --post_status="publish" --post_content="" --porcelain`);
        
        if (pageId) {
            // Set as WooCommerce shop page
            wp(`option update woocommerce_shop_page_id ${pageId}`);
            console.log(`[setup] Created Shop page (ID: ${pageId})`);
        }
    } else {
        console.log('[setup] Shop page already exists');
    }
}

// Main execution
console.log('[setup] Creating test pages for Checkout Blocks tests...');

createBlocksCheckoutPage();
createBlocksCartPage();
createClassicCheckoutPage();
createClassicCartPage();
createMyAccountPage();
createShopPage();

console.log('[setup] Page creation complete');