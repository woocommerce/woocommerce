# Experimental Products App

This package is a prototype for the work tracked in [Epic: Improve Product Catalog Management Experience](https://github.com/woocommerce/woocommerce/issues/64414).

It is used to explore a faster, more scalable **All Products** experience in WooCommerce, especially for stores with large catalogs.

Current areas of exploration:

-   A more flexible table-based product view
-   Better filtering, sorting, and scanning
-   Inline handling of product variations
-   Faster quick edit and bulk edit flows
-   A clearer extension surface for integrations

## Try It Quickly

You can try the experimental products app in [WordPress Playground](https://playground.wordpress.net/?blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2Fwoocommerce%2Fwoocommerce%2F8588e7cc98f16c51527136ff0600e74967db77a3%2Fpackages%2Fjs%2Fexperimental-products-app%2Fblueprint.json&random=pf6owa52dsr).

The shared Blueprint:

-   Installs WooCommerce nightly, Gutenberg, and WooCommerce Beta Tester
-   Enables the required feature flags
-   Imports WooCommerce sample products from CSV
-   Opens the experimental products dashboard directly
