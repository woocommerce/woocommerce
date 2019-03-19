#!/usr/bin/env bash

if [[ ${RUN_PHPCS} == 1 ]] || [[ ${RUN_E2E} == 1 ]]; then
	exit
fi

if [[ ${RUN_CODE_COVERAGE} == 1 ]]; then
	if [[ -n $COMPOSER_BIN_DIR && -x $COMPOSER_BIN_DIR/phpunit ]]; then
		$COMPOSER_BIN_DIR/phpunit --version
		phpdbg -qrr -d memory_limit=-1 $COMPOSER_BIN_DIR/phpunit -c phpunit.xml --coverage-clover=coverage.clover --exclude-group=timeout
	else
		phpunit --version
		phpdbg -qrr -d memory_limit=-1 phpunit -c phpunit.xml --coverage-clover=coverage.clover --exclude-group=timeout
	fi
else
	phpunit -c phpunit.xml
fi
