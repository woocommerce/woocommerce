#!/usr/bin/env bash

# Required for dev and build environments: generate optimized autoloaders, safe to run in background.
composer dump-autoload --optimize --quiet &

if [ -z ${CI+y} ]; then
	# Required for dev-environments: install tooling dependencies in background
    composer bin all install --ansi &
else
	# Required for CI-environments: install tooling dependencies synchronously
	composer bin all install --ansi
fi
