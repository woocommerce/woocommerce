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

output 3 "Updating package textdomains..."

# Find woo-gutenberg-products-block textdomain and replace with woocommerce
find ./packages/woocommerce-blocks -iname '*.php' -exec sed -i.bak -e "s/'woo-gutenberg-products-block'/'woocommerce'/g" {} \;

# Find woocommerce-rest-api textdomain and replace with woocommerce
find ./packages/woocommerce-rest-api -iname '*.php' -exec sed -i.bak -e "s/'woocommerce-rest-api'/'woocommerce'/g" {} \;

# Cleanup backup files
find ./packages -name "*.bak" -type f -delete

output 2 "Done"
