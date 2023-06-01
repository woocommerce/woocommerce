#!/usr/bin/env bash

###################################################################################################
# Get the directory of the current script
###################################################################################################

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

###################################################################################################
# Empty site to prevent conflicts with existing data
###################################################################################################

wp-env run tests-cli "wp site empty --yes"

###################################################################################################
# If no attributes exist, otherwiese create them
###################################################################################################

attributes=$(wp-env run tests-cli "wp wc product_attribute list --format=json --user=1")

if [ -z "$attributes" ] || [ "$attributes" == "[]" ]; then
    pa_color=$(wp-env run tests-cli "wp wc product_attribute create \
        --name=Color \
        --slug=pa_color \
        --user=1 \
        --porcelain \
    ")

    pa_size=$(wp-env run tests-cli "wp wc product_attribute create \
        --name=Size \
        --slug=pa_size \
        --user=1 \
        --porcelain \
    ")
fi

###################################################################################################
# Import sample products and regenerate product lookup tables
###################################################################################################
wp-env run tests-cli "wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip"
wp-env run tests-cli "wp wc tool run regenerate_product_lookup_tables --user=1"

###################################################################################################
# Create pages and posts
###################################################################################################

post_content=$(cat "${script_dir}/all-products.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--menu_order=0 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='All Products block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/cart.txt" | sed 's/"/\\"/g')
post_id=$(wp-env run tests-cli "wp post create \
	--porcelain \
	--menu_order=1 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='Cart block' \
	--post_content=\"$post_content\"
")
wp-env run tests-cli "wp option update woocommerce_cart_page_id $post_id"

post_content=$(cat "${script_dir}/checkout.txt" | sed 's/"/\\"/g')
post_id=$(wp-env run tests-cli "wp post create \
	--porcelain \
	--menu_order=2 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='Checkout block' \
	--post_content=\"$post_content\"
")
wp-env run tests-cli "wp option update woocommerce_checkout_page_id $post_id"

post_content=$(cat "${script_dir}/my-account.txt" | sed 's/"/\\"/g')
post_id=$(wp-env run tests-cli "wp post create \
	--porcelain \
	--menu_order=3 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='My Account' \
	--post_content=\"$post_content\"
")
wp-env run tests-cli "wp option update woocommerce_myaccount_page_id $post_id"

post_id=$(wp-env run tests-cli "wp post create \
	--porcelain \
	--menu_order=4 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='Terms'")
wp-env run tests-cli "wp option update woocommerce_terms_page_id $post_id"

post_id=$(wp-env run tests-cli "wp post create \
	--porcelain \
	--menu_order=5 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='Privacy'
")
wp-env run tests-cli "wp option update wp_page_for_privacy_policy $post_id"

post_content=$(cat "${script_dir}/all-reviews.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='All Reviews block' \
	--post_content=\"$post_content\"
"

if [ "$pa_color" ] && [ "$pa_size" ]; then
	post_content=$(cat "${script_dir}/active-filters.txt" | sed 's/"/\\"/g')
	wp-env run tests-cli "wp post create \
		--post_status=publish \
		--post_author=1 \
		--post_title='Active Filters block' \
		--post_content=\"$post_content\"
	"
fi

post_content=$(cat "${script_dir}/mini-cart.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Mini-Cart block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/product-best-sellers.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Best Selling Products block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/products-by-attribute.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Products by Attribute block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/single-product.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Single Product block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/customer-account.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Customer Account block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/featured-category.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Featured Category block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/featured-product.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Featured Product block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/handpicked-products.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Hand-picked Products block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/product-new.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Newest Products block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/product-on-sale.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='On Sale Products block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/product-category.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Products by Category block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/product-categories.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Product Categories List block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/product-search.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Product Search block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/reviews-by-category.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Reviews by Category block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/reviews-by-product.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Reviews by Product block' \
	--post_content=\"$post_content\"
"

post_content=$(cat "${script_dir}/product-top-rated.txt" | sed 's/"/\\"/g')
wp-env run tests-cli "wp post create \
	--post_status=publish \
	--post_author=1 \
	--post_title='Top Rated Products block' \
	--post_content=\"$post_content\"
"

###################################################################################################
# Set up shipping
###################################################################################################

wp-env run tests-cli "wp wc shipping_zone_method create 0 \
	--order=1 \
	--enabled=true \
	--user=1 \
	--settings='{\"title\":\"Flat rate shipping\", \"cost\": \"10\"}' \
	--method_id=flat_rate
"

wp-env run tests-cli "wp wc shipping_zone_method create 0 \
	--order=2 \
	--enabled=true \
	--user=1 \
	--settings='{\"title\":\"Free shipping\"}' \
	--method_id=free_shipping
"

###################################################################################################
# Set up payment methods
###################################################################################################

wp-env run tests-cli "wp option set --format=json woocommerce_cod_settings '{
    \"enabled\":\"yes\",
    \"title\":\"Cash on delivery\",
    \"description\":\"Cash on delivery description\",
    \"instructions\":\"Cash on delivery instructions\"
}'"

wp-env run tests-cli "wp option set --format=json woocommerce_bacs_settings '{
    \"enabled\":\"yes\",
    \"title\":\"Direct bank transfer\",
    \"description\":\"Direct bank transfer description\",
    \"instructions\":\"Direct bank transfer instructions\"
}'"

wp-env run tests-cli "wp option set --format=json woocommerce_cheque_settings '{
    \"enabled\":\"yes\",
    \"title\":\"Check payments\",
    \"description\":\"Check payments description\",
    \"instructions\":\"Check payments instructions\"
}'"

###################################################################################################
# Set up tax
###################################################################################################

wp-env run tests-cli "wp option set woocommerce_calc_taxes yes"

wp-env run tests-cli "wp wc tax create \
    --user=1 \
    --rate=20 \
    --class=standard \
"

wp-env run tests-cli "wp wc tax create \
    --user=1 \
    --rate=10 \
    --class=reduced-rate \
"

wp-env run tests-cli "wp wc tax create \
    --user=1 \
    --rate=0 \
    --class=zero-rate \
"

###################################################################################################
# Adjust and flush rewrite rules
###################################################################################################
# Currently, the rewrite rules don't work properly in the test environment: https://github.com/WordPress/gutenberg/issues/28201
wp-env run tests-wordpress "chmod -c ugo+w /var/www/html"
wp-env run tests-cli "wp rewrite structure /%postname%/ --hard"
wp-env run tests-cli "wp rewrite flush --hard"

###################################################################################################
# Create a customer
###################################################################################################

wp-env run tests-cli "wp user create customer customer@woocommerceblockse2etestsuite.com \
	--user_pass=password \
	--role=subscriber \
	--first_name='Jane' \
	--last_name='Smith' \
	--path=/var/www/html \
	--user_registered='2022-01-01 12:23:45'
"

###################################################################################################
# Update blog name
###################################################################################################

wp-env run tests-cli "wp option update blogname 'WooCommerce Blocks E2E Test Suite'"
