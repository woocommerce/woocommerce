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

# Function to generate report
report() {

	# Set the API_TEST_REPORT_DIR to $PWD if it wasn't set
	ALLURE_RESULTS_DIR="${API_TEST_REPORT_DIR:-$PWD}/allure-results"
	ALLURE_REPORT_DIR="${API_TEST_REPORT_DIR:-$PWD}/allure-report"

	echo "Generating report..."
	allure generate --clean "$ALLURE_RESULTS_DIR" --output "$ALLURE_REPORT_DIR"
	REPORT_EXIT_CODE=$?

	# Suggest opening the report
	if [[ $REPORT_EXIT_CODE -eq 0 && $GITHUB_ACTIONS != "true" ]]; then
		echo "To view the report on your browser, run:"
		echo ""
		echo "pnpm dlx allure open \"$ALLURE_REPORT_DIR\""
		echo ""
	fi
}

# Use the script symlink to find and change directory to the root of the package
SCRIPTPATH=$(dirname "$0")
REALPATH=$(readlink "$0")
cd "$SCRIPTPATH/$(dirname "$REALPATH")/.."

# Run scripts
case $1 in
'test')
	node_modules/.bin/jest --group=$2 --runInBand
	TESTRESULT=$?
	report
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
