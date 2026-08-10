#!/usr/bin/env bash

if ! wp user get customer --field=ID --path=/var/www/html >/dev/null 2>&1; then
	wp user create customer customer@woocommerceblockse2etestsuite.com \
		--user_pass=password \
		--role=subscriber \
		--first_name='Jane' \
		--last_name='Smith' \
		--path=/var/www/html \
		--user_registered='2022-01-01 12:23:45'
fi
