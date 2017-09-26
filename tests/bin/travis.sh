#!/usr/bin/env bash
# usage: travis.sh before|during|after

if [ $1 == 'before' ]; then

	# Composer install fails in PHP 5.2
	[[ ${TRAVIS_PHP_VERSION} == '5.2' ]] && exit;

	# No Xdebug and therefore no coverage in PHP 5.3
	[[ ${TRAVIS_PHP_VERSION} == '5.3' ]] && exit;

	if [[ ${TRAVIS_PHP_VERSION:0:2} == "5." ]]; then
		composer global require "phpunit/phpunit=4.8.*"
	else
		composer global require "phpunit/phpunit=6.2.*"
	fi

fi

if [ $1 == 'during' ]; then

	if [[ ${TRAVIS_PHP_VERSION} == ${PHP_LATEST_STABLE} ]]; then
		phpunit -c phpunit.xml --coverage-clover=coverage.clover
	else
		phpunit -c phpunit.xml
	fi

fi

if [ $1 == 'after' ]; then

	if [[ ${TRAVIS_PHP_VERSION} == ${PHP_LATEST_STABLE} ]]; then
		bash <(curl -s https://codecov.io/bash)

		wget https://scrutinizer-ci.com/ocular.phar
		php ocular.phar code-coverage:upload --format=php-clover coverage.clover
	fi

fi
