#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# composer install fails in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	composer self-update

	# install php-coveralls to send coverage info
	composer init --require=satooshi/php-coveralls:0.7.0 -n
	composer install --no-interaction

	# Install CodeSniffer for WordPress Coding Standards checks.
	git clone -b master --depth 1 https://github.com/squizlabs/PHP_CodeSniffer.git /tmp/phpcs
	# Install WordPress Coding Standards.
	git clone -b master --depth 1 https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards.git /tmp/sniffs
	# Install PHP Compatibility sniffs.
	git clone -b master --depth 1 https://github.com/wimg/PHPCompatibility.git /tmp/sniffs/PHPCompatibility
	# Set install path for PHPCS sniffs.
    # @link https://github.com/squizlabs/PHP_CodeSniffer/blob/4237c2fc98cc838730b76ee9cee316f99286a2a7/CodeSniffer.php#L1941
    /tmp/phpcs/scripts/phpcs --config-set installed_paths /tmp/sniffs
	# After CodeSniffer install you should refresh your path.
    phpenv rehash


elif [ $1 == 'after' ]; then

	# no Xdebug and therefore no coverage in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	# send coverage data to coveralls
	php vendor/bin/coveralls --verbose --exclude-no-stmt

	# get scrutinizer ocular and run it
	wget https://scrutinizer-ci.com/ocular.phar
	ocular.phar code-coverage:upload --format=php-clover ./tmp/clover.xml

fi
