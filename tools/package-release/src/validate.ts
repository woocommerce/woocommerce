/**
 * External dependencies
 */
import { existsSync, readFileSync, readdirSync } from 'fs';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT } from './const';

/**
 *	Get filepath for a given package name.
 *
 * @param {string} name package name.
 * @return {string} Absolute package path.
 */
export const getFilepathFromPackage = ( name: string ): string =>
	join( MONOREPO_ROOT, 'packages/js', name.replace( '@woocommerce', '' ) );

/**
 * Check if package is valid and can be deployed to npm.
 *
 * @param {string} name package name.
 * @return {boolean} true if the package is private.
 */
export const isValidPackage = ( name: string ): boolean => {
	const filepath = getFilepathFromPackage( name );
	const packageJsonFilepath = `${ filepath }/package.json`;
	const packageJsonExists = existsSync( packageJsonFilepath );
	if ( ! packageJsonExists ) {
		return false;
	}
	const packageJson = JSON.parse(
		readFileSync( packageJsonFilepath, 'utf8' )
	);

	if ( name !== packageJson.name ) {
		return false;
	}

	const isPrivatePackage = !! packageJson.private;

	if ( isPrivatePackage ) {
		return false;
	}

	return true;
};

/**
 * Validate package.
 *
 * @param {string}   name  package name.
 * @param {Function} error Error logging function.
 */
export const validatePackageName = (
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

export const getAllPackgeFilepaths = (): Array< string > => {
	// Package that are not meant to be released by monorepo team for whatever reason.
	const excludedPackages = [
		'@woocommerce/admin-e2e-tests',
		'@woocommerce/api',
		'@woocommerce/api-core-tests',
		'@woocommerce/e2e-core-tests',
		'@woocommerce/e2e-environment',
		'@woocommerce/e2e-utils',
	];

	const jsPackageFolders = readdirSync(
		join( MONOREPO_ROOT, 'packages/js' ),
		{
			encoding: 'utf-8',
		}
	);

	return jsPackageFolders
		.map( ( folder ) => '@woocommerce/' + folder )
		.filter( ( name ) => {
			if ( excludedPackages.includes( name ) ) {
				return false;
			}
			return isValidPackage( name );
		} )
		.map( getFilepathFromPackage );
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
		return getAllPackgeFilepaths();
	}
	return packages.split( ',' ).map( ( p ) => {
		validatePackageName( p, error );

		if ( ! isValidPackage( p ) ) {
			error(
				`${ p } is not a valid package. It may be private or incorrectly configured.`
			);
		}
		return getFilepathFromPackage( p );
	} );
};
