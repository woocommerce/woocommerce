---
title: wc tax_class
sidebar_label: wc tax_class
---

Manage WooCommerce tax class resources.

## wc tax_class list

List all tax classes.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc tax_class create

Create a new tax class.

- `--name` - Tax class name. (*Required*)
- `--porcelain` - Output just the id when the operation is successful.

## wc tax_class delete `<slug>`

Delete an existing tax class.

- `--force` - Required to be true, as resource does not support trashing.
- `--porcelain` - Output just the id when the operation is successful.
