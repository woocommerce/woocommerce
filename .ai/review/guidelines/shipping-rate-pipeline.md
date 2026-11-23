# Shipping rate pipeline order and defaults

**Source:**

- #68707 ran `woocommerce_package_rates` before the free-shipping hide; reverted by #68854.
- #57596: `price_decimals` became `false`.
- #58804 (9.9.4): empty rate cost.
- #57674 (9.8.5): rates hidden when optional fields were empty.
- #61628 (10.3.6): `needs_shipping` with a deleted product.
- #66613: shipping-disabled stores; #57176.

**What broke:** Reordering core steps around the rate filters, or changing rate defaults, changed which rates shipping extensions saw and which rates shoppers got.

**Pattern to catch:** Reordering core steps around rate filters, changing rate cost defaults or types, or changing `needs_shipping` or package building.

**How to confirm:** List what `woocommerce_package_rates` callbacks expect (does free shipping still exist when they run?). Walk shipping-disabled, local pickup, and free-shipping-plus-flat-rate setups.

**Don't flag when:** Display-only label changes.
