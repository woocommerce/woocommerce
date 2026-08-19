/**
 * The stored-dismissal contract for the incompatible extensions notices.
 *
 * Both surfaces — the editor sidebar notice and the storefront admin banner —
 * import from here, so the keys and the comparison that decides whether a
 * stored acknowledgement still covers what is incompatible live in one place.
 *
 * Deliberately dependency-free: the storefront bundle imports this, and the
 * shared `@woocommerce/utils` barrel runs code at import time.
 */

/**
 * The key the editor sidebar notice reads and writes. It holds an array of
 * `{ [blockName]: slugs }` records, one per block.
 *
 * The storefront banner also reads this key once, to carry over dismissals made
 * before it moved to its own.
 */
export const DISMISSED_INCOMPATIBLE_EXTENSIONS_STORAGE_KEY =
	'wc-blocks_dismissed_incompatible_extensions_notices';

/**
 * The key the storefront banner reads and writes. It holds a flat array of
 * slugs. Kept distinct from the editor notice's key so the two surfaces don't
 * overwrite each other's storage.
 */
export const DISMISSED_INCOMPATIBLE_EXTENSIONS_FRONTEND_STORAGE_KEY =
	'wc-blocks_dismissed_incompatible_extensions_notices_frontend';

/**
 * Whether every item in `subset` is also present in `superset`.
 *
 * The notices stay dismissed while everything currently incompatible has
 * already been acknowledged.
 */
export const isSubsetOf = ( subset: string[], superset: string[] ): boolean =>
	subset.every( ( item ) => superset.includes( item ) );
