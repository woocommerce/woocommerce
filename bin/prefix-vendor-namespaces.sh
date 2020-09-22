#!/bin/sh

. $(dirname "$0")/output.sh

output 6 "Prefixing the appropriate vendor namespaces with Automattic\WooCommerce\Vendor"

# Replace "League\Container" in "use" and "namespace" with "Automattic\WooCommerce\Vendor\League\Container".
REGEX='s/^[[:space:]]*(use|namespace)[[:space:]]*(League\\Container)/\1 Automattic\\WooCommerce\\Vendor\\\2/g'

# macOS and Linux have a slightly different syntax for 'sed -i' so we need two versions.
if [[ "$OSTYPE" == "darwin"* ]]; then
  find ./vendor/league/container -type f -name '*.php' -print0 | xargs -0 sed -i '' -E -e "$REGEX"
else
  find ./vendor/league/container -type f -name '*.php' -print0 | xargs -0 sed -i -E -e "$REGEX"
fi
