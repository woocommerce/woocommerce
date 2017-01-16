if [[ ${TRAVIS_PHP_VERSION} == ${PHP_LATEST_STABLE} ]]; then
	phpunit -c phpunit.xml.dist --coverage-clover ./tmp/clover.xml
else
	phpunit -c phpunit.xml.dist
fi
