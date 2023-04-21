#!/usr/bin/env bash

ENABLE_HPOS="${ENABLE_HPOS:-0}"
ENABLE_NEW_PRODUCT_EDITOR="${ENABLE_NEW_PRODUCT_EDITOR:-0}"
ENABLE_TRACKING="${ENABLE_TRACKING:-0}"

echo -e 'Normalize permissions for wp-content directory \n'
docker-compose -f $(wp-env install-path)/docker-compose.yml run --rm -u www-data -e HOME=/tmp tests-wordpress sh -c "chmod -c ugo+w /var/www/html/wp-config.php \
&& chmod -c ugo+w /var/www/html/wp-content \
&& chmod -c ugo+w /var/www/html/wp-content/themes \
&& chmod -c ugo+w /var/www/html/wp-content/plugins \
&& mkdir -p /var/www/html/wp-content/upgrade \
&& chmod -c ugo+w /var/www/html/wp-content/upgrade"

docker-compose -f $(wp-env install-path)/docker-compose.yml run --rm -u www-data -e HOME=/tmp tests-cli sh -c "ls \
&& wp theme install twentynineteen --activate \
&& wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate \
&& wp plugin install wp-mail-logging --activate \
&& wp plugin install https://github.com/woocommerce/woocommerce-reset/archive/refs/heads/trunk.zip --activate \
&& wp rewrite structure '/%postname%/' --hard \
&& wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--user_registered='2022-01-01 12:23:45'"

echo -e 'Update Blog Name \n'
docker-compose -f $(wp-env install-path)/docker-compose.yml run --rm -u $(id -u) -e HOME=/tmp tests-cli sh -c 'wp option update blogname "WooCommerce Core E2E Test Suite"'

if [ $ENABLE_HPOS == 1 ]; then
	echo 'Enable the COT feature'
	docker-compose -f $(wp-env install-path)/docker-compose.yml run --rm -u www-data -e HOME=/tmp tests-cli sh -c "wp plugin install https://gist.github.com/vedanshujain/564afec8f5e9235a1257994ed39b1449/archive/b031465052fc3e04b17624acbeeb2569ef4d5301.zip --activate"
fi

if [ $ENABLE_NEW_PRODUCT_EDITOR == 1 ]; then
	echo 'Enable the new product editor feature'
	docker-compose -f $(wp-env install-path)/docker-compose.yml run --rm -u www-data -e HOME=/tmp tests-cli sh -c "wp plugin install https://github.com/woocommerce/woocommerce-experimental-enable-new-product-editor/releases/download/0.1.0/woocommerce-experimental-enable-new-product-editor.zip --activate"
fi

if [ $ENABLE_TRACKING == 1 ]; then
	echo 'Enable tracking'
	docker-compose -f $(wp-env install-path)/docker-compose.yml run --rm -u $(id -u) -e HOME=/tmp tests-cli sh -c "wp option update woocommerce_allow_tracking 'yes'"
fi
