---
title: wc product import
sidebar_label: wc product import
---

Import products from a CSV file using the same underlying engine as the admin product importer
(**WooCommerce > Products > Import**).

## wc product import `<file>`

Import products from a CSV file, or `-` to read from STDIN.

- `--update-existing` - Update products that already exist (matched by ID or SKU) instead of skipping them.
- `--delimiter=<char>` - CSV field delimiter, exactly one character. Pass a tab as `--delimiter=$'\t'`.

    Default: `,`

- `--character-encoding=<encoding>` - Character encoding of the input file.

    Default: `UTF-8`

- `--mapping=<spec>` - Column overrides: `Header=target,...`, inline JSON, or `@file.json`. Target `-` skips a column. Headers must match the CSV exactly, including case.
- `--map-unknown=<mode>` - What to do with CSV columns that have no known target: `skip` or `meta`.

    Default: `skip`

- `--show-mapping` - Print the resolved column mapping and a sample row, then exit without importing.
- `--batch-size=<n>` - Number of rows to process per batch.

    Default: `100`

- `--format=<format>` - Render the mapping preview or failure report as `table`, `csv`, `json`, or `yaml`. With anything but `table`, an import always prints the report and writes its summary to STDERR.

    Default: `table`

- `--porcelain` - Output only imported and updated product IDs, one per line.
- `--strict` - Exit with status 1 if any row failed.
- `--yes` - Skip the confirmation prompt shown when a previous, possibly interrupted import left
  placeholder products behind. Required in that case when reading the CSV from STDIN, or with
  `--porcelain` or a `--format` other than `table`.

There is no dry-run mode: the importer cannot parse a row without persisting some of its effects
(images, terms, attributes, and placeholder/parent products are written as rows are read). Test
against a staging site or a database snapshot first.

Cleanup of placeholder products at the end of an import is site-wide, so this command takes a lock
for the duration of the run and refuses to start while an admin CSV import (or another
`wp wc product import`) holds it. Do not run this command at the same time as an import in
**WooCommerce > Products > Import**.

Importing requires a user with the `edit_products`, `import`, and `manage_product_terms` capabilities,
so pass `--user=<admin>`. Without `manage_product_terms` the importer silently drops categories. Rows with downloadable files outside an approved
directory fail unless that user can manage options. `--show-mapping` does not need a user.

If a run is interrupted or crashes and nothing is running, clear the lock with
`wp option delete wc_product_importing`. The next run will then offer to clean up leftover
placeholder products. When the CSV is read from STDIN, pass `--yes`, because the confirmation prompt
cannot be answered while STDIN carries the CSV.

### Examples

```bash
# Preview the resolved column mapping without importing.
wp wc product import products.csv --show-mapping

# Override two columns, sending any other unrecognized column to post meta.
wp wc product import products.csv --mapping="Vendor SKU=sku,Notes=meta:notes" --map-unknown=meta --user=admin

# Import from STDIN and print only the resulting product IDs.
cat products.csv | wp wc product import - --porcelain --user=admin
```
