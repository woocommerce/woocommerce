---
title: wc shop_order_refund
sidebar_label: wc shop_order_refund
---

Manage WooCommerce shop order refund resources.

## wc shop_order_refund list `<order_id>`

List all shop order refunds.

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
- `--dp` - Number of decimal points to use in each resource.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shop_order_refund create `<order_id>`

Create a new shop order refund.

- `--amount` - Refund amount.
- `--reason` - Reason for refund.
- `--refunded_by` - User ID of user who created the refund.
- `--meta_data` - Meta data.
- `--line_items` - Line items data.
- `--api_refund` - When true, the payment gateway API is used to generate the refund.
- `--porcelain` - Output just the id when the operation is successful.

## wc shop_order_refund get `<order_id>` `<id>`

Get a single shop order refund.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc shop_order_refund delete `<order_id>` `<id>`

Delete an existing shop order refund.

- `--force` - Required to be true, as resource does not support trashing.
- `--porcelain` - Output just the id when the operation is successful.
