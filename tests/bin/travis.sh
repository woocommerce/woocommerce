#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'after' ]; then

	if [[ ${RUN_CODE_COVERAGE} == 1 ]]; then
		bash <(curl -s https://codecov.io/bash)
		wget https://scrutinizer-ci.com/ocular.phar
		chmod +x ocular.phar
		php ocular.phar code-coverage:upload --format=php-clover coverage.clover
	fi

	if [[ ${RUN_E2E} == 1 && $(ls -A $TRAVIS_BUILD_DIR/screenshots) ]]; then
		if [[ -z "${ARTIFACTS_KEY}" ]]; then
			echo "Screenshots were not uploaded. Please run the e2e tests locally to see failures."
		else
			curl -sL https://raw.githubusercontent.com/travis-ci/artifacts/master/install | bash
			artifacts upload
		fi
	fi

fi
