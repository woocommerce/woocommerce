/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

export const useIncompatibleExtensionNotice = (): [
	{ [ k: string ]: string } | null,
	string[],
	number
] => {
	interface GlobalIncompatibleExtensions {
		id: string;
		title: string;
	}

	const incompatibleExtensions: Record< string, string > = {};

	if ( getSetting( 'incompatibleExtensions' ) ) {
		getSetting< GlobalIncompatibleExtensions[] >(
			'incompatibleExtensions'
		).forEach( ( extension ) => {
			incompatibleExtensions[ extension.id ] = extension.title;
		} );
	}

	const incompatibleExtensionSlugs = Object.keys( incompatibleExtensions );
	const incompatibleExtensionCount = incompatibleExtensionSlugs.length;

	return [
		incompatibleExtensions,
		incompatibleExtensionSlugs,
		incompatibleExtensionCount,
	];
};
