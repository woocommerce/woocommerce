/**
 * External dependencies
 */
import { existsSync, readFileSync } from 'fs';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT } from './const';

export const getFilepathFromPackage = ( name: string ): string =>
	join( MONOREPO_ROOT, 'packages/js', name.replace( '@woocommerce', '' ) );

/**
 * Check if package is private as we don't want to release private packages.
 *
 * @param {string} name package name.
 * @return {boolean} true if the package is private.
 */
export const isPrivatePackage = ( name: string ): boolean => {
	const filepath = getFilepathFromPackage( name );
	const packageJsonFilepath = `./${ filepath }/package.json`;
	const exists = existsSync( packageJsonFilepath );
	if ( ! exists ) {
		return false;
	}
	const packageJson = JSON.parse(
		readFileSync( packageJsonFilepath, 'utf8' )
	);
	return !! packageJson.private;
};

/**
 * Validate package.
 *
 * @param {string}   name  package name.
 * @param {Function} error Error logging function.
 */
export const validatePackage = (
	name: string,
	error: ( s: string ) => void
) => {
	const filepath = getFilepathFromPackage( name );

	try {
		const exists = existsSync( filepath );
		if ( ! exists ) {
			throw new Error();
		}
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

		if ( isPrivatePackage( p ) ) {
			error( `${ p } is a private package, it should not be released.` );
		}
		return getFilepathFromPackage( p );
	} );
};
