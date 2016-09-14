#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# Composer install fails in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	# No Xdebug and therefore no coverage in PHP 5.3
	[ $TRAVIS_PHP_VERSION == '5.3' ] && exit;

	composer self-update

	# Install php-coveralls to send coverage info
	composer init --require=satooshi/php-coveralls:0.7.0 -n
	composer install --no-interaction

elif [ $1 == 'after' ]; then

	# No Xdebug and therefore no coverage in PHP 5.2 or 5.3
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;
	[ $TRAVIS_PHP_VERSION == '5.3' ] && exit;

	# Send coverage data to coveralls
	php vendor/bin/coveralls --verbose --exclude-no-stmt

	# Get scrutinizer ocular and run it
	wget https://scrutinizer-ci.com/ocular.phar
	chmod +x ocular.phar
	php ocular.phar code-coverage:upload --format=php-clover ./tmp/clover.xml

fi
