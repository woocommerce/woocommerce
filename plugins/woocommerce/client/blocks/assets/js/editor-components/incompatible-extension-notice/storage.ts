/**
 * The stored-dismissal contract shared by the editor sidebar notice and the
 * storefront admin banner: the keys, their site scoping, and the containment
 * check.
 *
 * Dependencies are kept to `@woocommerce/settings` (a webpack external both
 * surfaces already load); barrels that run code at import time stay out,
 * because the storefront bundle imports this module.
 */

/**
 * External dependencies
 */
import { CURRENT_SITE_ID, IS_MULTISITE } from '@woocommerce/settings';

/**
 * The key both surfaces shared before either of them was scoped to a site. It
 * is only read, never written, so a revert stays harmless and the value keeps
 * working for a site still on the shipped version.
 */
export const UNSCOPED_STORAGE_KEY =
	'wc-blocks_dismissed_incompatible_extensions_notices';

/**
 * `localStorage` is scoped to the origin, so sites of a subdirectory multisite
 * share it; the blog ID separates them. Blog IDs are network-local, so two
 * independent installs on one origin still share a key.
 *
 * Not the home URL: `home_url()` passes through a public filter, so it varies
 * per request (a language directory, say) without the site changing.
 *
 * `0` is the "site unknown" fallback, and not a blog ID any site can hold.
 */
const scopeToSite = ( key: string ): string => `${ key }__${ CURRENT_SITE_ID }`;

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
 * can claim them. Both surfaces shared that key, so the value can hold bare
 * slug strings (storefront) and `{ [blockName]: slugs }` records (editor) at
 * once; callers keep the entries their own surface wrote.
 *
 * Nothing is migrated on a multisite: the value names no site, so there is no
 * telling whose dismissal it is, and warning an admin once more is safer than
 * hiding a live warning behind another site's dismissal.
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
		if ( Array.isArray( parsed ) ) {
			return parsed;
		}
	} catch {
		// Unparseable, handled the same way as a shape we don't recognise.
	}

	// A value we can't read is not a dismissal we can honour; the log gives a
	// merchant who reports the notice coming back something to point at.
	// eslint-disable-next-line no-console
	console.error(
		`Value for key '${ UNSCOPED_STORAGE_KEY }' could not be carried over from localStorage because it can't be read as a list of dismissals.`
	);
	return [];
};

/**
 * What a notice should hand `useLocalStorageState` as its initial value.
 *
 * That hook falls back to the initial value both when `key` holds nothing and
 * when it holds something unparseable, and cannot tell the two apart. Only
 * absence may open the migration: seeding a corrupt value from the pre-scoping
 * data would revive a dismissal the merchant has since replaced and hide a
 * warning that is currently owed.
 */
export const readInitialDismissals = < T >(
	key: string,
	migrate: () => T[]
): T[] => {
	try {
		// Deliberately `=== null`: an empty string is stored data we failed to
		// write, not an absent key, and must not open the migration path.
		return window.localStorage.getItem( key ) === null ? migrate() : [];
	} catch {
		// Storage can be unavailable altogether (private browsing, blocked
		// cookies). Nothing is stored, and nothing can be migrated into it.
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
