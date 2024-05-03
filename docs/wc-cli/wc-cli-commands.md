---
post_title: WooCommerce CLI Commands
menu_title: Commands
tags: reference
---

## Overview

This document serves as a comprehensive reference for the WooCommerce Command Line Interface (WC-CLI) commands. It includes syntax, options, and brief descriptions for each command. These commands are applicable for WooCommerce version 3.0.0 and later.

### General Command Structure

The general syntax for WC-CLI commands is:

```bash
wp wc [command] [options]
```

For detailed help on any specific command, use:

```bash
wp wc [command] --help
```

### Commands

### wc shop_coupon

#### wc shop_coupon list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--after` — Limit response to resources published after a given ISO8601 compliant date.
- `--before` — Limit response to resources published before a given ISO8601 compliant date.
- `--exclude` — Ensure result set excludes specific IDs.
- `--include` — Limit result set to specific ids.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by object attribute.
- `--code` — Limit result set to resources with a specific code.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shop_coupon create

- `--code` — Coupon code. (*Required*)
- `--amount` — The amount of discount. Should always be numeric, even if setting a percentage.
- `--discount_type` — Determines the type of discount that will be applied.
- `--description` — Coupon description.
- `--date_expires` — The date the coupon expires, in the site's timezone.
- `--date_expires_gmt` — The date the coupon expires, as GMT.
- `--individual_use` — If true, the coupon can only be used individually. Other applied coupons will be removed from the cart.
- `--product_ids` — List of product IDs the coupon can be used on.
- `--excluded_product_ids` — List of product IDs the coupon cannot be used on.
- `--usage_limit` — How many times the coupon can be used in total.
- `--usage_limit_per_user` — How many times the coupon can be used per customer.
- `--limit_usage_to_x_items` — Max number of items in the cart the coupon can be applied to.
- `--free_shipping` — If true and if the free shipping method requires a coupon, this coupon will enable free shipping.
- `--product_categories` — List of category IDs the coupon applies to.
- `--excluded_product_categories` — List of category IDs the coupon does not apply to.
- `--exclude_sale_items` — If true, this coupon will not be applied to items that have sale prices.
- `--minimum_amount` — Minimum order amount that needs to be in the cart before coupon applies.
- `--maximum_amount` — Maximum order amount allowed when using the coupon.
- `--email_restrictions` — List of email addresses that can use this coupon.
- `--meta_data` — Meta data.
- `--porcelain` — Output just the id when the operation is successful.

#### wc shop_coupon get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shop_coupon update [id]

- `--id` — Unique identifier for the resource.
- `--code` — Coupon code.
- `--amount` — The amount of discount. Should always be numeric, even if setting a percentage.
- `--discount_type` — Determines the type of discount that will be applied.
- `--description` — Coupon description.
- `--date_expires` — The date the coupon expires, in the site's timezone.
- `--date_expires_gmt` — The date the coupon expires, as GMT.
- `--individual_use` — If true, the coupon can only be used individually. Other applied coupons will be removed from the cart.
- `--product_ids` — List of product IDs the coupon can be used on.
- `--excluded_product_ids` — List of product IDs the coupon cannot be used on.
- `--usage_limit` — How many times the coupon can be used in total.
- `--usage_limit_per_user` — How many times the coupon can be used per customer.
- `--limit_usage_to_x_items` — Max number of items in the cart the coupon can be applied to.
- `--free_shipping` — If true and if the free shipping method requires a coupon, this coupon will enable free shipping.
- `--product_categories` — List of category IDs the coupon applies to.
- `--excluded_product_categories` — List of category IDs the coupon does not apply to.
- `--exclude_sale_items` — If true, this coupon will not be applied to items that have sale prices.
- `--minimum_amount` — Minimum order amount that needs to be in the cart before coupon applies.
- `--maximum_amount` — Maximum order amount allowed when using the coupon.
- `--email_restrictions` — List of email addresses that can use this coupon.
- `--meta_data` — Meta data.
- `--porcelain` — Output just the id when the operation is successful.

#### wc shop_coupon delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Whether to bypass trash and force deletion.
- `--porcelain` — Output just the id when the operation is successful.

