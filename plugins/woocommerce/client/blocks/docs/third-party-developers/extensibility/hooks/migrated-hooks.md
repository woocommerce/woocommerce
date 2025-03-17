---
post_title: Cart and Checkout - Legacy hooks
menu_title: Legacy Hooks
tags: reference, checkout-hooks
---

# Migrated Legacy Hooks

Below are the hooks that exist in WooCommerce core and that were brought over to WooCommerce Blocks.

Please note that the actions and filters here run on the server side. The client-side blocks won't necessarily change based on a callback added to a server side hook. [Please see our documentation relating to APIs for manipulating the blocks on the client-side](../README.md).

## Legacy Filters

- [loop_shop_per_page](./filters.md#loop_shop_per_page)
- [wc_session_expiration](./filters.md#wc_session_expiration)
- [woocommerce_add_cart_item](./filters.md#woocommerce_add_cart_item)
- [woocommerce_add_cart_item_data](./filters.md#woocommerce_add_cart_item_data)
- [woocommerce_add_to_cart_quantity](./filters.md#woocommerce_add_to_cart_quantity)
- [woocommerce_add_to_cart_sold_individually_quantity](./filters.md#woocommerce_add_to_cart_sold_individually_quantity)
- [woocommerce_add_to_cart_validation](./filters.md#woocommerce_add_to_cart_validation)
- [woocommerce_adjust_non_base_location_prices](./filters.md#woocommerce_adjust_non_base_location_prices)
- [woocommerce_apply_base_tax_for_local_pickup](./filters.md#woocommerce_apply_base_tax_for_local_pickup)
- [woocommerce_apply_individual_use_coupon](./filters.md#woocommerce_apply_individual_use_coupon)
- [woocommerce_apply_with_individual_use_coupon](./filters.md#woocommerce_apply_with_individual_use_coupon)
- [woocommerce_cart_contents_changed](./filters.md#woocommerce_cart_contents_changed)
- [woocommerce_cart_item_permalink](./filters.md#woocommerce_cart_item_permalink)
- [woocommerce_get_item_data](./filters.md#woocommerce_get_item_data)
- [woocommerce_loop_add_to_cart_args](./filters.md#woocommerce_loop_add_to_cart_args)
- [woocommerce_loop_add_to_cart_link](./filters.md#woocommerce_loop_add_to_cart_link)
- [woocommerce_new_customer_data](./filters.md#woocommerce_new_customer_data)
- [woocommerce_pay_order_product_has_enough_stock](./filters.md#woocommerce_pay_order_product_has_enough_stock)
- [woocommerce_pay_order_product_in_stock](./filters.md#woocommerce_pay_order_product_in_stock)
- [woocommerce_registration_errors](./filters.md#woocommerce_registration_errors)
- [woocommerce_shipping_package_name](./filters.md#woocommerce_shipping_package_name)
- [woocommerce_show_page_title](./filters.md#woocommerce_show_page_title)
- [woocommerce_single_product_image_thumbnail_html](./filters.md#woocommerce_single_product_image_thumbnail_html)

## Legacy Actions

- [woocommerce_add_to_cart](./actions.md#woocommerce_add_to_cart)
- [woocommerce_after_main_content](./actions.md#woocommerce_after_main_content)
- [woocommerce_after_shop_loop](./actions.md#woocommerce_after_shop_loop)
- [woocommerce_applied_coupon](./actions.md#woocommerce_applied_coupon)
- [woocommerce_archive_description](./actions.md#woocommerce_archive_description)
- [woocommerce_before_main_content](./actions.md#woocommerce_before_main_content)
- [woocommerce_before_shop_loop](./actions.md#woocommerce_before_shop_loop)
- [woocommerce_check_cart_items](./actions.md#woocommerce_check_cart_items)
- [woocommerce_created_customer](./actions.md#woocommerce_created_customer)
- [woocommerce_no_products_found](./actions.md#woocommerce_no_products_found)
- [woocommerce_register_post](./actions.md#woocommerce_register_post)
- [woocommerce_shop_loop](./actions.md#woocommerce_shop_loop)
