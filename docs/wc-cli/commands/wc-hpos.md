---
title: wc hpos
sidebar_label: wc hpos
---

Commands for managing High-Performance Order Storage (HPOS). For detailed examples, see [HPOS CLI tools](/docs/features/high-performance-order-storage/cli-tools/).

The older `wc cot` namespace is deprecated. Use `wc hpos` instead of the remaining deprecated aliases: `wc cot count_unmigrated`, `wc cot sync`, `wc cot verify_cot_data`, `wc cot enable`, and `wc cot disable`. The `wc cot migrate` command is fully deprecated and no longer works.

## wc hpos status

Displays a summary of HPOS settings and sync status for the site.

## wc hpos enable

- `--for-new-shop` - Enable HPOS only if this is a new shop, regardless of whether tables are in sync.
- `--with-sync` - Also enable compatibility mode, which keeps the HPOS and posts datastores in sync.
- `--ignore-plugin-compatibility` - Enable HPOS even if active plugins are incompatible with HPOS.

## wc hpos disable

- `--with-sync` - Also disable compatibility mode, which stops keeping the HPOS and posts datastores in sync.

## wc hpos compatibility-info

- `--include-inactive` - Include inactive plugins in the compatibility lists.
- `--display-filenames` - Display plugin file names instead of plugin names.

## wc hpos compatibility-mode enable

Enables compatibility mode, which keeps the HPOS and posts datastores in sync.

## wc hpos compatibility-mode disable

Disables compatibility mode, which stops keeping the HPOS and posts datastores in sync.

## wc hpos count_unmigrated

Prints the number of orders pending sync.

## wc hpos sync

- `--batch-size` - The number of orders to process in each batch.

Default: 500

## wc hpos verify_data

- `--batch-size` - The number of orders to verify in each batch.
- `--start-from` - Order ID to start from.
- `--end-at` - Order ID to end at.
- `--verbose` - Output errors as they happen in each batch instead of aggregating them at the end.
- `--order-types` - Comma-separated list of order types to verify. Defaults to `wc_get_order_types( 'cot-migration' )`.
- `--re-migrate` - Attempt to re-migrate orders that fail verification. Use only after confirming the destination datastore should be overwritten.

Default batch size: 500

## wc hpos diff `<order_id>`

- `--format` - Render output in a particular format.

Default: table

Options: table, csv, json, yaml

## wc hpos backfill `<order_id>`

- `--from` - Source datastore. (*Required*)
- `--to` - Destination datastore. (*Required*)
- `--meta_keys` - Comma-separated list of meta keys to backfill.
- `--props` - Comma-separated list of order properties to backfill.

Datastore options: hpos, posts

## wc hpos cleanup `<all|id|range>...`

- `--batch-size` - Number of orders to process per batch. Applies only when cleaning up all orders.
- `--force` - Clean up post meta even if the post appears to have been updated more recently than the order.

Default batch size: 500
