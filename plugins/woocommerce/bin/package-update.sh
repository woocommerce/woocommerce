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

if [ ! -d "packages/" ]; then
	output 1 "./packages doesn't exist!"
	output 1 "run \"composer install\" before proceed."
fi

# Autoloader optimization is forced to prevent OOM fatal errors in JetPack Autoloader.
# We running the optimization in background to speedup build process, with dev-environments in mind.
# Building zips enforcing the optimization during the install process, so we are good around building releases as well.
output 3 "Generating optimized autoloaders"
composer dump-autoload --optimize --quiet &

# Replace translation domains within 'packages' directory.
# Cleanup leftover backup files (e.g. sed used to update translation domains).
output 3 "Updating translation domains in 'packages' directory"
node ./bin/package-update-textdomain.js &

# find ./packages -name "*.bak" -type f -delete &

output 2 "Done!"
