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
} from '../../utils';

const config = require( 'config' );

describe( 'Store owner can login and make sure WooCommerce is activated', () => {

	it( 'can login', async () => {
		await StoreOwnerFlow.login();
	} );

	it( 'can make sure WooCommerce is activated. If not, activate it', async () => {
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

	it( 'can start Setup Wizard when visiting the site for the first time. Skip all other times.', async () => {
		// Check if Setup Wizard Notice is visible on the screen.
		// If yes - proceed with Setup Wizard, if not - skip Setup Wizard (already been completed).
		const setupWizardNotice = await Promise.race( [
			new Promise( resolve => setTimeout( () => resolve(), 1000 ) ), // resolves without value after 1s
			page.waitForSelector('.updated.woocommerce-message.wc-connect', { visible: true } )
		] );
		if ( setupWizardNotice ) {
			await StoreOwnerFlow.runSetupWizard();

			// Fill out store setup section details
			// Select country where the store is located
			await expect( page ).toSelect( 'select[name="store_country"]', config.get( 'addresses.admin.store.country' ) );
			// Fill store's address - first line
			await expect( page ).toFill( '#store_address', config.get( 'addresses.admin.store.addressfirstline' ) );

			// Fill store's address - second line
			await expect( page ).toFill( '#store_address_2', config.get( 'addresses.admin.store.addresssecondline' ) );

			// Fill the city where the store is located
			await expect( page ).toFill( '#store_city', config.get( 'addresses.admin.store.city' ) );

			// Select the state where the store is located
			await expect( page ).toSelect( 'select[name="store_state"]', config.get( 'addresses.admin.store.state' ) );

			// Fill postcode of the store
			await expect( page ).toFill( '#store_postcode', config.get( 'addresses.admin.store.postcode' ) );

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

			await Promise.all( [
				// Click on "Continue" button to move to the next step
				page.$eval( '#wc_tracker_submit', elem => elem.click() ),

				// Wait for the Payment section to load
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			] );

			// Fill out payment section details
			// Turn off Stripe account toggle
			await page.click( '.wc-wizard-service-toggle' );

			await Promise.all( [
				// Click on "Continue" button to move to the next step
				page.click( 'button[name=save_step]', { text: 'Continue' } ),

				// Wait for the Shipping section to load
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			] );

			// Fill out shipping section details
			// Turn off WooCommerce Shipping option
			await page.$eval( '#wc_recommended_woocommerce_services', elem => elem.click() );

			await page.waitForSelector( 'select[name="shipping_zones[domestic][method]"]' );
			await page.waitForSelector( 'select[name="shipping_zones[intl][method]"]' );

			// Select Flat Rate shipping method for domestic shipping zone
			await page.evaluate( () => {
				document.querySelector( 'select[name="shipping_zones[domestic][method]"] > option:nth-child(1)' ).selected = true;
				let element = document.querySelector( 'select[name="shipping_zones[domestic][method]"]' );
				let event = new Event( 'change', { bubbles: true } );
				event.simulated=true;
				element.dispatchEvent( event );
			} );

			await page.$eval('input[name="shipping_zones[domestic][flat_rate][cost]"]', e => e.setAttribute( 'value', '10.00' ) );

			// Select Flat Rate shipping method for the rest of the world shipping zone
			await page.evaluate( () => {
				document.querySelector( 'select[name="shipping_zones[intl][method]"] > option:nth-child(1)' ).selected = true;
				let element = document.querySelector( 'select[name="shipping_zones[intl][method]"]' );
				let event = new Event( 'change', { bubbles: true } );
				event.simulated=true;
				element.dispatchEvent( event );
			} );

			await page.$eval('input[name="shipping_zones[intl][flat_rate][cost]"]', e => e.setAttribute( 'value', '20.00' ) );

			// Select product weight and product dimensions options
			await expect( page ).toSelect( 'select[name="weight_unit"]', 'Pounds' );
			await expect( page ).toSelect( 'select[name="dimension_unit"]', 'Inches' );

			// Click on "Continue" button to move to the next step
			await page.click( 'button[name=save_step]', { text: 'Continue' } );

			// Fill out recommended section details
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

			// Skip activate Jetpack section
			// Click on "Skip this step" in order to skip Jetpack installation
			await page.click( '.wc-setup-footer-links' );

			// Finish Setup Wizard - Ready! section
			// Visit Dashboard
			await StoreOwnerFlow.openDashboard();
		}
	} );
} );

describe( 'Store owner can finish initial store setup', () => {

	it( 'can enable tax rates and calculations', async () => {
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
	} );

	it( 'can configure permalink settings', async () => {
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
	} );
} );
