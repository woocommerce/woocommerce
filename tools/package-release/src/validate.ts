/**
 * External dependencies
 */
import { existsSync, readFileSync } from 'fs';
import { execSync } from 'child_process';
import { gt as greaterVersionThan } from 'semver';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT, excludedPackages } from './const';

/**
 * Get pnpm's package data.
 *
 * @param {string} name package name.
 * @return {Object|Array} A package object or an array of package objects.
 */
const getPackageData = ( name?: string ) => {
	try {
		const rawData = execSync( 'pnpm m ls --json --depth=-1', {
			cwd: MONOREPO_ROOT,
			encoding: 'utf-8',
		} );

		const data = JSON.parse( rawData );

		if ( ! name ) {
			return data;
		}

		return data.find( ( p: { name: string } ) => p.name === name );
	} catch ( e ) {
		if ( e instanceof Error ) {
			// eslint-disable-next-line no-console
			console.log( e );
			throw e;
		}
	}
};

/**
 * Determine if package is JS or PHP.
 *
 * @param {string} name package name.
 * @return {undefined|string} Package type js or php.
 */
export const getPackageType = ( name: string ) => {
	const packageData = getPackageData( name );
	if ( ! packageData ) {
		return;
	}
	if ( packageData.path.includes( 'packages/js' ) ) {
		return 'js';
	}
	if ( packageData.path.includes( 'packages/php' ) ) {
		return 'php';
	}
};

/**
 * Get filepath for a given package name.
 *
 * @param {string} name package name.
 * @return {string} Absolute path for the package.
 */
export const getFilepathFromPackageName = ( name: string ): string => {
	const packageData = getPackageData( name );
	return packageData?.path;
};

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
 * Get a package's composer.json file in JSON format.
 *
 * @param {string} name package name.
 * @return {Object|false} JSON object or false if it fails.
 */
export const getComposerJson = ( name: string ) => {
	const filepath = getFilepathFromPackageName( name );
	const composerJsonFilepath = `${ filepath }/composer.json`;
	const composerJsonExists = existsSync( composerJsonFilepath );
	if ( ! composerJsonExists ) {
		return false;
	}

	return JSON.parse( readFileSync( composerJsonFilepath, 'utf8' ) );
};

/**
 * Get all releaseable package names.
 *
 * @return {Array<string>} Package names.
 */
export const getAllPackages = (): Array< string > => {
	const packageData = getPackageData();
	if ( ! packageData ) {
		return [];
	}
	return packageData
		.map( ( p: { name: string } ) => p.name )
		.filter( ( name: string ) => ! excludedPackages.includes( name ) );
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
	const packageData = getPackageData( name );

	if ( ! packageData ) {
		error( `${ name } is not a valid package.` );
	}

	if ( packageData.private ) {
		error(
			`${ name } is a private package, no need to prepare for a release.`
		);
	}

	return true;
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
