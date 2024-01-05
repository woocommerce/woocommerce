/**
 * External dependencies
 */

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
		const text = 'Enable local pickup';
		const input = page.locator(
			`//label[text()='${ text }']/preceding-sibling::span`
		);

		if ( ! ( await input.locator( 'svg' ).isVisible() ) ) {
			await page.getByText( text ).click();
			await utilsLocalPickup.savelocalPickupSettings( admin, page );
		}
	},
	disableLocalPickup: async ( admin, page ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);

		const text = 'Enable local pickup';
		const input = page.locator(
			`//label[text()='${ text }']/preceding-sibling::span`
		);

		if ( await input.locator( 'svg' ).isVisible() ) {
			await page.getByText( text ).click();
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
			await expect(
				page.locator( '.components-modal__content' )
			).toHaveCount( 0 );
		}

		await page.waitForSelector(
			'.pickup-locations tbody tr td:has-text("When you add a pickup location, it will appear here.")'
		);
		await utilsLocalPickup.savelocalPickupSettings( admin, page );
	},
	removeCostForLocalPickup: async ( admin, page ) => {
		const text = 'Add a price for customers who choose local pickup';
		const input = page.locator(
			`//label[text()='${ text }']/preceding-sibling::span`
		);

		if ( await input.locator( 'svg' ).isVisible() ) {
			await page.getByText( text ).click();
			await utilsLocalPickup.savelocalPickupSettings( admin, page );
		}
	},
	addPickupLocation: async ( admin, page, location ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);

		await page.getByText( 'Add pickup location' ).click();

		await page
			.locator( '.components-modal__content input[name="location_name"]' )
			.fill( location.name );
		await page
			.locator(
				'.components-modal__content input[name="location_address"]'
			)
			.fill( location.address );
		await page
			.locator( '.components-modal__content input[name="location_city"]' )
			.fill( location.city );
		await page
			.locator(
				'.components-modal__content input[name="location_postcode"]'
			)
			.fill( location.postcode );
		await page
			.locator(
				'.components-modal__content select[name="location_country_state"]'
			)
			.selectOption( location.state );

		await page
			.locator(
				'.components-modal__content input[name="pickup_details"]'
			)
			.fill( location.details );
		await page
			.locator(
				'.components-modal__content button.components-button.is-primary'
			)
			.click();

		await utilsLocalPickup.savelocalPickupSettings( admin, page );
	},
};
