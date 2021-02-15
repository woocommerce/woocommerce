#!/bin/sh

CURRENT_PATH=`pwd`

# Pull down the SVN repository.
echo "Pulling down the SVN repository for woocommerce-admin"
SVN_WOOCOMMERCE_ADMIN_PATH=/tmp/woocommerce/svn-woocommerce-admin
svn co https://plugins.svn.wordpress.org/woocommerce-admin/ $SVN_WOOCOMMERCE_ADMIN_PATH
cd  $SVN_WOOCOMMERCE_ADMIN_PATH

# Get the tagged version to release.
echo "Please enter the version number to release to wordpress.org, for example, 1.0.0: "
read -r VERSION

# Empty trunk/.
rm -rf trunk
mkdir trunk

# Download and unzip the plugin into trunk/.
echo "Downloading and unzipping the plugin"
PLUGIN_URL=https://github.com/woocommerce/woocommerce-admin/releases/download/v${VERSION}-plugin/woocommerce-admin.zip
curl -Lo woocommerce-admin.zip $PLUGIN_URL
unzip woocommerce-admin.zip -d trunk
rm woocommerce-admin.zip

# Add files in trunk/ to SVN.
cd trunk
svn add --force .
cd ..

# Commit the changes, which will automatically release the plugin to wordpress.org.
echo "Checking in the new version"
svn ci -m "Release v${VERSION}"

# Tag the release
echo "Tagging the release"
svn cp trunk tags/$VERSION
svn ci -m "Tagging v${VERSION}"

# Clean up.
cd ..
rm -rf svn-woocommerce-admin

cd $CURRENT_PATH


