#!/usr/bin/env bash

ENABLE_TRACKING="${ENABLE_TRACKING:-0}"

echo -e 'Install default theme \n'
wp --path=/var/www/html theme install twentytwentythree

echo -e 'Install TwentyTwentyFour theme \n'
wp --path=/var/www/html theme install twentytwentyfour

echo -e 'Install Basic Auth Plugin \n'
wp --path=/var/www/html plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate

echo -e 'Reset plugin that allows us to reset WooCommerce state between tests.'
wp --path=/var/www/html plugin install https://github.com/woocommerce/woocommerce-reset/zipball/trunk/ --activate

echo -e 'install the WP Mail Logging plugin to test emails'
wp --path=/var/www/html plugin install wp-mail-logging --activate

echo -e 'Update URL structure \n'
wp --path=/var/www/html rewrite structure '/%postname%/' --hard

echo -e 'Add Customer user \n'
wp --path=/var/www/html user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=customer \
	--first_name='Jane' \
	--last_name='Smith' \
	--user_registered='2022-01-01 12:23:45'

echo -e 'Update Blog Name \n'
wp --path=/var/www/html option update blogname 'WooCommerce Core E2E Test Suite'

if [ $ENABLE_TRACKING == 1 ]; then
	echo -e 'Enable tracking\n'
	wp --path=/var/www/html option update woocommerce_allow_tracking 'yes'
fi

