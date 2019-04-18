#!/usr/bin/env bash

if [[ ${RUN_PHPCS} == 1 ]] || [[ ${RUN_E2E} == 1 ]]; then
	exit
fi

if [[ ${RUN_CODE_COVERAGE} == 1 ]]; then
	phpdbg -qrr $HOME/.composer/vendor/bin/phpunit -d memory_limit=-1 -c phpunit.xml --coverage-clover=coverage.clover --exclude-group=timeout $@
elif [[ ${TRAVIS_PHP_VERSION:0:3} != "5.2" ]]; then
	$HOME/.composer/vendor/bin/phpunit -c phpunit.xml $@
else
	phpunit -c phpunit.xml $@
fi
