#!/usr/bin/env bash

# Required for dev and build environments: generate optimized autoloaders, safe to run in background.
composer dump-autoload --optimize --quiet &
# Required for dev environments: update tooling dependencies, not suitable to run in background.
composer bin all update --ansi
