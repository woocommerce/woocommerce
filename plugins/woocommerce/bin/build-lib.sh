#!/bin/sh

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

if [ -z "$(php -r "echo version_compare(PHP_VERSION,'7.2','>=');")" ]; then
	output 1 "PHP 7.2 or newer is required to run Mozart, the current PHP version is $(php -r 'echo PHP_VERSION;')"
	exit 1
fi

output 6 "Building lib package"

# Clean the output directories to remove any files not present anymore
rm -rf lib/packages lib/classes
mkdir lib/packages lib/classes

# Running update on the lib package will automatically run Mozart
composer update -d ./lib

output 6 "Updating autoload files"

composer dump-autoload
