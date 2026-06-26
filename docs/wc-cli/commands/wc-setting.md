---
title: wc setting
sidebar_label: wc setting
sidebar_position: 17
---

## wc setting get [id]

- `--group` - Settings group ID.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc setting update [id]

- `--group` - Settings group ID.
- `--id` - Unique identifier for the resource.
- `--value` - Setting value.
- `--porcelain` - Output just the id when the operation is successful.
