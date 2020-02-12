/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { completeOldSetupWizard } from '../../utils/components';
import {
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	settingsPageSaveChanges,
	verifyCheckboxIsSet,
	verifyCheckboxIsUnset, verifyValueOfInputField
} from '../../utils';

const config = require( 'config' );

describe( 'Login', () => {

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

describe( 'Setup Wizard', () => {

	it( 'can start Setup Wizard when visiting the site for the first time. Skip all other times.', async () => {
		// Check if Setup Wizard Notice is visible on the screen.
		// If yes - proceed with Setup Wizard, if not - skip Setup Wizard (already been completed).
		const setupWizardNotice = await Promise.race( [
			new Promise( resolve => setTimeout( () => resolve(), 1000 ) ), // resolves without value after 1s
			page.waitForSelector('.updated.woocommerce-message.wc-connect', { visible: true } )
		] );
		if ( setupWizardNotice ) {
			await StoreOwnerFlow.runSetupWizard();

			// Check if the New Setup Wizard Notice (since 3.9) is visible on the screen.
			// If yes - continue with the old Setup Wizard.
			// If not - the test will continue with the old wizard by default.
			const newSetupWizardNotice = await Promise.race( [
				new Promise( resolve => setTimeout( () => resolve(), 1000) ), // resolves without value after 1s
				page.waitForSelector( '.wc-setup-step__new_onboarding-wrapper', { visible: true } )
			] );
			if ( newSetupWizardNotice ) {
				// Continue with the old setup wizard
				await Promise.all( [
					// Click on "Continue with the old setup wizard" footer link to start the old setup wizard
					page.$eval( '.wc-setup-footer-links', elem => elem.click() ),

					// Wait for the store setup section to load
					page.waitForNavigation( { waitUntil: 'networkidle0' } ),
				] );
			}
			await completeOldSetupWizard();
		}
	} );
} );

describe( 'Initial Store Setup', () => {

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
