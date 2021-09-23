#!/bin/bash

echo "Initializing WooCommerce E2E"

wp plugin activate woocommerce

wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=subscriber --path=/var/www/html

# we cannot create API keys for the API, so we using basic auth, this plugin allows that.
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate

# update permalinks to `pretty` to make it easier for testing APIs with k6
wp option update permalink_structure '/%postname%'

# install the WP Mail Logging plugin to test emails
wp plugin install wp-mail-logging --activate

# Installing and activating the WordPress Importer plugin to import sample products"
wp plugin install wordpress-importer --activate

# Adding basic WooCommerce settings"
wp option set woocommerce_store_address "Example Address Line 1"
wp option set woocommerce_store_address_2 "Example Address Line 2"
wp option set woocommerce_store_city "Example City"
wp option set woocommerce_default_country "US:CA"
wp option set woocommerce_store_postcode "94110"
wp option set woocommerce_currency "USD"
wp option set woocommerce_product_type "both"
wp option set woocommerce_allow_tracking "no"
wp option set woocommerce_enable_checkout_login_reminder "yes"
wp option set --format=json woocommerce_cod_settings '{"enabled":"yes"}'

#  WooCommerce shop pages
wp wc --user=admin tool run install_pages

# Importing WooCommerce sample products"
wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip

# install Storefront
wp theme install storefront --activate

echo "Success! Your E2E Test Environment is now ready."
