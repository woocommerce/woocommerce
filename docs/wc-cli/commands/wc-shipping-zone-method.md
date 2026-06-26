---
title: wc shipping_zone_method
sidebar_label: wc shipping_zone_method
sidebar_position: 21
---

## wc shipping_zone_method list

- `--zone_id` - Unique ID for the zone.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shipping_zone_method create

- `--zone_id` - Unique ID for the zone.
- `--order` - Shipping method sort order.
- `--enabled` - Shipping method enabled status.
- `--settings` - Shipping method settings.
- `--method_id` - Shipping method ID. (*Required*)
- `--porcelain` - Output just the id when the operation is successful.

## wc shipping_zone_method get [id]

- `--zone_id` - Unique ID for the zone.
- `--instance_id` - Unique ID for the instance.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc shipping_zone_method update [id]

- `--zone_id` - Unique ID for the zone.
- `--instance_id` - Unique ID for the instance.
- `--order` - Shipping method sort order.
- `--enabled` - Shipping method enabled status.
- `--settings` - Shipping method settings.
- `--porcelain` - Output just the id when the operation is successful.

## wc shipping_zone_method delete [id]

- `--zone_id` - Unique ID for the zone.
- `--instance_id` - Unique ID for the instance.
- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
