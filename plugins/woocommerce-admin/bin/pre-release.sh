#!/bin/bash

# Enable nicer messaging for build status.
BLUE_BOLD='\033[1;34m';
GREEN_BOLD='\033[1;32m';
RED_BOLD='\033[1;31m';
YELLOW_BOLD='\033[1;33m';
COLOR_RESET='\033[0m';

error () {
	echo -e "\n🤯 ${RED_BOLD}$1${COLOR_RESET}\n"
}
status () {
	echo -e "\n👩‍💻 ${BLUE_BOLD}$1${COLOR_RESET}\n"
}
success () {
	echo -e "\n✅ ${GREEN_BOLD}$1${COLOR_RESET}\n"
}
warning () {
	echo -e "\n${YELLOW_BOLD}$1${COLOR_RESET}\n"
}

# Make sure there are no changes in the working tree.
changed=
if ! git diff --exit-code > /dev/null; then
	changed="file(s) modified"
elif ! git diff --cached --exit-code > /dev/null; then
	changed="file(s) staged"
fi
if [ ! -z "$changed" ]; then
	git status
	error "ERROR: Cannot start pre-release with dirty working tree. ☝️  Commit your changes and try again."
	exit 1
fi

status "Lets release WooCommerce Admin 🎉"

status "What branch/commit would you like to base this release on?"

echo -n "Branch/commit: "

read refspec

git checkout $refspec || { error "ERROR: Unable to checkout ${refspec}." ; exit 1; }

success "Checked out ${refspec}"

git pull origin ${refspec}

success "Pulled latest commits"

status "What version would you like to release?"

echo -n "Version: "

read release

branch="release/${release}"

exists=`git show-ref refs/heads/${branch}`

if [ -n "$exists" ]; then
    error "ERROR: release branch already exists."
    exit 1
fi

status "creating a release branch"

git checkout -b $branch || { error "ERROR: Unable to create release branch." ; exit 1; }

success "Release branch created: ${branch}"

status "Bumping version to ${release}"

npm --no-git-tag-version version $release || { error "ERROR: Invalid version number." ; exit 1; }

success "Version bumped successfully"

status "Run prestart scripts to propagate version numbers and update dependencies."

npm run prestart

status "Run docs script to make sure docs are updated."

npm run docs

status "Here are the changes so far. Make sure the following changes are reflected."

echo "- docs/: folder will have changes to documentation, if any."
echo "- package.json: new version number."
echo "- woocommerce-admin.php: new version numbers."
echo -e "\n"
echo -e "\n"

git status
