                        #!/bin/sh

RELEASER_PATH=$(pwd)
PLUGIN_SLUG="woocommerce-admin"
GITHUB_ORG="woocommerce"
# wc-admin is always pre-release for now
IS_PRE_RELEASE=true

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
output 5 "wc-admin->GitHub RELEASE SCRIPT"
output 5 "============================="
echo

if [[ $1 == '' || $2 == '' ]]
  then
    output 1 "Please supply a tag and zip file name"
    exit 1
fi

printf "This script will build files and create a tag on GitHub based on your local branch."
echo
echo
printf "Built files will reflect feature flag configs for Core."
echo
echo
printf "The /dist/ directory will also be pushed to the tagged release."
echo
echo
echo "Before proceeding:"
echo " • Ensure you have checked out the branch you wish to release"
echo " • Ensure you have committed/pushed all local changes"
echo " • Did you remember to update changelogs, the readme and plugin files?"
echo " • Are there any changes needed to the readme file?"
echo " • If you are running this script directly instead of via '$ npm run build:release', ensure you have built assets and installed composer in --no-dev mode."
echo
output 3 "Do you want to continue? [y/N]: "
read -r PROCEED
echo

if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" != "y" ]; then
  output 1 "Release cancelled!"
  exit 1
fi

VERSION=$1
ZIP_FILE=$2

CURRENTBRANCH="$(git rev-parse --abbrev-ref HEAD)"

if [ ! -d "dist" ]; then
	output 3 "Dist directory not found. Aborting."
	exit 1
fi

output 2 "Starting release to GitHub..."
echo

# Create a release branch.
BRANCH="build/${VERSION}"
git checkout -b $BRANCH

# Force add feature-config.php
git add includes/feature-config.php --force
git add .
git commit -m "Adding feature-config.php directory to release" --no-verify

# Force add language files
git add languages/woocommerce-admin.pot --force
git add languages/woocommerce-admin.php --force
git add .
git commit -m "Adding translations to release" --no-verify

# Force add build directory and commit.
git add dist/. --force
git add .
git commit -m "Adding /dist directory to release" --no-verify

# Force add vendor directory and commit.
git add vendor/. --force
git add .
git commit -m "Adding /vendor directory to release" --no-verify

# Push branch upstream
git push origin $BRANCH

output 2 "Creating archive... 🎁"

build_files=$(ls dist/*/*.{js,css})

zip -r ${ZIP_FILE} \
	woocommerce-admin.php \
	uninstall.php \
	includes/ \
	images/ \
	$build_files \
	languages/woocommerce-admin.pot \
	languages/woocommerce-admin.php \
	readme.txt \
	src/ \
	vendor/

# Create the new release.
if [ $IS_PRE_RELEASE = true ]; then
	hub release create -m $VERSION -m "Release of version $VERSION. See readme.txt for details." -t $BRANCH --prerelease "v${VERSION}" --attach "${ZIP_FILE}"
else
	hub release create -m $VERSION -m "Release of version $VERSION. See readme.txt for details." -t $BRANCH "v${VERSION}" --attach "${ZIP_FILE}"
fi

git checkout $CURRENTBRANCH
git branch -D $BRANCH
git push origin --delete $BRANCH

output 2 "GitHub release complete."
