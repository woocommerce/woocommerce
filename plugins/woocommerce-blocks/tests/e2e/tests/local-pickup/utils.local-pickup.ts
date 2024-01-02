import { expect } from '@woocommerce/e2e-playwright-utils';

export const utilsLocalPickup = {
	savelocalPickupSettings: async ( admin, page ) => {
		await page.locator( 'button:text("Save changes")' ).click();
		await page.waitForSelector(
			'.components-snackbar__content:has-text("Local Pickup settings have been saved.")'
		);
		// Refresh page.
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);
	},
	enableLocalPickup: async ( admin, page ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);
		const checkbox = await page.locator( '#inspector-checkbox-control-0' );

		if ( ! checkbox.isChecked() ) {
			await checkbox.click();
			await utilsLocalPickup.savelocalPickupSettings( admin, page );
		}
	},
	disableLocalPickup: async ( admin, page ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);

		const checkbox = await page.locator( '#inspector-checkbox-control-0' );

		if ( checkbox.isChecked() ) {
			await checkbox.click();
			await utilsLocalPickup.savelocalPickupSettings( admin, page );
		}
	},
	clearLocations: async ( admin, page ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);

		const editButtons = await page.$$(
			'.pickup-locations tbody tr .button-link-edit'
		);

		for ( const button of editButtons ) {
			await button.click();
			await page
				.locator(
					'.components-modal__content button:text("Delete location")'
				)
				.click();
			expect( page.locator( '.components-modal__content' ) ).toBeNull();
		}

		await page.waitForSelector(
			'.pickup-locations tbody tr td:has-text("When you add a pickup location, it will appear here.")'
		);
		await utilsLocalPickup.savelocalPickupSettings( admin, page );
	},
};
