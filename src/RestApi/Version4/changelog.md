# REST API v4 Change-log

## Changes

- All endpoints - Rewritten with namespaces as standalone classes.
- Coupons - Added `search` parameter.
- Orders - Added order number to schema.
- Product Reviews - Updated response links.
- Products - Added `low_in_stock` and `search` parameter.
- Product Variations - Added `search` parameter.
- Product Variations - Added `name`, `type`, `parent_id` to schema.
- Reports - Updated with updated list of available reports.
- Taxes - Added `code` and `include` params.

## New endpoints

- `reports/products`
- `reports/products/stats`
- `reports/categories`
- `reports/orders`
- `reports/orders/stats`
- `reports/performance-indicators`
- `reports/revenue/stats`
- `reports/stock`
- `reports/stock/stats`
- `reports/taxes`
- `reports/taxes/stats`
- `reports/variations`
- `reports/coupons`
- `reports/coupons/stats`
- `reports/customer`
- `reports/customers/stats`
- `reports/downloads`
- `reports/downloads/stats`
- `reports/import`
- `data/download-ips`
- `leaderboards`

## Removed endpoints

- `reports/top_sellers`
- `reports/sales`
- `reports/customers/totals`
- `reports/orders/totals`
- `reports/coupons/totals`
- `reports/reviews/totals`
- `reports/products/totals`
