#!/bin/bash

echo "Initializing WooCommerce E2E"

wp plugin install woocommerce --activate
wp theme install twentynineteen --activate
wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=customer --path=/var/www/html