### wc customer_download

#### wc customer_download list <customer_id>

- `--customer_id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

### wc customer

#### wc customer list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--exclude` — Ensure result set excludes specific IDs.
- `--include` — Limit result set to specific IDs.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by object attribute.
- `--email` — Limit result set to resources with a specific email.
- `--role` — Limit result set to resources with a specific role.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc customer create

- `--email` — New user email address. (*Required*)
- `--first_name` — Customer first name.
- `--last_name` — Customer last name.
- `--username` — New user username.
- `--password` — New user password. (*Required*)
- `--billing` — List of billing address data.
- `--shipping` — List of shipping address data.
- `--meta_data` — Meta data.
- `--porcelain` — Output just the id when the operation is successful.

#### wc customer get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc customer update [id]

- `--id` — Unique identifier for the resource.
- `--email` — The email address for the customer.
- `--first_name` — Customer first name.
- `--last_name` — Customer last name.
- `--username` — Customer login name.
- `--password` — Customer password.
- `--billing` — List of billing address data.
- `--shipping` — List of shipping address data.
- `--meta_data` — Meta data.
- `--porcelain` — Output just the id when the operation is successful.

#### wc customer delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Required to be true, as resource does not support trashing.
- `--reassign` — ID to reassign posts to.
- `--porcelain` — Output just the id when the operation is successful.

### wc order_note

#### wc order_note list <order_id>

- `--order_id` — The order ID.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--type` — Limit result to customers or internal notes.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc order_note create <order_id>

- `--order_id` — The order ID.
- `--note` — Order note content. (*Required*)
- `--customer_note` — If true, the note will be shown to customers and they will be notified. If false, the note will be for admin reference only.
- `--porcelain` — Output just the id when the operation is successful.

#### wc order_note get <order_id> [id]

- `--id` — Unique identifier for the resource.
- `--order_id` — The order ID.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc order_note delete <order_id> [id]

- `--id` — Unique identifier for the resource.
- `--order_id` — The order ID.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc shop_order_refund

#### wc shop_order_refund list <order_id>

- `--order_id` — The order ID.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--after` — Limit response to resources published after a given ISO8601 compliant date.
- `--before` — Limit response to resources published before a given ISO8601 compliant date.
- `--exclude` — Ensure result set excludes specific IDs.
- `--include` — Limit result set to specific ids.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by object attribute.
- `--parent` — Limit result set to those of particular parent IDs.
- `--parent_exclude` — Limit result set to all items except those of a particular parent ID.
- `--dp` — Number of decimal points to use in each resource.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shop_order_refund create <order_id>

- `--order_id` — The order ID.
- `--amount` — Refund amount.
- `--reason` — Reason for refund.
- `--refunded_by` — User ID of user who created the refund.
- `--meta_data` — Meta data.
- `--line_items` — Line items data.
- `--api_refund` — When true, the payment gateway API is used to generate the refund.
- `--porcelain` — Output just the id when the operation is successful.

#### wc shop_order_refund get <order_id> [id]

- `--order_id` — The order ID.
- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shop_order_refund delete <order_id> [id]

- `--order_id` — The order ID.
- `--id` — Unique identifier for the resource.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc shop_order

#### wc shop_order list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--after` — Limit response to resources published after a given ISO8601 compliant date.
- `--before` — Limit response to resources published before a given ISO8601 compliant date.
- `--exclude` — Ensure result set excludes specific IDs.
- `--include` — Limit result set to specific ids.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by object attribute.
- `--parent` — Limit result set to those of particular parent IDs.
- `--parent_exclude` — Limit result set to all items except those of a particular parent ID.
- `--status` — Limit result set to orders assigned a specific status.
- `--customer` — Limit result set to orders assigned a specific customer.
- `--product` — Limit result set to orders assigned a specific product.
- `--dp` — Number of decimal points to use in each resource.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shop_order create

