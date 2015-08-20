#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# Lint files
	for file in $(find . -name "*.php" -and -not -path "./tmp/*" -and -not -path "./tests/*" -and -not -path "./apigen/*"); do
		output=$( php -l $file )
		if [[ $output == *"Errors parsing"* ]]; then
			echo "Build stopped because of a syntax error: $output";
			exit 1;
		fi
	done

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

	# get scrutinizer ocular and run it
	wget https://scrutinizer-ci.com/ocular.phar
	ocular.phar code-coverage:upload --format=php-clover ./tmp/clover.xml

fi
