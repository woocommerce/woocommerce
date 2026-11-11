# Order and cart totals recalculation

**Source:**

- #63744 / #65535 (fixed end-prices, non-base location prices) reverted by #67142.
- #61091: reverted coupon recalculation on order save.
- #69441: VAT-exempt coupon discounts.
- #66342 (10.9.4): `is_vat_exempt` in block checkout.
- #46642 (8.8.2): third-party coupon extensions.

**What broke:** Changes to how or when totals, tax, and coupons are recalculated changed amounts on existing orders, especially with tax-inclusive prices, non-base locations, and VAT exemption. Several were reverted.

**Pattern to catch:** Changes to `calculate_totals`, `calculate_taxes`, `recalculate_coupons`, rounding, fee or tax-class handling, or when recalculation runs (on save, in admin, in the Store API).

**How to confirm:** Run a mixed order through before and after: tax-inclusive prices, non-base location, VAT-exempt customer, a fee, a percent coupon, and an admin edit. Check existing orders are not recalculated on save.

**Don't flag when:** New-order-only paths behind a setting that defaults off.
