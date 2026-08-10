#!/usr/bin/env bash

# Currently, the rewrite rules don't work properly in the test environment: https://github.com/WordPress/gutenberg/issues/28201
chmod -c ugo+w /var/www/html
wp rewrite structure /%postname%/ --hard
wp rewrite flush --hard
