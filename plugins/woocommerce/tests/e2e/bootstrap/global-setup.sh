#!/bin/bash
set -e

echo "[globalSetup] Starting minimal WooCommerce configuration..."

# ------------------------------------------------------------------
# MINIMAL WOOCOMMERCE SETUP - Only disable WooCommerce-specific
# features that would interfere with ANY test package
# ------------------------------------------------------------------

# Verify WooCommerce is active
echo "[globalSetup] Verifying WooCommerce activation..."
if wp plugin is-active woocommerce --allow-root; then
    echo "[globalSetup] WooCommerce is active!"
else
    echo "[globalSetup] ERROR: WooCommerce is not active!"
    exit 1
fi

# MINIMAL: Disable WooCommerce onboarding/setup wizard that would interfere
wp option update woocommerce_task_list_hidden yes --allow-root || true
wp option update woocommerce_onboarding_profile_completed yes --allow-root
wp option update woocommerce_admin_install_timestamp $(date +%s) --allow-root
wp option update woocommerce_allow_tracking no --allow-root
wp option update woocommerce_redirect_to_setup no --allow-root

# Setup onboarding profile to prevent redirects
wp option update woocommerce_onboarding_profile '{"completed":true,"skipped":true}' --format=json --allow-root || true
wp option update woocommerce_admin_customize_store_completed no --allow-root || true

# MINIMAL: Ensure WooCommerce pages exist (required for basic operation)
wp wc tool run install_pages --user=admin --allow-root || echo "Could not install pages"

# MINIMAL: Set only required settings
wp option update woocommerce_default_country "US:CA" --allow-root
wp option update woocommerce_currency "USD" --allow-root

echo "[globalSetup] Minimal WooCommerce configuration complete."
