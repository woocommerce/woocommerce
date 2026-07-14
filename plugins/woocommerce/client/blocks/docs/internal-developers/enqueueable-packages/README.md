# WooCommerce Blocks editor assets

WooCommerce includes an experimental configuration that unifies block editor scripts and styles into shared assets. The experiment is disabled by default so existing per-block assets and handles continue to work unchanged.

Enable **Unified block editor assets** under **WooCommerce > Settings > Advanced > Features** to test the shared configuration. The setting is stored in `woocommerce_feature_block_editor_unified_assets_enabled` and takes effect on the next request.

## Default editor assets

When the experiment is disabled, WooCommerce registers and loads its existing editor assets:

- Per-block editor scripts such as `wc-cart-block` and `wc-checkout-block`.
- Per-block editor styles declared by block metadata.
- The real `wc-blocks-vendors` and `wc-blocks` bundles.
- The existing `wc-blocks-editor-style` stylesheet.

This is the backward-compatible default.

## Unified editor assets

When the experiment is enabled, WooCommerce block types use these shared handles:

| Handle | Type | What it contains |
| --- | --- | --- |
| `wc-block-library` | Script | WooCommerce block editor entrypoints and packages that are safe to bundle together. |
| `wc-block-library-style` | Stylesheet | Editor styles for blocks shipped by WooCommerce. Third-party block editor styles are preserved. |

The generated asset file for `wc-block-library` declares packages that remain separate scripts:

| Package import | Script handle | Global | Why it stays external |
| --- | --- | --- | --- |
| `@woocommerce/block-data` | `wc-blocks-data-store` | `wc.wcBlocksData` | Registers WooCommerce Blocks data stores once and shares them with editor extensions and checkout APIs. |
| `@woocommerce/blocks-checkout` | `wc-blocks-checkout` | `wc.blocksCheckout` | Shares checkout filters, slot/fill APIs, and checkout registry helpers with extensions. |
| `@woocommerce/blocks-checkout-events` | `wc-blocks-checkout-events` | `wc.blocksCheckoutEvents` | Shares the checkout lifecycle event emitter between subscribers and emitters. |
| `@woocommerce/blocks-registry` | `wc-blocks-registry` | `wc.wcBlocksRegistry` | Keeps block, payment method, and product collection registrations in shared registries. |
| `@woocommerce/data` | `wc-store-data` | `wc.data` | Shares WooCommerce Admin data stores without re-registering them from the editor bundle. |
| `@woocommerce/entities` | `wc-entities` | `wc.wcEntities` | Shares WooCommerce entity registration in the editor. |
| `@woocommerce/price-format` | `wc-price-format` | `wc.priceFormat` | Shares price and currency formatting across editor, frontend, and extension code. |

This list comes from `editorExternalPackages` in `webpack-helpers.js`. Other WooCommerce package imports are bundled into `wc-block-library` only in the unified editor build.

## Compatibility handles

The following package handles remain real standalone bundles in both configurations:

| Package import | Script handle | Global |
| --- | --- | --- |
| `@woocommerce/blocks-components` | `wc-blocks-components` | `wc.blocksComponents` |
| `@woocommerce/shared-context` | `wc-blocks-shared-context` | `wc.wcBlocksSharedContext` |
| `@woocommerce/shared-hocs` | `wc-blocks-shared-hocs` | `wc.wcBlocksSharedHocs` |
| `@woocommerce/settings` | `wc-settings` | `wc.wcSettings` |
| `@woocommerce/types` | `wc-types` | `wc.wcTypes` |
| `@woocommerce/sanitize` | `wc-sanitize` | `wc.sanitize` |

When unified assets are enabled, `wc-blocks-vendors` and `wc-blocks` are registered as contentless compatibility placeholders and emit a console warning. Extensions can still resolve those dependencies, but must declare the specific package handles for any `window.wc.*` globals they consume. When unified assets are disabled, both handles continue to load their existing bundles.

`wc-blocks-middleware` is also registered by Blocks and loaded as a dependency of `wc-blocks-data-store`. It does not map to a public `@woocommerce/*` package import.
