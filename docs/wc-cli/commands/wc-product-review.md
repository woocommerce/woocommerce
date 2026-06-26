---
title: wc product_review
sidebar_label: wc product_review
sidebar_position: 13
---

## wc product_review list `<product_id>`

- `--product_id` - Unique identifier for the variable product.
- `--id` - Unique identifier for the variation.
- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_review create `<product_id>`

- `--product_id` - Unique identifier for the variable product.
- `--id` - Unique identifier for the variation.
- `--review` - Review content. (*Required*)
- `--date_created` - The date the review was created, in the site's timezone.
- `--date_created_gmt` - The date the review was created, as GMT.
- `--rating` - Review rating (0 to 5).
- `--name` - Name of the reviewer. (*Required*)
- `--email` - Email of the reviewer. (*Required*)
- `--porcelain` - Output just the id when the operation is successful.

## wc product_review get `<product_id>` [id]

- `--product_id` - Unique identifier for the variable product.
- `--id` - Unique identifier for the resource.
- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

Default: table

Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_review update `<product_id>` [id]

- `--product_id` - Unique identifier for the variable product.
- `--id` - Unique identifier for the resource.
- `--review` - The content of the review.
- `--date_created` - The date the review was created, in the site's timezone.
- `--date_created_gmt` - The date the review was created, as GMT.
- `--rating` - Review rating (0 to 5).
- `--name` - Reviewer name.
- `--email` - Reviewer email.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_review delete `<product_id>` [id]

- `--product_id` - Unique identifier for the variable product.
- `--id` - Unique identifier for the resource.
- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
