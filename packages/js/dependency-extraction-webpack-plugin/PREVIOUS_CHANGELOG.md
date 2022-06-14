# 2.0.0

-   Update all js packages with minor/patch version changes. #8392

## Breaking changes

    -   Updated to webpack 5 compatible #8476
    -   Will need to change webpack config output.libraryTarget from 'this' to 'window' #8476

# 1.6.0

-   Add new `bundledPackages` option to bundle in specific packages.

# 1.5.0

-   Add `@woocommerce/explat` to list of packages.
-   Add `@woocommerce/experimental` to list of packages.

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
