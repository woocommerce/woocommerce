#!/usr/bin/env bash

if [[ ${RUN_PHPCS} == 1 ]] || [[ ${RUN_E2E} == 1 ]]; then
	exit
fi

if [[ ${RUN_CODE_COVERAGE} == 1 ]]; then
	phpdbg -qrr $HOME/.composer/vendor/phpunit/phpunit/phpunit -d memory_limit=-1 -c phpunit.xml --coverage-clover=coverage.clover --exclude-group=timeout
else
	$HOME/.composer/vendor/phpunit/phpunit/phpunit -c phpunit.xml
fi
