#!/usr/bin/env bash
if [[ ${RUN_CODE_COVERAGE} == 1 ]]; then
	vendor/bin/phpunit -c phpunit.xml --coverage-clover=coverage.clover
else
	vendor/bin/phpunit -c phpunit.xml
fi
