#!/bin/sh

rm woocommerce-admin-test-helper.zip

echo "Building"
npm run build

echo "Creating archive... 🎁"
zip -r "woocommerce-admin-test-helper.zip" \
	woocommerce-admin-test-helper.php \
	plugin.php \
	build/ \
	api/ \
	README.md
