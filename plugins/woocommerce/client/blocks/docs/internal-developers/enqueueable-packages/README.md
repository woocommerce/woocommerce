# WooCommerce Blocks Editor Assets

WooCommerce Blocks uses shared editor assets instead of one editor script and stylesheet per block. This page documents which assets are loaded for WooCommerce blocks in the editor, which packages remain external dependencies of `wc-block-library`, and which handles are still registered for extensions that enqueue them manually.

Not every registered handle is enqueued by default. Handles listed as compatibility handles are available to avoid breaking existing consumers, but new editor code should usually rely on `wc-block-library` and its declared dependencies.

## Default editor assets

WooCommerce block types use these shared handles in the block editor:

| Handle | Type | What it contains |
| --- | --- | --- |
| `wc-block-library` | Script | The shared editor bundle for WooCommerce blocks. It is built from `entries.main.wc-block-library`, which includes `assets/js/index.js` and the block editor entrypoints. |
| `wc-block-library-style` | Stylesheet | The shared editor stylesheet for WooCommerce blocks. Block metadata and PHP block registration point editor styles to this handle. |

When `wc-block-library` is loaded, its generated asset file declares the package handles that still need to be shared as separate scripts:

| Package import | Script handle | Global | Why it stays external |
| --- | --- | --- | --- |
| `@woocommerce/block-data` | `wc-blocks-data-store` | `wc.wcBlocksData` | Registers WooCommerce Blocks data stores once and shares them with editor extensions and checkout APIs. |
| `@woocommerce/blocks-checkout` | `wc-blocks-checkout` | `wc.blocksCheckout` | Shares checkout filters, slot/fill APIs, and checkout block registry helpers with extensions. |
| `@woocommerce/blocks-checkout-events` | `wc-blocks-checkout-events` | `wc.blocksCheckoutEvents` | Shares the checkout lifecycle event emitter between subscribers and emitters. |
| `@woocommerce/blocks-registry` | `wc-blocks-registry` | `wc.wcBlocksRegistry` | Keeps block, payment method, and product collection registrations in shared registries. |
| `@woocommerce/entities` | `wc-entities` | `wc.wcEntities` | Shares WooCommerce entity registration in the editor. |
| `@woocommerce/price-format` | `wc-price-format` | `wc.priceFormat` | Shares price and currency formatting across editor, frontend, and extension code. |

This list comes from `editorExternalPackages` in `webpack-helpers.js`. Imports for other mapped WooCommerce packages are bundled into `wc-block-library` in the editor build.

## Compatibility handles

The packages below resolve as bundled editor imports when they are used by WooCommerce editor code, but their script handles remain registered so existing extensions and non-editor scripts can still enqueue or declare them directly.

| Package import | Script handle | Global | Registration owner | Notes |
| --- | --- | --- | --- | --- |
| `@woocommerce/blocks-components` | `wc-blocks-components` | `wc.blocksComponents` | `AssetsController::register_deprecated_package_scripts()` | Deprecated editor dependency target kept for backward compatibility. |
| `@woocommerce/shared-context` | `wc-blocks-shared-context` | `wc.wcBlocksSharedContext` | `AssetsController::register_deprecated_package_scripts()` | Deprecated editor dependency target kept for backward compatibility. |
| `@woocommerce/shared-hocs` | `wc-blocks-shared-hocs` | `wc.wcBlocksSharedHocs` | `AssetsController::register_deprecated_package_scripts()` | Deprecated editor dependency target kept for backward compatibility. |
| `@woocommerce/settings` | `wc-settings` | `wc.wcSettings` | `AssetDataRegistry::register_data_script()` | Still used when scripts need WooCommerce settings data; inline data is only printed when the handle is enqueued. |
| `@woocommerce/types` | `wc-types` | `wc.wcTypes` | `AssetsController::register_assets()` | Runtime type helpers remain available as a standalone handle. |
| `@woocommerce/sanitize` | `wc-sanitize` | `wc.sanitize` | `AssetsController::register_assets()` and WooCommerce Admin assets | Sanitization helpers remain available as a standalone handle. |
| `@woocommerce/data` | `wc-store-data` | `wc.data` | WooCommerce Admin assets | Registered by WooCommerce Admin, not by the Blocks core webpack entries. |

`wc-blocks-middleware` is also registered by Blocks and is loaded as a dependency of `wc-blocks-data-store`. It does not map to a public `@woocommerce/*` package import.
