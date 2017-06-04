if [[ ${TRAVIS_BRANCH} == 'master' ]] && [[ ${TRAVIS_EVENT_TYPE} != 'pull_request' ]] && [[ ${TRAVIS_PHP_VERSION} == ${PHP_LATEST_STABLE} ]]; then
	phpunit -c phpunit.xml.dist --coverage-clover ./tmp/clover.xml
else
	phpunit -c phpunit.xml.dist
fi
