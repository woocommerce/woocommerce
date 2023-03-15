/**
 * External dependencies
 */
import { merchant as wcMerchant } from '@woocommerce/e2e-utils';
import { visitAdminPage } from '@wordpress/e2e-test-utils';
import { findLabelWithText } from '@woocommerce/blocks-test-utils';

export const merchant = {
	...wcMerchant,
	changeLanguage: async ( language ) => {
		await visitAdminPage( 'options-general.php' );
		await page.select( 'select#WPLANG', language );
		await page.click( 'input[type="submit"]' );
		await page.waitForSelector( '#setting-error-settings_updated', {
			visible: true,
		} );
	},
	goToLocalPickupSettingsPage: async () => {
		await visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=shipping&section=pickup_location'
		);
		await page.waitForSelector(
			'#wc-shipping-method-pickup-location-settings-container'
		);
	},
	saveLocalPickupSettingsPageWithRefresh: async () => {
		await expect( page ).toClick( 'button', {
			text: 'Save changes',
		} );
		await expect( page ).toMatchElement( '.components-snackbar__content', {
			text: 'Local Pickup settings have been saved.',
		} );
		await merchant.goToLocalPickupSettingsPage();
	},
	enableLocalPickup: async () => {
		await merchant.goToLocalPickupSettingsPage();
		const enabledLabel = await findLabelWithText( 'Enable local pickup' );
		const enabledChecked = await page.$eval(
			'#inspector-checkbox-control-1',
			( el ) => ( el as HTMLInputElement ).checked
		);
		if ( ! enabledChecked ) {
			await enabledLabel.click();
		}

		await expect( page ).toFill(
			'input[name="local_pickup_title"]',
			'Local Pickup'
		);
		await merchant.saveLocalPickupSettingsPageWithRefresh();
	},
	disableLocalPickup: async () => {
		await merchant.goToLocalPickupSettingsPage();
		const enabledLabel = await findLabelWithText( 'Enable local pickup' );
		const enabledChecked = await page.$eval(
			'#inspector-checkbox-control-1',
			( el ) => ( el as HTMLInputElement ).checked
		);
		if ( enabledChecked ) {
			await enabledLabel.click();
		}
		await merchant.saveLocalPickupSettingsPageWithRefresh();
	},
	removeCostForLocalPickup: async () => {
		const costLabel = await findLabelWithText(
			'Add a price for customers who choose local pickup'
		);
		const costChecked = await page.$eval(
			'#inspector-checkbox-control-1',
			( el ) => ( el as HTMLInputElement ).checked
		);
		if ( costChecked ) {
			await costLabel.click();
		}
	},
	addLocalPickupLocation: async () => {
		await merchant.goToLocalPickupSettingsPage();
		await expect( page ).toClick( 'button', {
			text: 'Add pickup location',
		} );
		await expect( page ).toFill(
			'input[name="location_name"]',
			'Test Location'
		);
		await expect( page ).toFill(
			'input[name="location_address"]',
			'Test Address 1'
		);
		await expect( page ).toFill(
			'input[name="location_city"]',
			'Test City'
		);
		await expect( page ).toFill(
			'input[name="location_postcode"]',
			'90210'
		);
		await expect( page ).toFill(
			'input[name="pickup_details"]',
			'Collect from store'
		);
		await expect( page ).toSelect(
			'select[name="location_country_state"]',
			'United States (US) — California'
		);
		await expect( page ).toClick( 'button', { text: 'Done' } );
		await merchant.saveLocalPickupSettingsPageWithRefresh();
	},
};
