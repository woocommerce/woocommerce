#!/bin/bash

echo "Initializing WooCommerce E2E"

wp-env run tests-cli wp plugin activate woocommerce

wp-env run tests-cli wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=subscriber --path=/var/www/html

# Installing and activating the WordPress Importer plugin to import sample products"
wp-env run tests-cli wp plugin install wordpress-importer --activate

# Adding basic WooCommerce settings"
wp-env run tests-cli wp option set woocommerce_store_address 'Example Address Line 1'
wp-env run tests-cli wp option set woocommerce_store_address_2 'Example Address Line 2'
wp-env run tests-cli wp option set woocommerce_store_city 'Example City'
wp-env run tests-cli wp option set woocommerce_default_country 'US:CA'
wp-env run tests-cli wp option set woocommerce_store_postcode '94110'
wp-env run tests-cli wp option set woocommerce_currency 'USD'
wp-env run tests-cli wp option set woocommerce_product_type 'both'
wp-env run tests-cli wp option set woocommerce_allow_tracking 'no'
wp-env run tests-cli wp option set woocommerce_enable_checkout_login_reminder 'yes'
wp-env run tests-cli wp option set --format=json woocommerce_cod_settings '{"enabled":"yes"}'
wp-env run tests-cli wp option set woocommerce_coming_soon 'no'

#  WooCommerce shop pages
wp-env run tests-cli wp wc --user=admin tool run install_pages

# Importing WooCommerce sample products"
wp-env run tests-cli wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip

# install Storefront
wp-env run tests-cli wp theme install storefront --activate

# reduce the impact of background activities on the testing setup
wp-env run tests-cli wp config set DISABLE_WP_CRON true --raw --type=constant
wp-env run tests-cli wp config set WP_HTTP_BLOCK_EXTERNAL true --raw --type=constant

# Resolve container names once; fail loudly if wp-env is not running.
_wp_container="$(docker ps --filter name=tests-wordpress --format '{{.Names}}' | head -1)"
_db_container="$(docker ps --filter name=tests-mysql --format '{{.Names}}' | head -1)"
if [ -z "$_wp_container" ] || [ -z "$_db_container" ]; then
    echo "Error: wp-env containers not found. Run 'pnpm env:perf' first." >&2
    exit 1
fi

# Remove container-level strains for cleaner performance metrics: OPcache.
docker exec -u root "$_wp_container" bash -c \
    "printf '[opcache]\nopcache.enable=1\nopcache.memory_consumption=256\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=1\nopcache.revalidate_freq=0\n' > /usr/local/etc/php/conf.d/perf-opcache.ini"
docker restart "$_wp_container"

# Remove container-level strains for cleaner performance metrics: DB buffer and connections pool.
docker exec -u root "$_db_container" bash -c "printf '[mysqld]\ninnodb_buffer_pool_size=1073741824\ninnodb_flush_log_at_trx_commit=2\n' > /etc/mysql/conf.d/perf-tuning.cnf"
docker restart "$_db_container"
_deadline=$((SECONDS + 30))
until docker exec "$_db_container" mariadb -u root -ppassword -e "SELECT 1" &>/dev/null; do
    if [ $SECONDS -ge $_deadline ]; then
        echo "Error: MariaDB did not become ready within 30 seconds." >&2
        exit 1
    fi
    sleep 0.5
done

echo "Success! Your E2E Test Environment is now ready."
