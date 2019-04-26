/**
 * @format
 */

/** 
 * Internal dependencies
 */
const { selectSelect2Option, StoreOwnerFlow } = require( '../utils' );

describe( 'Add New Order Page', () => {
    beforeAll( async () => {
		await jestPuppeteer.resetContext();
		await StoreOwnerFlow.login();
    } );

    it( 'can create new order', async () => {
        // Go to "add order" page
        await StoreOwnerFlow.openNewOrder();
        
        // Make sure we're on the add order page
        await expect( page.title() ).resolves.toMatch( 'Add new order' );

        // Set order data
        await selectSelect2Option( '.wc-order-status', 'Processing' );
        await expect( page ).toFill( 'input[name=order_date]',  '2016-12-13' );
        await expect( page ).toFill( 'input[name=order_date_hour]',  '18' );
        await expect( page ).toFill( 'input[name=order_date_minute]',  '55' );

        // Create order
        await expect( page ).toClick( 'button', { text: 'Create' } );
        await page.waitForSelector( '#message' );

        // Verify
        await expect( page ).toMatchElement( '#message', { text: 'Order updated.' } );
        await expect( page ).toMatchElement( '#select2-order_status-container', { text: 'Processing' } );
        await expect( page ).toMatchElement(
            '#woocommerce-order-notes .note_content',
            {
                text: 'Order status changed from Pending payment to Processing.',
            }
        );
    } );
} );
