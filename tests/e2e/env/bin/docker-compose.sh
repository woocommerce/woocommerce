#!/bin/bash
#
if [[ $1 ]]; then
#
# Set environment variables for Docker
#
	if ! [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		WP_VERSION=$(docker image ls wordpress | grep -E '\s\d+\.\d+(?:\.\d+)?\s' | head -n 1 | awk '{print $2}')
	fi
	if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		export WORDPRESS_VERSION=$WP_VERSION
	else
		export WORDPRESS_VERSION="5.4.2"
	fi

	if ! [[ $TRAVIS_PHP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		TRAVIS_PHP_VERSION=$(docker image ls php | grep -E '\s\d+\.\d+(?:\.\d+)?\s' | head -n 1 | awk '{print $2}')
	fi
	if [[ $TRAVIS_PHP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		export DC_PHP_VERSION=$TRAVIS_PHP_VERSION
	else
		export DC_PHP_VERSION="7.4.8"
	fi

	if ! [[ $TRAVIS_MARIADB_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		TRAVIS_MARIADB_VERSION=$(docker image ls mariadb | grep -E '\s\d+\.\d+(?:\.\d+)?\s' | head -n 1 | awk '{print $2}')
	fi
	if [[ $TRAVIS_MARIADB_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		export DC_MARIADB_VERSION=$TRAVIS_MARIADB_VERSION
	else
		export DC_MARIADB_VERSION="10.5.4"
	fi

	if [[ $1 == 'up' ]]; then
		echo WordPress $WORDPRESS_VERSION
		echo PHP $DC_PHP_VERSION
		echo MariaDB $DC_MARIADB_VERSION
	fi
fi
#
# Run Docker
./bin/docker-compose.js $1
