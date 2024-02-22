/* eslint-disable no-console */

/**
 * External dependencies
 */
import { request } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { BASE_URL, adminFile, customerFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { customer, admin } from './test-data/data/data';

async function globalSetup() {
	const requestContext = await request.newContext( {
		baseURL: BASE_URL,
	} );

	console.log( 'Authenticating users...' );
	console.time( '> Done in' );
	await new RequestUtils( requestContext, {
		user: admin,
		storageStatePath: adminFile,
	} ).setupRest();
	await new RequestUtils( requestContext, {
		user: customer,
		storageStatePath: customerFile,
	} ).setupRest();
	console.timeEnd( '> Done in' );

	await requestContext.dispose();
}

export default globalSetup;
