#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# Composer install fails in PHP 5.2
	[ $TRAVIS_PHP_VERSION == '5.2' ] && exit;

	# No Xdebug and therefore no coverage in PHP 5.3
	[ $TRAVIS_PHP_VERSION == '5.3' ] && exit;

	composer self-update
	composer install --no-interaction

	## Only run on 7.0 box.
	if [[ ${TRAVIS_PHP_VERSION:0:3} == "7.0" ]]; then
		# Install CodeSniffer for WordPress Coding Standards checks. Only check once.
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
	fi

elif [ $1 == 'during' ]; then

	## Only run on 7.0 box.
	if [[ ${TRAVIS_PHP_VERSION:0:3} == "7.0" ]]; then
		# WordPress Coding Standards.
		# @link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards
		# @link http://pear.php.net/package/PHP_CodeSniffer/
		# -p flag: Show progress of the run.
		# -s flag: Show sniff codes in all reports.
		# -v flag: Print verbose output.
		# -n flag: Do not print warnings. (shortcut for --warning-severity=0)
		# --standard: Use WordPress as the standard.
		# --extensions: Only sniff PHP files.
		/tmp/phpcs/scripts/phpcs -p -s -n ./*.php --standard=./phpcs.ruleset.xml --extensions=php
		/tmp/phpcs/scripts/phpcs -p -s -n ./**/*.php --standard=./phpcs.ruleset.xml --extensions=php --ignore=./vendor/*.php
		/tmp/phpcs/scripts/phpcs -p -s -n ./**/**/*.php --standard=./phpcs.ruleset.xml --extensions=php --ignore=./vendor/**/*.php
		/tmp/phpcs/scripts/phpcs -p -s -n ./**/**/**/*.php --standard=./phpcs.ruleset.xml --extensions=php --ignore=./vendor/**/**/*.php
		/tmp/phpcs/scripts/phpcs -p -s -n ./**/**/**/**/*.php --standard=./phpcs.ruleset.xml --extensions=php --ignore=./vendor/**/**/*.php
	fi

elif [ $1 == 'after' ]; then

	## Only run on 7.0 box.
	if [[ ${TRAVIS_PHP_VERSION:0:3} == "7.0" ]]; then
		# Get scrutinizer ocular and run it
		wget https://scrutinizer-ci.com/ocular.phar
		chmod +x ocular.phar
		php ocular.phar code-coverage:upload --format=php-clover ./tmp/clover.xml
	fi

fi
