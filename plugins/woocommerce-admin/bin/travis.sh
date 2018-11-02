#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	composer global require "phpunit/phpunit=6.*"
	cd "$WP_CORE_DIR/wp-content/plugins/wc-admin/"
	composer install

fi
