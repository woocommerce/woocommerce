#!/usr/bin/env php
<?php
/**
 * Creates necessary pages for checkout blocks testing
 * This mimics the page creation from WooCommerce Core's test suite
 */

echo "[setup] Creating test pages for Checkout Blocks tests...\n";

// Helper to check if a page exists by slug
function page_exists($slug) {
    $result = trim(exec("wp post list --post_type=page --name=\"$slug\" --format=count"));
    return $result && intval($result) > 0;
}

// Create checkout blocks page
if (!page_exists('checkout')) {
    echo "[setup] Creating Blocks Checkout page...\n";
    $content = '<!-- wp:woocommerce/checkout -->
<div class="wp-block-woocommerce-checkout wc-block-checkout">
</div>
<!-- /wp:woocommerce/checkout -->';
    
    $page_id = trim(exec("wp post create --post_type=page --post_title=\"Checkout\" --post_name=\"checkout\" --post_status=\"publish\" --post_content='$content' --porcelain"));
    
    if ($page_id) {
        exec("wp option update woocommerce_checkout_page_id $page_id");
        echo "[setup] Created Blocks Checkout page (ID: $page_id)\n";
    }
} else {
    echo "[setup] Blocks Checkout page already exists\n";
}

// Create cart blocks page
if (!page_exists('cart')) {
    echo "[setup] Creating Blocks Cart page...\n";
    $content = '<!-- wp:woocommerce/cart -->
<div class="wp-block-woocommerce-cart wc-block-cart">
</div>
<!-- /wp:woocommerce/cart -->';
    
    $page_id = trim(exec("wp post create --post_type=page --post_title=\"Cart\" --post_name=\"cart\" --post_status=\"publish\" --post_content='$content' --porcelain"));
    
    if ($page_id) {
        exec("wp option update woocommerce_cart_page_id $page_id");
        echo "[setup] Created Blocks Cart page (ID: $page_id)\n";
    }
} else {
    echo "[setup] Blocks Cart page already exists\n";
}

// Create classic checkout page for comparison tests
if (!page_exists('classic-checkout')) {
    echo "[setup] Creating Classic Checkout page...\n";
    $content = '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->';
    
    $page_id = trim(exec("wp post create --post_type=page --post_title=\"Classic Checkout\" --post_name=\"classic-checkout\" --post_status=\"publish\" --post_content='$content' --porcelain"));
    
    if ($page_id) {
        echo "[setup] Created Classic Checkout page (ID: $page_id)\n";
    }
} else {
    echo "[setup] Classic Checkout page already exists\n";
}

// Create classic cart page
if (!page_exists('classic-cart')) {
    echo "[setup] Creating Classic Cart page...\n";
    $content = '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->';
    
    $page_id = trim(exec("wp post create --post_type=page --post_title=\"Classic Cart\" --post_name=\"classic-cart\" --post_status=\"publish\" --post_content='$content' --porcelain"));
    
    if ($page_id) {
        echo "[setup] Created Classic Cart page (ID: $page_id)\n";
    }
} else {
    echo "[setup] Classic Cart page already exists\n";
}

// Create My Account page
if (!page_exists('my-account')) {
    echo "[setup] Creating My Account page...\n";
    $content = '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->';
    
    $page_id = trim(exec("wp post create --post_type=page --post_title=\"My Account\" --post_name=\"my-account\" --post_status=\"publish\" --post_content='$content' --porcelain"));
    
    if ($page_id) {
        exec("wp option update woocommerce_myaccount_page_id $page_id");
        echo "[setup] Created My Account page (ID: $page_id)\n";
    }
} else {
    echo "[setup] My Account page already exists\n";
}

// Create shop page
if (!page_exists('shop')) {
    echo "[setup] Creating Shop page...\n";
    
    $page_id = trim(exec("wp post create --post_type=page --post_title=\"Shop\" --post_name=\"shop\" --post_status=\"publish\" --post_content=\"\" --porcelain"));
    
    if ($page_id) {
        exec("wp option update woocommerce_shop_page_id $page_id");
        echo "[setup] Created Shop page (ID: $page_id)\n";
    }
} else {
    echo "[setup] Shop page already exists\n";
}

echo "[setup] Page creation complete\n";