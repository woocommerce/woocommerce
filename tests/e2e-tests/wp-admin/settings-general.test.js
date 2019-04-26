/**
 * @format
 */

/** 
 * Internal dependencies
 */
const { settingsPageSaveChanges, StoreOwnerFlow } = require( '../utils' );

describe( 'WooCommerce General Settings', () => {
    beforeAll( async () => {
		await jestPuppeteer.resetContext();
		await StoreOwnerFlow.login();
    } );

    it( 'can update settings', async () => {
        // Go to general settings page
        await StoreOwnerFlow.openSettings( 'general' );
        
        // Make sure the general tab is active
        await expect( page ).toMatchElement( 'a.nav-tab-active', { text: 'General' } );

		// Set selling location to all countries first,
		// so we can choose california as base location.
		await expect( page ).toSelect( '#woocommerce_allowed_countries', 'Sell to all countries' );
		await settingsPageSaveChanges();
		await expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } );

		// Set base location with state CA.
		await expect( page ).toSelect( 'select[name="woocommerce_default_country"]', 'United States (US) — California' );
		await settingsPageSaveChanges();
		await expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } );

		// Set selling location to specific countries first, so we can choose U.S as base location (without state).
		// This will makes specific countries option appears.
		await expect( page ).toSelect( '#woocommerce_allowed_countries', 'Sell to specific countries' );
		await expect( page ).toSelect( 'select[name="woocommerce_specific_allowed_countries[]"]', 'United States (US)' );
		await settingsPageSaveChanges();
		await expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } );

		// Set currency options.
		await expect( page ).toFill( '#woocommerce_price_thousand_sep', ',' );
		await expect( page ).toFill( '#woocommerce_price_decimal_sep', '.' );
		await expect( page ).toFill( '#woocommerce_price_num_decimals', '2' );
		await settingsPageSaveChanges();
		await expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } );
    } );
} );