- `--parent_id` — Parent order ID.
- `--status` — Order status.
- `--currency` — Currency the order was created with, in ISO format.
- `--customer_id` — User ID who owns the order. 0 for guests.
- `--customer_note` — Note left by customer during checkout.
- `--billing` — Billing address.
- `--shipping` — Shipping address.
- `--payment_method` — Payment method ID.
- `--payment_method_title` — Payment method title.
- `--transaction_id` — Unique transaction ID.
- `--meta_data` — Meta data.
- `--line_items` — Line items data.
- `--shipping_lines` — Shipping lines data.
- `--fee_lines` — Fee lines data.
- `--coupon_lines` — Coupons line data.
- `--set_paid` — Define if the order is paid. It will set the status to processing and reduce stock items.
- `--porcelain` — Output just the id when the operation is successful.

#### wc shop_order get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shop_order update [id]

- `--id` — Unique identifier for the resource.
- `--parent_id` — Parent order ID.
- `--status` — Order status.
- `--currency` — Currency the order was created with, in ISO format.
- `--customer_id` — User ID who owns the order. 0 for guests.
- `--customer_note` — Note left by customer during checkout.
- `--billing` — Billing address.
- `--shipping` — Shipping address.
- `--payment_method` — Payment method ID.
- `--payment_method_title` — Payment method title.
- `--transaction_id` — Unique transaction ID.
- `--meta_data` — Meta data.
- `--line_items` — Line items data.
- `--shipping_lines` — Shipping lines data.
- `--fee_lines` — Fee lines data.
- `--coupon_lines` — Coupons line data.
- `--set_paid` — Define if the order is paid. It will set the status to processing and reduce stock items.
- `--porcelain` — Output just the id when the operation is successful.

#### wc shop_order delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Whether to bypass trash and force deletion.
- `--porcelain` — Output just the id when the operation is successful.

### wc product_attribute_term

#### wc product_attribute_term list <attribute_id>

- `--attribute_id` — Unique identifier for the attribute of the terms.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--exclude` — Ensure result set excludes specific ids.
- `--include` — Limit result set to specific ids.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by resource attribute.
- `--hide_empty` — Whether to hide resources not assigned to any products.
- `--parent` — Limit result set to resources assigned to a specific parent.
- `--product` — Limit result set to resources assigned to a specific product.
- `--slug` — Limit result set to resources with a specific slug.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_attribute_term create <attribute_id>

- `--attribute_id` — Unique identifier for the attribute of the terms.
- `--name` — Name for the resource. (*Required*)
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--description` — HTML description of the resource.
- `--menu_order` — Menu order, used to custom sort the resource.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_attribute_term get <attribute_id> [id]

- `--id` — Unique identifier for the resource.
- `--attribute_id` — Unique identifier for the attribute of the terms.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_attribute_term update <attribute_id> [id]

- `--id` — Unique identifier for the resource.
- `--attribute_id` — Unique identifier for the attribute of the terms.
- `--name` — Term name.
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--description` — HTML description of the resource.
- `--menu_order` — Menu order, used to custom sort the resource.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_attribute_term delete <attribute_id> [id]

- `--id` — Unique identifier for the resource.
- `--attribute_id` — Unique identifier for the attribute of the terms.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc product_attribute

#### wc product_attribute list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_attribute create

- `--name` — Name for the resource. (*Required*)
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--type` — Type of attribute.
- `--order_by` — Default sort order.
- `--has_archives` — Enable/Disable attribute archives.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_attribute get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_attribute update [id]

- `--id` — Unique identifier for the resource.
- `--name` — Attribute name.
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--type` — Type of attribute.
- `--order_by` — Default sort order.
- `--has_archives` — Enable/Disable attribute archives.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_attribute delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc product_cat

#### wc product_cat list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--exclude` — Ensure result set excludes specific ids.
- `--include` — Limit result set to specific ids.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by resource attribute.
- `--hide_empty` — Whether to hide resources not assigned to any products.
- `--parent` — Limit result set to resources assigned to a specific parent.
- `--product` — Limit result set to resources assigned to a specific product.
- `--slug` — Limit result set to resources with a specific slug.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_cat create

