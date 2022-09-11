/**
 * External dependencies
 */
import { valid, lt as versionLessThan, prerelease, parse } from 'semver';
import { readFile } from 'fs/promises';

/**
 * Internal dependencies
 */
import { Logger } from './logger';

/**
 * Get a plugin's current version.
 *
 * @param  plugin plugin to update.
 */
export const getCurrentVersion = async (
	plugin: string
): Promise< string | void > => {
	try {
		const composerJSON = JSON.parse(
			await readFile( `plugins/${ plugin }/composer.json`, 'utf8' )
		);
		return composerJSON.version;
	} catch ( e ) {
		Logger.error( 'Unable to read current version.' );
	}
};

/**
 * When given a prerelease version, return just the version.
 *
 * @param {string} prereleaseVersion version with prerelease params
 * @return {string} version
 */
export const stripPrereleaseParameters = (
	prereleaseVersion: string
): string => {
	const parsedVersion = parse( prereleaseVersion );
	if ( parsedVersion ) {
		const { major, minor, patch } = parsedVersion;
		return `${ major }.${ minor }.${ patch }`;
	}
	return prereleaseVersion;
};

/**
 * Validate inputs.
 *
 * @param  plugin          plugin
 * @param  options         options
 * @param  options.version version
 */
export const validateArgs = async (
	plugin: string,
	options: { version: string }
): Promise< void > => {
	const nextVersion = options.version;

	if ( ! valid( nextVersion ) ) {
		Logger.error(
			'Invalid version supplied, please pass in a semantically correct version.'
		);
	}

	const currentVersion = await getCurrentVersion( plugin );

	if ( ! currentVersion ) {
		Logger.error( 'Unable to determine current version' );
	} else if ( versionLessThan( nextVersion, currentVersion ) ) {
		Logger.error(
			'The version supplied is less than the current version, please supply a valid version.'
		);
	}
};
