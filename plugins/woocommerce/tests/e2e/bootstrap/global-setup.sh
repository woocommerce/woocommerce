#!/bin/bash
set -e

echo "[globalSetup] Starting universal WooCommerce baseline configuration..."

# ------------------------------------------------------------------
# UNIVERSAL WOOCOMMERCE BASELINE - Configuration that benefits ALL
# test packages, creating a ready-to-test WooCommerce store
# ------------------------------------------------------------------

# Verify WooCommerce is active
echo "[globalSetup] Verifying WooCommerce activation..."
if wp plugin is-active woocommerce --allow-root; then
    echo "[globalSetup] WooCommerce is active!"
else
    echo "[globalSetup] ERROR: WooCommerce is not active!"
    exit 1
fi

# Disable WooCommerce onboarding/setup wizard that would interfere with tests
echo "[globalSetup] Disabling onboarding and setup wizards..."
wp option update woocommerce_task_list_hidden yes --allow-root || true
wp option update woocommerce_onboarding_profile_completed yes --allow-root
wp option update woocommerce_admin_install_timestamp $(date +%s) --allow-root
wp option update woocommerce_allow_tracking no --allow-root
wp option update woocommerce_redirect_to_setup no --allow-root

# Setup onboarding profile to prevent redirects
wp option update woocommerce_onboarding_profile '{"completed":true,"skipped":true}' --format=json --allow-root || true
wp option update woocommerce_admin_customize_store_completed no --allow-root || true

# Ensure WooCommerce pages exist (shop, cart, checkout, my-account)
echo "[globalSetup] Installing WooCommerce pages..."
wp wc tool run install_pages --user=admin --allow-root || echo "Could not install pages"

# Set permalink structure for clean URLs (needed for proper page routing)
echo "[globalSetup] Setting permalink structure..."
wp rewrite structure '/%postname%/' --hard --allow-root

# Configure complete store address (a functional store needs this)
echo "[globalSetup] Configuring store address..."
wp option update woocommerce_store_address 'addr 1' --allow-root
wp option update woocommerce_store_city 'San Francisco' --allow-root
wp option update woocommerce_default_country 'US:CA' --allow-root
wp option update woocommerce_store_postcode '94107' --allow-root

# Set currency and price formatting (standardized for consistent testing)
echo "[globalSetup] Setting currency and price formatting..."
wp option update woocommerce_currency 'USD' --allow-root
wp option update woocommerce_price_thousand_sep ',' --allow-root
wp option update woocommerce_price_decimal_sep '.' --allow-root
wp option update woocommerce_price_num_decimals '2' --allow-root

# Set allowed countries to all (tests may need international scenarios)
echo "[globalSetup] Setting allowed countries..."
wp option update woocommerce_allowed_countries 'all' --allow-root

# Create a standard customer user that any test package can use
echo "[globalSetup] Creating standard customer user..."
wp user create customer customer@woocommercecoree2etestsuite.com \
    --user_pass=password \
    --role=customer \
    --first_name='Jane' \
    --last_name='Smith' \
    --user_registered='2022-01-01 12:23:45' \
    --allow-root || echo "Customer user already exists"

# Dismiss email improvements modals to prevent test interference
echo "[globalSetup] Dismissing email improvements modals..."
wp option update woocommerce_admin_dismissed_email_improvements_modal 'yes' --allow-root
wp option update woocommerce_admin_dismissed_try_email_improvements_modal 'yes' --allow-root

echo "[globalSetup] Universal WooCommerce baseline configuration complete."
