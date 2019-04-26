/**
 * @format
 */

/** 
 * Internal dependencies
 */
const { settingsPageSaveChanges, StoreOwnerFlow } = require( '../utils' );

describe( 'WooCommerce Products > Downloadable Products Settings', () => {
    beforeAll( async () => {
		await jestPuppeteer.resetContext();
		await StoreOwnerFlow.login();
    } );

    it( 'can update settings', async () => {
        // Go to downloadable products settings page
        await StoreOwnerFlow.openSettings( 'products', 'downloadable' );
        
        // Make sure the product tab is active
		await expect( page ).toMatchElement( 'a.nav-tab-active', { text: 'Products' } );
		await expect( page ).toMatchElement( 'ul.subsubsub > li > a.current', { text: 'Downloadable products' } );
		
		await expect( page ).toSelect( '#woocommerce_file_download_method', 'Redirect only' );
		await expect( page ).toClick( '#woocommerce_downloads_require_login' );
		await expect( page ).toClick( '#woocommerce_downloads_grant_access_after_payment' );
		await settingsPageSaveChanges();
		await expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } );

		await expect( page ).toSelect( '#woocommerce_file_download_method', 'Force downloads' );
		await expect( page ).toClick( '#woocommerce_downloads_require_login' );
		await expect( page ).toClick( '#woocommerce_downloads_grant_access_after_payment' );
		await settingsPageSaveChanges();
		await expect( page ).toMatchElement( '#message', { text: 'Your settings have been saved.' } );
	} );
} );
