---
title: wc product_attribute
sidebar_label: wc product_attribute
---

Manage WooCommerce product attribute resources.

## wc product_attribute list

List all product attributes.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_attribute create

Create a new product attribute.

- `--name` - Name for the resource. (*Required*)
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--type` - Type of attribute.
- `--order_by` - Default sort order.
- `--has_archives` - Enable/Disable attribute archives.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_attribute get `<id>`

Get a single product attribute.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc product_attribute update `<id>`

Update an existing product attribute.

- `--name` - Attribute name.
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--type` - Type of attribute.
- `--order_by` - Default sort order.
- `--has_archives` - Enable/Disable attribute archives.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_attribute delete `<id>`

Delete an existing product attribute.

- `--force` - Required to be true, as resource does not support trashing.
- `--porcelain` - Output just the id when the operation is successful.
