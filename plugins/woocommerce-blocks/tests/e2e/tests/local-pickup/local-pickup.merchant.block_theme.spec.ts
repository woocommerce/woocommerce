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
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);
		await page.waitForSelector( '#local-pickup-settings' );
		expect( page.locator( '#local-pickup-settings' ) ).not.toBeNull();
		expect(
			await page.locator( '#inspector-checkbox-control-0' ).isChecked()
		).toBeTruthy();
	} );

	test.describe( 'Core Settings', () => {
		test.beforeEach( async ( { admin, page } ) => {
			await utils.clearLocations( admin, page );
			await utils.enableLocalPickup( admin, page );
		} );

		test( 'Shows the correct shipping options depending on whether Local Pickup is enabled', async ( {
			admin,
			page,
		} ) => {
			await utils.disableLocalPickup( admin, page );

			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=pickup_location'
			);

			expect(
				await page.locator( '#inspector-checkbox-control-0' ).isChecked()
			).toBeFalsy();

			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=options'
			);
			expect(
				await page.locator( '#woocommerce_shipping_cost_requires_address' )
			).not.toBeNull();

			await utils.enableLocalPickup( admin, page );

			await admin.visitAdminPage(
				'admin.php',
				'page=wc-settings&tab=shipping&section=options'
			);
			expect(
				await page.locator( '#woocommerce_shipping_cost_requires_address' )
			).not.toBeNull();
		} );
	} );
} );
