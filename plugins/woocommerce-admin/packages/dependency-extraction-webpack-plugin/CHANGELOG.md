# Unreleased

-   Add `@woocommerce/explat` to list of packages.

# 1.4.0

-   Add `@woocommerce/settings` to list of packages.

# 1.3.0

-   Remove `@woocommerce/block-settings` from internal maps and use `@woocommerce/settings` instead. This external exposes the `getSetting` API interface which encourages read only use of the global.
-   Remove explicitly scoping externals to `this`. The plugin compiler will take care of scoping the external to the correct context and `this` is not correct in some contexts consuming this package.

# 1.2.0

-   Add WooCommerce Blocks Dependencies. #6228

# 1.1.0

-   Fix: Handle irregular package names that don't conform to a pattern.

# 1.0.1

-   Fix: Avoid transpiling packaged code.

# 1.0.0

-   Released package
