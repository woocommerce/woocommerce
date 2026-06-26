---
title: wc payment_gateway
sidebar_label: wc payment_gateway
sidebar_position: 7
---

## wc payment_gateway list

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc payment_gateway get [id]

- `--id` - Unique identifier for the resource.
- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc payment_gateway update [id]

- `--id` - Unique identifier for the resource.
- `--title` - Payment gateway title on checkout.
- `--description` - Payment gateway description on checkout.
- `--order` - Payment gateway sort order.
- `--enabled` - Payment gateway enabled status.
- `--settings` - Payment gateway settings.
- `--porcelain` - Output just the id when the operation is successful.
