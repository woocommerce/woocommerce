---
title: wc palt
sidebar_label: wc palt
---

Commands for managing the product attributes lookup table. The table stores denormalized product attribute term data used to speed up catalog filtering.

## wc palt enable

Enable usage of the product attributes lookup table.

- `--force` - Skip confirmation when enabling table usage while regeneration is in progress, was aborted, or the lookup table is empty.

## wc palt disable

Disables usage of the product attributes lookup table.

## wc palt info

Displays information about the product attributes lookup table.

## wc palt regenerate_for_product `<product_id>`

Regenerate product attributes lookup table data for one product.

- `--disable-db-optimization` - Do not use optimized database access even if products are stored as custom post types.

## wc palt abort_regeneration

Abort background regeneration of the product attributes lookup table.

- `--cleanup` - Also clean up temporary data, so regeneration cannot be resumed but can be restarted.

## wc palt resume_regeneration

Resumes background regeneration of the product attributes lookup table after it has been aborted.

## wc palt cleanup_regeneration_progress

Deletes temporary data used during product attributes lookup table regeneration.

## wc palt initiate_regeneration

Initiate background regeneration of the product attributes lookup table using scheduled actions.

- `--force` - Do not prompt for confirmation if the product attributes lookup table is not empty.

## wc palt regenerate

Regenerate the product attributes lookup table immediately without using scheduled tasks.

- `--force` - Do not prompt for confirmation if the product attributes lookup table is not empty.
- `--from-scratch` - Start table regeneration from scratch even if regeneration is already in progress.
- `--disable-db-optimization` - Do not use optimized database access even if products are stored as custom post types.
- `--batch-size` - How many products to process in each iteration of the loop.

    Default: 10
