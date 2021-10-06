#!/bin/bash
#
if [[ $1 ]]; then
#
# Set environment variables for Docker
#
	if ! [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		WP_VERSION=$(./bin/get-latest-docker-tag.js wordpress 5 2> /dev/null)
	fi
	if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		export WORDPRESS_VERSION=$WP_VERSION
	else
		export WORDPRESS_VERSION="5.8.0"
	fi

	if [[ $LATEST_WP_VERSION_MINUS ]]; then
		export WORDPRESS_VERSION=$(./bin/get-previous-version.js $WORDPRESS_VERSION $LATEST_WP_VERSION_MINUS 2> /dev/null)
	fi

	if ! [[ $TRAVIS_PHP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		TRAVIS_PHP_VERSION=$(./bin/get-latest-docker-tag.js php 7 2> /dev/null)
	fi
	if [[ $TRAVIS_PHP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		export DC_PHP_VERSION=$TRAVIS_PHP_VERSION
	else
		export DC_PHP_VERSION="7.4.22"
	fi

	if ! [[ $TRAVIS_MARIADB_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		TRAVIS_MARIADB_VERSION=$(./bin/get-latest-docker-tag.js mariadb 10 2> /dev/null)
	fi
	if [[ $TRAVIS_MARIADB_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		export DC_MARIADB_VERSION=$TRAVIS_MARIADB_VERSION
	else
		export DC_MARIADB_VERSION="10.6.4"
	fi

	if [[ $1 == 'up' ]]; then
		echo WordPress $WORDPRESS_VERSION
		echo PHP $DC_PHP_VERSION
		echo MariaDB $DC_MARIADB_VERSION
	fi
fi
#
# Run Docker
./bin/docker-compose.js $@
