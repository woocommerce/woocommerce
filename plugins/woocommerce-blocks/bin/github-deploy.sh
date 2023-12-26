#!/bin/sh

RELEASER_PATH="$(pwd)"
IS_PRE_RELEASE=false

# When it is set to true, the commands are just printed but not executed.
DRY_RUN_MODE=false
# When it is set to true, the commands that affect the local env are executed (e.g. git commit), while the commands that affect the remote env are not executed but just printed (e.g. git push)
SIMULATE_RELEASE_MODE=false

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

simulate() {
  if $2 = true ; then
	eval "$1"
  else
	output 3 "DRY RUN: $1"
  fi
}


run_command() {
  if $DRY_RUN_MODE = true; then
	output 3 "DRY RUN: $1"
  elif $SIMULATE_RELEASE_MODE = true; then
		simulate "$1" $2
  else
	eval "$1"
  fi
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

# Update versions in files in case they were not updated.
output 2 "Updating version numbers in files and regenerating php autoload classmap (note pre-releases will not have the readme.txt stable tag updated)..."
run_command "source '$RELEASER_PATH/bin/change-versions.sh'" true

composer dump-autoload

# remove composer.json version bump after autoload regen (we don't commit it)
run_command "git checkout -- composer.json" true

output 2 "Committing version change..."
echo

run_command "git commit -am 'Bumping version strings to new version.' --no-verify" true
run_command "git push origin $CURRENTBRANCH" false

# Tag existing version for reference
output 2 "Creating tag for current non-built branch on GitHub..."
echo
DEVTAG="v${VERSION}-dev"
run_command "git tag $DEVTAG" true
run_command "git push origin $DEVTAG" false

output 2 "Prepping release for GitHub..."
echo

# Create a release branch.
BRANCH="build/${VERSION}"

run_command "git checkout -b $BRANCH" true

# Force add build directory and commit.
run_command "git add build/. --force" true
run_command "git add ." true
run_command "git commit -m 'Adding /build directory to release' --no-verify" true

# # Force add vendor directory and commit.
run_command "git add vendor/. --force" true
run_command "git add ." true
run_command "git commit -m 'Adding /vendor directory to release' --no-verify" true

# # Push branch upstream
run_command "git push origin $BRANCH" false

# Create the new release.
if [ "$(echo "${DO_WP_DEPLOY:-n}" | tr "[:upper:]" "[:lower:]")" = "y" ]; then
	if [ $IS_PRE_RELEASE = true ]; then
		run_command "hub release create -m $VERSION -m 'Release of version $VERSION. See readme.txt for details.' -t $BRANCH --prerelease 'v${VERSION}'" false
	else
		run_command "hub release create -m $VERSION -m 'Release of version $VERSION. See readme.txt for details.' -t $BRANCH 'v${VERSION}'" false
	fi
else
	run_command "git tag 'v${VERSION}'" true
	run_command "git push origin 'v${VERSION}'" false
fi

run_command "git checkout $CURRENTBRANCH" true
run_command "git branch -D $BRANCH" true
run_command "git push origin --delete $BRANCH" false

# regenerate classmap for development
run_command "composer dump-autoload" false

output 2 "GitHub release complete."
