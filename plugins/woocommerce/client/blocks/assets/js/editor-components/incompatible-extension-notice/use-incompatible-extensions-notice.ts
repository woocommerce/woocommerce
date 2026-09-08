/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * The extensions this site currently declares incompatible.
 *
 * The fourth element says whether the list was delivered at all, which an empty
 * list on its own cannot. `incompatibleExtensions` is registered by the Cart and
 * Checkout blocks rather than by core data, so a `woocommerce_shared_settings`
 * callback that trims the settings drops it, and both blocks skip registering it
 * when `get_plugins()` is not loaded. Callers that act on an extension having
 * *stopped* being incompatible need to tell "we don't know" apart from "none".
 */
export const useIncompatibleExtensionNotice = (): [
	{ [ k: string ]: string },
	string[],
	number,
	boolean,
] => {
	interface GlobalIncompatibleExtensions {
		id: string;
		title: string;
	}

	const declared = getSetting< GlobalIncompatibleExtensions[] | undefined >(
		'incompatibleExtensions',
		undefined
	);
	const areIncompatibleExtensionsKnown = Array.isArray( declared );

	const incompatibleExtensions: Record< string, string > = {};

	if ( areIncompatibleExtensionsKnown ) {
		declared.forEach( ( extension ) => {
			incompatibleExtensions[ extension.id ] = extension.title;
		} );
	}

	const incompatibleExtensionSlugs = Object.keys( incompatibleExtensions );
	const incompatibleExtensionCount = incompatibleExtensionSlugs.length;

	return [
		incompatibleExtensions,
		incompatibleExtensionSlugs,
		incompatibleExtensionCount,
		areIncompatibleExtensionsKnown,
	];
};