- `--name` — Name for the resource. (*Required*)
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--parent` — The ID for the parent of the resource.
- `--description` — HTML description of the resource.
- `--display` — Category archive display type.
- `--image` — Image data.
- `--menu_order` — Menu order, used to custom sort the resource.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_cat get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_cat update [id]

- `--id` — Unique identifier for the resource.
- `--name` — Category name.
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--parent` — The ID for the parent of the resource.
- `--description` — HTML description of the resource.
- `--display` — Category archive display type.
- `--image` — Image data.
- `--menu_order` — Menu order, used to custom sort the resource.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_cat delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc product_review

#### wc product_review list <product_id>

- `--product_id` — Unique identifier for the variable product.
- `--id` — Unique identifier for the variation.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_review create <product_id>

- `--product_id` — Unique identifier for the variable product.
- `--id` — Unique identifier for the variation.
- `--review` — Review content. (*Required*)
- `--date_created` — The date the review was created, in the site's timezone.
- `--date_created_gmt` — The date the review was created, as GMT.
- `--rating` — Review rating (0 to 5).
- `--name` — Name of the reviewer. (*Required*)
- `--email` — Email of the reviewer. (*Required*)
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_review get <product_id> [id]

- `--product_id` — Unique identifier for the variable product.
- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_review update <product_id> [id]

- `--product_id` — Unique identifier for the variable product.
- `--id` — Unique identifier for the resource.
- `--review` — The content of the review.
- `--date_created` — The date the review was created, in the site's timezone.
- `--date_created_gmt` — The date the review was created, as GMT.
- `--rating` — Review rating (0 to 5).
- `--name` — Reviewer name.
- `--email` — Reviewer email.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_review delete <product_id> [id]

- `--product_id` — Unique identifier for the variable product.
- `--id` — Unique identifier for the resource.
- `--force` — Whether to bypass trash and force deletion.
- `--porcelain` — Output just the id when the operation is successful.

### wc product_shipping_class

#### wc product_shipping_class list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--exclude` — Ensure result set excludes specific ids.
- `--include` — Limit result set to specific ids.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by resource attribute.
- `--hide_empty` — Whether to hide resources not assigned to any products.
- `--product` — Limit result set to resources assigned to a specific product.
- `--slug` — Limit result set to resources with a specific slug.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_shipping_class create

- `--name` — Name for the resource. (*Required*)
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--description` — HTML description of the resource.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_shipping_class get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_shipping_class update [id]

- `--id` — Unique identifier for the resource.
- `--name` — Shipping class name.
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--description` — HTML description of the resource.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_shipping_class delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc product_tag

#### wc product_tag list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--exclude` — Ensure result set excludes specific ids.
- `--include` — Limit result set to specific ids.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by resource attribute.
- `--hide_empty` — Whether to hide resources not assigned to any products.
- `--product` — Limit result set to resources assigned to a specific product.
- `--slug` — Limit result set to resources with a specific slug.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_tag create

- `--name` — Name for the resource. (*Required*)
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--description` — HTML description of the resource.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_tag get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_tag update [id]

- `--id` — Unique identifier for the resource.
- `--name` — Tag name.
- `--slug` — An alphanumeric identifier for the resource unique to its type.
- `--description` — HTML description of the resource.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_tag delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc product

#### wc product list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--after` — Limit response to resources published after a given ISO8601 compliant date.
- `--before` — Limit response to resources published before a given ISO8601 compliant date.
- `--exclude` — Ensure result set excludes specific IDs.
- `--include` — Limit result set to specific ids.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by object attribute.
- `--parent` — Limit result set to those of particular parent IDs.
- `--parent_exclude` — Limit result set to all items except those of a particular parent ID.
- `--slug` — Limit result set to products with a specific slug.
- `--status` — Limit result set to products assigned a specific status.
- `--type` — Limit result set to products assigned a specific type.
- `--sku` — Limit result set to products with a specific SKU.
- `--featured` — Limit result set to featured products.
- `--category` — Limit result set to products assigned a specific category ID.
- `--tag` — Limit result set to products assigned a specific tag ID.
- `--shipping_class` — Limit result set to products assigned a specific shipping class ID.
- `--attribute` — Limit result set to products with a specific attribute.
- `--attribute_term` — Limit result set to products with a specific attribute term ID (required an assigned attribute).
- `--tax_class` — Limit result set to products with a specific tax class.
- `--in_stock` — Limit result set to products in stock or out of stock.
- `--on_sale` — Limit result set to products on sale.
- `--min_price` — Limit result set to products based on a minimum price.
- `--max_price` — Limit result set to products based on a maximum price.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product create

