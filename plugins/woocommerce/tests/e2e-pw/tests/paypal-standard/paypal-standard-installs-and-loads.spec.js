/**
 * Internal dependencies
 */
import { expect, tags, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe(
	'PayPal Standard Installation',
	{ tag: [ tags.PAYMENTS, tags.PAYPAL ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test( 'PayPal Standard can be installed', async ( { page} ) => {
			await test.step( 'Go to the payment gateways page', async () => {
				await page.goto( '/wp-admin/admin.php?page=wc-settings' );

				await page
					.getByRole( 'link', { name: 'Payments', exact: true } )
					.click();

				await page.waitForSelector(
					'.settings-payment-gateways__header-title'
				);
			} );

			const paypalDiv = page.locator( '#paypal' );

			await test.step( 'Install PayPal Standard', async () => {
				// Confirm PayPal is listed.
				await expect( paypalDiv ).toBeVisible();

				// Confirm the Enable button is present.
				const enableButton = paypalDiv.getByRole( 'button', {
					name: 'Enable',
				} );
				await expect( enableButton ).toBeVisible();

				// Click the Enable button.
				await enableButton.click();
			} );

			// Confirm the Active label is present.
			await expect( paypalDiv.getByText( 'Active' ) ).toBeVisible();
		} );

		test( 'PayPal Standard Orders V2 integration is available when the feature flag is enabled', async ( {
			page,
		} ) => {
			// Click the Manage button
			await page
				.getByRole( 'link', { name: 'Manage', exact: true } )
				.click();

			// Confirm the PayPal Buttons setting is present. It is only available for the Orders V2 integration.
			await expect(
				page.getByText( 'Enable PayPal Buttons' )
			).toBeVisible();
		} );
	}
);
