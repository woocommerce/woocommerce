---
title: wc product export
sidebar_label: wc product export
---

Export products to a CSV file using the same underlying engine as the admin product exporter
(**WooCommerce > Products > Export**).

## wc product export `[<file>]`

Export products to a CSV file, or `-` to print the CSV to STDOUT. Required unless `--list-columns`
is used.

- `--columns=<list>` - Comma-separated column keys to export. Defaults to all. Use `--list-columns` to see keys.
- `--types=<list>` - Comma-separated product types, such as simple, variable, variation, grouped, and external. Also accepts types registered by extensions or added through `woocommerce_exporter_product_types`. Defaults to all.
- `--category=<list>` - Comma-separated category slugs.
- `--ids=<list>` - Comma-separated product IDs. Takes priority over `--types` and `--category`, which are ignored. A variable product's variations are included.
- `--meta` - Include custom meta columns.
- `--batch-size=<n>` - Products per batch.

    Default: `50`

- `--list-columns` - Print available column keys and labels, then exit.
- `--porcelain` - Print only the number of exported products. Cannot be used with `-`.

### Examples

```bash
# List the available column keys.
wp wc product export --list-columns

# Export every simple product to a file.
wp wc product export products.csv --types=simple

# Export a specific set of products and columns, including meta.
wp wc product export products.csv --ids=12,34,56 --columns=id,sku,name --meta

# Export to STDOUT and pipe it elsewhere.
wp wc product export - --types=simple | gzip > products.csv.gz
```