- `--name` — Product name.
- `--slug` — Product slug.
- `--type` — Product type.
- `--status` — Product status (post status).
- `--featured` — Featured product.
- `--catalog_visibility` — Catalog visibility.
- `--description` — Product description.
- `--short_description` — Product short description.
- `--sku` — Unique identifier.
- `--regular_price` — Product regular price.
- `--sale_price` — Product sale price.
- `--date_on_sale_from` — Start date of sale price, in the site's timezone.
- `--date_on_sale_from_gmt` — Start date of sale price, as GMT.
- `--date_on_sale_to` — End date of sale price, in the site's timezone.
- `--date_on_sale_to_gmt` — End date of sale price, in the site's timezone.
- `--virtual` — If the product is virtual.
- `--downloadable` — If the product is downloadable.
- `--downloads` — List of downloadable files.
- `--download_limit` — Number of times downloadable files can be downloaded after purchase.
- `--download_expiry` — Number of days until access to downloadable files expires.
- `--external_url` — Product external URL. Only for external products.
- `--button_text` — Product external button text. Only for external products.
- `--tax_status` — Tax status.
- `--tax_class` — Tax class.
- `--manage_stock` — Stock management at product level.
- `--stock_quantity` — Stock quantity.
- `--in_stock` — Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.
- `--backorders` — If managing stock, this controls if backorders are allowed.
- `--sold_individually` — Allow one item to be bought in a single order.
- `--weight` — Product weight (lbs).
- `--dimensions` — Product dimensions.
- `--shipping_class` — Shipping class slug.
- `--reviews_allowed` — Allow reviews.
- `--upsell_ids` — List of up-sell products IDs.
- `--cross_sell_ids` — List of cross-sell products IDs.
- `--parent_id` — Product parent ID.
- `--purchase_note` — Optional note to send the customer after purchase.
- `--categories` — List of categories.
- `--tags` — List of tags.
- `--images` — List of images.
- `--attributes` — List of attributes.
- `--default_attributes` — Defaults variation attributes.
- `--menu_order` — Menu order, used to custom sort products.
- `--meta_data` — Meta data.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product update [id]

- `--id` — Unique identifier for the resource.
- `--name` — Product name.
- `--slug` — Product slug.
- `--type` — Product type.
- `--status` — Product status (post status).
- `--featured` — Featured product.
- `--catalog_visibility` — Catalog visibility.
- `--description` — Product description.
- `--short_description` — Product short description.
- `--sku` — Unique identifier.
- `--regular_price` — Product regular price.
- `--sale_price` — Product sale price.
- `--date_on_sale_from` — Start date of sale price, in the site's timezone.
- `--date_on_sale_from_gmt` — Start date of sale price, as GMT.
- `--date_on_sale_to` — End date of sale price, in the site's timezone.
- `--date_on_sale_to_gmt` — End date of sale price, in the site's timezone.
- `--virtual` — If the product is virtual.
- `--downloadable` — If the product is downloadable.
- `--downloads` — List of downloadable files.
- `--download_limit` — Number of times downloadable files can be downloaded after purchase.
- `--download_expiry` — Number of days until access to downloadable files expires.
- `--external_url` — Product external URL. Only for external products.
- `--button_text` — Product external button text. Only for external products.
- `--tax_status` — Tax status.
- `--tax_class` — Tax class.
- `--manage_stock` — Stock management at product level.
- `--stock_quantity` — Stock quantity.
- `--in_stock` — Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.
- `--backorders` — If managing stock, this controls if backorders are allowed.
- `--sold_individually` — Allow one item to be bought in a single order.
- `--weight` — Product weight (lbs).
- `--dimensions` — Product dimensions.
- `--shipping_class` — Shipping class slug.
- `--reviews_allowed` — Allow reviews.
- `--upsell_ids` — List of up-sell products IDs.
- `--cross_sell_ids` — List of cross-sell products IDs.
- `--parent_id` — Product parent ID.
- `--purchase_note` — Optional note to send the customer after purchase.
- `--categories` — List of categories.
- `--tags` — List of tags.
- `--images` — List of images.
- `--attributes` — List of attributes.
- `--default_attributes` — Defaults variation attributes.
- `--menu_order` — Menu order, used to custom sort products.
- `--meta_data` — Meta data.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Whether to bypass trash and force deletion.
- `--porcelain` — Output just the id when the operation is successful.

