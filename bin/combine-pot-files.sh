#!/usr/bin/env bash

# Check for required version
WPCLI_VERSION=`wp cli version | cut -f2 -d' '`
if [ ${WPCLI_VERSION:0:1} -lt "2" -o ${WPCLI_VERSION:0:1} -eq "2" -a ${WPCLI_VERSION:2:1} -lt "1" ]; then
	echo WP-CLI version 2.1.0 or greater is required to make JSON translation files
	exit
fi

# Substitute JS source references with build references
for T in `find packages/woocommerce-admin/languages -name "*.pot"`
	do
		sed \
			-e 's/ client\/[^:]*:/ packages\/woocommerce-admin\/dist\/app\/index.js:/gp' \
			-e 's/ packages\/components[^:]*:/ packages\/woocommerce-admin\/dist\/components\/index.js:/gp' \
			-e 's/ packages\/date[^:]*:/ packages\/woocommerce-admin\/dist\/date\/index.js:/gp' \
			-e 's/ src\// packages\/woocommerce-admin\/src\//gp' \
			-e 's/ includes\// packages\/woocommerce-admin\/includes\//gp' \
			$T | uniq > $T-build
		rm $T
		mv $T-build $T
	done

# Combine the WooCommerce and WooCommerce Admin translations files
php bin/combine-pot-files.php packages/woocommerce-admin/languages/woocommerce-admin.pot i18n/languages/woocommerce.pot