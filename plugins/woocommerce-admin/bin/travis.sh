#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then
	cd "$WP_CORE_DIR/wp-content/plugins/woocommerce-admin/"
	npm run build:feature-config
	if [[ ${RUN_PHPCS} == 1 ]]; then
		composer install
	else
		composer install --no-dev
	fi

fi
