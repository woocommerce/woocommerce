#!/usr/bin/env bash
# usage: travis.sh before|after

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

elif [ $1 == 'after' ]; then

	# Only run on master, not pull requests, latest stable PHP box (defined in .travis.yml).
	if [[ ${TRAVIS_BRANCH} == 'master' ]] && [[ ${TRAVIS_EVENT_TYPE} != 'pull_request' ]] && [[ ${TRAVIS_PHP_VERSION} == ${PHP_LATEST_STABLE} ]]; then
		wget https://scrutinizer-ci.com/ocular.phar
		chmod +x ocular.phar
		php ocular.phar code-coverage:upload --format=php-clover ./tmp/clover.xml
	fi

fi
