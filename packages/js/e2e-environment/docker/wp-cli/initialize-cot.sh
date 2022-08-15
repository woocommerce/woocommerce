#!/bin/bash

echo "Initializing WooCommerce E2E with COT enabled"

# This is a workaround to accommodate different directory names.
wp plugin activate --all
wp plugin deactivate akismet
wp plugin deactivate hello

wp theme install twentynineteen --activate
wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--path=/var/www/html

# we cannot create API keys for the API, so we using basic auth, this plugin allows that.
wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate

# Reset plugin that allows us to reset WooCommerce state between tests.
wp plugin install https://github.com/woocommerce/woocommerce-reset/zipball/trunk/ --activate

# install the WP Mail Logging plugin to test emails
wp plugin install wp-mail-logging --activate

# Enable the COT feature
wp plugin install https://gist.github.com/vedanshujain/564afec8f5e9235a1257994ed39b1449/archive/9d5f174ebf8eec8e0ce5417d00728524c7f3b6b3.zip --activate

# initialize pretty permalinks
wp rewrite structure /%postname%/
