---
title: wc product_review
sidebar_label: wc product_review
---

Manage WooCommerce product review resources.

## wc product_review list `<product_id>`

List all product reviews.

- `--id` - Unique identifier for the review.
- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_review create `<product_id>`

Create a new product review.

- `--id` - Unique identifier for the review.
- `--review` - Review content. (*Required*)
- `--date_created` - The date the review was created, in the site's timezone.
- `--date_created_gmt` - The date the review was created, as GMT.
- `--rating` - Review rating (0 to 5).
- `--name` - Name of the reviewer. (*Required*)
- `--email` - Email of the reviewer. (*Required*)
- `--porcelain` - Output just the id when the operation is successful.

## wc product_review get `<product_id>` `<id>`

Get a single product review.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc product_review update `<product_id>` `<id>`

Update an existing product review.

- `--review` - The content of the review.
- `--date_created` - The date the review was created, in the site's timezone.
- `--date_created_gmt` - The date the review was created, as GMT.
- `--rating` - Review rating (0 to 5).
- `--name` - Reviewer name.
- `--email` - Reviewer email.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_review delete `<product_id>` `<id>`

Delete an existing product review.

- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
