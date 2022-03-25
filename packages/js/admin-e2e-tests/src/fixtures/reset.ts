/**
 * Internal dependencies
 */
import { httpClient } from './http-client';
import { deactivateAndDeleteAllPlugins } from './plugins';

const resetEndpoint = '/woocommerce-reset/v1/state';

const skippedPlugins = [
	'woocommerce',
	'woocommerce-admin',
	'woocommerce-reset',
	'basic-auth',
	'wp-mail-logging'
];

export async function resetWooCommerceState() {
	const response = await httpClient.delete( resetEndpoint );
	expect( response.data.options ).toEqual( true );
	expect( response.data.transients ).toEqual( true );
	expect( response.data.notes ).toEqual( true );
	expect( response.statusCode ).toEqual( 200 );
	await deactivateAndDeleteAllPlugins( skippedPlugins );
}
