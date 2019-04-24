/**
 * @format
 */

/** 
 * Internal dependencies
 */
const { clickTab, StoreOwnerFlow } = require( '../utils' );

describe( 'Add New Coupon Page', () => {
	beforeAll( async () => {
		await jestPuppeteer.resetContext();
		await StoreOwnerFlow.login();
	} );

    it( 'can create new coupon', async () => {
        // Go to "add coupon" page
        await StoreOwnerFlow.openNewCoupon();
        
        // Make sure we're on the add coupon page
        await expect( page.title() ).resolves.toMatch( 'Add new coupon' );

        // Fill in coupon code and description
        await expect( page ).toFill( '#title', 'code-' + new Date().getTime().toString() );
        await expect( page ).toFill( '#woocommerce-coupon-description', 'test coupon' );
        
        // Set general coupon data
        await clickTab( 'General' );
        await expect( page ).toSelect( '#discount_type', 'Fixed cart discount' );
        await expect( page ).toFill( '#coupon_amount', '100' );

        // Publish coupon
        await expect( page ).toClick( '#publish' );
        await page.waitForSelector( '#message' );

        // Verify
        await expect( page ).toMatchElement( '#message', { text: 'Coupon updated.' } );
    } );
} );
