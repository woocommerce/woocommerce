const { GITHUB_TOKEN, UPDATE_WC } = process.env;
const { downloadZip, deleteZip } = require( './utils/plugin-utils' );
const { test, expect } = require( '@playwright/test' );

test.beforeAll( async ( { request } ) => {
	await test.step( `Download WooCommerce build zip`, async () => {
		const url = `https://github.com/woocommerce/woocommerce/releases/download/${ UPDATE_WC }/woocommerce.zip`;
		await request.get( url, {
			headers: {
				Authorization: `token ${ GITHUB_TOKEN }`,
				Accept: 'application/octet-stream',
			},
		} );
	} );
} );

test( 'Login', async () => {
	expect( true ).toBeTruthy();
} );
