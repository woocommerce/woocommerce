import { StoreOwnerFlow } from '../../utils/flows';
import { visitAdminPage } from '@wordpress/e2e-test-utils';
import {
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	settingsPageSaveChanges,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset, verifyValueOfInputField
} from "../../utils";

describe( 'Store owner can login and make sure WooCommerce is activated', () => {

	it('Can login', async () => {
		await StoreOwnerFlow.login();
	});

	it('Can make sure WooCommerce is activated. If not, activate it', async () => {
		const slug = 'woocommerce';
		await visitAdminPage('plugins.php');
		const disableLink = await page.$(`tr[data-slug="${ slug }"] .deactivate a`);
		if (disableLink) {
			return;
		}
		await page.click(`tr[data-slug="${ slug }"] .activate a`);
		await page.waitForSelector(`tr[data-slug="${ slug }"] .deactivate a`);
	});

} );

describe( 'Store owner can go through store Setup Wizard', () => {

	it( 'Can start Setup Wizard', async () => {
		await StoreOwnerFlow.runSetupWizard();
	} );

	it( 'Can fill out Store setup details', async () => {
		await expect( page ).toSelect( 'select[name="store_country"]', 'United States (US)' );
		await expect( page ).toFill( '#store_address', 'addr 1' );
		await expect( page ).toFill( '#store_address_2', 'addr 2' );
		await expect( page ).toFill( '#store_city', 'San Francisco' );
		await expect( page ).toSelect( 'select[name="store_state"]', 'California' );
		await expect( page ).toFill( '#store_postcode', '94107' );
		await expect( page ).toMatchElement( '#currency_code', { text: 'United States (US) dollar ($ USD)' } );
		await expect( page ).toMatchElement( '#product_type', { text: 'I plan to sell both physical and digital products' } );
		await verifyCheckboxIsUnset( '#woocommerce_sell_in_person' );

		await expect( page ).toDisplayDialog( async () => {
			await expect( page ).toClick(`button[name=save_step]`, { text: 'Let\'s go!' } );
			await verifyCheckboxIsUnset( '#wc_tracker_checkbox_dialog' );
			await page.$eval( '#wc_tracker_submit', elem => elem.click() );
		});
	} );

	it( 'Can fill out Payment details', async () => {
		// Turn off Stripe account toggle
		await page.click( '.wc-wizard-service-toggle' );
		await page.click( 'button[name=save_step]', { text: 'Continue' } );
	} );

	it( 'Can fill out Shipping details', async () => {
		await page.click( 'button[name=save_step]', { text: 'Continue' } );
	} );

	it( 'Can fill out Recommended details', async () => {
		await page.click( '.wc-setup-footer-links' );
	} );

	it( 'Can skip Activate Jetpack section', async () => {
		await page.click( '.wc-setup-footer-links' );
	} );

	it( 'Can finish Setup Wizard - Ready! section', async () => {
		await StoreOwnerFlow.openDashboard();
	} );

} );

describe( 'Store owner can finish initial store setup', () => {

	it('Can enable tax rates and calculations', async () => {
		// Go to general settings page
		await StoreOwnerFlow.openSettings( 'general' );

		// Make sure the general tab is active
		await expect( page ).toMatchElement( 'a.nav-tab-active', { text: 'General' } );

		// Enable tax rates and calculations
		await setCheckbox( '#woocommerce_calc_taxes' );

		await settingsPageSaveChanges();

		// Verify that settings have been saved
		await Promise.all( [
			expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } ),
			verifyCheckboxIsSet( '#woocommerce_calc_taxes' ),
		] );
	});

	it('Can configure permalink settings', async () => {
		await StoreOwnerFlow.openPermalinkSettings();
		await page.click( 'input[value="/%postname%/"]', { text: ' Post name' } );
		await page.click( '#woocommerce_custom_selection' );
		await expect( page ).toFill( '#woocommerce_permalink_structure', '/product/' );

		await permalinkSettingsPageSaveChanges();

		// Verify that settings have been saved
		await Promise.all( [
			expect( page ).toMatchElement( '#setting-error-settings_updated', { text: 'Permalink structure updated.' } ),
			verifyValueOfInputField( '#permalink_structure', '/%postname%/' ),
			verifyValueOfInputField( '#woocommerce_permalink_structure', '/product/' ),
		] );
	});

} );
