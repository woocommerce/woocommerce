/**
 * External dependencies
 */
import fs from 'fs';

/**
 * Internal dependencies
 */
import { test as baseTest } from './fixtures';
import { ADMIN_STATE_PATH, STORAGE_DIR_PATH } from '../playwright.config';
import { wpCLI } from '../utils/cli';

const LOCK_FILE = `${ STORAGE_DIR_PATH }/paypal.lock`;

export const test = baseTest.extend( {
	page: async ( { page }, use ) => {
		// Wait for lock to be available
		while ( fs.existsSync( LOCK_FILE ) ) {
			await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );
		}

		// Acquire lock
		fs.writeFileSync( LOCK_FILE, process.pid.toString() );

		await wpCLI(
			"wp option patch update woocommerce_paypal_settings _should_load 'yes'"
		);

		await use( page );

		await wpCLI(
			"wp option patch update woocommerce_paypal_settings _should_load 'no'"
		);

		// Release lock
		try {
			fs.unlinkSync( LOCK_FILE );
		} catch ( e ) {
			// Lock already released
		}
	},
	storageState: ADMIN_STATE_PATH,
} );
