#!/usr/bin/env bash
if [[ ${RUN_CODE_COVERAGE} == 1 ]]; then
	phpunit -c phpunit.xml --coverage-clover=coverage.clover
else
	phpunit -c phpunit.xml
fi
