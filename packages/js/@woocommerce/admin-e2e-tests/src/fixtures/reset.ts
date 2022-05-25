/**
 * Internal dependencies
 */
import { httpClient } from './http-client';
import { deactivateAndDeleteAllPlugins } from './plugins';

/* eslint-disable @typescript-eslint/no-var-requires */
const { utils } = require( '@woocommerce/e2e-utils' );

const { PLUGIN_NAME } = process.env;

const resetEndpoint = '/woocommerce-reset/v1/state';

const pluginName = PLUGIN_NAME ? PLUGIN_NAME : 'WooCommerce';
const pluginNameSlug = utils.getSlug( pluginName );

const skippedPlugins = [
	'woocommerce',
	'woocommerce-admin',
	'woocommerce-reset',
	'basic-auth',
	'wp-mail-logging',
	pluginNameSlug,
];

export async function resetWooCommerceState() {
	const response = await httpClient.delete( resetEndpoint );
	expect( response.data.options ).toEqual( true );
	expect( response.data.transients ).toEqual( true );
	expect( response.data.notes ).toEqual( true );
	expect( response.statusCode ).toEqual( 200 );
	await deactivateAndDeleteAllPlugins( skippedPlugins );
}
