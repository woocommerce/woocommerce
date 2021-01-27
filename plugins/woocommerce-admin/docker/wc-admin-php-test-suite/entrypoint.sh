#!/bin/bash

# Wait for MySQL to be up and running
until mysqladmin ping -h"$DB_HOST" --silent; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 2
done
  
>&2 echo "MySQL is up - executing tests"

# Run the install script if the WordPress directory is not found.
if [ ! -d /tmp/wordpress-tests-lib ]; then
	bin/install-wp-tests.sh $DB_NAME $DB_USER $DB_PASS $DB_HOST latest true
fi

exec phpunit "$@"
