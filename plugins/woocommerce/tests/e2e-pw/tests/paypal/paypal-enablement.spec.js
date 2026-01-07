/**
 * Internal dependencies
 */
import { expect, tags, test } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

test.describe(
	'PayPal Standard Enablement',
	{ tag: [ tags.PAYMENTS, tags.PAYPAL ] },
	() => {
		test.use( { storageState: ADMIN_STATE_PATH } );

		async function openPayments( page ) {
			await page.goto( '/wp-admin/index.php' );
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

		test( 'PayPal Standard can be enabled', async ( { page } ) => {
			await openPayments( page );

			const paypalDiv = await waitForPayPalToLoad( page );

			await test.step( 'Enable PayPal Standard', async () => {
				// Confirm the Enable button is present.
				const enableButton = paypalDiv.getByRole( 'link', {
					name: 'Enable',
				} );
				await expect( enableButton ).toBeVisible();

				// Click the Enable button.
				await enableButton.click();
			} );

			const labelActive = paypalDiv.getByText( 'Active' );
			const labelTestAccount = paypalDiv.getByText( 'Test account' );

			// Confirm the status label is present with any of the expected texts.
			await expect( labelActive.or( labelTestAccount ) ).toBeVisible();
		} );
	}
);
