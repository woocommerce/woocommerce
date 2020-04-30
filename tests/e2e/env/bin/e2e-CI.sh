#!/bin/bash
#
# Script for Travis CI

if [[ ${RUN_CORE_E2E} == 1 ]]; then
  if [ -f ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini ]; then
    phpenv config-rm xdebug.ini
  else
    echo "xdebug.ini does not exist"
  fi

  if [[ ! -z "$WP_VERSION" ]] ; then
    composer install --no-dev
    npm explore @woocommerce/e2e-environment -- npm run install-wp-tests -- wc_e2e_tests root ' ' localhost $WP_VERSION
    composer global require "phpunit/phpunit=4.8.*|5.7.*"
  fi

  if [[ "$WP_TRAVISCI" == "phpcs" ]] ; then
    composer install
  fi

  npm install jest --global
  composer require wp-cli/i18n-command
  npm run build
  npm explore @woocommerce/e2e-environment -- npm run docker:up
  npm explore @woocommerce/e2e-environment -- npm run test:e2e
  npm explore @woocommerce/e2e-environment -- npm run docker:down
fi