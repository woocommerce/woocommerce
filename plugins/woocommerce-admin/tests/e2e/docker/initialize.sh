#!/bin/bash

echo "Initializing WooCommerce E2E"

# Turn off error display temporarily. This is to prevent deprecated function
# notices from breaking the display of some screens and then E2E tests.
# Message was for WC_Admin_Notes_Deactivate_Plugin usage in core WC.
wp config set WP_DEBUG_DISPLAY false --raw
wp config set JETPACK_AUTOLOAD_DEV true --raw
wp plugin install woocommerce --activate
wp theme install twentynineteen --activate
wp plugin activate woocommerce-admin
wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=customer --path=/var/www/html
