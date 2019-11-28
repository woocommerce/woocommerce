/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import {
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	settingsPageSaveChanges,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset, verifyValueOfInputField
} from "../../utils";

describe( 'Store owner can login and make sure WooCommerce is activated', () => {

	it( 'Can login', async () => {
		await StoreOwnerFlow.login();
	} );

	it( 'Can make sure WooCommerce is activated. If not, activate it', async () => {
		const slug = 'woocommerce';
		await StoreOwnerFlow.openPlugins();
		const disableLink = await page.$( `tr[data-slug="${ slug }"] .deactivate a` );
		if ( disableLink ) {
			return;
		}
		await page.click( `tr[data-slug="${ slug }"] .activate a` );
		await page.waitForSelector( `tr[data-slug="${ slug }"] .deactivate a` );
	} );

} );

describe( 'Store owner can go through store Setup Wizard', () => {

	it( 'Can start Setup Wizard', async () => {
		await StoreOwnerFlow.runSetupWizard();
	} );

	it( 'Can fill out Store setup details', async () => {
		// Fill out store address details
		await expect( page ).toSelect( 'select[name="store_country"]', 'United States (US)' );
		await expect( page ).toFill( '#store_address', 'addr 1' );
		await expect( page ).toFill( '#store_address_2', 'addr 2' );
		await expect( page ).toFill( '#store_city', 'San Francisco' );
		await expect( page ).toSelect( 'select[name="store_state"]', 'California' );
		await expect( page ).toFill( '#store_postcode', '94107' );

		// Select currency and type of products to sell details
		await expect( page ).toSelect( 'select[name="currency_code"]', '\n' +
			'\t\t\t\t\t\tUnited States (US) dollar ($ USD)\t\t\t\t\t' );
		await expect( page ).toSelect( 'select[name="product_type"]', 'I plan to sell both physical and digital products' );

		// Verify that checkbox next to "I will also be selling products or services in person." is not selected
		await verifyCheckboxIsUnset( '#woocommerce_sell_in_person' );

		// Click on "Let's go!" button to move to the next step
		await page.$eval( 'button[name=save_step]', elem => elem.click() );

		// Wait for usage tracking pop-up window to appear
		await page.waitForSelector( '#wc-backbone-modal-dialog' );
		await expect( page ).toMatchElement( '.wc-backbone-modal-header', { text: 'Help improve WooCommerce with usage tracking' } );

		await page.waitForSelector('#wc_tracker_checkbox_dialog');

		// Verify that checkbox next to "Enable usage tracking and help improve WooCommerce" is not selected
		await verifyCheckboxIsUnset('#wc_tracker_checkbox_dialog');

		await Promise.all([
			// Click on "Continue" button to move to the next step
			page.$eval( '#wc_tracker_submit', elem => elem.click() ),

			// Wait for the Payment section to load
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		]);
	} );

	it( 'Can fill out Payment details', async () => {
		// Turn off Stripe account toggle
		await page.click( '.wc-wizard-service-toggle' );

		// Click on "Continue" button to move to the next step
		await page.click( 'button[name=save_step]', { text: 'Continue' } );
	} );

	it( 'Can fill out Shipping details', async () => {
		// Turn off WooCommerce Shipping option
		await page.$eval( '#wc_recommended_woocommerce_services', elem => elem.click() );

		await page.waitForSelector( 'select[name="shipping_zones[domestic][method]"]' );
		await page.waitForSelector( 'select[name="shipping_zones[intl][method]"]' );

		// Select Free Shipping method for domestic shipping zone
		await page.evaluate( () => {
			document.querySelector( 'select[name="shipping_zones[domestic][method]"] > option:nth-child(2)' ).selected = true;
			let element = document.querySelector( 'select[name="shipping_zones[domestic][method]"]' );
			let event = new Event( 'change', { bubbles: true } );
			event.simulated=true;
			element.dispatchEvent( event );
		} );

		// Select Free Shipping method for the rest of the world shipping zone
		await page.evaluate( () => {
			document.querySelector( 'select[name="shipping_zones[intl][method]"] > option:nth-child(2)' ).selected = true;
			let element = document.querySelector( 'select[name="shipping_zones[intl][method]"]' );
			let event = new Event( 'change', { bubbles: true } );
			event.simulated=true;
			element.dispatchEvent( event );
		});

		// Select product weight and product dimensions options
		await expect( page ).toSelect( 'select[name="weight_unit"]', 'Pounds' );
		await expect( page ).toSelect( 'select[name="dimension_unit"]', 'Inches' );

		// Click on "Continue" button to move to the next step
		await page.click( 'button[name=save_step]', { text: 'Continue' } );
	} );

	it( 'Can fill out Recommended details', async () => {
		// Turn off Storefront Theme option
		await page.$eval( '#wc_recommended_storefront_theme', elem => elem.click() );

		// Turn off Automated Taxes option
		await page.$eval( '#wc_recommended_automated_taxes', elem => elem.click() );

		// Turn off WooCommerce Admin option
		await page.$eval( '#wc_recommended_wc_admin', elem => elem.click() );

		// Turn off Mailchimp option
		await page.$eval( '#wc_recommended_mailchimp', elem => elem.click() );

		// Turn off Facebook option
		await page.$eval( '#wc_recommended_facebook', elem => elem.click() );

		// Click on "Continue" button to move to the next step
		await page.click( 'button[name=save_step]', { text: 'Continue' } );
	} );

	it( 'Can skip Activate Jetpack section', async () => {
		// Click on "Skip this step" in order to skip Jetpack installation
		await page.click( '.wc-setup-footer-links' );
	} );

	it( 'Can finish Setup Wizard - Ready! section', async () => {
		// Visit Dashboard
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
		// Go to Permalink Settings page
		await StoreOwnerFlow.openPermalinkSettings();

		// Select "Post name" option in common settings section
		await page.click( 'input[value="/%postname%/"]', { text: ' Post name' } );

		// Select "Custom base" in product permalinks section
		await page.click( '#woocommerce_custom_selection' );

		// Fill custom base slug to use
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
