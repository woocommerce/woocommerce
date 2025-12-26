/**
 * Internal dependencies
 */
import { expect, tags, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe(
	'PayPal Installation',
	{ tag: [ tags.PAYMENTS, tags.PAYPAL ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		test( 'PayPal can be installed', async ( { page } ) => {
			await test.step( 'Go to the payment gateways page', async () => {
				await page.goto( '/wp-admin/admin.php?page=wc-settings' );

				await page
					.getByRole( 'link', { name: 'Payments', exact: true } )
					.click();

				await page.waitForSelector(
					'.settings-payment-gateways__header-title'
				);
			} );

			const paypalDiv = page.locator( '#_wc_pes_paypal_full_stack' );

			await test.step( 'Install PayPal', async () => {
				// Confirm PayPal is listed.
				await expect( paypalDiv ).toBeVisible();

				// Confirm Install button is present.
				const installButton = paypalDiv.getByRole( 'button', {
					name: 'Install',
				} );
				await expect( installButton ).toBeVisible();

				// Click Install button.
				await installButton.click();
			} );

			// Confirm Manage button is present after installation.
			const completeSetup = paypalDiv.getByRole( 'button', {
				name: 'Complete setup',
			} );
			await expect( completeSetup ).toBeVisible();
		} );
	}
);
