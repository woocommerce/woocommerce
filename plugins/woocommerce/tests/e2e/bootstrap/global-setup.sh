#!/bin/bash
set -e

echo "[globalSetup] Starting WooCommerce global configuration..."

# Check if WooCommerce is installed
echo "[globalSetup] Checking WooCommerce installation status..."
wp plugin list --allow-root

# Activate WooCommerce if not already active
echo "[globalSetup] Activating WooCommerce..."
wp plugin activate woocommerce --allow-root || echo "WooCommerce already active"

# Verify WooCommerce is active
echo "[globalSetup] Verifying WooCommerce activation..."
if wp plugin is-active woocommerce --allow-root; then
    echo "[globalSetup] WooCommerce is active!"
else
    echo "[globalSetup] ERROR: WooCommerce is not active!"
    exit 1
fi

# Test WC CLI is available
echo "[globalSetup] Testing WC CLI commands..."
wp wc --help --allow-root

# Configure WooCommerce
wp option update woocommerce_task_list_hidden yes --allow-root || true
wp option update woocommerce_onboarding_profile_completed yes --allow-root
wp option update woocommerce_admin_install_timestamp $(date +%s) --allow-root
wp option update woocommerce_allow_tracking no --allow-root
wp option update woocommerce_redirect_to_setup no --allow-root

# Setup onboarding profile for customize-store
wp option update woocommerce_onboarding_profile '{"completed":true,"industry":["fashion-and-apparel"],"selling_venues":["woocommerce"],"product_types":["physical"],"setup_client":false}' --format=json --allow-root || true
wp option update woocommerce_admin_customize_store_completed no --allow-root || true

# Setup payment methods
wp wc tool run install_pages --user=admin --allow-root || echo "Could not install pages"
wp option update woocommerce_cod_settings '{"enabled":"yes","title":"Cash on delivery","description":"Pay with cash upon delivery.","instructions":"Pay with cash upon delivery."}' --format=json --allow-root
wp option update woocommerce_bacs_settings '{"enabled":"yes","title":"Direct bank transfer"}' --format=json --allow-root  
wp option update woocommerce_cheque_settings '{"enabled":"yes","title":"Check payments"}' --format=json --allow-root

# Setup store settings
wp option update woocommerce_default_country "US:CA" --allow-root
wp option update woocommerce_currency "USD" --allow-root

# Install Basic Auth plugin for REST API authentication
echo "[globalSetup] Installing Basic Auth plugin for REST API..."
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate --allow-root || echo "Basic Auth plugin already installed"

# Note: WP Mail Logging is NOT installed globally to allow email tests to work correctly
# Some tests expect email sending to fail (e.g., "Send email preview" test)
# Tests that need email verification will handle WP Mail Logging installation themselves

# Install Customize Store workaround for known issues (GitHub #44766)
echo "[globalSetup] Installing Customize Store workaround..."
if [ -f "./bin/customize-store-workaround.php" ]; then
    mkdir -p wp-content/mu-plugins
    cp ./bin/customize-store-workaround.php wp-content/mu-plugins/customize-store-workaround.php
    echo "[globalSetup] Customize Store workaround installed"
else
    echo "[globalSetup] Warning: Customize Store workaround file not found"
fi

# Update blog name
echo "[globalSetup] Setting blog name..."
wp option update blogname 'WooCommerce Core E2E Test Suite' --allow-root

# Set admin email to match test expectations
echo "[globalSetup] Setting admin email..."
wp option update admin_email 'admin@woocommercecoree2etestsuite.com' --allow-root

# Set permalink structure
echo "[globalSetup] Setting permalink structure..."
wp rewrite structure '/%postname%/' --hard --allow-root

# Install and activate themes
echo "[globalSetup] Installing themes..."
wp theme install twentytwentythree --allow-root || echo "Theme already installed"
wp theme install twentytwentyfour --allow-root || echo "Theme already installed"
wp theme activate twentytwentythree --allow-root

# Create customer user
echo "[globalSetup] Creating customer user..."
wp user create customer customer@woocommercecoree2etestsuite.com \
    --user_pass=password \
    --role=customer \
    --first_name='Jane' \
    --last_name='Smith' \
    --user_registered='2022-01-01 12:23:45' \
    --allow-root || echo "Customer user already exists"

# Upload test images
echo "[globalSetup] Uploading test images..."
# QIT runs scripts from the test package directory, so we can use relative paths
for image in image-01.png image-02.png image-03.png; do
    image_path="./test-data/images/$image"
    if [ -f "$image_path" ]; then
        echo "[globalSetup] Uploading $image..."
        # Use --title to ensure checkbox name matches filename without extension
        wp media import "$image_path" --title="${image%.*}" --allow-root || echo "[globalSetup] Image $image already uploaded"
    else
        echo "[globalSetup] Warning: Image $image_path not found"
    fi
done

echo "[globalSetup] WooCommerce global configuration complete."