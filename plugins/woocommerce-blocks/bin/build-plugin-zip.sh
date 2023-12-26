#!/bin/bash

# Exit if any command fails.
set -e

TYPE='PRODUCTION'

print_usage() {
	echo "build-plugin-zip - attempt to build a plugin"
	echo "By default this will build a clean production build and zip archive"
	echo "of the built plugin assets"
	echo " "
	echo "build-plugin-zip [arguments]"
	echo " "
	echo "options:"
	echo "-h          show brief help"
	echo "-d          build plugin in development mode"
	echo "-z          build zip only, skipping build commands (so it uses files"
	echo "            existing on disk already)"
	echo " "
}

# get args
while getopts 'hdz' flag; do
	case "${flag}" in
		h) print_usage ;;
		d) TYPE='DEV' ;;
		z) TYPE='ZIP_ONLY' ;;
		*)
			print_usage
			exit 1
			;;
	esac
done

# Tool for grabbing version from package.json
get_version() {
	grep '\"version\"' package.json \
	| cut -d ':' -f 2 \
	| sed 's/"//g' \
	| sed 's/,//g' \
	| sed 's/ //g'
}

# Set version
VERSION=$(get_version)

# Store paths
SOURCE_PATH=$(pwd)

# Change to the expected directory.
cd "$(dirname "$0")"
cd ..

# Enable nicer messaging for build status.
BLUE_BOLD='\033[1;34m';
GREEN_BOLD='\033[1;32m';
RED_BOLD='\033[1;31m';
YELLOW_BOLD='\033[1;33m';
COLOR_RESET='\033[0m';
error () {
	echo -e "\n${RED_BOLD}$1${COLOR_RESET}\n"
}
status () {
	echo -e "\n${BLUE_BOLD}$1${COLOR_RESET}\n"
}
success () {
	echo -e "\n${GREEN_BOLD}$1${COLOR_RESET}\n"
}
warning () {
	echo -e "\n${YELLOW_BOLD}$1${COLOR_RESET}\n"
}

status "💃 Time to build a WooCommerce Blocks ZIP 🕺"

if [ -z "$NO_CHECKS" ]; then
	# Make sure there are no changes in the working tree. Release builds should be
	# traceable to a particular commit and reliably reproducible. (This is not
	# totally true at the moment because we download nightly vendor scripts).
	changed=
	if ! git diff --exit-code > /dev/null; then
		changed="file(s) modified"
	elif ! git diff --cached --exit-code > /dev/null; then
		changed="file(s) staged"
	fi
	if [ ! -z "$changed" ]; then
		git status
		error "ERROR: Cannot build plugin zip with dirty working tree. ☝️
		Commit your changes and try again."
		exit 1
	fi
fi

# Add version to composer.json
perl -i -pe "s/\"type\":*.+/\"type\":\"wordpress-plugin\",\n\t\"version\": \"${VERSION}\",/" composer.json

# Run the build.
npm list webpack
if [ $TYPE = 'DEV' ]; then
	status "Installing dependencies... 👷‍♀️"
	status "==========================="
	composer install
	PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true npm install
	status "==========================="
	status "Generating development build... 👷‍♀️"
	status "==========================="
	npm list webpack
	npm run dev
	status "==========================="
elif [ $TYPE = 'ZIP_ONLY' ]; then
	composer dump-autoload
	status "Skipping build commands - using current built assets on disk for built archive...👷‍♀️"
	status "==========================="
else
	status "Installing dependencies... 📦"
	composer install --no-dev
	PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true npm install
	status "==========================="
	status "Generating production build... 👷‍♀️"
	status "==========================="
	npm list webpack
	npm run build
	status "==========================="
fi

# Generate the plugin zip file.
status "Creating archive... 🎁"
mkdir zip-file
mkdir zip-file/build
sh "$SOURCE_PATH/bin/copy-plugin-files.sh" "$SOURCE_PATH" "$SOURCE_PATH/zip-file"
cd "$(pwd)/zip-file"
if [ $TYPE = 'DEV' ]; then
	touch blocks.ini
	printf 'woocommerce_blocks_phase = 3\nwoocommerce_blocks_env = development' > blocks.ini
fi
zip -r ../woocommerce-gutenberg-products-block.zip ./
cd ..
rm -r zip-file

# cleanup composer.json
git checkout -- composer.json

# regenerate classmap for development use
composer dump-autoload

success "Done. You've built WooCommerce Blocks! 🎉"
