---
title: wc shipping_zone
sidebar_label: wc shipping_zone
sidebar_position: 19
---

## wc shipping_zone list

- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shipping_zone create

- `--name` - Shipping zone name. (*Required*)
- `--order` - Shipping zone order.
- `--porcelain` - Output just the id when the operation is successful.

## wc shipping_zone get [id]

- `--id` - Unique ID for the resource.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shipping_zone update [id]

- `--id` - Unique ID for the resource.
- `--name` - Shipping zone name.
- `--order` - Shipping zone order.
- `--porcelain` - Output just the id when the operation is successful.

## wc shipping_zone delete [id]

- `--id` - Unique ID for the resource.
- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
