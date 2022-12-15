#!/usr/bin/env bash

ENABLE_HPOS="${ENABLE_HPOS:-0}"

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
	--path=/var/www/html \
	--user_registered='2022-01-01 12:23:45'
"

echo -e 'Update Blog Name \n'
wp-env run tests-cli 'wp option update blogname "WooCommerce Core E2E Test Suite"'

if [ $ENABLE_HPOS == 1 ]; then
	echo 'Enable the COT feature'
	wp-env run tests-cli "wp plugin install https://gist.github.com/vedanshujain/564afec8f5e9235a1257994ed39b1449/archive/b031465052fc3e04b17624acbeeb2569ef4d5301.zip --activate"
fi
