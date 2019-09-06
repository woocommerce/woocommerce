#!/bin/sh
VERSION=${VERSION:=\$VID\:\$}
IS_PRE_RELEASE=${IS_PRE_RELEASE:=false}

# replace all instances of $VID:$ with the release version but only when not pre-release.
if [ $IS_PRE_RELEASE = false ]; then
	find ./src -name "*.php" -print0 | xargs -0 perl -i -pe 's/\$VID:\$/'${VERSION}'/g'
fi

# Update version number in readme.txt
perl -i -pe 's/Stable tag:*.+/Stable tag: '${VERSION}'/' readme.txt

# Update version in main plugin file
perl -i -pe 's/Version:*.+/Version: '${VERSION}'/' woocommerce-gutenberg-products-block.php

# Update version in package.json
perl -i -pe 's/"version":*.+/"version": "'${VERSION}'",/' package.json

# Update version in main file
perl -i -pe "s/VERSION =*.+/VERSION = '${VERSION}';/" src/Package.php
