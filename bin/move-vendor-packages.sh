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

output 6 "Moving the appropriate vendor packages to Automattic\WooCommerce\Internal\Vendor"

# Delete the entire contents of src/Internal/Vendor, except README.md
find src/Internal/Vendor/* ! -name README.md -prune -exec rm -rf {} +

# Move the appropriate vendor packages to src/Internal/Vendor
# (see extra/mozart for configuration)
./vendor/bin/mozart compose || exit "$?"

output 6 "Updating autoload files"

composer dump-autoload
