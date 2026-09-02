---
title: wc product_cat
sidebar_label: wc product_cat
---

Manage WooCommerce product category resources.

## wc product_cat list

List all product categories.

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

## wc product_cat create

Create a new product category.

- `--name` - Name for the resource. (*Required*)
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--parent` - The ID for the parent of the resource.
- `--description` - HTML description of the resource.
- `--display` - Category archive display type.
- `--image` - Image data.
- `--menu_order` - Menu order, used to custom sort the resource.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_cat get `<id>`

Get a single product category.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc product_cat update `<id>`

Update an existing product category.

- `--name` - Category name.
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--parent` - The ID for the parent of the resource.
- `--description` - HTML description of the resource.
- `--display` - Category archive display type.
- `--image` - Image data.
- `--menu_order` - Menu order, used to custom sort the resource.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_cat delete `<id>`

Delete an existing product category.

- `--force` - Required to be true, as resource does not support trashing.
- `--porcelain` - Output just the id when the operation is successful.
