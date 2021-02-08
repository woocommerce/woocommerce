#!/bin/bash

# Wait for MySQL to be up and running
until mysqladmin ping -h"$DB_HOST" --silent; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 2
done

>&2 echo "MySQL is up - executing tests"

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

exec phpunit "$@"
