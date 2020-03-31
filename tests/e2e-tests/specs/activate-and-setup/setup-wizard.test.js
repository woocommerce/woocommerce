/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { completeOldSetupWizard, completeOnboardingWizard } from '../../utils/components';
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
			await completeOnboardingWizard();
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

	it( 'can setup shipping', async () => {
		// Go to shipping settings page
		await StoreOwnerFlow.openSettings( 'shipping' );
		// Wait for "Add shipping zone" button to become active
		await page.waitForSelector( 'a.wc-shipping-zone-add:not(:disabled)' );

		await Promise.all( [
			// Click on "Add shipping zone" button
			await page.click( 'a.wc-shipping-zone-add' ),

			// Wait for the shipping zone section to load
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );

		// Fill shipping zone name
		await expect( page ).toFill( '#zone_name', config.get( 'settings.shipping.zonename' ) );

		// Wait for "Add shipping method" button to become active
		await page.waitForSelector( 'button.wc-shipping-zone-add-method:not(:disabled)' );
		// Click on "Add shipping method" button
		await page.click( 'button.wc-shipping-zone-add-method' )

		// Wait for "Add shipping method" window to appear
		await page.waitForSelector( '.wc-backbone-modal-header' );
		await expect( page ).toMatchElement(
			'.wc-backbone-modal-header', { text: 'Add shipping method' }
		);

		// Select Free Shipping
		await expect( page ).toSelect( 'select[name="add_method_id"]', config.get( 'settings.shipping.shippingmethod') );

		// Wait for "Add shipping method" button to become active
		await page.waitForSelector( '#btn-ok:not(:disabled)' );

		await Promise.all( [
			// Click on "Continue" button to move to the next step
			page.click( '#btn-ok' ),

			// Wait for "Shipping zones" section to load
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );
	} );
} );
