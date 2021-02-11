#!/bin/bash

echo "Initializing WooCommerce E2E"

wp plugin install woocommerce --activate
wp theme install twentynineteen --activate
wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=customer --path=/var/www/html

# we cannot create API keys for the API, so we using basic auth, this plugin allows that.
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate

echo "Updating to WordPress Nightly Point Release"

wp plugin install wordpress-beta-tester --activate
wp core check-update
