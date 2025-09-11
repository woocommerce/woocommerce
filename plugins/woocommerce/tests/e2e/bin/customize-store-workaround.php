<?php
/**
 * Workaround for WooCommerce Customize Store redirect loop issue
 * GitHub issue #44766 - affects WooCommerce 8.8+
 * 
 * This mu-plugin provides fixes for known Customize Store issues:
 * 1. Redirect loops between assembler-hub and intro pages
 * 2. Iframe opacity stuck at 0
 * 3. Loading screen freezes
 */

// Force mark customize store as completed if we're in a redirect loop
add_action('init', function() {
    $current_path = $_GET['path'] ?? '';
    
    // Detect redirect loop scenario
    if (strpos($current_path, '/customize-store/') !== false) {
        $completed = get_option('woocommerce_admin_customize_store_completed');
        
        // If trying to access assembler-hub but not marked as completed, mark it
        if (strpos($current_path, 'assembler-hub') !== false && $completed !== 'yes') {
            update_option('woocommerce_admin_customize_store_completed', 'yes');
        }
    }
}, 5);

// CSS fix for iframe opacity issue
add_action('admin_head', function() {
    ?>
    <style>
        /* Fix for iframe opacity stuck at 0 */
        .cys-fullscreen-iframe {
            opacity: 1 !important;
        }
        
        /* Ensure assembler content is visible */
        .woocommerce-customize-store-assembler iframe {
            opacity: 1 !important;
            visibility: visible !important;
        }
    </style>
    <?php
});

// Skip onboarding redirect for customize-store tests
add_filter('woocommerce_admin_onboarding_redirect_url', function($url) {
    if (strpos($url, 'customize-store') !== false) {
        return false;
    }
    return $url;
}, 10, 1);