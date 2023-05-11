#!/usr/bin/env bash

ENABLE_HPOS="${ENABLE_HPOS:-0}"
ENABLE_NEW_PRODUCT_EDITOR="${ENABLE_NEW_PRODUCT_EDITOR:-0}"
ENABLE_TRACKING="${ENABLE_TRACKING:-0}"

echo -e 'Activate twentynineteen theme \n'
wp-env run tests-cli "wp theme activate twentynineteen"

echo -e 'Update URL structure \n'
wp-env run tests-cli "wp rewrite structure '/%postname%/' --hard"

echo -e 'Add Customer user \n'
wp-env run tests-cli "wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--user_registered='2022-01-01 12:23:45'"

echo -e 'Update Blog Name \n'
wp-env run tests-cli "wp option update blogname 'WooCommerce Core E2E Test Suite'"

if [ $ENABLE_HPOS == 1 ]; then
	echo 'Enable the COT feature'
	wp-env run tests-cli "wp plugin install https://gist.github.com/vedanshujain/564afec8f5e9235a1257994ed39b1449/archive/b031465052fc3e04b17624acbeeb2569ef4d5301.zip --activate"
fi

if [ $ENABLE_NEW_PRODUCT_EDITOR == 1 ]; then
	echo 'Enable the new product editor feature'
	wp-env run tests-cli "wp plugin install https://github.com/woocommerce/woocommerce-experimental-enable-new-product-editor/releases/download/0.1.0/woocommerce-experimental-enable-new-product-editor.zip --activate"
fi

if [ $ENABLE_TRACKING == 1 ]; then
	echo 'Enable tracking'
	wp-env run tests-cli "wp option update woocommerce_allow_tracking 'yes'"
fi
