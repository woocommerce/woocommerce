#!/bin/sh

if ! [ -x "$(command -v hub)" ]; then
  echo 'Error: hub is not installed. Install from https://github.com/github/hub' >&2
  exit 1
fi

echo "Releasing to GitHub"
echo "==================="
echo "Before proceeding:"
echo " • Make sure you have updated the plugin version in woocommerce-admin-test-helper.php"
echo " • Ensure you have checked out the branch you wish to release"
echo " • Ensure you have committed/pushed all local changes"
echo " • Did you remember to update changelogs, the readme and plugin files?"
echo
echo "Do you want to continue? [y/N]: "
read -r PROCEED
echo

if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" != "y" ]; then
  echo "Release cancelled!"
  exit 1
fi

echo "What version do you want to release as? (make sure this matches the version in woocommerce-admin-test-helper.php)"
read -r VERSION

CURRENTBRANCH="$(git rev-parse --abbrev-ref HEAD)"

if [ ! -d "build" ]; then
	echo "Build directory not found. Aborting."
	exit 1
fi

echo "Starting release to GitHub..."
echo

# Create a release branch.
BRANCH="build/${VERSION}"
git checkout -b $BRANCH

# Force add build directory and commit.
git add build/. --force
git add .
git commit -m "Adding /build directory to release" --no-verify

# Push branch upstream
git push origin $BRANCH

# Create the zip archive
./bin/build-zip.sh

# Create the new release.
hub release create -m $VERSION -m "Release of version $VERSION." -t $BRANCH "v${VERSION}" --attach "./woocommerce-admin-test-helper.zip"

git checkout $CURRENTBRANCH
git branch -D $BRANCH
git push origin --delete $BRANCH

echo "GitHub release complete."

