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

		test( 'PayPal should be listed for install', async ( { page } ) => {
			await page.goto( '/wp-admin/admin.php?page=wc-settings' );

			await page
				.getByRole( 'link', { name: 'Payments', exact: true } )
				.click();

			await page.waitForSelector(
				'.settings-payment-gateways__header-title'
			);

			// Confirm PayPal is listed.
			const paypalDiv = page.locator( '#_wc_pes_paypal_full_stack' );
			await expect( paypalDiv ).toBeVisible();
			await expect(
				paypalDiv.getByRole( 'button', { name: 'Install' } )
			).toBeVisible();
		} );
	}
);
