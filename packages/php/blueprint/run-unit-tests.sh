#!/bin/bash

wp-env run tests-cli --env-cwd=wp-content/plugins/blueprint ./vendor/bin/phpunit
