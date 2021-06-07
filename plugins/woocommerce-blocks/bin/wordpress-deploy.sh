#!/bin/sh

RELEASER_PATH=$(pwd)
PLUGIN_SLUG="woo-gutenberg-products-block"
GITHUB_ORG="woocommerce"
GITHUB_SLUG="woocommerce-gutenberg-products-block"
IS_PRE_RELEASE=false
BUILD_PATH="${HOME}/blocks-deployment"

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

# Output colorized list
output_list() {
  echo "$(tput setaf "$1") • $2:$(tput sgr0) \"$3\""
}

# Release script
echo
output 4 "BLOCKS->WordPress.org RELEASE SCRIPT"
output 4 "===================================="
echo
printf "This script prepares a GitHub tag/release for WordPress.org SVN."
echo
echo
echo "Before proceeding:"
echo " • Ensure you have already created the release on GitHub. You can use '$ npm run deploy'."
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

# Set deploy variables
SVN_REPO="http://plugins.svn.wordpress.org/${PLUGIN_SLUG}/"
GIT_REPO="https://github.com/${GITHUB_ORG}/${GITHUB_SLUG}.git"
SVN_PATH="${BUILD_PATH}/${PLUGIN_SLUG}-svn"
GIT_PATH="${BUILD_PATH}/${PLUGIN_SLUG}-git"
BRANCH="v$VERSION"

echo
echo "-------------------------------------------"
echo
echo "Review all data before proceeding:"
echo
output_list 3 "Version to release" "${VERSION}"
output_list 3 "GIT tag to release" "${BRANCH}"
output_list 3 "GIT repository" "${GIT_REPO}"
output_list 3 "wp.org repository" "${SVN_REPO}"
echo
output 3 "Do you want to continue? [y/N]: "
read -r PROCEED
echo

if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" != "y" ]; then
  output 1 "Release cancelled!"
  exit 1
fi

# Create build directory if does not exists
if [ ! -d "$BUILD_PATH" ]; then
  mkdir -p "$BUILD_PATH"
fi

# Delete old GIT directory
rm -rf "$GIT_PATH"

# Clone GIT repository
output 2 "Cloning GIT repository..."
git clone "$GIT_REPO" "$GIT_PATH" --branch "$BRANCH" --single-branch || exit "$?"

if [ ! -d "$GIT_PATH/build" ]; then
	output 3 "Build directory not found in tag. Aborting."
	exit 1
fi

if [ ! -d "$GIT_PATH/vendor" ]; then
	output 3 "Vendor directory not found in tag. Aborting."
	exit 1
fi

# Checkout SVN repository if not exists
if [ ! -d "$SVN_PATH" ]; then
	output 2 "No SVN directory found, fetching files..."
	# Checkout project without any file
	svn co --depth=files "$SVN_REPO" "$SVN_PATH"

	cd "$SVN_PATH" || exit

	# Fetch main directories
	svn up assets branches trunk

	# Fetch tags directories without content
	svn up --set-depth=immediates tags
	# To fetch content for a tag, use:
	# svn up --set-depth=infinity tags/<tag_number>
else
	# Update SVN
	cd "$SVN_PATH" || exit
	output 2 "Updating SVN..."
	svn up
fi

# Copy GIT directory to trunk
output 2 "Copying project files to SVN trunk..."
sh "${RELEASER_PATH}/bin/copy-plugin-files.sh" "$GIT_PATH" "$SVN_PATH/trunk"
cd "$SVN_PATH"

# Update stable tag on trunk/readme.txt
if [ $IS_PRE_RELEASE = false ]; then
	output 2 "Updating \"Stable tag\" to ${VERSION} on trunk/readme.txt..."
	perl -i -pe"s/Stable tag: .*/Stable tag: ${VERSION}/" trunk/readme.txt
fi

# Do the remove all deleted files
svn st | grep -v "^.[ \t]*\..*" | grep "^\!" | awk '{print $2"@"}' | xargs svn rm

# Do the add all not know files
svn st | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2"@"}' | xargs svn add

# Copy trunk to tag/$VERSION
if [ ! -d "tags/${VERSION}" ]; then
	output 2 "Creating SVN tags/${VERSION}..."
	svn cp trunk tags/"${VERSION}"
fi

# Remove the GIT directory
output 2 "Removing GIT directory..."
rm -rf "$GIT_PATH"

# SVN commit messsage
output 2 "Ready to commit into WordPress.org Plugin's Directory!"
echo
echo "-------------------------------------------"
echo
output 3 "Run the following command to commit to SVN:"
echo "cd ${SVN_PATH} && svn ci -m \"Release ${VERSION}, see readme.txt for changelog.\""
