---
title: wc shipping_zone
sidebar_label: wc shipping_zone
---

Manage WooCommerce shipping zone resources.

## wc shipping_zone list

List all shipping zones.

- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shipping_zone create

Create a new shipping zone.

- `--name` - Shipping zone name. (*Required*)
- `--order` - Shipping zone order.
- `--porcelain` - Output just the id when the operation is successful.

## wc shipping_zone get `<id>`

Get a single shipping zone.

- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc shipping_zone update `<id>`

Update an existing shipping zone.

- `--name` - Shipping zone name.
- `--order` - Shipping zone order.
- `--porcelain` - Output just the id when the operation is successful.

## wc shipping_zone delete `<id>`

Delete an existing shipping zone.

- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
