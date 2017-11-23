#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# Composer install fails in PHP 5.2
	[[ ${TRAVIS_PHP_VERSION} == '5.2' ]] && exit;

	# Remove Xdebug from PHP runtime for all PHP version except 7.1 to speed up builds.
	# We need Xdebug enabled in the PHP 7.1 build job as it is used to generate code coverage.
	if [[ ${RUN_CODE_COVERAGE} != 1 ]]; then
		phpenv config-rm xdebug.ini
	fi

	if [[ ${TRAVIS_PHP_VERSION:0:2} == "5." ]]; then
		composer global require "phpunit/phpunit=4.8.*"
	else
		composer global require "phpunit/phpunit=6.2.*"
	fi

	if [[ ${RUN_PHPCS} == 1 ]]; then
		composer install
	fi

fi

if [ $1 == 'after' ]; then

	if [[ ${RUN_CODE_COVERAGE} == 1 ]]; then
		bash <(curl -s https://codecov.io/bash)
		wget https://scrutinizer-ci.com/ocular.phar
		chmod +x ocular.phar
		php ocular.phar code-coverage:upload --format=php-clover coverage.clover
	fi

fi
