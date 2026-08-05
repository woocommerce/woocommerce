#!/bin/sh

# Output colorized strings
#
# Color codes:
# 0 - black
# 1 - red
# 2 - green
# 3 - yellow
# 4 - blue
# 5 - magenta
# 6 - cian
# 7 - white
output() {
	echo "$(tput setaf "$1")$2$(tput sgr0)"
}

if [ -z "$(php -r "echo version_compare(PHP_VERSION,'7.2','>=');")" ]; then
	output 1 "PHP 7.2 or newer is required to run Mozart, the current PHP version is $(php -r 'echo PHP_VERSION;')"
	exit 1
fi

output 6 "Building lib package"

# Clean the output directories to remove any files not present anymore
rm -rf lib/packages lib/classes
mkdir lib/packages lib/classes

# Prefer `composer install` so pinned versions in lib/composer.lock are respected
# and routine rebuilds don't silently bump unrelated dependencies. Fall back to
# `composer update` only when the lock is out of sync with composer.json — i.e. a
# developer has just added or bumped a package on purpose. Pass --update to force
# an update (useful to pick up in-range upstream releases without editing composer.json).
if [ "${1:-}" = "--update" ] || ! composer validate -d ./lib --check-lock --quiet; then
	composer update -d ./lib
else
	composer install -d ./lib
fi

# Re-apply manual patches that Mozart overwrites on rebuild (see lib/README.md).
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
	git restore lib/packages/Detection/MobileDetect.php 2>/dev/null || true
fi

output 6 "Updating autoload files"

composer dump-autoload
