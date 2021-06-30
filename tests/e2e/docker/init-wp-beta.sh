#!/bin/bash

echo "Initializing WooCommerce E2E"

wp plugin activate woocommerce
wp theme install twentynineteen --activate
wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=customer --path=/var/www/html

# we cannot create API keys for the API, so we using basic auth, this plugin allows that.
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate

# install the WP Mail Logging plugin to test emails
wp plugin install wp-mail-logging --activate

echo "Updating to WordPress Nightly Point Release"

wp plugin install wordpress-beta-tester --activate
wp core check-update
