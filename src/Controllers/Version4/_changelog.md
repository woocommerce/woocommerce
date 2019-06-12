# REST API v4 Change-log

## Changes

- All endpoints - Rewritten with namespaces as standalone classes.
- All endpoints - Normalized DELETE responses to return previous object.
- Coupons - Added `search` parameter.
- Orders - Added order number to schema.
- Orders - Added currency_symbol to schema.
- Product Reviews - Updated response links.
- Products - Added `low_in_stock` and `search` parameter.
- Product Variations - Added `search` parameter.
- Product Variations - Added `name`, `type`, `parent_id` to schema.
- Reports - Updated with updated list of available reports.
- Taxes - Added `code` and `include` params.

## New endpoints

- `data/download-ips`

## Removed endpoints

- `reports/top_sellers`
- `reports/sales`
- `reports/customers/totals`
- `reports/orders/totals`
- `reports/coupons/totals`
- `reports/reviews/totals`
- `reports/products/totals`
