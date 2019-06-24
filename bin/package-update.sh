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

updating=false

# Script args.
while [ ! $# -eq 0 ]
do
	case "$1" in
		--updating | -u)
			updating=true
			;;
	esac
	shift
done

# Autoloader
output 3 "Updating autoloader classmaps..."
composer dump-autoload --no-dev
output 2 "Done"

# Convert textdomains
output 3 "Updating package textdomains..."

# Replace text domains within packages with woocommerce
find ./packages/woocommerce-blocks -iname '*.php' -exec sed -i.bak -e "s/'woo-gutenberg-products-block'/'woocommerce'/g" {} \;
find ./packages/woocommerce-rest-api -iname '*.php' -exec sed -i.bak -e "s/'woocommerce-rest-api'/'woocommerce'/g" {} \;

# Cleanup backup files
find ./packages -name "*.bak" -type f -delete
output 2 "Done"

if ( $updating ); then
	# Update POT file
	output 3 "Updating POT file..."
	grunt makepot
	output 2 "Done"
fi
