#!/usr/bin/env bash
"$TRAVIS_BUILD_DIR"/vendor/bin/phpunit --version
"$TRAVIS_BUILD_DIR"/vendor/bin/phpunit -c phpunit.xml $@