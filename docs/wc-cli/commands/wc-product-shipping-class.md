---
title: wc product_shipping_class
sidebar_label: wc product_shipping_class
---

## wc product_shipping_class list

- `--context` - Scope under which the request is made; determines fields present in response.
- `--page` - Current page of the collection.
- `--per_page` - Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` - Limit results to those matching a string.
- `--exclude` - Ensure result set excludes specific ids.
- `--include` - Limit result set to specific ids.
- `--offset` - Offset the result set by a specific number of items.
- `--order` - Order sort attribute ascending or descending.
- `--orderby` - Sort collection by resource attribute.
- `--hide_empty` - Whether to hide resources not assigned to any products.
- `--product` - Limit result set to resources assigned to a specific product.
- `--slug` - Limit result set to resources with a specific slug.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_shipping_class create

- `--name` - Name for the resource. (*Required*)
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--description` - HTML description of the resource.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_shipping_class get `<id>`

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_shipping_class update `<id>`

- `--name` - Shipping class name.
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--description` - HTML description of the resource.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_shipping_class delete `<id>`

- `--force` - Required to be true, as resource does not support trashing.
- `--porcelain` - Output just the id when the operation is successful.
