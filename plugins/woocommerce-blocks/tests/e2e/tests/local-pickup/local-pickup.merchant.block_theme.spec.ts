/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { utilsLocalPickup as utils } from './utils.local-pickup';

test.describe( 'Merchant → Local Pickup Settings', () => {
	test.beforeEach( async ( { admin, page } ) => {
		await utils.clearLocations( admin, page );
		await utils.enableLocalPickup( admin, page );
	} );

	test( 'Renders without crashing, and settings persist', async ( {
		page,
	} ) => {
		await page.reload( { waitUntil: 'load' } );
		await page.waitForSelector( '#local-pickup-settings' );
		expect( page.locator( '#local-pickup-settings' ) ).not.toBeNull();
		const checkboxSelector = '#inspector-checkbox-control-0';
		// eslint-disable-next-line playwright/no-eval
		const isChecked = await page.$eval(checkboxSelector, checkbox => checkbox.checked);
		expect( isChecked ).toBe( true );
		// expect(
		// 	page.locator( '#inspector-checkbox-control-0' ).isChecked()
		// ).toBeTruthy();
	} );

	test.describe( 'Core Settings', () => {
		test( 'Shows the correct shipping options depending on whether Local Pickup is enabled', async ( {
			admin,
			page,
		} ) => {
			await utils.disableLocalPickup( admin, page );
			console.log( await page.locator( '#inspector-checkbox-control-0' ).isChecked());
			expect(
				page.locator( '#inspector-checkbox-control-0' ).isChecked()
			).toBeFalsy();
			// await visitAdminPage(
			// 	'admin.php',
			// 	'page=wc-settings&tab=shipping&section=options'
			// );
			// const hideShippingLabel = await page.$x(
			// 	'//label[contains(., "Hide shipping costs until an address is entered")]'
			// );
			// await expect( hideShippingLabel ).toHaveLength( 1 );
			//
			// await merchant.enableLocalPickup();
			// await visitAdminPage(
			// 	'admin.php',
			// 	'page=wc-settings&tab=shipping&section=options'
			// );
			// const modifiedHideShippingLabel = await page.$x(
			// 	'//label[contains(., "Hide shipping costs until an address is entered (Not available when using WooCommerce Blocks Local Pickup)")]'
			// );
			// await expect( modifiedHideShippingLabel ).toHaveLength( 1 );
		} );
	} );
} );
