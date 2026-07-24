# WooCommerce Blocks public API packages

This directory contains JavaScript package entry points that WooCommerce
extensions may consume. Stable exports from each package root are public
contracts and require backward-compatibility handling.

## API contract

- Import packages through their `@woocommerce/*` package root.
- Treat stable root exports as public API.
- Add new APIs instead of renaming or removing existing APIs.
- Deprecate an existing API before removing it.
- Treat exports prefixed with `__experimental` or `__unstable` as unstable.
- Treat deep imports and files not exported from a package root as internal
  unless they are documented separately.

The `"private": true` field in the Blocks `package.json` only prevents npm
publication. It does not make these browser APIs private.

## Packages

| Package | Script handle | Browser global |
| --- | --- | --- |
| `@woocommerce/block-data` | `wc-blocks-data-store` | `wc.wcBlocksData` |
| `@woocommerce/blocks-checkout` | `wc-blocks-checkout` | `wc.blocksCheckout` |
| `@woocommerce/blocks-checkout-events` | `wc-blocks-checkout-events` | `wc.blocksCheckoutEvents` |
| `@woocommerce/blocks-components` | `wc-blocks-components` | `wc.blocksComponents` |
| `@woocommerce/blocks-registry` | `wc-blocks-registry` | `wc.wcBlocksRegistry` |
| `@woocommerce/price-format` | `wc-price-format` | `wc.priceFormat` |
| `@woocommerce/settings` | `wc-settings` | `wc.wcSettings` |
| `@woocommerce/shared-context` | `wc-blocks-shared-context` | `wc.wcBlocksSharedContext` |
| `@woocommerce/shared-hocs` | `wc-blocks-shared-hocs` | `wc.wcBlocksSharedHocs` |
| `@woocommerce/types` | `wc-types` | `wc.wcTypes` |

The independently published `@woocommerce/data` and `@woocommerce/sanitize`
packages remain in the monorepo-level `packages/js` directory.

Being externalized is not, by itself, evidence that a package is public. See
the [internal packages](../internal/README.md) for runtime-only packages that
also produce separate scripts.
