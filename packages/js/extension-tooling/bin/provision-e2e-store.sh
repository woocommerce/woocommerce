#!/usr/bin/env bash
set -e

if [[ $# -gt 1 || ( $# -eq 1 && "$1" != "--metric-units" ) ]]; then
	echo 'Usage: bash provision-e2e-store.sh [--metric-units]' >&2
	exit 1
fi

# The extension's test-only mu-plugin registers the product importer and checkout filters.
wp eval 'if ( ! class_exists( "WC_Docker_Provision_Manager" ) ) { WP_CLI::error( "Load the extension’s E2E provisioning mu-plugin first." ); }'

echo 'Setting up the WooCommerce test store…'

wp rewrite structure '/%postname%/'
wp rewrite flush
wp core version --extra
wp plugin list
wp theme activate storefront
wp theme list

address='{"first_name":"John","last_name":"Doe","company":"Automattic","country":"US","address_1":"addr 1","address_2":"addr 2","city":"San Francisco","state":"CA","postcode":"94107","phone":"123456789"}'
wp wc customer update 1 --user=1 --billing="$address" --shipping="$address"

wp option update woocommerce_store_address '60 29th Street #343'
wp option update woocommerce_store_city 'San Francisco'
wp option update woocommerce_store_postcode 94110
wp option update woocommerce_default_country 'US:CA'
wp option update woocommerce_default_customer_address 'geolocation'
wp option update woocommerce_currency 'USD'
wp option update woocommerce_currency_pos 'left'
wp option update woocommerce_onboarding_profile --format=json '{"skipped":true}'
if [[ ${1:-} == --metric-units ]]; then
	wp option update woocommerce_weight_unit 'kg'
	wp option update woocommerce_dimension_unit 'cm'
fi
wp option update woocommerce_coming_soon 'no'

block_cart_id=$(wp option get woocommerce_cart_page_id)
block_checkout_id=$(wp option get woocommerce_checkout_page_id)
wp post update "$block_cart_id" --post_title='Blocks cart' --post_name='blocks-cart'
wp post update "$block_checkout_id" --post_title='Blocks checkout' --post_name='blocks-checkout'

cart_id=$(wp post create --post_type=page --porcelain --post_status=publish --post_title='Cart' --post_name='cart' --post_content='[woocommerce_cart]')
checkout_id=$(wp post create --post_type=page --porcelain --post_status=publish --post_title='Checkout' --post_name='checkout' --post_content='[woocommerce_checkout]')
wp option set tests_legacy_woocommerce_cart_page_id "$cart_id"
wp option set tests_legacy_woocommerce_checkout_page_id "$checkout_id"
wp option set e2e_tests_mode 'legacy'

wp wc docker-e2e provision
wp action-scheduler run
wp config set WP_DEBUG true --raw
