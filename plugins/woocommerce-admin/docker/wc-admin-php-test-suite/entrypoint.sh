#!/bin/bash

# Run the install script if the WordPress directory is not found.
if [ ! -d /tmp/wordpress-tests-lib ]; then
	bin/install-wp-tests.sh $DB_NAME $DB_USER $DB_PASS $DB_HOST latest true
fi

exec phpunit "$@"
