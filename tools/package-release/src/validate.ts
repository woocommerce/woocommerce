/**
 * External dependencies
 */
import { existsSync, readFileSync, readdirSync } from 'fs';
import { join } from 'path';
import { execSync } from 'child_process';
import { gt as greaterVersionThan } from 'semver';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT, excludedPackages } from './const';

/**
 * Get filepath for a given package name.
 *
 * @param {string} name package name.
 * @return {string} Absolute path for the package.
 */
export const getFilepathFromPackageName = ( name: string ): string =>
	join( MONOREPO_ROOT, 'packages/js', name.replace( '@woocommerce', '' ) );

/**
 * Get a package's package.json file in JSON format.
 *
 * @param {string} name package name.
 * @return {Object|false} JSON object or false if it fails.
 */
export const getPackageJson = ( name: string ) => {
	const filepath = getFilepathFromPackageName( name );
	const packageJsonFilepath = `${ filepath }/package.json`;
	const packageJsonExists = existsSync( packageJsonFilepath );
	if ( ! packageJsonExists ) {
		return false;
	}

	return JSON.parse( readFileSync( packageJsonFilepath, 'utf8' ) );
};

/**
 * Check if package is valid and can be deployed to NPM.
 *
 * @param {string} name package name.
 * @return {boolean} true if the package is private.
 */
export const isValidPackage = ( name: string ): boolean => {
	const packageJson = getPackageJson( name );

	if ( ! packageJson ) {
		return false;
	}

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
 * Validate package name.
 *
 * @param {string}   name  package name.
 * @param {Function} error Error logging function.
 */
export const validatePackageName = (
	name: string,
	error: ( s: string ) => void
) => {
	const filepath = getFilepathFromPackageName( name );

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
 * Get all releaseable package names.
 *
 * @return {Array<string>} Package names.
 */
export const getAllPackges = (): Array< string > => {
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
		} );
};

/**
 * Validate a package.
 *
 * @param {string}   name  package name.
 * @param {Function} error Error logging function.
 */
export const validatePackage = (
	name: string,
	error: ( s: string ) => void
) => {
	validatePackageName( name, error );

	if ( ! isValidPackage( name ) ) {
		error(
			`${ name } is not a valid package. It may be private or incorrectly configured.`
		);
	}
};

/**
 * Determine if an update is valid by comparing version numbers.
 *
 * @param {string}  name           package name.
 * @param {boolean} initialRelease if package has not been released yet.
 * @return {boolean} If an update is valid.
 */
export const isValidUpdate = (
	name: string,
	initialRelease: boolean
): boolean => {
	const packageJson = getPackageJson( name );

	if ( ! packageJson ) {
		return false;
	}

	const nextVersion = packageJson.version;

	if ( ! nextVersion ) {
		return false;
	}

	if ( initialRelease ) {
		return true;
	}

	const npmVersion = execSync( `pnpm view ${ name } version`, {
		encoding: 'utf-8',
	} );

	return greaterVersionThan( nextVersion.trim(), npmVersion.trim() );
};
