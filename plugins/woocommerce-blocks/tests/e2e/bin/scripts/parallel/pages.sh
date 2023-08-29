#!/usr/bin/env bash

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && cd ../../pages && pwd)"

post_id=$(wp post create \
	--porcelain \
	--menu_order=1 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='Shop' \
)
wp option update woocommerce_shop_page_id $post_id

post_id=$(wp post create \
	--porcelain \
	--menu_order=1 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='Cart block' \
	${script_dir}/cart.html
)
wp option update woocommerce_cart_page_id $post_id

post_id=$(wp post create \
	--porcelain \
	--menu_order=2 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='Checkout block' \
	${script_dir}/checkout.html
)
wp option update woocommerce_checkout_page_id $post_id

post_id=$(wp post create \
	--porcelain \
	--menu_order=3 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='My Account' \
	${script_dir}/my-account.html
)
wp option update woocommerce_myaccount_page_id $post_id

post_id=$(wp post create \
	--porcelain \
	--menu_order=4 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='Terms')
wp option update woocommerce_terms_page_id $post_id

post_id=$(wp post create \
	--porcelain \
	--menu_order=5 \
	--post_type=page \
	--post_status=publish \
	--post_author=1 \
	--post_title='Privacy'
)
wp option update wp_page_for_privacy_policy $post_id

# Create renaming WooCommerce pages using tool
wp wc tool run install_pages --user=1
