#!/bin/bash

echo "Initializing WooCommerce E2E"

wp core install \
	--url=http://localhost:${WORDPRESS_PORT} \
	--title="${WORDPRESS_TITLE}" \
	--admin_user=${WORDPRESS_LOGIN} \
	--admin_password="${WORDPRESS_PASSWORD}" \
	--admin_email=${WORDPRESS_EMAIL} \
	--skip-email
wp plugin install woocommerce --activate
wp theme install twentynineteen --activate
wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=customer --path=/var/www/html
wp post create --post_type=page --post_status=publish --post_title='Ready' --post_content='E2E-tests.'
