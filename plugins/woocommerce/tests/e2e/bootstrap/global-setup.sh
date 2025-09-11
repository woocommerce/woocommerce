#!/bin/bash
# ------------------------------------------------------------------
# Global Setup – executed INSIDE the WP container
# ------------------------------------------------------------------
# Put your plugin/extension into a _minimal ready state_ here.
#   – Creates sandbox credentials
#   – Disables onboarding banners
#   – Turns off tracking, etc.
# This runs **once** per test run (even if your package is only in
# `global_setup`) and should finish fast.

set -euo pipefail

echo "[globalSetup] Starting WooCommerce configuration..."

# Dismiss WooCommerce onboarding wizard and setup
echo "[globalSetup] Dismissing WooCommerce onboarding wizard..."
wp option update woocommerce_onboarding_profile '{"completed":true,"skipped":true}' --format=json

# Mark WooCommerce admin as installed
wp option update woocommerce_admin_install_timestamp $(date +%s)

# Disable admin notices and suggestions
wp option update woocommerce_show_marketplace_suggestions no
wp option update woocommerce_merchant_email_notifications no

# Disable the setup wizard redirect
wp option delete _wc_activation_redirect
wp option update woocommerce_admin_version $(wp plugin get woocommerce --field=version)

# Prevent any onboarding redirects
wp option update woocommerce_onboarding_profile_completed yes
wp option update woocommerce_task_list_do_this_later yes

# Set basic WooCommerce settings
echo "[globalSetup] Configuring basic WooCommerce settings..."
wp option update woocommerce_store_address "123 Main St"
wp option update woocommerce_store_city "New York"
wp option update woocommerce_default_country "US:NY"
wp option update woocommerce_store_postcode "10001"
wp option update woocommerce_currency "USD"
wp option update woocommerce_currency_pos "left"

# Enable payment gateways (basic ones, PayPal will be configured by its own tests)
wp option update woocommerce_cod_settings '{"enabled":"yes","title":"Cash on delivery"}' --format=json
wp option update woocommerce_bacs_settings '{"enabled":"yes","title":"Direct bank transfer"}' --format=json

# Disable tracking
wp option update woocommerce_allow_tracking no
wp option update woocommerce_tracker_last_send $(date +%s)

# Ensure WooCommerce pages are created
echo "[globalSetup] Ensuring WooCommerce pages exist..."
wp wc tool run install_pages --user=1

echo "[globalSetup] WooCommerce configuration complete."