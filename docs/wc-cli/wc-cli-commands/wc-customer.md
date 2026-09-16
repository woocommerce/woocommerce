---
title: wc customer
sidebar_label: wc customer
---

Manage WooCommerce customer resources.

## wc customer list

List all customers.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--page` - Current page of the collection.
- `--per_page` - Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` - Limit results to those matching a string.
- `--exclude` - Ensure result set excludes specific IDs.
- `--include` - Limit result set to specific IDs.
- `--offset` - Offset the result set by a specific number of items.
- `--order` - Order sort attribute ascending or descending.
- `--orderby` - Sort collection by object attribute.
- `--email` - Limit result set to resources with a specific email.
- `--role` - Limit result set to resources with a specific role.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc customer create

Create a new customer.

- `--email` - New user email address. (*Required*)
- `--first_name` - Customer first name.
- `--last_name` - Customer last name.
- `--username` - New user username.
- `--password` - New user password. (*Required*)
- `--billing` - List of billing address data.
- `--shipping` - List of shipping address data.
- `--meta_data` - Meta data.
- `--porcelain` - Output just the id when the operation is successful.

## wc customer get `<id>`

Get a single customer.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc customer update `<id>`

Update an existing customer.

- `--email` - The email address for the customer.
- `--first_name` - Customer first name.
- `--last_name` - Customer last name.
- `--username` - Customer login name.
- `--password` - Customer password.
- `--billing` - List of billing address data.
- `--shipping` - List of shipping address data.
- `--meta_data` - Meta data.
- `--porcelain` - Output just the id when the operation is successful.

## wc customer delete `<id>`

Delete an existing customer.

- `--force` - Required to be true, as resource does not support trashing.
- `--reassign` - ID to reassign posts to.
- `--porcelain` - Output just the id when the operation is successful.
