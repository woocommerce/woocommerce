#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# Composer install fails in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	# No Xdebug and therefore no coverage in PHP 5.3
	[ $TRAVIS_PHP_VERSION == '5.3' ] && exit;

	composer self-update
	composer install --no-interaction

elif [ $1 == 'after' ]; then

	# Get scrutinizer ocular and run it
	wget https://scrutinizer-ci.com/ocular.phar
	chmod +x ocular.phar
	php ocular.phar code-coverage:upload --format=php-clover ./tmp/clover.xml

fi
