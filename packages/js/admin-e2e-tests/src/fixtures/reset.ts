/**
 * External dependencies
 */
import { utils } from '@woocommerce/e2e-utils';
/**
 * Internal dependencies
 */
import { httpClient } from './http-client';
import { deactivateAndDeleteAllPlugins } from './plugins';

const { PLUGIN_NAME } = process.env;

const resetEndpoint = '/woocommerce-reset/v1/state';
const switchLanguageEndpoint = '/woocommerce-reset/v1/switch-language';

const pluginName = PLUGIN_NAME ? PLUGIN_NAME : 'WooCommerce';
const pluginNameSlug = utils.getSlug( pluginName );

const skippedPlugins = [
	'woocommerce',
	'woocommerce-admin',
	'woocommerce-reset',
	'basic-auth',
	'wp-mail-logging',
	'woocommerce-enable-cot',
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

export async function switchLanguage( lang: string ) {
	await httpClient.post( switchLanguageEndpoint, {
		lang,
	} );
}
