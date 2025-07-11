/**
 * External dependencies
 */
import { test as setup } from '@playwright/test';
import fs from 'fs';

/**
 * Internal dependencies
 */
const { admin, customer } = require( '../test-data/data' );
import {
	ADMIN_STATE_PATH,
	CUSTOMER_STATE_PATH,
	STORAGE_DIR_PATH,
} from '../playwright.config';

async function authenticate( request, user, storagePath ) {
	await request.post( './wp-login.php', {
		form: {
			log: user.username,
			pwd: user.password,
		},
	} );
	await request.storageState( { path: storagePath } );
}

setup.beforeAll( 'clear existing state', async () => {
	fs.rm( STORAGE_DIR_PATH, { recursive: true }, ( err ) => {
		if ( err && err.code !== 'ENOENT' ) {
			console.error( `Error while deleting state folder: ${ err }` );
		}
	} );
} );

setup( 'authenticate users', async ( { request } ) => {
	await setup.step( 'authenticate admin', async () => {
		await authenticate( request, admin, ADMIN_STATE_PATH );
	} );
	await setup.step( 'authenticate customer', async () => {
		await authenticate( request, customer, CUSTOMER_STATE_PATH );
	} );
} );
