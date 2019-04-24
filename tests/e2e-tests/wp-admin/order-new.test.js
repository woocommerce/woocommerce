/**
 * @format
 */

/** 
 * Internal dependencies
 */
const { StoreOwnerFlow } = require( '../utils/flows' );

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
        await expect( page ).toClick( '.wc-order-status span.select2' );
        const [ processingOption ] = await page.$x(
            '//li[contains(@class, "select2-results__option") and contains(text(), "Processing")]'
        );
        processingOption.click();

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
