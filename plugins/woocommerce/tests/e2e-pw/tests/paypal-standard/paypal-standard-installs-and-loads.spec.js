/**
 * External dependencies
 */
import { request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { expect, tags, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
const { setOption } = require( '../../utils/options' );

test.describe(
	'PayPal Standard Installation',
	{ tag: [ tags.PAYMENTS, tags.PAYPAL ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		async function openPayments( page ) {
			await page.goto( '/wp-admin/admin.php?page=wc-settings' );
			await page
				.getByRole( 'link', { name: 'Payments', exact: true } )
				.click();
			await page.waitForSelector(
				'.settings-payment-gateways__header-title'
			);
		}

		async function waitForPayPalToLoad( page ) {
			const paypalDiv = page.locator( '#paypal' );
			await expect( paypalDiv ).toBeVisible();

			return paypalDiv;
		}

		test( 'PayPal Standard can be installed', async ( { page } ) => {
			await openPayments( page );

			const paypalDiv = await waitForPayPalToLoad( page );

			await test.step( 'Install PayPal Standard', async () => {

				// Confirm the Enable button is present.
				const enableButton = paypalDiv.getByRole( 'link', {
					name: 'Enable',
				} );
				await expect( enableButton ).toBeVisible();

				// Click the Enable button.
				await enableButton.click();
			} );

			// Confirm the Active label is present.
			await expect( paypalDiv.getByText( 'Active' ) ).toBeVisible();
		} );

		test( 'PayPal Standard Orders V2 integration is available after onboarding to Jetpack', async ( {
			page,
			baseURL,
		} ) => {
			await setOption(
				request,
				baseURL,
				'transact_onboarding_complete',
				'yes'
			);

			await openPayments( page );

			const paypalDiv = await waitForPayPalToLoad( page );

			const manageButton = paypalDiv.getByRole( 'button', {
				name: 'Manage',
			} );
			await expect( manageButton ).toBeVisible();

			// Click the Manage button
			await manageButton.click();

			// Confirm the PayPal Buttons setting is present. It is only available for the Orders V2 integration.
			await expect(
				page.getByText( 'Enable PayPal Buttons' )
			).toBeVisible();
		} );
	}
);
