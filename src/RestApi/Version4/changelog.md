# REST API v4 Change-log

## Changes

- All endpoints - Rewritten with namespaces as standalone classes.
- Coupons - Added `search` parameter.
- Orders - Added order number to schema.
- Product Reviews - Updated response links.
- Products - Added `low_in_stock` and `search` parameter.
- Reports - Updated with updated list of available reports.
- Taxes - Added `code` and `include` params.

## New endpoints

- Added `reports/products` endpoint.
- Added `reports/products/stats` endpoint.
- Added `reports/categories` endpoint.
- Added `reports/orders` endpoint.
- Added `reports/orders/stats` endpoint.
- Added `reports/performance-indicators` endpoint.
- Added `reports/revenue/stats` endpoint.
- Added `reports/stock` endpoint.
- Added `reports/stock/stats` endpoint.
- Added `reports/taxes` endpoint.
- Added `reports/taxes/stats` endpoint.
- Added `reports/variations` endpoint.
- Added `reports/coupons` endpoint.
- Added `reports/coupons/stats` endpoint.
- Added `reports/customer` endpoint.
- Added `reports/customers/stats` endpoint.
- Added `reports/downloads` endpoint.
- Added `reports/downloads/stats` endpoint.
- Added `reports/import` endpoint.

## Removed endpoints

- `reports/top_sellers`
- `reports/sales`
- `reports/customers/totals`
- `reports/orders/totals`
- `reports/coupons/totals`
- `reports/reviews/totals`
- `reports/products/totals`
