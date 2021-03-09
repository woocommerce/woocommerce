#!/usr/bin/env bash
#
# Run package scripts
#

# Script help
usage() {
	echo 'usage: npx wc-e2e <script>'
	echo 'scripts:'
	echo '         docker:up [initialization-script] - boot docker container'
	echo '         docker:down - shut down docker container'
	echo '         docker:ssh - open SSH shell into docker container'
	echo '         docker:clear-all - remove all docker containers'
	echo '         test:e2e [test-script] - run e2e test suite or specific test-script'
	echo '         test:e2e-dev [test-script] - run e2e test(s) in non-headless mode'
	echo '         test:e2e-debug [test-script] - run e2e test(s) in non-headless debug mode'
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
	'docker:up')
		./bin/docker-compose.sh up $2
		;;
	'docker:down')
		./bin/docker-compose.sh down
		;;
	'docker:ssh')
		docker exec -it $(node utils/get-app-name.js)_wordpress-www /bin/bash
		;;
	'docker:clear-all')
		docker rmi --force $(docker images -q)
		;;
	'test:e2e')
		./bin/wait-for-build.sh && ./bin/e2e-test-integration.js $2
		TESTRESULT=$?
		;;
	'test:e2e-dev')
		./bin/wait-for-build.sh && ./bin/e2e-test-integration.js --dev $2
		TESTRESULT=$?
		;;
	'test:e2e-debug')
		./bin/wait-for-build.sh && ./bin/e2e-test-integration.js --dev --debug $2
		TESTRESULT=$?
		;;
	*)
		usage
		;;
esac

# Restore working path
cd "$OLDPATH"

exit $TESTRESULT
