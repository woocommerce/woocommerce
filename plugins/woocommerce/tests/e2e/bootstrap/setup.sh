#!/bin/bash
set -e

echo "[setup] WooCommerce E2E test-specific setup..."

# ------------------------------------------------------------------
# E2E TEST-SPECIFIC SETUP - Configuration specific to WooCommerce's
# own E2E test suite, not needed by other test packages
# ------------------------------------------------------------------

# Install Basic Auth plugin for REST API authentication (needed for API tests)
echo "[setup] Installing Basic Auth plugin for REST API..."
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate --allow-root || echo "Basic Auth plugin already installed"

# Install WP Mail Logging plugin for email tests
echo "[setup] Installing WP Mail Logging plugin..."
wp plugin install wp-mail-logging --activate --allow-root || echo "WP Mail Logging already installed"

# Update blog name for test suite
echo "[setup] Setting blog name for E2E test suite..."
wp option update blogname 'WooCommerce Core E2E Test Suite' --allow-root

# Set admin email to match test expectations
echo "[setup] Setting admin email for E2E tests..."
wp option update admin_email 'admin@woocommercecoree2etestsuite.com' --allow-root

# Disable email improvements feature to use classic email defaults in tests
echo "[setup] Disabling email improvements feature for consistent E2E testing..."
wp option update woocommerce_feature_email_improvements_enabled 'no' --allow-root

# Install and activate themes needed by E2E tests
echo "[setup] Installing themes for E2E tests..."
wp theme install twentytwentythree --allow-root || echo "Theme already installed"
wp theme install twentytwentyfour --allow-root || echo "Theme already installed"
wp theme install storefront --allow-root || echo "Theme already installed"
wp theme activate twentytwentythree --allow-root

# Upload test images specific to E2E tests
echo "[setup] Uploading E2E test images..."
# QIT runs scripts from the test package directory, so we can use relative paths
for image in image-01.png image-02.png image-03.png; do
    image_path="./test-data/images/$image"
    if [ -f "$image_path" ]; then
        echo "[setup] Uploading $image..."
        # Use --title to ensure checkbox name matches filename without extension
        wp media import "$image_path" --title="${image%.*}" --allow-root || echo "[setup] Image $image already uploaded"
    else
        echo "[setup] Warning: Image $image_path not found"
    fi
done

# Note: Tests create their own product/coupon data as needed to ensure isolation
# This follows the e2e-pw pattern where each test manages its own data

echo "[setup] E2E test-specific setup complete."
