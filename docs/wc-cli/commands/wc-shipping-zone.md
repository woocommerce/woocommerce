---
title: wc shipping_zone
sidebar_label: wc shipping_zone
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

## wc shipping_zone get `<id>`

- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shipping_zone update `<id>`

- `--name` - Shipping zone name.
- `--order` - Shipping zone order.
- `--porcelain` - Output just the id when the operation is successful.

## wc shipping_zone delete `<id>`

- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
