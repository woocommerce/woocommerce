# Experimental Products App

This package is a prototype for the work tracked in [Epic: Improve Product Catalog Management Experience](https://github.com/woocommerce/woocommerce/issues/64414).

It is used to explore a faster, more scalable **All Products** experience in WooCommerce, especially for stores with large catalogs.

Current areas of exploration:

-   A more flexible table-based product view
-   Better filtering, sorting, and scanning
-   Inline handling of product variations
-   Faster quick edit and [bulk edit flows](docs/bulk-editing.md)
-   A clearer extension surface for integrations

## Try It Quickly

You can try the experimental products app in [WordPress Playground](https://playground.wordpress.net/?blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2Fwoocommerce%2Fwoocommerce%2F8588e7cc98f16c51527136ff0600e74967db77a3%2Fpackages%2Fjs%2Fexperimental-products-app%2Fblueprint.json&random=pf6owa52dsr).

The shared Blueprint:

-   Installs WooCommerce nightly, Gutenberg, and WooCommerce Beta Tester
-   Enables the required feature flags
-   Imports WooCommerce sample products from CSV
-   Opens the experimental products dashboard directly

## DataViews Dependency

This package currently uses a custom build of `@wordpress/dataviews` from [WordPress/gutenberg#77905](https://github.com/WordPress/gutenberg/pull/77905). The package is installed from the tarball referenced in `package.json` so the prototype can use the new table tree hierarchy API before it is available in a published WordPress package release.

The custom build is expected to be temporary. Once the DataViews hierarchy changes are published in the regular `@wordpress/dataviews` package, replace the tarball dependency with the published version and refresh `pnpm-lock.yaml`.
