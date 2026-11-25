#!/usr/bin/env bash
#
# Seeds the wp-env development site (localhost:$WP_ENV_PORT) with a browsable store: store
# address, admin and customer addresses, payment, shipping, tax and WooCommerce's sample
# products, then runs the supplied hook for the plugin's data.
#
# Runs inside the `cli` container via `pnpm run env:dev:seed`; `pnpm run env:dev:reset` wipes
# the site and seeds again.
#
set -euo pipefail
trap 'echo "Seeding failed; run pnpm run env:dev:reset to start over." >&2' ERR

if [ "$(wp option get wp_env_dev_seeded 2>/dev/null || true)" = "yes" ]; then
	echo "Development site is already seeded. Run 'pnpm run env:dev:reset' to start over."
	exit 0
fi

if [[ $# -ne 1 || ! -r "$1" || ! -f "$1" ]]; then
	echo 'Usage: seed-dev.sh <path to the plugin seed script in the container>' >&2
	exit 1
fi

echo "Configuring the store..."
# wp-env installs the themes in .wp-env.json but activates none.
if wp theme is-installed storefront; then
	wp theme activate storefront --quiet
	wp option update storefront_nux_dismissed 1
fi
rm -rf .htaccess wp-content/debug.log # Older installs mounted these, leaving root-owned stubs.
wp rewrite structure '/%postname%/'
# WP-CLI cannot detect mod_rewrite in the container, so write WordPress's default rules itself.
cat > .htaccess <<'HTACCESS'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTACCESS
wp option update woocommerce_onboarding_profile --format=json '{"skipped":true}'
wp option update woocommerce_coming_soon 'no'
wp option update woocommerce_store_address '60 29th Street #343'
wp option update woocommerce_store_city 'San Francisco'
wp option update woocommerce_store_postcode '94110'
wp option update woocommerce_default_country 'US:CA'
wp option update woocommerce_default_customer_address 'geolocation'
wp option update woocommerce_currency 'USD'
wp option update woocommerce_currency_pos 'left'

echo "Adding addresses and a customer..."
address='"company":"Automattic","country":"US","address_1":"60 29th Street #343","city":"San Francisco","state":"CA","postcode":"94110","phone":"123456789"'
wp wc customer update 1 --user=1 \
	--billing="{\"first_name\":\"John\",\"last_name\":\"Doe\",${address},\"email\":\"admin@example.com\"}" \
	--shipping="{\"first_name\":\"John\",\"last_name\":\"Doe\",${address}}"
wp wc customer create --user=1 --email='customer@example.com' --username='customer' --password='password' \
	--first_name='Jane' --last_name='Smith' \
	--billing="{\"first_name\":\"Jane\",\"last_name\":\"Smith\",${address},\"email\":\"customer@example.com\"}" \
	--shipping="{\"first_name\":\"Jane\",\"last_name\":\"Smith\",${address}}" --porcelain >/dev/null

echo "Enabling payment, shipping and tax..."
wp option update --format=json woocommerce_dummy_settings '{"enabled":"yes","title":"Dummy Payment","description":"The goods are yours. No money needed.","result":"success"}'
zone=$(wp wc shipping_zone create --user=1 --name='United States (US)' --porcelain)
wp db query "INSERT INTO $(wp db prefix)woocommerce_shipping_zone_locations (zone_id, location_code, location_type) VALUES (${zone}, 'US', 'country')"
wp wc shipping_zone_method create "${zone}" --user=1 --method_id=flat_rate --enabled=true --settings='{"title":"Flat Rate","cost":"10"}' --porcelain >/dev/null
wp wc shipping_zone_method create 0 --user=1 --method_id=flat_rate --enabled=true --settings='{"title":"Flat Rate","cost":"20"}' --porcelain >/dev/null
wp option update woocommerce_calc_taxes 'yes'
wp wc tax create --user=1 --rate=10 --class=standard --porcelain >/dev/null

echo "Importing WooCommerce sample products..."
wp plugin install wordpress-importer --activate --quiet
# wp-env names the WooCommerce folder after its zip (e.g. woocommerce.latest-stable), so look it up by its main file.
wc_dir="$(wp plugin path)/$(dirname "$(wp plugin list --field=file | grep '/woocommerce\.php$')")"
wp import "${wc_dir}/sample-data/sample_products.xml" --authors=create --quiet

# Plugin-specific products and fixtures.
bash -- "$1"

wp option update wp_env_dev_seeded 'yes'

cat <<EOF

Development site ready: $(wp option get home)
  Admin:     admin / password
  Customer:  customer / password
EOF
