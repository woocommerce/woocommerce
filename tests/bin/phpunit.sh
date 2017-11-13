#!/usr/bin/env bash
if [[ ${TRAVIS_PHP_VERSION} == '7.1' ]]; then
	phpunit -c phpunit.xml --coverage-clover=coverage.clover
else
	phpunit -c phpunit.xml
fi
