/**
 * Transitional re-export shim.
 *
 * `woocommerce/products` no longer registers a store, and carries no
 * getter, setter, or `findProduct` at runtime — every absorbed member
 * (`baseProductInContext`, `productVariationInContext`, `productInContext`,
 * `findProduct`) now lives on the unified `woocommerce` root module
 * (`./index`), reached through `state.itemInContext`/`state.findItem`. The
 * setter is dropped outright — an assignable envelope member has no
 * absorbed equivalent.
 *
 * This file exists only so a not-yet-migrated consumer's
 * `import type … from '.../products'` — and the side-effect
 * `import '.../products'` some of them still carry — keeps resolving; it is
 * deleted once every consumer has re-pointed to `./index`.
 */

/**
 * Internal dependencies
 */
import type { WooCommerce } from './index';

/**
 * @deprecated Transitional alias of the unified `WooCommerce` store type.
 * Migrate to `WooCommerce`/`Envelope` (from `./index`) directly; this alias
 * is removed with the rest of this file.
 */
export type ProductsStore = WooCommerce;

/**
 * @deprecated Transitional alias of `WooCommerce['state']`. Migrate to it
 * directly; this alias is removed with the rest of this file.
 */
export type ProductsStoreState = WooCommerce[ 'state' ];
