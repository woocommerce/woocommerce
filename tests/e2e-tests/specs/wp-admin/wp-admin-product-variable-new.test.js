/**
 * @format
 */

/**
 * Internal dependencies
 */
import { StoreOwnerFlow } from '../../utils/flows';
import { clickTab, deleteProduct, uiUnblocked } from '../../utils';
import { verifyAndPublish } from '../../utils/components';

describe( 'Add New Variable Product Page', () => {
	it( 'should login', async () => {
		await StoreOwnerFlow.login();
	} );

	it( 'should open "add new product" page and set initial product data', async () => {
		// Go to "add product" page
		await StoreOwnerFlow.openNewProduct();

		// Make sure we're on the add product page
		await expect( page ).toMatchElement( '.wp-heading-inline', { text: 'Add new product' } );

		// Set product data
		await expect( page ).toFill( '#title', 'Variable Product with Three Variations' );
		await expect( page ).toSelect( '#product-type', 'Variable product' );
	} );

	it( 'should create attributes', async () => {
		// Create attributes for variations
		await clickTab( 'Attributes' );
		await expect( page ).toSelect( 'select[name="attribute_taxonomy"]', 'Custom product attribute' );

		for ( let i = 0; i < 3; i++ ) {
			await expect( page ).toClick( 'button.add_attribute', { text: 'Add' } );
			// Wait for attribute form to load
			await uiUnblocked();

			await page.focus( `input[name="attribute_names[${ i }]"]` );
			await expect( page ).toFill( `input[name="attribute_names[${ i }]"]`, 'attr #' + ( i + 1 ) );
			await expect( page ).toFill( `textarea[name="attribute_values[${ i }]"]`, 'val1 | val2' );
			await expect( page ).toClick( `input[name="attribute_variation[${ i }]"]` );
		}

		await expect( page ).toClick( 'button', { text: 'Save attributes' } );

		// Wait for attribute form to save (triggers 2 UI blocks)
		await uiUnblocked();
		await uiUnblocked();
	} );

	it( 'should create variations', async () => {
		// Create variations from attributes
		await clickTab( 'Variations' );
		await page.waitForSelector( 'select.variation_actions:not([disabled])' );
		await page.focus( 'select.variation_actions' );
		await expect( page ).toSelect( 'select.variation_actions', 'Create variations from all attributes' );

		// Close all dialogues that pop up
		page.on( 'dialog', async dialog => {
			await dialog.accept();
		} );

		// Normally clicking a link would be like this:
		// 		await page.waitForSelector('a.do_variation_action');
		// 		await page.click('a.do_variation_action');
		// However this doesn't work. Using another technique:
		// See: https://github.com/GoogleChrome/puppeteer/issues/1805#issuecomment-464802876
		await page.$eval( 'a.do_variation_action', elem => elem.click() );

		// Set variation data (2 UI blocks)
		await uiUnblocked();
		await uiUnblocked();
	} );

	it( 'should make sure that variations were created', async () => {
		await page.waitFor( 2000 ); // waitForSelector fails here...To-Do
		// 'Variations price is not set...' notice should be displayed
		await page.waitForSelector( '.woocommerce-notice-invalid-variation' );

		// Verify that variations were created
		await Promise.all( [
			expect( page ).toMatchElement( 'select[name="attribute_attr-1[0]"]', { text: 'val1' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-2[0]"]', { text: 'val1' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-3[0]"]', { text: 'val1' } ),

			expect( page ).toMatchElement( 'select[name="attribute_attr-1[1]"]', { text: 'val1' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-2[1]"]', { text: 'val1' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-3[1]"]', { text: 'val2' } ),

			expect( page ).toMatchElement( 'select[name="attribute_attr-1[2]"]', { text: 'val1' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-2[2]"]', { text: 'val2' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-3[2]"]', { text: 'val1' } ),

			expect( page ).toMatchElement( 'select[name="attribute_attr-1[3]"]', { text: 'val1' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-2[3]"]', { text: 'val2' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-3[3]"]', { text: 'val2' } ),

			expect( page ).toMatchElement( 'select[name="attribute_attr-1[4]"]', { text: 'val2' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-2[4]"]', { text: 'val1' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-3[4]"]', { text: 'val1' } ),

			expect( page ).toMatchElement( 'select[name="attribute_attr-1[5]"]', { text: 'val2' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-2[5]"]', { text: 'val1' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-3[5]"]', { text: 'val2' } ),

			expect( page ).toMatchElement( 'select[name="attribute_attr-1[6]"]', { text: 'val2' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-2[6]"]', { text: 'val2' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-3[6]"]', { text: 'val1' } ),

			expect( page ).toMatchElement( 'select[name="attribute_attr-1[7]"]', { text: 'val2' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-2[7]"]', { text: 'val2' } ),
			expect( page ).toMatchElement( 'select[name="attribute_attr-3[7]"]', { text: 'val2' } ),
		] );
	} );

	it( 'should fill out details of the 3 variations and publish product', async () => {
		await page.waitFor( 2000 ); // waitForSelector fails here...To-Do
		await expect( page ).toClick( '.woocommerce_variation:nth-of-type(2) .handlediv' );

		await page.waitFor( 2000 ); // wait for dropdown details to load...To-Do
		await expect( page ).toClick( 'input[name="variable_is_virtual[0]"]' );
		await expect( page ).toFill( 'input[name="variable_regular_price[0]"]', '9.99' );

		await expect( page ).toClick( '.woocommerce_variation:nth-of-type(3) .handlediv' );

		await page.waitFor( 2000 ); // wait for dropdown details to load...To-Do
		await expect( page ).toClick( 'input[name="variable_is_virtual[1]"]' );
		await expect( page ).toFill( 'input[name="variable_regular_price[1]"]', '11.99' );

		await expect( page ).toClick( '.woocommerce_variation:nth-of-type(4) .handlediv' );

		await page.waitFor( 2000 ); // wait for dropdown details to load...To-Do
		await expect( page ).toClick( 'input[name="variable_manage_stock[2]"]' );
		await expect( page ).toFill( 'input[name="variable_regular_price[2]"]', '20' );
		await expect( page ).toFill( 'input[name="variable_weight[2]"]', '200' );
		await expect( page ).toFill( 'input[name="variable_length[2]"]', '10' );
		await expect( page ).toFill( 'input[name="variable_width[2]"]', '20' );
		await expect( page ).toFill( 'input[name="variable_height[2]"]', '15' );

		await page.focus( 'button.save-variation-changes' );
		await expect( page ).toClick( 'button.save-variation-changes', { text: 'Save changes' } );
	} );

	it( 'should publish variable product', async () => {
		await verifyAndPublish();
	} );

	it( 'should delete variable product', async () => {
		await deleteProduct();
	} );
} );

