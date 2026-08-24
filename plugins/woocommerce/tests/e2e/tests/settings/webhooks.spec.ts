/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe( 'Manage webhooks', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.afterAll( async ( { restApi } ) => {
		const response = await restApi.get( `${ WC_API_PATH }/webhooks`, {
			per_page: 100,
		} );
		const ids = response.data.map( ( webhook ) => webhook.id );

		if ( ids.length ) {
			await restApi.post( `${ WC_API_PATH }/webhooks/batch`, {
				delete: ids,
			} );
		}
	} );

	const WEBHOOKS_SCREEN_URI =
		'wp-admin/admin.php?page=wc-settings&tab=advanced&section=webhooks';

	test(
		'Webhook cannot be bulk deleted without nonce',
		{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
		async ( { page } ) => {
			await page.goto( WEBHOOKS_SCREEN_URI );

			await page.getByRole( 'link', { name: 'Add webhook' } ).click();
			await page
				.getByRole( 'textbox', { name: 'Name' } )
				.fill( 'Webhook 1' );
			await page.getByRole( 'button', { name: 'Save webhook' } ).click();

			await expect(
				page.getByText( 'Webhook updated successfully.' )
			).toBeVisible();

			await page.goto( WEBHOOKS_SCREEN_URI );

			await expect(
				page.getByRole( 'row', { name: 'Webhook 1' } )
			).toBeVisible();

			const editLink = page.getByRole( 'link', {
				name: 'Webhook 1',
				exact: true,
			} );
			await expect( editLink ).toHaveAttribute( 'href' );
			const editHref = await editLink.getAttribute( 'href' );
			const editURL = new URL( editHref! );
			const webhookID = editURL.searchParams.get( 'edit-webhook' );

			await page.goto(
				`${ WEBHOOKS_SCREEN_URI }&action=delete&webhook[]=${ webhookID }`
			);

			await expect(
				page.getByText( 'The link you followed has expired.' )
			).toBeVisible();

			await expect(
				page.getByText( 'webhook permanently deleted' )
			).toBeHidden( { timeout: 1 } );
		}
	);

	test(
		'Webhooks can be activated, paused, and deactivated in bulk',
		async ( { page, restApi } ) => {
			const response = await restApi.post( `${ WC_API_PATH }/webhooks`, {
				name: 'Bulk status webhook',
				status: 'disabled',
				topic: 'order.created',
				delivery_url: 'https://example.com/webhook',
			} );

			await page.goto( WEBHOOKS_SCREEN_URI );

			const row = page.getByRole( 'row', {
				name: 'Bulk status webhook',
			} );
			await row.getByRole( 'checkbox' ).check();

			await page.locator( 'select[name="action"]' ).selectOption( 'activate' );
			await page.getByRole( 'button', { name: 'Apply' } ).first().click();

			await expect(
				page.getByText( '1 webhook activated.' )
			).toBeVisible();
			await expect( row.getByText( 'Active' ) ).toBeVisible();
			const activeWebhook = await restApi.get(
				`${ WC_API_PATH }/webhooks/${ response.data.id }`
			);
			expect( activeWebhook.data.status ).toBe( 'active' );

			await row.getByRole( 'checkbox' ).check();
			await page.locator( 'select[name="action"]' ).selectOption( 'pause' );
			await page.getByRole( 'button', { name: 'Apply' } ).first().click();

			await expect(
				page.getByText( '1 webhook paused.' )
			).toBeVisible();
			await expect( row.getByText( 'Paused' ) ).toBeVisible();
			const pausedWebhook = await restApi.get(
				`${ WC_API_PATH }/webhooks/${ response.data.id }`
			);
			expect( pausedWebhook.data.status ).toBe( 'paused' );

			await row.getByRole( 'checkbox' ).check();
			await page.locator( 'select[name="action"]' ).selectOption( 'deactivate' );
			await page.getByRole( 'button', { name: 'Apply' } ).first().click();

			await expect(
				page.getByText( '1 webhook deactivated.' )
			).toBeVisible();
			await expect( row.getByText( 'Disabled' ) ).toBeVisible();
			const disabledWebhook = await restApi.get(
				`${ WC_API_PATH }/webhooks/${ response.data.id }`
			);
			expect( disabledWebhook.data.status ).toBe( 'disabled' );
		}
	);
} );
