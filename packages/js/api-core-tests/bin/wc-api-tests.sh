#!/usr/bin/env bash
#
# Run package scripts
#

# Script help
usage() {
	echo 'usage: npx wc-api-tests <script>'
	echo 'scripts:'
	echo '         test <group> - run API tests with the specified group'
	echo '         make:collection - build a Postman API Collection'
}

# Parameter check
if [ $# -lt 1 ]; then
	usage
	exit 1
fi

# Store original path
OLDPATH=$(pwd)

# Return value for CI test runs
TESTRESULT=0

# Use the script symlink to find and change directory to the root of the package
SCRIPTPATH=$(dirname "$0")
REALPATH=$(readlink "$0")
cd "$SCRIPTPATH/$(dirname "$REALPATH")/.."

# Run scripts
case $1 in
	'test')
		jest --group=$2
		TESTRESULT=$?
		;;
	'make:collection')
		node utils/api-collection/build-collection.js $2
		TESTRESULT=$?
		;;
	*)
		usage
		;;
esac

# Restore working path
cd "$OLDPATH"

exit $TESTRESULT
