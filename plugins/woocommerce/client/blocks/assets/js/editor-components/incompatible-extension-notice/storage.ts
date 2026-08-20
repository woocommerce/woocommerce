/**
 * The stored-dismissal contract for the incompatible extensions notices.
 *
 * Both surfaces — the editor sidebar notice and the storefront admin banner —
 * import from here, so the keys, the site they are scoped to, and the comparison
 * that decides whether a stored acknowledgement still covers what is
 * incompatible live in one place.
 *
 * Kept to `@woocommerce/settings`, which is a webpack external both surfaces
 * already load. Barrels that run code at import time (`@woocommerce/utils`) stay
 * out, because the storefront bundle imports this module.
 */

/**
 * External dependencies
 */
import { HOME_URL, IS_MULTISITE } from '@woocommerce/settings';

/**
 * The key both surfaces shared before either of them was scoped to a site. It
 * is only read, never written, so a revert stays harmless and the value keeps
 * working for a site still on the shipped version.
 */
export const UNSCOPED_STORAGE_KEY =
	'wc-blocks_dismissed_incompatible_extensions_notices';

/**
 * `localStorage` is scoped to the origin, not to the path, so every site of a
 * subdirectory multisite reads and writes the same keys. Appending the site's
 * own home URL gives each of them their own storage, so one site's dismissal
 * can no longer hide another site's warning.
 */
const scopeToSite = ( key: string ): string => `${ key }__${ HOME_URL }`;

/**
 * The key the editor sidebar notice reads and writes. It holds an array of
 * `{ [blockName]: slugs }` records, one per block.
 */
export const getEditorStorageKey = (): string =>
	scopeToSite( UNSCOPED_STORAGE_KEY );

/**
 * The key the storefront banner reads and writes. It holds a flat array of
 * slugs. Kept distinct from the editor notice's key so the two surfaces don't
 * overwrite each other's storage.
 */
export const getFrontendStorageKey = (): string =>
	scopeToSite( `${ UNSCOPED_STORAGE_KEY }_frontend` );

/**
 * The dismissals stored before the keys were scoped to a site, when this site
 * can claim them.
 *
 * Both surfaces shared that one key, so its value can hold either shape, and on
 * a real site usually holds both: the storefront wrote bare slug strings, while
 * the editor appends `{ [blockName]: slugs }` records without discarding what it
 * finds. Callers keep the entries their own surface wrote.
 *
 * Nothing is migrated on a multisite. The value names no site and every site on
 * the origin sees it, so there is no way to tell whose dismissal it is. Warning
 * an admin one more time is safer than hiding a live warning behind a dismissal
 * that was made somewhere else.
 */
export const readDismissalsFromBeforeScoping = (): unknown[] => {
	if ( IS_MULTISITE ) {
		return [];
	}

	try {
		const stored = window.localStorage.getItem( UNSCOPED_STORAGE_KEY );
		if ( ! stored ) {
			return [];
		}
		const parsed = JSON.parse( stored );
		return Array.isArray( parsed ) ? parsed : [];
	} catch {
		// A value we can't read is not a dismissal we can honour; showing the
		// notice is the safe fallback.
		return [];
	}
};

/**
 * Whether every item in `subset` is also present in `superset`.
 *
 * The notices stay dismissed while everything currently incompatible has
 * already been acknowledged.
 */
export const isSubsetOf = ( subset: string[], superset: string[] ): boolean =>
	subset.every( ( item ) => superset.includes( item ) );
