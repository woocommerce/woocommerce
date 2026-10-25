---
title: wc shop_coupon
sidebar_label: wc shop_coupon
---

Manage WooCommerce shop coupon resources.

## wc shop_coupon list

List all shop coupons.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--page` - Current page of the collection.
- `--per_page` - Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` - Limit results to those matching a string.
- `--after` - Limit response to resources published after a given ISO8601 compliant date.
- `--before` - Limit response to resources published before a given ISO8601 compliant date.
- `--exclude` - Ensure result set excludes specific IDs.
- `--include` - Limit result set to specific ids.
- `--offset` - Offset the result set by a specific number of items.
- `--order` - Order sort attribute ascending or descending.
- `--orderby` - Sort collection by object attribute.
- `--code` - Limit result set to resources with a specific code.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shop_coupon create

Create a new shop coupon.

- `--code` - Coupon code. (*Required*)
- `--amount` - The amount of discount. Should always be numeric, even if setting a percentage.
- `--discount_type` - Determines the type of discount that will be applied.
- `--description` - Coupon description.
- `--date_expires` - The date the coupon expires, in the site's timezone.
- `--date_expires_gmt` - The date the coupon expires, as GMT.
- `--individual_use` - If true, the coupon can only be used individually. Other applied coupons will be removed from the cart.
- `--product_ids` - List of product IDs the coupon can be used on.
- `--excluded_product_ids` - List of product IDs the coupon cannot be used on.
- `--usage_limit` - How many times the coupon can be used in total.
- `--usage_limit_per_user` - How many times the coupon can be used per customer.
- `--limit_usage_to_x_items` - Max number of items in the cart the coupon can be applied to.
- `--free_shipping` - If true and if the free shipping method requires a coupon, this coupon will enable free shipping.
- `--product_categories` - List of category IDs the coupon applies to.
- `--excluded_product_categories` - List of category IDs the coupon does not apply to.
- `--exclude_sale_items` - If true, this coupon will not be applied to items that have sale prices.
- `--minimum_amount` - Minimum order amount that needs to be in the cart before coupon applies.
- `--maximum_amount` - Maximum order amount allowed when using the coupon.
- `--email_restrictions` - List of email addresses that can use this coupon.
- `--meta_data` - Meta data.
- `--porcelain` - Output just the id when the operation is successful.

## wc shop_coupon get `<id>`

Get a single shop coupon.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc shop_coupon update `<id>`

Update an existing shop coupon.

- `--code` - Coupon code.
- `--amount` - The amount of discount. Should always be numeric, even if setting a percentage.
- `--discount_type` - Determines the type of discount that will be applied.
- `--description` - Coupon description.
- `--date_expires` - The date the coupon expires, in the site's timezone.
- `--date_expires_gmt` - The date the coupon expires, as GMT.
- `--individual_use` - If true, the coupon can only be used individually. Other applied coupons will be removed from the cart.
- `--product_ids` - List of product IDs the coupon can be used on.
- `--excluded_product_ids` - List of product IDs the coupon cannot be used on.
- `--usage_limit` - How many times the coupon can be used in total.
- `--usage_limit_per_user` - How many times the coupon can be used per customer.
- `--limit_usage_to_x_items` - Max number of items in the cart the coupon can be applied to.
- `--free_shipping` - If true and if the free shipping method requires a coupon, this coupon will enable free shipping.
- `--product_categories` - List of category IDs the coupon applies to.
- `--excluded_product_categories` - List of category IDs the coupon does not apply to.
- `--exclude_sale_items` - If true, this coupon will not be applied to items that have sale prices.
- `--minimum_amount` - Minimum order amount that needs to be in the cart before coupon applies.
- `--maximum_amount` - Maximum order amount allowed when using the coupon.
- `--email_restrictions` - List of email addresses that can use this coupon.
- `--meta_data` - Meta data.
- `--porcelain` - Output just the id when the operation is successful.

## wc shop_coupon delete `<id>`

Delete an existing shop coupon.

- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
