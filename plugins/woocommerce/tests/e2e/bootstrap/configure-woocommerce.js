#!/usr/bin/env node
/**
 * Configures WooCommerce settings for checkout blocks testing
 * Based on WooCommerce Core's test configuration
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

console.log('[setup] Configuring WooCommerce settings...');

// General store settings
wp(`option update woocommerce_store_address "addr 1"`);
wp(`option update woocommerce_store_city "San Francisco"`);
wp(`option update woocommerce_default_country "US:CA"`);
wp(`option update woocommerce_store_postcode "94107"`);
wp(`option update woocommerce_currency "USD"`);
wp(`option update woocommerce_price_thousand_sep ","`);
wp(`option update woocommerce_price_decimal_sep "."`);
wp(`option update woocommerce_price_num_decimals "2"`);
console.log('[setup] Store location configured: San Francisco, CA');

// Tax settings
wp(`option update woocommerce_calc_taxes "yes"`);
wp(`option update woocommerce_tax_based_on "shipping"`);
wp(`option update woocommerce_shipping_tax_class ""`);
wp(`option update woocommerce_tax_round_at_subtotal "no"`);
wp(`option update woocommerce_tax_display_shop "excl"`);
wp(`option update woocommerce_tax_display_cart "excl"`);
wp(`option update woocommerce_price_display_suffix ""`);
wp(`option update woocommerce_tax_total_display "single"`);
console.log('[setup] Tax settings configured');

// Checkout settings
wp(`option update woocommerce_enable_checkout_login_reminder "yes"`);
wp(`option update woocommerce_enable_signup_and_login_from_checkout "yes"`);
wp(`option update woocommerce_enable_guest_checkout "yes"`);
wp(`option update woocommerce_force_ssl_checkout "no"`);
wp(`option update woocommerce_registration_generate_username "yes"`);
wp(`option update woocommerce_registration_generate_password "yes"`);
console.log('[setup] Checkout settings configured');

// Shipping settings
wp(`option update woocommerce_ship_to_countries "all"`);
wp(`option update woocommerce_ship_to_destination "billing"`);
wp(`option update woocommerce_shipping_cost_requires_address "no"`);
wp(`option update woocommerce_shipping_debug_mode "no"`);
wp(`option update woocommerce_enable_shipping_calc "yes"`);
console.log('[setup] Shipping settings configured');

// Payment methods
wp(`option update woocommerce_cod_settings '{"enabled":"yes","title":"Cash on delivery","description":"Pay with cash upon delivery.","instructions":"Pay the delivery person in cash when you receive your order.","enable_for_methods":[],"enable_for_virtual":"yes"}'`);
wp(`option update woocommerce_bacs_settings '{"enabled":"yes","title":"Direct bank transfer","description":"Make your payment directly into our bank account.","instructions":"","account_details":[]}'`);
wp(`option update woocommerce_cheque_settings '{"enabled":"yes","title":"Check payments","description":"Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.","instructions":""}'`);
console.log('[setup] Payment methods configured');

// Create tax rates
console.log('[setup] Creating tax rates...');

// Create US tax rate (25% for testing)
const taxData = JSON.stringify({
    country: 'US',
    state: '*',
    rate: '25',
    name: 'US Tax',
    priority: 1,
    compound: 0,
    shipping: 1,
    class: ''
});

// Use REST API to create tax
wp(`eval "
    if (!class_exists('WC_Tax')) {
        require_once(WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-tax.php');
    }
    
    global \\$wpdb;
    \\$wpdb->insert(
        \\$wpdb->prefix . 'woocommerce_tax_rates',
        array(
            'tax_rate_country' => 'US',
            'tax_rate_state' => '',
            'tax_rate' => '25.0000',
            'tax_rate_name' => 'US Tax',
            'tax_rate_priority' => 1,
            'tax_rate_compound' => 0,
            'tax_rate_shipping' => 1,
            'tax_rate_order' => 0,
            'tax_rate_class' => ''
        )
    );
    echo 'Tax rate created';
"`);

console.log('[setup] Tax rate created: US Tax (25%)');

// Create shipping zones
console.log('[setup] Creating shipping zones...');

// Create free shipping zone for California
wp(`eval "
    if (!class_exists('WC_Shipping_Zone')) {
        require_once(WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-shipping-zone.php');
    }
    
    \\$zone = new WC_Shipping_Zone();
    \\$zone->set_zone_name('California');
    \\$zone->set_zone_order(0);
    \\$zone->save();
    
    \\$zone->add_location('US:CA', 'state');
    
    \\$zone->add_shipping_method('free_shipping');
    
    echo 'Shipping zone created';
"`);

console.log('[setup] Shipping zone created: California with free shipping');

// Create flat rate for rest of US
wp(`eval "
    if (!class_exists('WC_Shipping_Zone')) {
        require_once(WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-shipping-zone.php');
    }
    
    \\$zone = new WC_Shipping_Zone();
    \\$zone->set_zone_name('United States');
    \\$zone->set_zone_order(1);
    \\$zone->save();
    
    \\$zone->add_location('US', 'country');
    
    \\$method_id = \\$zone->add_shipping_method('flat_rate');
    
    echo 'Flat rate zone created';
"`);

console.log('[setup] Shipping zone created: United States with flat rate');

// Ensure local pickup is available
wp(`option update woocommerce_pickup_location_settings '{"enabled":"yes","title":"Local Pickup","tax_status":"taxable","cost":""}'`);
console.log('[setup] Local pickup enabled');

console.log('[setup] WooCommerce configuration complete');