### wc product_variation

#### wc product_variation list <product_id>

- `--product_id` — Unique identifier for the variable product.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--after` — Limit response to resources published after a given ISO8601 compliant date.
- `--before` — Limit response to resources published before a given ISO8601 compliant date.
- `--exclude` — Ensure result set excludes specific IDs.
- `--include` — Limit result set to specific ids.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by object attribute.
- `--parent` — Limit result set to those of particular parent IDs.
- `--parent_exclude` — Limit result set to all items except those of a particular parent ID.
- `--slug` — Limit result set to products with a specific slug.
- `--status` — Limit result set to products assigned a specific status.
- `--type` — Limit result set to products assigned a specific type.
- `--sku` — Limit result set to products with a specific SKU.
- `--featured` — Limit result set to featured products.
- `--category` — Limit result set to products assigned a specific category ID.
- `--tag` — Limit result set to products assigned a specific tag ID.
- `--shipping_class` — Limit result set to products assigned a specific shipping class ID.
- `--attribute` — Limit result set to products with a specific attribute.
- `--attribute_term` — Limit result set to products with a specific attribute term ID (required an assigned attribute).
- `--tax_class` — Limit result set to products with a specific tax class.
- `--in_stock` — Limit result set to products in stock or out of stock.
- `--on_sale` — Limit result set to products on sale.
- `--min_price` — Limit result set to products based on a minimum price.
- `--max_price` — Limit result set to products based on a maximum price.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_variation create <product_id>

- `--product_id` — Unique identifier for the variable product.
- `--description` — Variation description.
- `--sku` — Unique identifier.
- `--regular_price` — Variation regular price.
- `--sale_price` — Variation sale price.
- `--date_on_sale_from` — Start date of sale price, in the site's timezone.
- `--date_on_sale_from_gmt` — Start date of sale price, as GMT.
- `--date_on_sale_to` — End date of sale price, in the site's timezone.
- `--date_on_sale_to_gmt` — End date of sale price, in the site's timezone.
- `--visible` — Define if the attribute is visible on the "Additional information" tab in the product's page.
- `--virtual` — If the variation is virtual.
- `--downloadable` — If the variation is downloadable.
- `--downloads` — List of downloadable files.
- `--download_limit` — Number of times downloadable files can be downloaded after purchase.
- `--download_expiry` — Number of days until access to downloadable files expires.
- `--tax_status` — Tax status.
- `--tax_class` — Tax class.
- `--manage_stock` — Stock management at variation level.
- `--stock_quantity` — Stock quantity.
- `--in_stock` — Controls whether or not the variation is listed as "in stock" or "out of stock" on the frontend.
- `--backorders` — If managing stock, this controls if backorders are allowed.
- `--weight` — Variation weight (lbs).
- `--dimensions` — Variation dimensions.
- `--shipping_class` — Shipping class slug.
- `--image` — Variation image data.
- `--attributes` — List of attributes.
- `--menu_order` — Menu order, used to custom sort products.
- `--meta_data` — Meta data.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_variation get <product_id> [id]

- `--product_id` — Unique identifier for the variable product.
- `--id` — Unique identifier for the variation.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc product_variation update <product_id> [id]

- `--product_id` — Unique identifier for the variable product.
- `--id` — Unique identifier for the variation.
- `--description` — Variation description.
- `--sku` — Unique identifier.
- `--regular_price` — Variation regular price.
- `--sale_price` — Variation sale price.
- `--date_on_sale_from` — Start date of sale price, in the site's timezone.
- `--date_on_sale_from_gmt` — Start date of sale price, as GMT.
- `--date_on_sale_to` — End date of sale price, in the site's timezone.
- `--date_on_sale_to_gmt` — End date of sale price, in the site's timezone.
- `--visible` — Define if the attribute is visible on the "Additional information" tab in the product's page.
- `--virtual` — If the variation is virtual.
- `--downloadable` — If the variation is downloadable.
- `--downloads` — List of downloadable files.
- `--download_limit` — Number of times downloadable files can be downloaded after purchase.
- `--download_expiry` — Number of days until access to downloadable files expires.
- `--tax_status` — Tax status.
- `--tax_class` — Tax class.
- `--manage_stock` — Stock management at variation level.
- `--stock_quantity` — Stock quantity.
- `--in_stock` — Controls whether or not the variation is listed as "in stock" or "out of stock" on the frontend.
- `--backorders` — If managing stock, this controls if backorders are allowed.
- `--weight` — Variation weight (lbs).
- `--dimensions` — Variation dimensions.
- `--shipping_class` — Shipping class slug.
- `--image` — Variation image data.
- `--attributes` — List of attributes.
- `--menu_order` — Menu order, used to custom sort products.
- `--meta_data` — Meta data.
- `--porcelain` — Output just the id when the operation is successful.

#### wc product_variation delete <product_id> [id]

- `--product_id` — Unique identifier for the variable product.
- `--id` — Unique identifier for the variation.
- `--force` — Whether to bypass trash and force deletion.
- `--porcelain` — Output just the id when the operation is successful.

### wc setting

#### wc setting get [id]

- `--group` — Settings group ID.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc setting update [id]

- `--group` — Settings group ID.
- `--id` — Unique identifier for the resource.
- `--value` — Setting value.
- `--porcelain` — Output just the id when the operation is successful.

### wc shipping_zone

#### wc shipping_zone list

- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shipping_zone create

- `--name` — Shipping zone name. (*Required*)
- `--order` — Shipping zone order.
- `--porcelain` — Output just the id when the operation is successful.

#### wc shipping_zone get [id]

- `--id` — Unique ID for the resource.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shipping_zone update [id]

- `--id` — Unique ID for the resource.
- `--name` — Shipping zone name.
- `--order` — Shipping zone order.
- `--porcelain` — Output just the id when the operation is successful.

#### wc shipping_zone delete [id]

- `--id` — Unique ID for the resource.
- `--force` — Whether to bypass trash and force deletion.
- `--porcelain` — Output just the id when the operation is successful.

### wc shipping_zone_location

#### wc shipping_zone_location list

- `--id` — Unique ID for the resource.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

### wc shipping_zone_method

#### wc shipping_zone_method list

- `--zone_id` — Unique ID for the zone.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shipping_zone_method create

- `--zone_id` — Unique ID for the zone.
- `--order` — Shipping method sort order.
- `--enabled` — Shipping method enabled status.
- `--settings` — Shipping method settings.
- `--method_id` — Shipping method ID. (*Required*)
- `--porcelain` — Output just the id when the operation is successful.

#### wc shipping_zone_method get [id]

- `--zone_id` — Unique ID for the zone.
- `--instance_id` — Unique ID for the instance.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shipping_zone_method update [id]

- `--zone_id` — Unique ID for the zone.
- `--instance_id` — Unique ID for the instance.
- `--order` — Shipping method sort order.
- `--enabled` — Shipping method enabled status.
- `--settings` — Shipping method settings.
- `--porcelain` — Output just the id when the operation is successful.

#### wc shipping_zone_method delete [id]

- `--zone_id` — Unique ID for the zone.
- `--instance_id` — Unique ID for the instance.
- `--force` — Whether to bypass trash and force deletion.
- `--porcelain` — Output just the id when the operation is successful.

### wc tax_class

#### wc tax_class list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc tax_class create

- `--name` — Tax class name. (*Required*)
- `--porcelain` — Output just the id when the operation is successful.

#### wc tax_class delete [id]

- `--slug` — Unique slug for the resource.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc tax

#### wc tax list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--exclude` — Ensure result set excludes specific IDs.
- `--include` — Limit result set to specific IDs.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by object attribute.
- `--class` — Sort by tax class.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc tax create

