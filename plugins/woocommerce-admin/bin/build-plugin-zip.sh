#!/bin/bash

ZIP_FILE='woocommerce-admin.zip';
IS_CUSTOM_BUILD=false;
SLUG='';

while [ $# -gt 0 ]; do
	if [[ $1 == '-s' || $1 == '--slug' ]]; then
		IS_CUSTOM_BUILD=true
		SLUG=$2
		ZIP_FILE="woocommerce-admin-$2.zip";
	fi
	shift
done

# Exit if any command fails.
set -e

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

status "💃 Time to release WooCommerce Admin 🕺"
if [ $DRY_RUN ]; then
  warning "This is a dry run, nothing will be pushed up to Github, it will only generate zip files."
fi

warning "Please enter the version number to tag, for example, 1.0.0: "
read -r VERSION

if [ $IS_CUSTOM_BUILD = true ]; then
	PLUGIN_TAG="${VERSION}-${SLUG}"

	warning "A release on Github will be made with the tag ${GREEN_BOLD}$PLUGIN_TAG${COLOR_RESET}"
	warning "The resulting zip will be called ${GREEN_BOLD}$ZIP_FILE${COLOR_RESET}"
else
	PLUGIN_TAG="${VERSION}-plugin"
	CORE_TAG="${VERSION}"

	warning "You are building a regular release of wc-admin."
	warning "A plugin and Core release will be made to Github with the tags ${GREEN_BOLD}$PLUGIN_TAG${YELLOW_BOLD} and ${GREEN_BOLD}$CORE_TAG${COLOR_RESET}"
fi

warning "Ready to proceed? [y/N]: "
read -r PROCEED

if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" != "y" ]; then
  error "Release cancelled!"
  exit 1
fi

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

# Do a dry run of the repository reset. Prompting the user for a list of all
# files that will be removed should prevent them from losing important files!
status "Resetting the repository to pristine condition. ✨"
git clean -xdf --dry-run
warning "🚨 About to delete everything above! Is this okay? 🚨"
echo -n "[y]es/[N]o: "
read answer
if [ "$answer" != "${answer#[Yy]}" ]; then
	# Remove ignored files to reset repository to pristine condition. Previous
	# test ensures that changed files abort the plugin build.
	status "Cleaning working directory... 🛀"
	git clean -xdf
else
	error "Fair enough; aborting. Tidy up your repo and try again. 🙂"
	exit 1
fi

# Install PHP dependencies
status "Gathering PHP dependencies... 🐿️"
composer install --no-dev

# Build the plugin files.
status "Generating the plugin build... 👷‍♀️"
 WC_ADMIN_PHASE=plugin pnpm run build

# Make a Github release.
status "Starting a Github release... 👷‍♀️"
if [ $DRY_RUN ]; then
  PLUGIN_ZIP_FILE="woocommerce-admin-plugin.zip"
else
  PLUGIN_ZIP_FILE=$ZIP_FILE
fi
./bin/github-deploy.sh ${PLUGIN_TAG} ${PLUGIN_ZIP_FILE}

if [ $IS_CUSTOM_BUILD = false ]; then
	# Remove ignored files to reset repository to pristine condition. Previous
	# test ensures that changed files abort the plugin build.
	status "Cleaning working directory... 🛀"
	git clean -xdf -e woocommerce-admin-plugin.zip

	# Install PHP dependencies
	status "Gathering PHP dependencies... 🐿️"
	composer install --no-dev

	# Build the Core files.
	status "Generating a Core build... 👷‍♀️"
	WC_ADMIN_PHASE=core pnpm run build

	# Make a Github release.
	status "Starting a Github release... 👷‍♀️"
	./bin/github-deploy.sh ${CORE_TAG} ${ZIP_FILE}
fi

if [ $DRY_RUN ]; then
  output 2 "Dry run successfully finished."
  echo
  echo "Generated $PLUGIN_ZIP_FILE for the woocommerce-admin plugin build"
  echo "Generated $ZIP_FILE for the woocommerce-admin core build"
  exit;
fi
success "Done. You've built WooCommerce Admin! 🎉 "
