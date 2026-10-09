---
title: wc product
sidebar_label: wc product
---

Manage WooCommerce product resources.

## wc product list

List all products.

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

## wc product create

Create a new product.

- `--name` - Product name.
- `--slug` - Product slug.
- `--type` - Product type.
- `--status` - Product status (post status).
- `--featured` - Featured product.
- `--catalog_visibility` - Catalog visibility.
- `--description` - Product description.
- `--short_description` - Product short description.
- `--sku` - Unique identifier.
- `--regular_price` - Product regular price.
- `--sale_price` - Product sale price.
- `--date_on_sale_from` - Start date of sale price, in the site's timezone.
- `--date_on_sale_from_gmt` - Start date of sale price, as GMT.
- `--date_on_sale_to` - End date of sale price, in the site's timezone.
- `--date_on_sale_to_gmt` - End date of sale price, as GMT.
- `--virtual` - If the product is virtual.
- `--downloadable` - If the product is downloadable.
- `--downloads` - List of downloadable files.
- `--download_limit` - Number of times downloadable files can be downloaded after purchase.
- `--download_expiry` - Number of days until access to downloadable files expires.
- `--external_url` - Product external URL. Only for external products.
- `--button_text` - Product external button text. Only for external products.
- `--tax_status` - Tax status.
- `--tax_class` - Tax class.
- `--manage_stock` - Stock management at product level.
- `--stock_quantity` - Stock quantity.
- `--in_stock` - Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.
- `--backorders` - If managing stock, this controls if backorders are allowed.
- `--sold_individually` - Allow one item to be bought in a single order.
- `--weight` - Product weight (lbs).
- `--dimensions` - Product dimensions.
- `--shipping_class` - Shipping class slug.
- `--reviews_allowed` - Allow reviews.
- `--upsell_ids` - List of up-sell products IDs.
- `--cross_sell_ids` - List of cross-sell products IDs.
- `--parent_id` - Product parent ID.
- `--purchase_note` - Optional note to send the customer after purchase.
- `--categories` - List of categories.
- `--tags` - List of tags.
- `--images` - List of images.
- `--attributes` - List of attributes.
- `--default_attributes` - Defaults variation attributes.
- `--menu_order` - Menu order, used to custom sort products.
- `--meta_data` - Meta data.
- `--porcelain` - Output just the id when the operation is successful.

## wc product get `<id>`

Get a single product.

- `--context` - Scope under which the request is made; determines fields present in response.
- `--fields` - Limit response to specific fields. Defaults to all fields.
- `--field` - Get the value of an individual field.
- `--format` - Render response in a particular format.

    Default: table

    Options: table, json, csv, yaml, headers, body, envelope

## wc product update `<id>`

Update an existing product.

- `--name` - Product name.
- `--slug` - Product slug.
- `--type` - Product type.
- `--status` - Product status (post status).
- `--featured` - Featured product.
- `--catalog_visibility` - Catalog visibility.
- `--description` - Product description.
- `--short_description` - Product short description.
- `--sku` - Unique identifier.
- `--regular_price` - Product regular price.
- `--sale_price` - Product sale price.
- `--date_on_sale_from` - Start date of sale price, in the site's timezone.
- `--date_on_sale_from_gmt` - Start date of sale price, as GMT.
- `--date_on_sale_to` - End date of sale price, in the site's timezone.
- `--date_on_sale_to_gmt` - End date of sale price, as GMT.
- `--virtual` - If the product is virtual.
- `--downloadable` - If the product is downloadable.
- `--downloads` - List of downloadable files.
- `--download_limit` - Number of times downloadable files can be downloaded after purchase.
- `--download_expiry` - Number of days until access to downloadable files expires.
- `--external_url` - Product external URL. Only for external products.
- `--button_text` - Product external button text. Only for external products.
- `--tax_status` - Tax status.
- `--tax_class` - Tax class.
- `--manage_stock` - Stock management at product level.
- `--stock_quantity` - Stock quantity.
- `--in_stock` - Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend.
- `--backorders` - If managing stock, this controls if backorders are allowed.
- `--sold_individually` - Allow one item to be bought in a single order.
- `--weight` - Product weight (lbs).
- `--dimensions` - Product dimensions.
- `--shipping_class` - Shipping class slug.
- `--reviews_allowed` - Allow reviews.
- `--upsell_ids` - List of up-sell products IDs.
- `--cross_sell_ids` - List of cross-sell products IDs.
- `--parent_id` - Product parent ID.
- `--purchase_note` - Optional note to send the customer after purchase.
- `--categories` - List of categories.
- `--tags` - List of tags.
- `--images` - List of images.
- `--attributes` - List of attributes.
- `--default_attributes` - Defaults variation attributes.
- `--menu_order` - Menu order, used to custom sort products.
- `--meta_data` - Meta data.
- `--porcelain` - Output just the id when the operation is successful.

## wc product delete `<id>`

Delete an existing product.

- `--force` - Whether to bypass trash and force deletion.
- `--porcelain` - Output just the id when the operation is successful.
