#!/usr/bin/env bash
# usage: travis.sh before|after

if [ "$1" == 'before' ]; then
	cd "$WP_CORE_DIR/wp-content/plugins/woocommerce-admin/"
	pnpm run build:feature-config

	# This is a temporary solution for a breaking change in composer 2.0.7
	# to the Jetpack autoloader. This can be removed when
	# https://github.com/Automattic/jetpack/pull/17813 is merged.
	composer self-update 2.0.6
	if [[ "$COMPOSER_DEV" == "1" ]]; then
		composer install
	else
		composer install --no-dev
	fi
	# phpunit ^7 is not supported in php 7.0
	if [[ "$PHPUNIT" == "6" ]]; then
		curl -fsSL -o vendor/bin/phpunit https://phar.phpunit.de/phpunit-6.5.9.phar --create-dirs && chmod +x vendor/bin/phpunit
	fi
fi
