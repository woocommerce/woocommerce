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

if [ ! -d "packages/" ]; then
	output 1 "./packages doesn't exist!"
	output 1 "run \"composer install\" before proceed."
fi

# Autoloader
output 3 "Updating autoloader classmaps..."
composer dump-autoload
output 2 "Done"

# Convert textdomains
output 3 "Updating package PHP textdomains..."

# Replace text domains within packages with woocommerce
npm run packages:fix:textdomain
output 2 "Done!"

output 3 "Updating package JS textdomains..."
find ./packages/woocommerce-blocks -iname '*.js' -exec sed -i.bak -e "s/'woo-gutenberg-products-block'/'woocommerce'/g" -e "s/\"woo-gutenberg-products-block\"/'woocommerce'/g" {} \;
find ./packages/woocommerce-blocks -iname '*.js' -exec sed -i.bak -e "s/'woocommerce-admin'/'woocommerce'/g" -e "s/\"woocommerce-admin\"/'woocommerce'/g" {} \;
find ./packages/woocommerce-admin -iname '*.js' -exec sed -i.bak -e "s/'woocommerce-admin'/'woocommerce'/g" -e "s/\"woocommerce-admin\"/'woocommerce'/g" {} \;

# Cleanup backup files
find ./packages -name "*.bak" -type f -delete
output 2 "Done!"
