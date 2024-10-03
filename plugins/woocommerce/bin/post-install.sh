#!/usr/bin/env bash

# Required for dev and build environments: generate optimized autoloaders, safe to run in background.
composer dump-autoload --optimize --quiet &
# Required for dev environments: install tooling dependencies, if it failing we'll notice it in CI/locally.
composer bin all install --ansi &
