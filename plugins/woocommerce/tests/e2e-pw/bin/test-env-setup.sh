#!/usr/bin/env bash

wp-env run tests-cli "wp theme install twentynineteen --activate"

wp-env run tests-cli "wp plugin install https://github.com/WP-API/Basic-Auth/archive/master.zip --activate"

wp-env run tests-cli "wp plugin install wp-mail-logging --activate"

wp-env run tests-cli "wp plugin install https://github.com/woocommerce/woocommerce-reset/archive/refs/heads/trunk.zip --activate"

wp-env run tests-cli "wp rewrite structure /%postname%/"

wp-env run tests-cli "wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--path=/var/www/html \
	--user_registered='2022-01-01 12:23:45'
"

echo -e 'Update Blog Name \n'
wp-env run tests-cli 'wp option update blogname "WooCommerce Core E2E Test Suite"'

# Enable additional WooCommerce features based on command options
while :; do
	case $1 in
		-c|--cot)	# Enable the COT feature
			echo 'Enable the COT feature'
			wp-env run tests-cli "wp plugin install https://gist.github.com/vedanshujain/564afec8f5e9235a1257994ed39b1449/archive/9d5f174ebf8eec8e0ce5417d00728524c7f3b6b3.zip --activate"
			;;
		--)			# End of all options
			shift
			break
			;;
		-?*)
			echo "WARN: Unknown option (ignored):" $1 >&2
			;;
		*)			# No more options, so break out of the loop
			break
	esac

	shift
done
