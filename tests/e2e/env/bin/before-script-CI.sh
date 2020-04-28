#!/bin/bash
#
# Setup for Travis CI

if [ -f ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini ]; then
  phpenv config-rm xdebug.ini
else
  echo "xdebug.ini does not exist"
fi

if [[ ! -z "$WP_VERSION" ]] ; then
  composer install --no-dev
  npm explore @woocommerce/e2e-env -- npm run install-wp-tests -- wc_e2e_tests root ' ' localhost $WP_VERSION
  composer global require "phpunit/phpunit=4.8.*|5.7.*"
fi

if [[ "$WP_TRAVISCI" == "phpcs" ]] ; then
  composer install
fi

npm install jest --global
