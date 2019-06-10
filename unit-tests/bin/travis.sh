#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	if [[ ${RUN_PHPCS} == 1 ]]; then
		cd "$WP_CORE_DIR/wp-content/plugins/woocommerce-rest-api/"
		# This can (currently) only run for PHP 7.1+
		composer install
	fi

fi
