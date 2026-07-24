# WooCommerce Blocks editor assets

WooCommerce includes an experimental configuration that replaces its per-block editor scripts and styles with shared editor assets. The experiment is disabled by default, so existing per-block assets and handles continue to work unchanged.

Enable **Unified block editor assets** under **WooCommerce > Settings > Advanced > Features** to test the shared configuration. The feature flag is `block_editor_unified_assets`, its setting is stored in `woocommerce_feature_block_editor_unified_assets_enabled`, and changes take effect on the next request.

## Asset configurations

### Default

When the experiment is disabled, WooCommerce uses:

- Per-block scripts such as `wc-cart-block` and `wc-checkout-block`.
- Per-block styles declared by block metadata.
- The `wc-blocks-vendors` and `wc-blocks` bundles.
- The `wc-blocks-editor-style` stylesheet.

### Unified

When the experiment is enabled, WooCommerce block types use:

| Handle | Type | What it contains |
| --- | --- | --- |
| `wc-block-library` | Script | WooCommerce block editor entry points and internal package imports. |
| `wc-block-library-style` | Stylesheet | Editor styles for blocks shipped by WooCommerce. Styles registered by third-party blocks remain separate. |

These assets replace WooCommerce's per-block editor assets. They do not combine every script loaded by the editor: shared WordPress dependencies and the enqueueable WooCommerce packages below remain external.

## External package dependencies

The generated asset file for `wc-block-library` declares packages that remain separate scripts:

| Package import | Script handle | Global | Why it stays external |
| --- | --- | --- | --- |
| `@woocommerce/block-data` | `wc-blocks-data-store` | `wc.wcBlocksData` | Registers WooCommerce Blocks data stores once and shares them with editor extensions and checkout APIs. |
| `@woocommerce/blocks-checkout` | `wc-blocks-checkout` | `wc.blocksCheckout` | Shares checkout filters, slot/fill APIs, and checkout registry helpers with extensions. |
| `@woocommerce/blocks-checkout-events` | `wc-blocks-checkout-events` | `wc.blocksCheckoutEvents` | Shares the checkout lifecycle event emitter between subscribers and emitters. |
| `@woocommerce/blocks-components` | `wc-blocks-components` | `wc.blocksComponents` | Shares components used by WooCommerce blocks and extensions. |
| `@woocommerce/blocks-registry` | `wc-blocks-registry` | `wc.wcBlocksRegistry` | Keeps block, payment method, and product collection registrations in shared registries. |
| `@woocommerce/data` | `wc-store-data` | `wc.data` | Shares WooCommerce Admin data stores without re-registering them from the editor bundle. |
| `@woocommerce/entities` | `wc-entities` | `wc.wcEntities` | Shares WooCommerce entity registration in the editor. |
| `@woocommerce/price-format` | `wc-price-format` | `wc.priceFormat` | Shares price and currency formatting across editor, frontend, and extension code. |
| `@woocommerce/sanitize` | `wc-sanitize` | `wc.sanitize` | Shares HTML sanitization with the Components and Checkout package bundles. |
| `@woocommerce/settings` | `wc-settings` | `wc.wcSettings` | Shares one runtime settings object across the editor and package bundles. |
| `@woocommerce/shared-context` | `wc-blocks-shared-context` | `wc.wcBlocksSharedContext` | Shares React contexts across separately built scripts. |
| `@woocommerce/shared-hocs` | `wc-blocks-shared-hocs` | `wc.wcBlocksSharedHocs` | Shares higher-order components across separately built scripts. |
| `@woocommerce/types` | `wc-types` | `wc.wcTypes` | Shares runtime type guards used by the external package bundles. |

This list is defined by `editorExternalPackages` in `bin/webpack-config-block-editor-unified-assets.js`. Other `@woocommerce/*` imports are bundled into `wc-block-library` for the unified editor build. Their standalone handles may still be registered for frontend assets or third-party scripts.

`wc-blocks-middleware` is registered separately and loaded by both `wc-block-library` and `wc-blocks-data-store`. It does not map to an enqueueable `@woocommerce/*` package.

### Cart and Checkout frontend chunks

The `wc-blocks-checkout` and `wc-blocks-components` package bundles are built with the Cart and Checkout frontend configuration. Their generated asset metadata depends on:

- `wc-cart-checkout-base`
- `wc-cart-checkout-vendors`

WordPress therefore loads `wc-cart-checkout-base-frontend.js` and `wc-cart-checkout-vendors-frontend.js` when the unified editor needs either package. The `-frontend` suffix identifies the build configuration that produced the files; it does not restrict them to storefront requests. Removing these dependencies without changing how the packages are built would leave required modules unavailable.
