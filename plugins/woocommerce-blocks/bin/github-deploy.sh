#!/bin/sh

RELEASER_PATH="$(pwd)"
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
output 5 "BLOCKS->GitHub RELEASE SCRIPT"
output 5 "============================="
echo
printf "This script will build files and create a tag on GitHub based on your local branch."
echo
echo
printf "The /build/ directory will also be pushed to the tagged release."
echo
echo
echo "Before proceeding:"
echo " • Ensure you have checked out the branch you wish to release"
echo " • Ensure you have committed/pushed all local changes"
echo " • Did you remember to update changelogs, the readme and plugin files?"
echo " • Are there any changes needed to the readme file?"
echo " • If you are running this script directly instead of via '$ npm run deploy', ensure you have built assets and installed composer in --no-dev mode."
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

CURRENTBRANCH="$(git rev-parse --abbrev-ref HEAD)"

# Check if is a pre-release.
if is_substring "-" "${VERSION}"; then
    IS_PRE_RELEASE=true
	output 2 "Detected pre-release version!"
fi


echo
output 3 "Will this release get published to WordPress.org? Note: If the version on WordPress.org is greater than ${VERSION}, then you should answer 'N' here. [y/N]:"
read -r DO_WP_DEPLOY
echo

if [ ! -d "build" ]; then
	output 3 "Build directory not found. Aborting."
	exit 1
fi

# Safety check, if a patch release is detected ask for verification.
VERSION_PIECES=${VERSION//[^.]}

# explode version parts
split_version() {
	echo ${VERSION} \
	| sed 's/\./ /g'
}

SPLIT_VERSION=($(split_version))

# IF VERSION_PIECES is less than 2 then its invalid so let's update it and notify
if [[ "${#VERSION_PIECES}" -lt "2" ]]; then
	if [[ ${#VERSION_PIECES} -eq "0" ]]; then
		VERSION=${VERSION}.0.0
	else
		VERSION=${VERSION}.0
	fi
fi

if [[ "${#VERSION_PIECES}" -ge "2" && "${SPLIT_VERSION[2]}" -ne "0" && "$(echo "${DO_WP_DEPLOY:-n}" | tr "[:upper:]" "[:lower:]")" = "y" ]]; then
	output 1 "The version you entered (${VERSION}) looks like a patch version. Since this version will be deployed to WordPress.org, it will become the latest available version. Are you sure you want that (no will abort)?: [y/N]"
	read -r ABORT
	echo
	if [ "$(echo "${ABORT:-n}" | tr "[:upper:]" "[:lower:]")" != "y" ]; then
		output 1 "Release cancelled!"
		exit 1
	fi
else
	echo "$(output 4 "The version is set as ") $(output 3 "${VERSION}") $(output 4 " and the next step will be to bump all the version strings in relevant files.")"
	printf "Ready to proceed? [y/N]: "
	read -r PROCEED
	echo
fi

if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" != "y" ]; then
  output 1 "Release cancelled!"
  exit 1
fi

# Version changes
output 2 "Updating version numbers in files and regenerating php autoload classmap (note pre-releases will not have the readme.txt stable tag updated)..."
source "$RELEASER_PATH/bin/version-changes.sh"

composer dump-autoload

# remove composer.json version bump after autoload regen (we don't commit it)
git checkout -- composer.json

output 2 "Committing version change..."
echo

git commit -am "Bumping version strings to new version." --no-verify
git push origin $CURRENTBRANCH

# Tag existing version for reference
output 2 "Creating tag for current non-built branch on GitHub..."
echo
DEVTAG="v${VERSION}-dev"
git tag $DEVTAG
git push origin $DEVTAG

output 2 "Prepping release for GitHub..."
echo

# Create a release branch.
BRANCH="build/${VERSION}"
git checkout -b $BRANCH

# Force add build directory and commit.
git add build/. --force
git add .
git commit -m "Adding /build directory to release" --no-verify

# Force add vendor directory and commit.
git add vendor/. --force
git add .
git commit -m "Adding /vendor directory to release" --no-verify

# Push branch upstream
git push origin $BRANCH

# Create the new release.
if [ "$(echo "${DO_WP_DEPLOY:-n}" | tr "[:upper:]" "[:lower:]")" = "y" ]; then
	if [ $IS_PRE_RELEASE = true ]; then
		hub release create -m $VERSION -m "Release of version $VERSION. See readme.txt for details." -t $BRANCH --prerelease "v${VERSION}"
	else
		hub release create -m $VERSION -m "Release of version $VERSION. See readme.txt for details." -t $BRANCH "v${VERSION}"
	fi
else
	git tag "v${VERSION}"
	git push origin "v${VERSION}"
fi

git checkout $CURRENTBRANCH
git branch -D $BRANCH
git push origin --delete $BRANCH

# regenerate classmap for development
composer dump-autoload

output 2 "GitHub release complete."
