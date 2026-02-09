/**
 * External dependencies
 */
import { createServer, Server } from 'net';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { execAsync } from './util';

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
 * @param {Function} error       - error print method.
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
