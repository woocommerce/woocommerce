const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'Manage webhooks', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.get( 'webhooks' ).then( ( response ) => {
			let ids = response.data.map( webhook => webhook.id );

			api.post( 'webhooks/batch', {
				delete: ids,
			} );
		} );
	} );

	const WEBHOOKS_SCREEN_URI = 'wp-admin/admin.php?page=wc-settings&tab=advanced&section=webhooks';

	test( 'Webhook cannot be bulk deleted without nonce', async ( {
		page,
	} ) => {
		await page.goto( WEBHOOKS_SCREEN_URI );

		await page.getByRole( 'link', { name: 'Add webhook' } )
			.click()
			.catch( () => {} );

		await page.waitForLoadState( 'networkidle' );

		await page.getByRole( 'textbox', { name: 'Name' } )
			.fill( 'Webhook 1' );

		await page.getByRole( 'button', { name: 'Save webhook' } )
			.click()
			.catch( () => {} );

		await page.waitForLoadState( 'networkidle' );

		await expect(
			page.locator( '#message.updated.inline' )
		).toContainText( 'Webhook updated successfully.' );

		await page.goto( WEBHOOKS_SCREEN_URI );

		await expect(
			page.getByRole( 'link', { name: 'Webhook 1' } ).first().isVisible()
		).toBeTruthy();

		let editURL = await page.getByRole( 'link', { name: 'Webhook 1' } ).first().getAttribute( 'href' );
		editURL = new URL( editURL );
		const origin = editURL.origin;
		const webhookID = editURL.searchParams.get( 'edit-webhook' );

		let actionURI = new URL( origin + '/' + WEBHOOKS_SCREEN_URI );
		actionURI.searchParams.set( 'action', 'delete' );
		actionURI.searchParams.set( 'webhook[]', webhookID );

		await page.goto( actionURI.toString() );

		await page.waitForLoadState( 'networkidle' );

		await expect(
			page.locator( '#message.updated.inline' ).filter( { hasText: /webhook permanently deleted/ } )
		).toHaveCount( 0, { timeout: 1 } ); // No timeout since we already waited for networkidle.

		await expect(
			page.locator( '.wp-die-message' )
		).toContainText( 'The link you followed has expired.' );
	} );
} );
