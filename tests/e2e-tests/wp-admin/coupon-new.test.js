/**
 * @format
 */

/** 
 * Internal dependencies
 */
const { StoreOwnerFlow } = require( '../utils/flows' );

/** 
 * External dependencies
 */
const config = require( 'config' );

const baseUrl = config.get( 'url' );

describe( 'Add New Coupon Page', () => {
    it( 'can create new coupon', async () => {
        // Login and go to "add coupon" page
        await StoreOwnerFlow.login();
        await StoreOwnerFlow.openNewCoupon();
        
        // Make sure we're on the add coupon page
        await expect( page.title() ).resolves.toMatch( 'Add new coupon' );

        // Fill in coupon code and description
        await expect( page ).toFill( '#title', 'code-' + new Date().getTime().toString() );
        await expect( page ).toFill( '#woocommerce-coupon-description', 'test coupon' );
        
        // Set general coupon data
        await expect( page ).toClick( '.wc-tabs > li > a', { text: 'General' } );
        await expect( page ).toSelect( '#discount_type', 'Fixed cart discount' );
        await expect( page ).toFill( '#coupon_amount', '100' );

        // Publish coupon
        await expect( page ).toClick( '#publish' );
        await page.waitForSelector( '#message' );

        // Verify
        await expect( page ).toMatchElement( '#message', { text: 'Coupon updated.' } );
    } );
} );
