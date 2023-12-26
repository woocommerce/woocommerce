#!/bin/sh
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

if [ ! $VERSION ]; then
	output 3 "Please enter the version number, for example, 1.0.0:"
	read -r VERSION
fi

output 2 "Updating version numbers in files..."

IS_PRE_RELEASE=false
# Check if is a pre-release.
if is_substring "-" "${VERSION}"; then
    IS_PRE_RELEASE=true
	output 4 "Detected pre-release version."
fi

if [ $IS_PRE_RELEASE = false ]; then
	# Replace all instances of $VID:$ with the release version but only if not pre-release.
	find ./src woocommerce-gutenberg-products-block.php -name "*.php" -print0 | xargs -0 perl -i -pe 's/\$VID:\$/'${VERSION}'/g'
	# Update version number in readme.txt but only if not pre-release.
	perl -i -pe 's/Stable tag:*.+/Stable tag: '${VERSION}'/' readme.txt
	output 2 "Version numbers updated in readme.txt and \$VID:\$ instances."
else
	output 4 "Note: pre-releases will not have the readme.txt stable tag updated."
fi

# Update version in main plugin file.
perl -i -pe 's/Version:*.+/Version: '${VERSION}'/' woocommerce-gutenberg-products-block.php

# Update version in package.json.
perl -i -pe 's/"version":*.+/"version": "'${VERSION}'",/' package.json

# Update version in package-lock.json.
perl -i -0777 -pe 's/"name": "\@woocommerce\/block-library",\s*\K"version":*.+\n/"version": "'${VERSION}'",\n/g' package-lock.json

# Update version in src/Package.php.
perl -i -pe "s/version \= '*.+';/version = '${VERSION}';/" src/Package.php

# Update version in composer.json.
perl -i -pe 's/"version":*.+/"version": "'${VERSION}'",/' composer.json

output 2 "Version numbers updated in main plugin file, package.json, package-lock.json, src/Package.php and composer.json."
