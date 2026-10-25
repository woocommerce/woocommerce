---
title: wc payment_gateway
sidebar_label: wc payment_gateway
---

Manage WooCommerce payment gateway resources.

## wc payment_gateway list

List all payment gateways.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc payment_gateway get `<id>`

Get a single payment gateway.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc payment_gateway update `<id>`

Update an existing payment gateway.

- `--title` - Payment gateway title on checkout.
- `--description` - Payment gateway description on checkout.
- `--order` - Payment gateway sort order.
- `--enabled` - Payment gateway enabled status.
- `--settings` - Payment gateway settings.
- `--porcelain` - Output just the id when the operation is successful.
