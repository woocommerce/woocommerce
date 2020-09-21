#!/bin/sh

. $(dirname "$0")/output.sh

output 6 "Prefixing the appropriate vendor namespaces with Automattic\WooCommerce\Vendor"

find vendor/league/container -type f -name "*.php" -print0 | \
xargs -0 sed -i '' -E -e 's/^[[:space:]]*(use|namespace)[[:space:]]*(League\\Container)/\1 Automattic\\WooCommerce\\Vendor\\\2/g'
