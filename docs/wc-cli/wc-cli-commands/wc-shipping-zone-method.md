---
title: wc shipping_zone_method
sidebar_label: wc shipping_zone_method
---

Manage WooCommerce shipping zone method resources.

## wc shipping_zone_method list `<zone_id>`

List all shipping zone methods.

- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shipping_zone_method create `<zone_id>`

Create a new shipping zone method.

- `--order` - Shipping method sort order.
- `--enabled` - Shipping method enabled status.
- `--settings` - Shipping method settings.
- `--method_id` - Shipping method ID. (*Required*)
- `--porcelain` - Output just the id when the operation is successful.

## wc shipping_zone_method get `<zone_id>` `<instance_id>`

Get a single shipping zone method.

- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc shipping_zone_method update `<zone_id>` `<instance_id>`

Update an existing shipping zone method.

- `--order` - Shipping method sort order.
- `--enabled` - Shipping method enabled status.
- `--settings` - Shipping method settings.
- `--porcelain` - Output just the id when the operation is successful.

## wc shipping_zone_method delete `<zone_id>` `<instance_id>`

Delete an existing shipping zone method.

- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
