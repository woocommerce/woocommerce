#!/usr/bin/env bash

wp-env run tests-cli "wp theme install twentynineteen --activate"

wp-env run tests-cli "wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate"

wp-env run tests-cli "wp plugin install wp-mail-logging --activate"

wp-env run tests-cli "wp plugin install https://github.com/woocommerce/woocommerce-reset/archive/refs/heads/trunk.zip --activate"

wp-env run tests-cli "wp rewrite structure /%postname%/"

wp-env run tests-cli "wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--path=/var/www/html
"

echo -e 'Update Blog Name \n'
wp-env run tests-cli 'wp option update blogname "WooCommerce Core E2E Test Suite"'
