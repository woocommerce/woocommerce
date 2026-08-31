---
title: wc shop_order
sidebar_label: wc shop_order
---

Manage WooCommerce shop order resources.

## wc shop_order list

List all shop orders.

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
- `--parent` - Limit result set to those of particular parent IDs.
- `--parent_exclude` - Limit result set to all items except those of a particular parent ID.
- `--status` - Limit result set to orders assigned a specific status.
- `--customer` - Limit result set to orders assigned a specific customer.
- `--product` - Limit result set to orders assigned a specific product.
- `--dp` - Number of decimal points to use in each resource.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shop_order create

Create a new shop order.

- `--parent_id` - Parent order ID.
- `--status` - Order status.
- `--currency` - Currency the order was created with, in ISO format.
- `--customer_id` - User ID who owns the order. 0 for guests.
- `--customer_note` - Note left by customer during checkout.
- `--billing` - Billing address.
- `--shipping` - Shipping address.
- `--payment_method` - Payment method ID.
- `--payment_method_title` - Payment method title.
- `--transaction_id` - Unique transaction ID.
- `--meta_data` - Meta data.
- `--line_items` - Line items data.
- `--shipping_lines` - Shipping lines data.
- `--fee_lines` - Fee lines data.
- `--coupon_lines` - Coupons line data.
- `--set_paid` - Define if the order is paid. It will set the status to processing and reduce stock items.
- `--porcelain` - Output just the id when the operation is successful.

## wc shop_order get `<id>`

Get a single shop order.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc shop_order update `<id>`

Update an existing shop order.

- `--parent_id` - Parent order ID.
- `--status` - Order status.
- `--currency` - Currency the order was created with, in ISO format.
- `--customer_id` - User ID who owns the order. 0 for guests.
- `--customer_note` - Note left by customer during checkout.
- `--billing` - Billing address.
- `--shipping` - Shipping address.
- `--payment_method` - Payment method ID.
- `--payment_method_title` - Payment method title.
- `--transaction_id` - Unique transaction ID.
- `--meta_data` - Meta data.
- `--line_items` - Line items data.
- `--shipping_lines` - Shipping lines data.
- `--fee_lines` - Fee lines data.
- `--coupon_lines` - Coupons line data.
- `--set_paid` - Define if the order is paid. It will set the status to processing and reduce stock items.
- `--porcelain` - Output just the id when the operation is successful.

## wc shop_order delete `<id>`

Delete an existing shop order.

- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
