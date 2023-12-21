/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test.describe( 'Merchant → Local Pickup Settings', () => {
	test.beforeEach( async ( { admin, page } ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);
		await page.getByLabel( 'Enable local pickup' ).check();
		await page.locator( 'button:text("Save changes")' ).click();
		await page.waitForSelector(
			'.components-snackbar__content:has-text("Local Pickup settings have been saved.")'
		);
	} );

	test( 'Renders without crashing, and settings persist', async ( {
		page,
	} ) => {
		await page.reload( { waitUntil: 'load' } );
		await page.waitForSelector( '#local-pickup-settings' );
		expect( page.locator( '#local-pickup-settings' ) ).not.toBeNull();
		expect(
			page.getByLabel( 'Enable local pickup' ).isChecked()
		).toBeTruthy();
	} );
} );
