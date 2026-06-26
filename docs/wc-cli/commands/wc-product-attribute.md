---
title: wc product_attribute
sidebar_label: wc product_attribute
sidebar_position: 11
---

## wc product_attribute list

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_attribute create

- `--name` - Name for the resource. (*Required*)
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--type` - Type of attribute.
- `--order_by` - Default sort order.
- `--has_archives` - Enable/Disable attribute archives.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_attribute get [id]

- `--id` - Unique identifier for the resource.
- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_attribute update [id]

- `--id` - Unique identifier for the resource.
- `--name` - Attribute name.
- `--slug` - An alphanumeric identifier for the resource unique to its type.
- `--type` - Type of attribute.
- `--order_by` - Default sort order.
- `--has_archives` - Enable/Disable attribute archives.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_attribute delete [id]

- `--id` - Unique identifier for the resource.
- `--force` - Required to be true, as resource does not support trashing.
- `--porcelain` - Output just the id when the operation is successful.
