#!/usr/bin/env bash

# Required for dev and build environments: generate optimized autoloaders, safe to run in background.
composer dump-autoload --optimize --quiet &
# Install tooling dependencies per the pinned bin locks so routine root updates don't
# cascade-bump unrelated dev tools (phpcs, phpunit, mozart, wp-cli). Falls back to update
# when a bin's lock is out of sync with its composer.json — i.e. a developer has just added
# or bumped a bin package on purpose. To intentionally refresh all bin versions without
# editing composer.json, run `composer bin all update` directly.
if ! composer bin all install --ansi; then
	composer bin all update --ansi
fi
