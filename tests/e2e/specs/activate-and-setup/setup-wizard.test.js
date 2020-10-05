/**
 * @format
 */

/**
 * Internal dependencies
 */
import {
	StoreOwnerFlow,
	completeOldSetupWizard,
	completeOnboardingWizard,
	permalinkSettingsPageSaveChanges,
	setCheckbox,
	settingsPageSaveChanges,
	verifyCheckboxIsSet,
	verifyValueOfInputField
} from '@woocommerce/e2e-utils';

describe( 'Store owner can login and make sure WooCommerce is activated', () => {
	beforeAll( async () => {
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

	it( 'can start and complete Setup Wizard when visiting the site for the first time.', async () => {
		await StoreOwnerFlow.runSetupWizard();
		await completeOnboardingWizard();
	} );
} );

describe( 'Store owner can go through setup Task List', () => {
	it.skip( 'can setup shipping', async () => {
		await page.evaluate( () => {
			document.querySelector( '.woocommerce-list__item-title' ).scrollIntoView();
		} );
		// Query for all tasks on the list
		const taskListItems = await page.$$( '.woocommerce-list__item-title' );
		expect( taskListItems ).toHaveLength( 6 );

		await Promise.all( [
			// Click on "Set up shipping" task to move to the next step
			taskListItems[4].click(),

			// Wait for shipping setup section to load
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
		] );

		// Wait for "Proceed" button to become active
		await page.waitForSelector( 'button.is-primary:not(:disabled)' );
		await page.waitFor( 3000 );

		// Click on "Proceed" button to save shipping settings
		await page.click( 'button.is-primary' );
		await page.waitFor( 3000 );
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
