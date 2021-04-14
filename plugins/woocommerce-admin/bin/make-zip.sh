#!/bin/sh
#
# Build a installable plugin zip

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

output 2 "Creating archive... 🎁"

ZIP_FILE=$1

build_files=$(find dist \( -name '*.js' -o -name '*.css' \))
asset_files=$(find dist \( -name '*.asset.php' \))
if [[ $(find dist/app \( -name '*.asset.php' \) | wc -l) -gt 1 ]]; then
	output 3 "$asset_files"
	output 1 "Multiple asset.php files exists per directory, have you removed the old build?"
	exit;
fi

zip -r "${ZIP_FILE}" \
	woocommerce-admin.php \
	uninstall.php \
	includes/ \
	images/ \
	$build_files \
	$asset_files \
	languages/woocommerce-admin.pot \
	readme.txt \
	src/ \
	vendor/
