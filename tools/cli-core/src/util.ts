/**
 * External dependencies
 */
import { createServer, Server } from 'net';
import { join } from 'path';
import { writeFile } from 'fs/promises';
import { exec } from 'child_process';
import { promisify } from 'util';

export const execAsync = promisify( exec );

/**
 * Format version string for regex.
 *
 * @param {string} rawVersion Raw version number.
 * @return {string} version regex.
 */
export const getVersionRegex = ( rawVersion: string ): string => {
	const version = rawVersion.replace( /\./g, '\\.' );

	if ( rawVersion.endsWith( '.0' ) ) {
		return version + '|' + version.slice( 0, -3 ) + '\\n';
	}

	return version;
};

/**
 * Get filename from patch
 *
 * @param {string} str String to extract filename from.
 * @return {string} formatted filename.
 */
export const getFilename = ( str: string ): string => {
	return str.replace( /^a(.*)\s.*/, '$1' );
};

/**
 * Get patches
 *
 * @param {string} content Patch content.
 * @param {RegExp} regex   Regex to find specific patches.
 * @return {string[]} Array of patches.
 */
export const getPatches = ( content: string, regex: RegExp ): string[] => {
	const patches = content.split( 'diff --git ' );
	const changes: string[] = [];

	for ( const p in patches ) {
		const patch = patches[ p ];
		const id = patch.match( regex );

		if ( id ) {
			changes.push( patch );
		}
	}

	return changes;
};

/**
 * Determine if the default port for wp-env is already taken. If so, see
 * https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#2-check-the-port-number
 * for alternatives.
 *
 * @return {Promise<boolean>} if the port is being currently used.
 */
export const isWPEnvPortTaken = () => {
	return new Promise< boolean >( ( resolve, reject ) => {
		const test: Server = createServer()
			.once( 'error', ( err: { code: string } ) => {
				return err.code === 'EADDRINUSE'
					? resolve( true )
					: reject( err );
			} )
			.once( 'listening', () => {
				return test.once( 'close', () => resolve( false ) ).close();
			} )
			.listen( '8888' );
	} );
};

/**
 * Start wp-env.
 *
 * @param {string}   tmpRepoPath - path to the temporary repo to start wp-env from.
 * @param {Function} error       -  error print method.
 * @return {boolean} if starting the container succeeded.
 */
export const startWPEnv = async (
	tmpRepoPath: string,
	error: ( s: string ) => void
) => {
	try {
		// Stop wp-env if its already running.
		await execAsync( 'wp-env stop', {
			cwd: join( tmpRepoPath, 'plugins/woocommerce' ),
			encoding: 'utf-8',
		} );
	} catch ( e ) {
		// If an error is produced here, it means wp-env is not initialized and therefore not running already.
	}

	try {
		if ( await isWPEnvPortTaken() ) {
			throw new Error(
				'Unable to start wp-env. Make sure port 8888 is available or specify port number WP_ENV_PORT in .wp-env.override.json'
			);
		}

		await execAsync( 'wp-env start', {
			cwd: join( tmpRepoPath, 'plugins/woocommerce' ),
			encoding: 'utf-8',
		} );
		return true;
	} catch ( e ) {
		let message = '';
		if ( e instanceof Error ) {
			message = e.message;
			error( message );
		}
		return false;
	}
};

/**
 * Stop wp-env.
 *
 * @param {string}   tmpRepoPath - path to the temporary repo to stop wp-env from.
 * @param {Function} error       - error print method.
 * @return {boolean} if stopping the container succeeded.
 */
export const stopWPEnv = async (
	tmpRepoPath: string,
	error: ( s: string ) => void
): Promise< boolean > => {
	try {
		await execAsync( 'wp-env stop', {
			cwd: join( tmpRepoPath, 'plugins/woocommerce' ),
			encoding: 'utf-8',
		} );
		return true;
	} catch ( e ) {
		let message = '';
		if ( e instanceof Error ) {
			message = e.message;
			error( message );
		}
		return false;
	}
};

/**
 * Generate a JSON file with the data passed.
 *
 * @param  filePath - path to the file to be created.
 * @param  data     - data to be written to the file.
 * @return {Promise<void>} - promise that resolves when the file is written.
 */
export const generateJSONFile = ( filePath: string, data: unknown ) => {
	const json = JSON.stringify(
		data,
		function replacer( _, value ) {
			if ( value instanceof Map ) {
				return Array.from( value.entries() );
			}
			return value;
		},
		2
	);
	return writeFile( filePath, json );
};
