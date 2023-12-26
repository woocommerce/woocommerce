#!/bin/bash

# Enable nicer messaging for build status.
BLUE_BOLD='\033[1;34m';
RED_BOLD='\033[1;31m';
COLOR_RESET='\033[0m';
GREEN_BOLD='\033[1;32m';
RED_BOLD='\033[1;31m';
YELLOW_BOLD='\033[1;33m';
error () {
	echo -e "${RED_BOLD}$1${COLOR_RESET}\n";
	exit 0;
}
status () {
	echo -e "${BLUE_BOLD}$1${COLOR_RESET}\n"
}
success () {
	echo -e "${GREEN_BOLD}$1${COLOR_RESET}\n"
}
warning () {
	echo -e "${YELLOW_BOLD}$1${COLOR_RESET}\n"
}

[[ -z "$1" ]] && {
	error "You must specify a branch to fix, for example: npm run fix-package-lock your/branch";
}

echo -e "${YELLOW_BOLD} ___ ___ ___
|   |   |   |
|___|___|___|
|   |   |   |
|___|___|___|
|   |   |   |
|___|___|___|

FIX PACKAGE LOCK
================
This script will attempt to rebase a Renovate PR and update the package.lock file.
Usage: npm run fix-package-lock branch/name
${COLOR_RESET}"

echo -e "${RED_BOLD}BEFORE PROCEEDING\n=================
You should check the PR on GitHub to see if it already has conflicts with trunk.
If it does, use the checkbox in the PR to force Renovate to rebase it for you.
Once the PR has been rebased, you can run this script, and then do a squash merge on GitHub.${COLOR_RESET}"

printf "Ready to proceed? [y/N]: "
read -r PROCEED
echo

if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" != "y" ]; then
	exit
fi

git fetch

if ! git checkout $1
then
	error "Unable to checkout branch";
else
	success "Checked out branch"
fi

status "Removing package-lock.json...";
rm package-lock.json

status "Installing dependencies...";
npm cache verify
npm install

status "Comitting updated package-lock.json...";
git add package-lock.json
git commit -m 'update package-lock.json'
git push --force-with-lease

success "Done. Package Lock has been updated. 🎉"
