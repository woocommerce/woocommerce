---
title: wc product_attribute_term
sidebar_label: wc product_attribute_term
---

## wc product_attribute_term list `<attribute_id>`

- `--context` - Scope under which the request is made; determines fields present in response.
- `--page` - Current page of the collection.
- `--per_page` - Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` - Limit results to those matching a string.
- `--exclude` - Ensure result set excludes specific ids.
- `--include` - Limit result set to specific ids.
- `--order` - Order sort attribute ascending or descending.
- `--orderby` - Sort collection by resource attribute.
- `--hide_empty` - Whether to hide resources not assigned to any products.
- `--parent` - Limit result set to resources assigned to a specific parent.
- `--product` - Limit result set to resources assigned to a specific product.
- `--slug` - Limit result set to resources with a specific slug.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_attribute_term create `<attribute_id>`

- `--name` - Name for the resource. (*Required*)
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--description` - HTML description of the resource.
- `--menu_order` - Menu order, used to custom sort the resource.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_attribute_term get `<attribute_id>` `<id>`

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_attribute_term update `<attribute_id>` `<id>`

- `--name` - Term name.
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--description` - HTML description of the resource.
- `--menu_order` - Menu order, used to custom sort the resource.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_attribute_term delete `<attribute_id>` `<id>`

- `--force` - Required to be true, as resource does not support trashing.
- `--porcelain` - Output just the id when the operation is successful.
