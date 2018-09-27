#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	composer global require "phpunit/phpunit=6.*"

	if [[ ${RUN_PHPCS} == 1 ]]; then
		composer install
	fi

fi
