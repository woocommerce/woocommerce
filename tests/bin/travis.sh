#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# Composer install fails in PHP 5.2
	[[ ${TRAVIS_PHP_VERSION} == '5.2' ]] && exit;

	# No Xdebug and therefore no coverage in PHP 5.3
	[[ ${TRAVIS_PHP_VERSION} == '5.3' ]] && exit;

	if [[ ${TRAVIS_PHP_VERSION:0:2} == "7." ]]; then
		composer global require "phpunit/phpunit=5.7.*"
	else
		composer global require "phpunit/phpunit=4.8.*"
	fi

	composer self-update
	composer install --no-interaction

elif [ $1 == 'during' ]; then

	## Only run on latest stable PHP box (defined in .travis.yml).
	if [[ ${TRAVIS_PHP_VERSION} == ${PHP_LATEST_STABLE} ]]; then
		# WordPress Coding Standards.
		# @link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards
		# @link http://pear.php.net/package/PHP_CodeSniffer/
		# -p flag: Show progress of the run.
		# -s flag: Show sniff codes in all reports.
		# -v flag: Print verbose output.
		# -n flag: Do not print warnings. (shortcut for --warning-severity=0)
		# --standard: Use WordPress as the standard.
		# --extensions: Only sniff PHP files.
		./vendor/bin/phpcs -p -s -n ./*.php --standard=./phpcs.ruleset.xml --extensions=php
		./vendor/bin/phpcs -p -s -n ./**/*.php --standard=./phpcs.ruleset.xml --extensions=php --ignore=./vendor/*.php --ignore=./tests/*.php
		./vendor/bin/phpcs -p -s -n ./**/**/*.php --standard=./phpcs.ruleset.xml --extensions=php --ignore=./vendor/**/*.php --ignore=./tests/**/*.php
		./vendor/bin/phpcs -p -s -n ./**/**/**/*.php --standard=./phpcs.ruleset.xml --extensions=php --ignore=./vendor/**/**/*.php --ignore=./tests/**/**/*.php
		./vendor/bin/phpcs -p -s -n ./**/**/**/**/*.php --standard=./phpcs.ruleset.xml --extensions=php --ignore=./vendor/**/**/*.php --ignore=./tests/**/**/*.php
	fi

elif [ $1 == 'after' ]; then

	## Only run on master, not pull requests, latest stable PHP box (defined in .travis.yml).
	if [[ ${TRAVIS_BRANCH} == 'master' ]] && [[ ${TRAVIS_EVENT_TYPE} != 'pull_request' ]] && [[ ${TRAVIS_PHP_VERSION} == ${PHP_LATEST_STABLE} ]]; then
		wget https://scrutinizer-ci.com/ocular.phar
		chmod +x ocular.phar
		php ocular.phar code-coverage:upload --format=php-clover ./tmp/clover.xml
	fi

fi
