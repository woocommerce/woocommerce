#!/bin/bash
export APP_NAME=$2
if [ "$APP_NAME" == "" ]; then
  echo "Please supply app directory name!"
  exit 1
elif [ ! -d apps/$APP_NAME/public/wp-content/plugins ]; then
  echo "App directory apps/$APP_NAME/public/wp-content/plugins does not exist."
  exit 1
fi

cd apps/$APP_NAME/public/

prefix=`wp db prefix`
sed -i 's/wp_commentmeta/'"$prefix"'commentmeta/g' ~/e2e-db.sql
sed -i 's/wp_comments/'"$prefix"'comments/g' ~/e2e-db.sql
sed -i 's/wp_links/'"$prefix"'links/g' ~/e2e-db.sql
sed -i 's/wp_options/'"$prefix"'options/g' ~/e2e-db.sql
sed -i 's/wp_postmeta/'"$prefix"'postmeta/g' ~/e2e-db.sql
sed -i 's/wp_posts/'"$prefix"'posts/g' ~/e2e-db.sql
sed -i 's/wp_term_relationships/'"$prefix"'term_relationships/g' ~/e2e-db.sql
sed -i 's/wp_relationships/'"$prefix"'relationships/g' ~/e2e-db.sql
sed -i 's/wp_term_taxonomy/'"$prefix"'term_taxonomy/g' ~/e2e-db.sql
sed -i 's/wp_termmeta/'"$prefix"'termmeta/g' ~/e2e-db.sql
sed -i 's/wp_terms/'"$prefix"'terms/g' ~/e2e-db.sql
sed -i 's/wp_usermeta/'"$prefix"'usermeta/g' ~/e2e-db.sql
sed -i 's/wp_users/'"$prefix"'users/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_api_keys/'"$prefix"'woocommerce_api_keys/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_attribute_taxonomies/'"$prefix"'woocommerce_attribute_taxonomies/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_downloadable_product_permissions/'"$prefix"'woocommerce_downloadable_product_permissions/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_log/'"$prefix"'woocommerce_log/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_order_itemmeta/'"$prefix"'woocommerce_order_itemmeta/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_order_items/'"$prefix"'woocommerce_order_items/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_payment_tokenmeta/'"$prefix"'woocommerce_payment_tokenmeta/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_payment_tokens/'"$prefix"'woocommerce_payment_tokens/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_sessions/'"$prefix"'woocommerce_sessions/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_shipping_zone_locations/'"$prefix"'woocommerce_shipping_zone_locations/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_shipping_zone_methods/'"$prefix"'woocommerce_shipping_zone_methods/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_shipping_zones/'"$prefix"'woocommerce_shipping_zones/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_tax_rate_locations/'"$prefix"'woocommerce_tax_rate_locations/g' ~/e2e-db.sql
sed -i 's/wp_woocommerce_tax_rates/'"$prefix"'woocommerce_tax_rates/g' ~/e2e-db.sql

wp theme install twentytwelve --activate
wget https://github.com/woocommerce/woocommerce/archive/$1.zip -O woocommerce.zip
wp plugin install woocommerce.zip
wp db import ~/e2e-db.sql
wp plugin activate --all
wp plugin list

