#!/bin/sh
#
# Build a test zip from current branch for uploading to test site
#
npm run build
composer install
rm woocommerce-admin.zip
./bin/make-zip.sh woocommerce-admin.zip
