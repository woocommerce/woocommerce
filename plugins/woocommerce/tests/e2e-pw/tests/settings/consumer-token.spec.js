/**
 * External dependencies
 */
import { HTTPClientFactory } from '@woocommerce/api';
/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/fixtures';
import playwrightConfig, { ADMIN_STATE_PATH } from '../../playwright.config';
import { getFakeProduct } from '../../utils/data';

test.use( { storageState: ADMIN_STATE_PATH } );

test( 'admin can manage consumer keys', async ( { page } ) => {
	const keyName = `e2e-api-access-${ Date.now() }`;
	let key = '';
	let secret = '';
	const testProduct = getFakeProduct();

	await test.step( 'navigate to rest api settings page', async () => {
		await page.goto(
			`./wp-admin/admin.php?page=wc-settings&tab=advanced&section=keys`
		);
		await page
			.getByRole( 'link', {
				name: 'Add key',
			} )
			.click();
	} );

	await test.step( 'can generate a consumer key', async () => {
		await page.locator( '#key_description' ).fill( keyName );
		await page.locator( '#key_permissions' ).selectOption( 'read_write' );
		await page.locator( 'text=Generate API key' ).click();
		key = await page.locator( '#key_consumer_key' ).inputValue();
		secret = await page.locator( '#key_consumer_secret' ).inputValue();

		await expect( page.locator( 'button.copy-key' ) ).toBeEnabled();
		await expect( page.locator( 'button.copy-secret' ) ).toBeEnabled();
		await expect( page.locator( '#keys-qrcode' ) ).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Revoke key' } )
		).toBeVisible();
	} );

	const apiClient = HTTPClientFactory.build( playwrightConfig.use.baseURL )
		.withOAuth( key, secret )
		.withIndexPermalinks()
		.create();

	await test.step( 'can use the consumer key', async () => {
		const createResponse = await apiClient.post(
			'wc/v3/products',
			testProduct
		);

		await expect( createResponse.statusCode ).toBe( 201 );

		testProduct.id = createResponse.data.id;

		const readResponse = await apiClient.get(
			`wc/v3/products/${ testProduct.id }`
		);

		await expect( readResponse.data.id ).toBe( testProduct.id );
		await expect( readResponse.data.name ).toBe( testProduct.name );
	} );

	await test.step( 'can revoke the consumer key', async () => {
		await page.goto(
			`./wp-admin/admin.php?page=wc-settings&tab=advanced&section=keys`
		);
		await page
			.getByRole( 'link', {
				name: keyName,
			} )
			.click();

		await expect( page.locator( '#key_description' ) ).toHaveValue(
			keyName
		);

		await page.getByRole( 'link', { name: 'Revoke key' } ).click();

		await expect(
			page.getByText( '1 API key permanently revoked' )
		).toBeVisible();

		await expect(
			apiClient.get( `/wc/v3/products/${ testProduct.id }` )
		).rejects.toMatchObject( {
			statusCode: 401,
			data: {
				message: 'Consumer key is invalid.',
			},
		} );
	} );
} );
