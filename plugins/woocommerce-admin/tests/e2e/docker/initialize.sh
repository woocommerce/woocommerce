#!/bin/bash

echo "Initializing WooCommerce E2E"

wp config set JETPACK_AUTOLOAD_DEV true --raw
wp plugin install woocommerce --activate
wp theme install twentynineteen --activate
wp plugin activate woocommerce-admin
wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=customer --path=/var/www/html
