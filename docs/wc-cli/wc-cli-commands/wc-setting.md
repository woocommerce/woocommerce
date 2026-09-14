---
title: wc setting
sidebar_label: wc setting
---

Manage WooCommerce setting resources.

## wc setting get `<id>`

Get a single setting.

- `--group` - Settings group ID.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc setting update `<id>`

Update an existing setting.

- `--group` - Settings group ID.
- `--value` - Setting value.
- `--porcelain` - Output just the id when the operation is successful.
