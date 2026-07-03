# Enqueueable WooCommerce Blocks Packages

WooCommerce Blocks exposes a small set of JavaScript packages as WordPress script handles. These handles let WooCommerce scripts, extensions, and third-party code depend on shared Blocks APIs without copying the package into every bundle.

The main sources of truth are:

- `plugins/woocommerce/client/blocks/bin/webpack-helpers.js` for import-to-global and import-to-handle mapping.
- `plugins/woocommerce/client/blocks/bin/webpack-entries.js` for Blocks package entry points emitted by webpack.
- `plugins/woocommerce/src/Blocks/AssetsController.php` for script registration.
- `plugins/woocommerce/src/Blocks/DependencyDetection.php` for runtime warnings when scripts use `window.wc.*` globals without declaring the matching handle.

## Block library bundling

The `@woocommerce/block-library` editor build intentionally bundles most WooCommerce package imports. That keeps the editor from enqueueing a long list of package scripts.

`@woocommerce/entities` is the exception: it remains external in the editor build and is loaded through the `wc-entities` handle.

The standalone handles below must still be registered and emitted. They are public dependency targets for non-editor scripts and third-party consumers that explicitly enqueue or declare them.

## Source locations

Package source location communicates the intended surface:

- `assets/js/blocks-registry`, `assets/js/data`, `assets/js/entities`, `assets/js/middleware`, `assets/js/shared`, and `assets/js/types` contain Blocks package surfaces that are emitted as standalone handles and/or bundled into `@woocommerce/block-library`.
- `packages/*` contains packages that already use the standalone package layout, such as `checkout`, `components`, and `prices`.
- `assets/js/blocks/*` contains concrete block implementation code.

## Package handles

| Package import | Script handle | Global | Purpose |
| --- | --- | --- | --- |
| `@woocommerce/block-data` | `wc-blocks-data-store` | `wc.wcBlocksData` | Blocks data stores for cart, checkout, collections, payment, query state, schema, store notices, and validation. Depends on `wc-blocks-middleware`. |
| `@woocommerce/blocks-checkout` | `wc-blocks-checkout` | `wc.blocksCheckout` | Checkout and cart extension APIs, including checkout components, hooks, utilities, slot/fill support, checkout filter registry, and checkout block registry helpers. |
| `@woocommerce/blocks-checkout-events` | `wc-blocks-checkout-events` | `wc.blocksCheckoutEvents` | Checkout lifecycle event emitter used for validation and checkout result handling, such as checkout validation, success, and failure events. |
| `@woocommerce/blocks-components` | `wc-blocks-components` | `wc.blocksComponents` | Shared Blocks UI components such as buttons, form controls, chips, notices, totals, text inputs, labels, panels, and spinners. |
| `@woocommerce/blocks-registry` | `wc-blocks-registry` | `wc.wcBlocksRegistry` | Registries for payment methods, block components, and product collection variations/extensions. |
| `@woocommerce/data` | `wc-store-data` | `wc.data` | WooCommerce admin data stores, store descriptors, hydration helpers, REST constants, and shared admin data types. This package is registered by WooCommerce Admin assets rather than the Blocks core webpack entries. |
| `@woocommerce/entities` | `wc-entities` | `wc.wcEntities` | Entity registration and hooks for WooCommerce entities, currently including product entities and conditional settings entities. |
| `@woocommerce/price-format` | `wc-price-format` | `wc.priceFormat` | Price and currency formatting utilities used by cart, checkout, Mini Cart, Product Filters, product elements, and extensions. |
| `@woocommerce/sanitize` | `wc-sanitize` | `wc.sanitize` | HTML sanitization helpers and Trusted Types policy utilities. This package is registered from the WooCommerce admin sanitize build. |
| `@woocommerce/settings` | `wc-settings` | `wc.wcSettings` | Shared settings access, default constants, default field definitions, and block settings initialization through `allSettings`. |
| `@woocommerce/shared-context` | `wc-blocks-shared-context` | `wc.wcBlocksSharedContext` | Shared React contexts for custom data, inner block layout, and product data. |
| `@woocommerce/shared-hocs` | `wc-blocks-shared-hocs` | `wc.wcBlocksSharedHocs` | Shared higher-order components for product data context and filtered attributes. |
| `@woocommerce/types` | `wc-types` | `wc.wcTypes` | Shared type definitions and runtime type guards used by Blocks packages and extension-facing APIs. |

## Supporting handles

These handles are registered alongside the package handles but do not map directly to a public `@woocommerce/*` package import.

| Script handle | Global | Purpose |
| --- | --- | --- |
| `wc-blocks-middleware` | None | Installs Store API request middleware, including nonce handling, locale removal, and cart hash behavior. `wc-blocks-data-store` depends on it. |
| `wc-schema-parser` | `window.schemaParser` | AJV-based JSON schema parser used by checkout validation and schema-aware block behavior. |

## Bundled-only imports

Some WooCommerce imports are intentionally bundled and do not have a Blocks package handle.

| Package import | Reason |
| --- | --- |
| `@woocommerce/tracks` | Tracking helpers are bundled into consumers and are not exposed as a Blocks package handle. |

## Adding or changing a package handle

When adding, removing, or renaming one of these packages, update the full chain:

1. Update `wcDepMap` and `wcHandleMap` in `bin/webpack-helpers.js`.
2. Add or update the webpack entry in `bin/webpack-entries.js` when Blocks emits the standalone script.
3. Register the handle in `src/Blocks/AssetsController.php`, or confirm another asset controller already registers it.
4. Update dependency detection if the package exposes a `window.wc.*` global that third-party scripts can consume.
5. Update this README.

Changing the public API of these packages can affect third-party extensions that declare the matching script handle, even when `@woocommerce/block-library` bundles the same package internally.