- `--country` — Country ISO 3166 code.
- `--state` — State code.
- `--postcode` — Postcode / ZIP.
- `--city` — City name.
- `--rate` — Tax rate.
- `--name` — Tax rate name.
- `--priority` — Tax priority.
- `--compound` — Whether or not this is a compound rate.
- `--shipping` — Whether or not this tax rate also gets applied to shipping.
- `--order` — Indicates the order that will appear in queries.
- `--class` — Tax class.
- `--porcelain` — Output just the id when the operation is successful.

#### wc tax get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc tax update [id]

- `--id` — Unique identifier for the resource.
- `--country` — Country ISO 3166 code.
- `--state` — State code.
- `--postcode` — Postcode / ZIP.
- `--city` — City name.
- `--rate` — Tax rate.
- `--name` — Tax rate name.
- `--priority` — Tax priority.
- `--compound` — Whether or not this is a compound rate.
- `--shipping` — Whether or not this tax rate also gets applied to shipping.
- `--order` — Indicates the order that will appear in queries.
- `--class` — Tax class.
- `--porcelain` — Output just the id when the operation is successful.

#### wc tax delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc webhook_delivery

#### wc webhook_delivery list

- `--webhook_id` — Unique identifier for the webhook.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc webhook_delivery get [id]

- `--webhook_id` — Unique identifier for the webhook.
- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

