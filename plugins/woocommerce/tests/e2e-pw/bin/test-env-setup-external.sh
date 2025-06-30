#!/usr/bin/env bash

# Load env vars from the .env file
source "../.env"

clear

echo "--------------------------------------------------------"
echo -e "Installing plugins from .wp-env.json"
echo "--------------------------------------------------------"
wp plugin install --activate https://downloads.wordpress.org/plugin/akismet.zip \
    https://github.com/WP-API/Basic-Auth/archive/master.zip \
    https://downloads.wordpress.org/plugin/wp-mail-logging.zip

printf "\n\n\n"

echo "--------------------------------------------------------"
echo -e "Set default theme"
echo "--------------------------------------------------------"
wp theme install twentytwentythree
wp theme activate twentytwentythree

printf "\n\n\n"

echo "--------------------------------------------------------"
echo -e "Update URL structure"
echo "--------------------------------------------------------"
wp rewrite structure '/%postname%/' --hard

printf "\n\n\n"

echo "--------------------------------------------------------"
echo -e "Installing mu plugins"
echo "--------------------------------------------------------"

# Define the list of PHP files to process
mu_plugins=(
    "filter-setter"
    "process-waiting-actions"
    "test-helper-apis"
	"woocommerce-cleanup"
)

# Process each plugin
for plugin in "${mu_plugins[@]}"; do
    echo "Processing $plugin..."

    # Download the PHP file
    curl -o "$plugin.php" "https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/tests/e2e-pw/bin/$plugin.php"

    # Create a zip file
    (chmod 755 "$plugin.php" && zip "${plugin%}.zip" "$plugin.php")

    # Install the plugin from the local zip
    wp plugin install --force --activate "${plugin%}.zip"

    rm "$plugin.php" "${plugin%}.zip"
done

printf "\n\n\n"

echo "--------------------------------------------------------"
echo -e 'Add Customer user'
echo "--------------------------------------------------------"
wp user create customer customer@woocommercecoree2etestsuite.com \
    --user_pass=$CUSTOMER_PASSWORD \
    --role=customer \
    --first_name='Jane' \
    --last_name='Smith' \
    --display_name='Jane Smith'

printf "\n\n\n"

echo "--------------------------------------------------------"
echo -e 'Update Blog Name'
echo "--------------------------------------------------------"
wp option update blogname 'WooCommerce Core E2E Test Suite'

printf "\n\n\n"

echo "--------------------------------------------------------"
echo -e 'Enable tracking'
echo "--------------------------------------------------------"
wp option update woocommerce_allow_tracking 'yes'

printf "\n\n\n"

echo "--------------------------------------------------------"
echo -e 'Upload test images'
echo "--------------------------------------------------------"
echo "Importing test images..."
wp media import https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/tests/e2e-pw/test-data/images/image-01.png \
    https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/tests/e2e-pw/test-data/images/image-02.png \
    https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/tests/e2e-pw/test-data/images/image-03.png
