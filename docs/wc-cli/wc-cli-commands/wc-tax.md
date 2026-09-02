---
title: wc tax
sidebar_label: wc tax
---

Manage WooCommerce tax resources.

## wc tax list

List all taxes.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--page` - Current page of the collection.
- `--per_page` - Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` - Limit results to those matching a string.
- `--exclude` - Ensure result set excludes specific IDs.
- `--include` - Limit result set to specific IDs.
- `--offset` - Offset the result set by a specific number of items.
- `--order` - Order sort attribute ascending or descending.
- `--orderby` - Sort collection by object attribute.
- `--class` - Sort by tax class.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc tax create

Create a new tax.

- `--country` - Country ISO 3166 code.
- `--state` - State code.
- `--postcode` - Postcode / ZIP.
- `--city` - City name.
- `--rate` - Tax rate.
- `--name` - Tax rate name.
- `--priority` - Tax priority.
- `--compound` - Whether or not this is a compound rate.
- `--shipping` - Whether or not this tax rate also gets applied to shipping.
- `--order` - Indicates the order that will appear in queries.
- `--class` - Tax class.
- `--porcelain` - Output just the id when the operation is successful.

## wc tax get `<id>`

Get a single tax.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc tax update `<id>`

Update an existing tax.

- `--country` - Country ISO 3166 code.
- `--state` - State code.
- `--postcode` - Postcode / ZIP.
- `--city` - City name.
- `--rate` - Tax rate.
- `--name` - Tax rate name.
- `--priority` - Tax priority.
- `--compound` - Whether or not this is a compound rate.
- `--shipping` - Whether or not this tax rate also gets applied to shipping.
- `--order` - Indicates the order that will appear in queries.
- `--class` - Tax class.
- `--porcelain` - Output just the id when the operation is successful.

## wc tax delete `<id>`

Delete an existing tax.

- `--force` - Required to be true, as resource does not support trashing.
- `--porcelain` - Output just the id when the operation is successful.
