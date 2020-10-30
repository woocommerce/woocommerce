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

output 6 "Moving the appropriate vendor packages to Automattic\WooCommerce\Internal\Vendor"

# Delete the entire contents of src/Internal/Vendor, except README.md
find src/Internal/Vendor/* ! -name README.md -prune -exec rm -rf {} +

# Move the appropriate vendor packages to src/Internal/Vendor
# (see extra/mozart for configuration)
./vendor/bin/mozart compose
