#!/bin/sh

# See output.sh for an explanation of available the color codes.
. $(dirname "$0")/output.sh

output 6 "Prefixing the appropriate vendor namespaces with Automattic\WooCommerce\Vendor"

# Replace "League\Container" in "use" and "namespace" with "Automattic\WooCommerce\Vendor\League\Container".
REGEX='s/^[[:space:]]*(use|namespace)[[:space:]]*(League\\Container)/\1 Automattic\\WooCommerce\\Vendor\\\2/g'

find ./vendor/league/container -iname '*.php' -exec sed -i '.bak' -E -e "$REGEX" {} \;
find ./vendor/league/container -name "*.php.bak" -type f -delete

# Replace too in the composer.json file for the package.
sed -i '.bak' -E -e "s/\"(League\\\\\\\Container)/\"Automattic\\\\\\\WooCommerce\\\\\\\Vendor\\\\\\\\\1/g" vendor/league/container/composer.json
rm -f vendor/league/container/composer.json.bak

