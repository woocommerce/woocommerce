#!/bin/bash

ENABLE_HPOS="${ENABLE_HPOS:-0}"

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

#  WooCommerce shop pages
wp-env run tests-cli wp wc --user=admin tool run install_pages

# Importing WooCommerce sample products"
wp-env run tests-cli wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip

# install Storefront
wp-env run tests-cli wp theme install storefront --activate

if [ $ENABLE_HPOS == 1 ]; then
	echo -e 'Enable High-Performance Order Tables\n'
	wp-env run tests-cli wp plugin install https://gist.github.com/vedanshujain/564afec8f5e9235a1257994ed39b1449/archive/b031465052fc3e04b17624acbeeb2569ef4d5301.zip --activate
fi

echo "Success! Your E2E Test Environment is now ready."
