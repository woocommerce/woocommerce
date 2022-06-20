/**
 * External dependencies
 */
import { existsSync } from 'fs';

export const getFilepathFromPackage = ( name: string ): string =>
	'packages/js' + name.replace( '@woocommerce', '' );

export const validatePackage = (
	name: string,
	error: ( s: string ) => void
) => {
	const filepath = getFilepathFromPackage( name );

	try {
		existsSync( filepath );
	} catch ( e ) {
		error( `${ name } does not exist as a package.` );
	}
};

/**
 * Get filepaths for packages to update from CLI arguments.
 *
 * @param {Object}   args
 * @param {string}   args.packages package names separated by commas.
 * @param {Object}   flags
 * @param {boolean}  flags.all     if all packages need to be released.
 * @param {Function} error         Error logging function.
 * @return {Array<string>} Array of filepath strings.
 */
export const getFilepaths = (
	{ packages }: { [ name: string ]: string },
	{ all }: { all: boolean } & { json: boolean | undefined },
	error: ( s: string ) => void
): Array< string > => {
	if ( all ) {
		return [];
	}
	return packages.split( ',' ).map( ( p ) => {
		validatePackage( p, error );
		return getFilepathFromPackage( p );
	} );
};
