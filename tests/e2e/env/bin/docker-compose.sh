#!/bin/bash
#
if [[ "$1" != '' ]];
then
#
# Set environment variables for Docker
#
	if [[ $WP_VERSION ]];
	then
		export WORDPRESS_VERSION=$WP_VERSION
	else
		export WORDPRESS_VERSION=`git ls-remote --tags https://github.com/wordpress/wordpress.git | cut -f3 -d/ | grep -E '^\d+\.\d+(.\d+)*$' | tail -n 1`
	fi

	if [[ $TRAVIS_PHP_VERSION ]];
	then
		export DC_PHP_VERSION=$TRAVIS_PHP_VERSION
	else
		PVER=`git ls-remote --tags https://github.com/php/php-src.git | cut -f3 -d/ | grep -E '^php-\d+\.\d+(.\d+)*$' | tail -n 1`
		export DC_PHP_VERSION=${PVER/php-/}
	fi

	if [[ $TRAVIS_MARIADB_VERSION ]];
	then
		export DC_MARIADB_VERSION=$TRAVIS_MARIADB_VERSION
	else
		MVER=`git ls-remote --tags https://github.com/mariadb/server.git | cut -f3 -d/ | grep -E '^mariadb-\d\d\.\d+(.\d+)*$' | tail -n 1`
		export DC_MARIADB_VERSION=${MVER/mariadb-/}
	fi

	if [[ "$1" == 'up']];
	then
		echo WordPress $WORDPRESS_VERSION
		echo PHP $DC_PHP_VERSION
		echo MariaDB $DC_MARIADB_VERSION
	fi
fi
#
# Run Docker
node ./bin/docker-compose.js $1
