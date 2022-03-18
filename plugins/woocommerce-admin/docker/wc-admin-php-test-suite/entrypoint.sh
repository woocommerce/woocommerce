#!/bin/bash

# Wait for MySQL to be up and running
until mysqladmin ping -h"$DB_HOST" --silent; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 2
done

>&2 echo "MySQL is up - executing tests"

if [ "$(php -r "echo version_compare(PHP_VERSION,'8','>=');")" ]; then
  # WP_VERSION="$WP_VERSION"
  # WC_VERSION="$WC_VERSION"
  if [ "$(php -r "echo version_compare('$WP_VERSION','5.6','<');")" ]; then
    >&2 echo "$WP_VERSION is not supported for PHP 8 updating WP_VERSION to 5.6"
	WP_VERSION=5.6
  fi
  if [ "$(php -r "echo version_compare('$WC_VERSION','5.1','<');")" ]; then
    >&2 echo "$WC_VERSION is not supported for PHP 8 updating WC_VERSION to 5.1"
	WC_VERSION=5.1.0
  fi
fi

# Function to install the test suite.
function install {
	# Delete previous state.
	rm -f /tmp/.INSTALLED_WC_VERSION
	rm -f /tmp/.INSTALLED_WP_VERSION

	# Delete any previous installations.
	rm -rf /tmp/*

	# Run the install script.
	bin/install-wp-tests.sh $DB_NAME $DB_USER $DB_PASS $DB_HOST $WP_VERSION true

	# Update state.
	echo "$WC_VERSION" > /tmp/.INSTALLED_WC_VERSION
	echo "$WP_VERSION" > /tmp/.INSTALLED_WP_VERSION
}

# Run the install script if the installed WooCommerce version is unknown.
if [ ! -f /tmp/.INSTALLED_WC_VERSION ]; then
	install
else
	# Run the install script if the WooCommerce version has changed.
	INSTALLED_WC_VERSION=`cat /tmp/.INSTALLED_WC_VERSION`
	if [ "$WC_VERSION" != "$INSTALLED_WC_VERSION" ]; then
		install
	fi
fi

# Run the install script if the installed WordPress version is unknown.
if [ ! -f /tmp/.INSTALLED_WP_VERSION ]; then
	install
else
	# Run the install script if the WordPress version has changed.
	INSTALLED_WP_VERSION=`cat /tmp/.INSTALLED_WP_VERSION`
	if [ "$WP_VERSION" != "$INSTALLED_WP_VERSION" ]; then
		install
	fi
fi


# Run the install script if the WordPress directory is not found.
if [ ! -d /tmp/wordpress-tests-lib ]; then
	install
fi

if [ "$(php -r "echo version_compare(PHP_VERSION,'8','>=');")" ]; then
	if [ -f "/tmp/phpunit-7.5-fork.zip" ]; then
	    echo "phpunit 7.5 fork already exists"
	else
	    echo "Retrieving phpunit 7.5 fork"
		curl -L https://github.com/woocommerce/phpunit/archive/add-compatibility-with-php8-to-phpunit-7.zip -o /tmp/phpunit-7.5-fork.zip
		unzip -q -d /tmp/phpunit-7.5-fork -o /tmp/phpunit-7.5-fork.zip
    fi
    composer bin phpunit config --unset platform
    composer bin phpunit config repositories.0 '{"type": "path", "url": "/tmp/phpunit-7.5-fork/phpunit-add-compatibility-with-php8-to-phpunit-7", "options": {"symlink": false}}'
    composer bin phpunit require --dev -W phpunit/phpunit:@dev --ignore-platform-reqs
	exec ./vendor/bin/phpunit "$@"
else
	exec phpunit "$@"
fi

