/**
 * External dependencies
 */
import { test as setup, APIRequestContext } from '@playwright/test';
import { rm } from 'fs/promises';

/**
 * Internal dependencies
 */
import { admin, customer } from '../test-data/data.js';
import {
	ADMIN_STATE_PATH,
	CUSTOMER_STATE_PATH,
	STORAGE_DIR_PATH,
} from '../playwright.config.js';

/**
 * User credentials interface.
 */
interface UserCredentials {
	username: string;
	password: string;
}

/**
 * Authenticate a user and save the storage state.
 *
 * @param request     - Playwright API request context
 * @param user        - User credentials object
 * @param storagePath - Path to save the storage state
 */
async function authenticate(
	request: APIRequestContext,
	user: UserCredentials,
	storagePath: string
): Promise< void > {
	await request.post( './wp-login.php', {
		form: {
			log: user.username,
			pwd: user.password,
		},
	} );
	await request.storageState( { path: storagePath } );
}

setup.beforeAll( 'clear existing state', async (): Promise< void > => {
	try {
		await rm( STORAGE_DIR_PATH, { recursive: true } );
	} catch ( err ) {
		const error = err as NodeJS.ErrnoException;
		if ( error.code !== 'ENOENT' ) {
			console.error( `Error while deleting state folder: ${ error }` );
		}
	}
} );

setup( 'authenticate users', async ( { request } ): Promise< void > => {
	await setup.step( 'authenticate admin', async (): Promise< void > => {
		await authenticate( request, admin, ADMIN_STATE_PATH );
	} );
	await setup.step( 'authenticate customer', async (): Promise< void > => {
		await authenticate( request, customer, CUSTOMER_STATE_PATH );
	} );
} );
