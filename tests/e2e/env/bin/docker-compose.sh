#!/bin/bash
#
if [[ $1 ]]; then
#
# Set environment variables for Docker
#
	if ! [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		WP_VERSION=$(git ls-remote --tags https://github.com/wordpress/wordpress.git | cut -f3 -d/ | grep -E '^\d+\.\d+(.\d+)*$' | tail -n 1)
	fi
	if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		export WORDPRESS_VERSION=$WP_VERSION
	else
		export WORDPRESS_VERSION="5.5.1"
	fi

	if ! [[ $TRAVIS_PHP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		PVER=$(git ls-remote --tags https://github.com/php/php-src.git | cut -f3 -d/ | grep -E '^php-\d+\.\d+(.\d+)*$' | tail -n 1)
		TRAVIS_PHP_VERSION=${PVER/php-/}
	fi
	if [[ $TRAVIS_PHP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		export DC_PHP_VERSION=$TRAVIS_PHP_VERSION
	else
		export DC_PHP_VERSION="7.4.9"
	fi

	if ! [[ $TRAVIS_MARIADB_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		MVER=$(git ls-remote --tags https://github.com/mariadb/server.git | cut -f3 -d/ | grep -E '^mariadb-\d\d\.\d+(.\d+)*$' | tail -n 1)
		TRAVIS_MARIADB_VERSION=${MVER/mariadb-/}
	fi
	if [[ $TRAVIS_MARIADB_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
		export DC_MARIADB_VERSION=$TRAVIS_MARIADB_VERSION
	else
		export DC_MARIADB_VERSION="10.5.5"
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
