#!/usr/bin/env php
<?php
/**
 * Configures WooCommerce settings for checkout blocks testing
 * Based on WooCommerce Core's test configuration
 */

echo "[setup] Configuring WooCommerce settings...\n";

// General store settings
exec('wp option update woocommerce_store_address "addr 1"');
exec('wp option update woocommerce_store_city "San Francisco"');
exec('wp option update woocommerce_default_country "US:CA"');
exec('wp option update woocommerce_store_postcode "94107"');
exec('wp option update woocommerce_currency "USD"');
exec('wp option update woocommerce_price_thousand_sep ","');
exec('wp option update woocommerce_price_decimal_sep "."');
exec('wp option update woocommerce_price_num_decimals "2"');
echo "[setup] Store location configured: San Francisco, CA\n";

// Tax settings
exec('wp option update woocommerce_calc_taxes "yes"');
exec('wp option update woocommerce_tax_based_on "shipping"');
exec('wp option update woocommerce_shipping_tax_class ""');
exec('wp option update woocommerce_tax_round_at_subtotal "no"');
exec('wp option update woocommerce_tax_display_shop "excl"');
exec('wp option update woocommerce_tax_display_cart "excl"');
exec('wp option update woocommerce_price_display_suffix ""');
exec('wp option update woocommerce_tax_total_display "single"');
echo "[setup] Tax settings configured\n";

// Checkout settings
exec('wp option update woocommerce_enable_checkout_login_reminder "yes"');
exec('wp option update woocommerce_enable_signup_and_login_from_checkout "yes"');
exec('wp option update woocommerce_enable_guest_checkout "yes"');
exec('wp option update woocommerce_force_ssl_checkout "no"');
exec('wp option update woocommerce_registration_generate_username "yes"');
exec('wp option update woocommerce_registration_generate_password "yes"');
echo "[setup] Checkout settings configured\n";

// Shipping settings
exec('wp option update woocommerce_ship_to_countries "all"');
exec('wp option update woocommerce_ship_to_destination "billing"');
exec('wp option update woocommerce_shipping_cost_requires_address "no"');
exec('wp option update woocommerce_shipping_debug_mode "no"');
exec('wp option update woocommerce_enable_shipping_calc "yes"');
echo "[setup] Shipping settings configured\n";

// Payment methods
$cod_settings = json_encode([
    'enabled' => 'yes',
    'title' => 'Cash on delivery',
    'description' => 'Pay with cash upon delivery.',
    'instructions' => 'Pay the delivery person in cash when you receive your order.',
    'enable_for_methods' => [],
    'enable_for_virtual' => 'yes'
]);
exec("wp option update woocommerce_cod_settings '$cod_settings'");

$bacs_settings = json_encode([
    'enabled' => 'yes',
    'title' => 'Direct bank transfer',
    'description' => 'Make your payment directly into our bank account.',
    'instructions' => '',
    'account_details' => []
]);
exec("wp option update woocommerce_bacs_settings '$bacs_settings'");

$cheque_settings = json_encode([
    'enabled' => 'yes',
    'title' => 'Check payments',
    'description' => 'Please send a check to Store Name, Store Street, Store Town, Store State / County, Store Postcode.',
    'instructions' => ''
]);
exec("wp option update woocommerce_cheque_settings '$cheque_settings'");

echo "[setup] Payment methods configured\n";

// Create tax rates using WP-CLI eval
echo "[setup] Creating tax rates...\n";

$tax_eval = <<<'PHP'
global $wpdb;
$table = $wpdb->prefix . 'woocommerce_tax_rates';
$existing = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE tax_rate_country = 'US' AND tax_rate_name = 'US Tax'");
if (!$existing) {
    $wpdb->insert(
        $table,
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
    echo "Tax rate created";
} else {
    echo "Tax rate already exists";
}
PHP;

exec("wp eval '$tax_eval'");
echo "[setup] Tax rate created: US Tax (25%)\n";

// Create shipping zones
echo "[setup] Creating shipping zones...\n";

$shipping_eval = <<<'PHP'
if (!class_exists('WC_Shipping_Zone')) {
    require_once(WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-shipping-zone.php');
}

// Check if California zone exists
$zones = WC_Shipping_Zones::get_zones();
$ca_exists = false;
foreach ($zones as $zone_data) {
    if ($zone_data['zone_name'] === 'California') {
        $ca_exists = true;
        break;
    }
}

if (!$ca_exists) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('California');
    $zone->set_zone_order(0);
    $zone->save();
    
    $zone->add_location('US:CA', 'state');
    $zone->add_shipping_method('free_shipping');
    
    echo "California shipping zone created";
} else {
    echo "California shipping zone already exists";
}
PHP;

exec("wp eval '$shipping_eval'");
echo "[setup] Shipping zone created: California with free shipping\n";

// Create flat rate for rest of US
$flat_rate_eval = <<<'PHP'
if (!class_exists('WC_Shipping_Zone')) {
    require_once(WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-shipping-zone.php');
}

// Check if US zone exists
$zones = WC_Shipping_Zones::get_zones();
$us_exists = false;
foreach ($zones as $zone_data) {
    if ($zone_data['zone_name'] === 'United States') {
        $us_exists = true;
        break;
    }
}

if (!$us_exists) {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name('United States');
    $zone->set_zone_order(1);
    $zone->save();
    
    $zone->add_location('US', 'country');
    $method_id = $zone->add_shipping_method('flat_rate');
    
    echo "US flat rate zone created";
} else {
    echo "US flat rate zone already exists";
}
PHP;

exec("wp eval '$flat_rate_eval'");
echo "[setup] Shipping zone created: United States with flat rate\n";

// Ensure local pickup is available
$pickup_settings = json_encode([
    'enabled' => 'yes',
    'title' => 'Local Pickup',
    'tax_status' => 'taxable',
    'cost' => ''
]);
exec("wp option update woocommerce_pickup_location_settings '$pickup_settings'");
echo "[setup] Local pickup enabled\n";

echo "[setup] WooCommerce configuration complete\n";