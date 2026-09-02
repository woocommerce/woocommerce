---
title: wc product_variation
sidebar_label: wc product_variation
---

Manage WooCommerce product variation resources.

## wc product_variation list `<product_id>`

List all product variations.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--page` - Current page of the collection.
- `--per_page` - Maximum number of items to be returned in result set. Defaults to 100 items.
- `--search` - Limit results to those matching a string.
- `--after` - Limit response to resources published after a given ISO8601 compliant date.
- `--before` - Limit response to resources published before a given ISO8601 compliant date.
- `--exclude` - Ensure result set excludes specific IDs.
- `--include` - Limit result set to specific ids.
- `--offset` - Offset the result set by a specific number of items.
- `--order` - Order sort attribute ascending or descending.
- `--orderby` - Sort collection by object attribute.
- `--parent` - Limit result set to those of particular parent IDs.
- `--parent_exclude` - Limit result set to all items except those of a particular parent ID.
- `--slug` - Limit result set to products with a specific slug.
- `--status` - Limit result set to products assigned a specific status.
- `--type` - Limit result set to products assigned a specific type.
- `--sku` - Limit result set to products with a specific SKU.
- `--featured` - Limit result set to featured products.
- `--category` - Limit result set to products assigned a specific category ID.
- `--tag` - Limit result set to products assigned a specific tag ID.
- `--shipping_class` - Limit result set to products assigned a specific shipping class ID.
- `--attribute` - Limit result set to products with a specific attribute.
- `--attribute_term` - Limit result set to products with a specific attribute term ID (requires an assigned attribute).
- `--tax_class` - Limit result set to products with a specific tax class.
- `--in_stock` - Limit result set to products in stock or out of stock.
- `--on_sale` - Limit result set to products on sale.
- `--min_price` - Limit result set to products based on a minimum price.
- `--max_price` - Limit result set to products based on a maximum price.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, ids, yaml, count, headers, body, envelope

## wc product_variation create `<product_id>`

Create a new product variation.

- `--description` - Variation description.
- `--sku` - Unique identifier.
- `--regular_price` - Variation regular price.
- `--sale_price` - Variation sale price.
- `--date_on_sale_from` - Start date of sale price, in the site's timezone.
- `--date_on_sale_from_gmt` - Start date of sale price, as GMT.
- `--date_on_sale_to` - End date of sale price, in the site's timezone.
- `--date_on_sale_to_gmt` - End date of sale price, as GMT.
- `--visible` - Define if the attribute is visible on the "Additional information" tab in the product's page.
- `--virtual` - If the variation is virtual.
- `--downloadable` - If the variation is downloadable.
- `--downloads` - List of downloadable files.
- `--download_limit` - Number of times downloadable files can be downloaded after purchase.
- `--download_expiry` - Number of days until access to downloadable files expires.
- `--tax_status` - Tax status.
- `--tax_class` - Tax class.
- `--manage_stock` - Stock management at variation level.
- `--stock_quantity` - Stock quantity.
- `--in_stock` - Controls whether or not the variation is listed as "in stock" or "out of stock" on the frontend.
- `--backorders` - If managing stock, this controls if backorders are allowed.
- `--weight` - Variation weight (lbs).
- `--dimensions` - Variation dimensions.
- `--shipping_class` - Shipping class slug.
- `--image` - Variation image data.
- `--attributes` - List of attributes.
- `--menu_order` - Menu order, used to custom sort products.
- `--meta_data` - Meta data.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_variation get `<product_id>` `<id>`

Get a single product variation.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc product_variation update `<product_id>` `<id>`

Update an existing product variation.

- `--description` - Variation description.
- `--sku` - Unique identifier.
- `--regular_price` - Variation regular price.
- `--sale_price` - Variation sale price.
- `--date_on_sale_from` - Start date of sale price, in the site's timezone.
- `--date_on_sale_from_gmt` - Start date of sale price, as GMT.
- `--date_on_sale_to` - End date of sale price, in the site's timezone.
- `--date_on_sale_to_gmt` - End date of sale price, as GMT.
- `--visible` - Define if the attribute is visible on the "Additional information" tab in the product's page.
- `--virtual` - If the variation is virtual.
- `--downloadable` - If the variation is downloadable.
- `--downloads` - List of downloadable files.
- `--download_limit` - Number of times downloadable files can be downloaded after purchase.
- `--download_expiry` - Number of days until access to downloadable files expires.
- `--tax_status` - Tax status.
- `--tax_class` - Tax class.
- `--manage_stock` - Stock management at variation level.
- `--stock_quantity` - Stock quantity.
- `--in_stock` - Controls whether or not the variation is listed as "in stock" or "out of stock" on the frontend.
- `--backorders` - If managing stock, this controls if backorders are allowed.
- `--weight` - Variation weight (lbs).
- `--dimensions` - Variation dimensions.
- `--shipping_class` - Shipping class slug.
- `--image` - Variation image data.
- `--attributes` - List of attributes.
- `--menu_order` - Menu order, used to custom sort products.
- `--meta_data` - Meta data.
- `--porcelain` - Output just the id when the operation is successful.

## wc product_variation delete `<product_id>` `<id>`

Delete an existing product variation.

- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