### wc webhook

#### wc webhook list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--page` — Current page of the collection.
- `--per_page` — Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` — Limit results to those matching a string.
- `--after` — Limit response to resources published after a given ISO8601 compliant date.
- `--before` — Limit response to resources published before a given ISO8601 compliant date.
- `--exclude` — Ensure result set excludes specific IDs.
- `--include` — Limit result set to specific ids.
- `--offset` — Offset the result set by a specific number of items.
- `--order` — Order sort attribute ascending or descending.
- `--orderby` — Sort collection by object attribute.
- `--status` — Limit result set to webhooks assigned a specific status.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc webhook create

- `--name` — A friendly name for the webhook.
- `--status` — Webhook status.
- `--topic` — Webhook topic. (*Required*)
- `--secret` — Webhook secret. (*Required*)
- `--delivery_url` — Webhook delivery URL. (*Required*)
- `--porcelain` — Output just the id when the operation is successful.

#### wc webhook get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc webhook update [id]

- `--id` — Unique identifier for the resource.
- `--name` — A friendly name for the webhook.
- `--status` — Webhook status.
- `--topic` — Webhook topic.
- `--secret` — Secret key used to generate a hash of the delivered webhook and provided in the request headers. This will default is a MD5 hash from the current user's ID|username if not provided.
- `--porcelain` — Output just the id when the operation is successful.

#### wc webhook delete [id]

- `--id` — Unique identifier for the resource.
- `--force` — Required to be true, as resource does not support trashing.
- `--porcelain` — Output just the id when the operation is successful.

### wc shipping_method

#### wc shipping_method list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc shipping_method get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

### wc payment_gateway

#### wc payment_gateway list

- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc payment_gateway get [id]

- `--id` — Unique identifier for the resource.
- `--context` — Scope under which the request is made; determines fields present in response.
- `--fields` — Limit response to specific fields. Defaults to all fields.
- `--field` — Get the value of an individual field.
- `--format` — Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

#### wc payment_gateway update [id]

- `--id` — Unique identifier for the resource.
- `--title` — Payment gateway title on checkout.
- `--description` — Payment gateway description on checkout.
- `--order` — Payment gateway sort order.
- `--enabled` — Payment gateway enabled status.
- `--settings` — Payment gateway settings.
- `--porcelain` — Output just the id when the operation is successful.

### wc com

#### wc com extension list

- `--format` — Render output in a particular format.

Default: table

Options: table, csv, json, yaml

- `--fields` — Limit the output to specific object fields.

Default: all

Options: product_slug, product_name, auto_renew, expires_on, expired, sites_max, sites_active, maxed

#### wc com extension install [extension]

- `--extension` — Install one plugin from the available extensions.Accepts a plugin slug
- `--force` — If set, the command will overwrite any installed version of the extension without prompting for confirmation.
- `--activate` — If set, after installation, the plugin will activate it.
- `--activate-network` — If set, the plugin will be network activated immediately after installation
- `--insecure` — Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.
