#!/bin/bash
set -e

echo "[setup] WooCommerce E2E test environment setup..."

# ------------------------------------------------------------------
# TEST-SPECIFIC SETUP - Configuration needed for E2E tests to run
# ------------------------------------------------------------------

# Install Basic Auth plugin for REST API authentication (needed for API tests)
echo "[setup] Installing Basic Auth plugin for REST API..."
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate --allow-root || echo "Basic Auth plugin already installed"

# Install WP Mail Logging plugin for email tests
echo "[setup] Installing WP Mail Logging plugin..."
wp plugin install wp-mail-logging --activate --allow-root || echo "WP Mail Logging already installed"

# Note: Mail is disabled via PHP configuration (see disable-mail.ini)
# This allows email tests to work correctly

# Install Customize Store workaround for known issues (GitHub #44766)
echo "[setup] Installing Customize Store workaround..."
if [ -f "./bin/customize-store-workaround.php" ]; then
    mkdir -p wp-content/mu-plugins
    cp ./bin/customize-store-workaround.php wp-content/mu-plugins/customize-store-workaround.php
    echo "[setup] Customize Store workaround installed"
else
    echo "[setup] Warning: Customize Store workaround file not found"
fi

# Setup payment methods for checkout tests
echo "[setup] Configuring payment methods..."
wp option update woocommerce_cod_settings '{"enabled":"yes","title":"Cash on delivery","description":"Pay with cash upon delivery.","instructions":"Pay with cash upon delivery."}' --format=json --allow-root
wp option update woocommerce_bacs_settings '{"enabled":"yes","title":"Direct bank transfer"}' --format=json --allow-root  
wp option update woocommerce_cheque_settings '{"enabled":"yes","title":"Check payments"}' --format=json --allow-root

# Update blog name for test suite
echo "[setup] Setting blog name..."
wp option update blogname 'WooCommerce Core E2E Test Suite' --allow-root

# Set admin email to match test expectations
echo "[setup] Setting admin email..."
wp option update admin_email 'admin@woocommercecoree2etestsuite.com' --allow-root

# Set permalink structure for clean URLs
echo "[setup] Setting permalink structure..."
wp rewrite structure '/%postname%/' --hard --allow-root

# Install and activate themes needed by tests
echo "[setup] Installing themes..."
wp theme install twentytwentythree --allow-root || echo "Theme already installed"
wp theme install twentytwentyfour --allow-root || echo "Theme already installed"
wp theme install storefront --allow-root || echo "Theme already installed"
wp theme activate twentytwentythree --allow-root

# Create customer user for tests
echo "[setup] Creating customer user..."
wp user create customer customer@woocommercecoree2etestsuite.com \
    --user_pass=password \
    --role=customer \
    --first_name='Jane' \
    --last_name='Smith' \
    --user_registered='2022-01-01 12:23:45' \
    --allow-root || echo "Customer user already exists"

# Upload test images
echo "[setup] Uploading test images..."
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

echo "[setup] E2E test environment setup complete."