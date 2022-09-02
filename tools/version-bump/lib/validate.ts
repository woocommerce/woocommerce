/**
 * External dependencies
 */
import { valid, lt as versionLessThan } from 'semver';
import { readFileSync } from 'fs';

/**
 * Internal dependencies
 */
import { Logger } from './logger';

/**
 * Get a plugin's current version.
 *
 * @param plugin plugin to update
 */
export const getCurrentVersion = ( plugin: string ): string | void => {
	try {
		const composerJSON = JSON.parse(
			readFileSync( `plugins/${ plugin }/composer.json`, 'utf8' )
		);
		return composerJSON.version;
	} catch ( e ) {
		Logger.error( 'Unable to read current version.' );
		return;
	}
};

/**
 * Validate inputs.
 *
 * @param plugin CLI arguments
 * @param options CLI flags
 */
export const validateArgs = (
	plugin: string,
	options: { version: string }
): void => {
	const nextVersion = options.version;

	if ( ! valid( nextVersion ) ) {
		Logger.error(
			'Invalid version supplied, please pass in a semantically correct version.'
		);
	}

	const currentVersion = getCurrentVersion( plugin );

	if ( ! currentVersion ) {
		Logger.error( 'Unable to determine current version' );
	} else if ( versionLessThan( nextVersion, currentVersion ) ) {
		Logger.error(
			'The version supplied is less than the current version, please supply a valid version.'
		);
	}
};
