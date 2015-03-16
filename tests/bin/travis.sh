#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# composer install fails in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	# install php-coveralls to send coverage info
	composer init --require=satooshi/php-coveralls:0.7.x-dev -n
	composer install --no-interaction

elif [ $1 == 'after' ]; then

	# no Xdebug and therefore no coverage in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	# send coverage data to coveralls
	php vendor/bin/coveralls --verbose --exclude-no-stmt
fi
