#!/bin/sh

RELEASER_PATH=$(pwd)
PLUGIN_SLUG="woocommerce-gutenberg-products-block"
GITHUB_ORG="woocommerce"
IS_PRE_RELEASE=false

# Functions
# Check if string contains substring
is_substring() {
  case "$2" in
    *$1*)
      return 0
      ;;
    *)
      return 1
      ;;
  esac
}

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

if ! [ -x "$(command -v hub)" ]; then
  echo 'Error: hub is not installed. Install from https://github.com/github/hub' >&2
  exit 1
fi

# Release script
echo
output 2 "BLOCKS RELEASE SCRIPT"
output 2 "====================="
echo
printf "This script will build files and create a tag on GitHub based on your local branch."
echo
echo
printf "The /build/ directory will also be pushed to the tag."
echo
echo
printf "Before proceeding, ensure you have checked out the correct branch you wish to release, and have committed/pushed all local changes."
echo
echo
output 3 "Do you want to continue? [y/N]: "
read -r PROCEED
echo

if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" != "y" ]; then
  output 1 "Release cancelled!"
  exit 1
fi
echo
output 3 "Please enter the version number to tag, for example, 1.0.0:"
read -r VERSION
echo

# Check if is a pre-release.
if is_substring "-" "${VERSION}"; then
    IS_PRE_RELEASE=true
	output 2 "Detected pre-release version!"
fi

if [ ! -d "build" ]; then
	output 3 "Build directory not found. Aborting."
	exit 1
fi

printf "Ready to proceed? [y/N]: "
read -r PROCEED
echo

if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" != "y" ]; then
  output 1 "Release cancelled!"
  exit 1
fi

output 2 "Starting release to GitHub..."
echo

CURRENTBRANCH="$(git rev-parse --abbrev-ref HEAD)"

# Create a release branch.
BRANCH="build/${VERSION}"
git checkout -b $BRANCH

# Force add build directory and commit.
git add build/. --force
git add .
git commit -m "Adding /build directory to release"

# Push branch upstream
git push origin $BRANCH

# Create the new release.
if [ $IS_PRE_RELEASE ]; then
	hub release create -m $VERSION -m "Release of version $VERSION. See readme.txt for details." -t $BRANCH --prerelease "v${VERSION}"
else
	hub release create -m $MESSAGE -m "Release of version $VERSION. See readme.txt for details." -t $BRANCH "v${VERSION}"
fi

git checkout $CURRENTBRANCH
git branch -D $BRANCH
git push origin --delete $BRANCH

output 2 "GitHub release complete."
