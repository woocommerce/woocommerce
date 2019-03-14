#!/bin/sh

echo "-------------------------------------------------------"
echo "           WOOCOMMERCE BETA TESTER RELEASER            "
echo "-------------------------------------------------------"

# TEST USER SETTINGS
if [ -r $HOME/.wc-deploy ]; then
  echo "User config file read successfully!"
  . $HOME/.wc-deploy
else
  echo "You need create a ~/.wc-deploy file with your GITHUB_ACCESS_TOKEN settings."
  echo "Deploy aborted!"
  exit
fi

if [ -z $GITHUB_ACCESS_TOKEN ]; then
  echo "You need set the GITHUB_ACCESS_TOKEN in your ~/.wc-deploy file."
  echo "Deploy aborted!"
  exit
fi

# ASK INFO
read -p "VERSION: " VERSION
read -p "BRANCH: " BRANCH
echo "-------------------------------------------"
read -p "PRESS [ENTER] TO RELEASE VERSION ${VERSION} USING BRANCH ${BRANCH}"

# VARS
BUILD_PATH=$(pwd)"/build"
PRODUCT_NAME="woocommerce-beta-tester"
PRODUCT_NAME_GIT=${PRODUCT_NAME}"-git"
PRODUCT_NAME_SVN=${PRODUCT_NAME}"-svn"
SVN_REPO="http://plugins.svn.wordpress.org/woocommerce-beta-tester/"
GIT_REPO="https://github.com/woocommerce/woocommerce-beta-tester.git"
SVN_PATH="$BUILD_PATH/$PRODUCT_NAME_SVN"
GIT_PATH="$BUILD_PATH/$PRODUCT_NAME_GIT"

# Create build directory if does not exists
if [ ! -d $BUILD_PATH ]; then
  mkdir -p $BUILD_PATH
fi

# CHECKOUT SVN DIR IF NOT EXISTS
if [ ! -d $SVN_PATH ]; then
  echo "No SVN directory found, will do a checkout"
  svn checkout $SVN_REPO $SVN_PATH
fi

# DELETE OLD GIT DIR
rm -Rf $GIT_PATH

# CLONE GIT DIR
echo "Cloning GIT repo"
git clone $GIT_REPO $GIT_PATH --branch ${BRANCH} --single-branch

# MOVE INTO SVN DIR
cd $SVN_PATH

# UPDATE SVN
echo "Updating SVN"
svn update

# COPY GIT DIR TO TRUNK
cd $GIT_PATH
# rsync ./ $SVN_PATH/tags/${VERSION}/ --recursive --verbose --delete --delete-excluded \
rsync ./ $SVN_PATH/trunk/ --recursive --verbose --delete --delete-excluded \
  --exclude=".*/" \
  --exclude="*.md" \
  --exclude=".*" \
  --exclude="composer.*" \
  --exclude="*.lock" \
  --exclude=/vendor/ \
  --exclude=apigen.neon \
  --exclude=apigen/ \
  --exclude=bin/ \
  --exclude=CHANGELOG.txt \
  --exclude=Gruntfile.js \
  --exclude=node_modules/ \
  --exclude=package.json \
  --exclude=package-lock.json \
  --exclude=phpcs.xml \
  --exclude=phpunit.xml \
  --exclude=phpunit.xml.dist \
  --exclude=README.md \
  --exclude=tests/

cd $SVN_PATH

# DO THE REMOVE ALL DELETED FILES UNIX COMMAND
svn rm $( svn status | sed -e '/^!/!d' -e 's/^!//' )

# DO THE ADD ALL NOT KNOWN FILES UNIX COMMAND
svn add --force * --auto-props --parents --depth infinity -q

# COPY TRUNK TO TAGS/$VERSION
svn copy trunk tags/${VERSION}

# REMOVE THE GIT DIR
echo "Removing GIT dir"
rm -Rf $GIT_PATH

# CREATE THE GITHUB RELEASE
echo "Creating GITHUB release"
API_JSON=$(printf '{"tag_name": "%s","target_commitish": "%s","name": "%s","body": "Release of version %s","draft": false,"prerelease": false}' $VERSION $BRANCH $VERSION $VERSION)
curl --data "$API_JSON" https://api.github.com/repos/woocommerce/${PRODUCT_NAME}/releases?access_token=${GITHUB_ACCESS_TOKEN}

# DO SVN COMMIT
svn status
echo "svn commit -m \"Release "${VERSION}", see readme.txt for changelog.\""

# DONE, BYE
echo "RELEASER DONE"