/**
 * External dependencies
 */
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage from './product-collection.page';

const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Product Page: error notices', () => {
	test( 'displays error notice when attempting to add product beyond stock limit', async ( {
		page,
		pageObject,
	} ) => {
		const productName = 'A Managed Stock';

		await wpCLI(
			`wc product create --name="${ productName }" --regular_price=10 --manage_stock=true --stock_quantity=1 --user=admin`
		);
		await pageObject.createNewPostAndInsertBlock();
		await pageObject.publishAndGoToFrontend();

		const productBlock = page
			.locator( '.wc-block-product' )
			.filter( { hasText: productName } );

		await expect( productBlock ).toBeVisible();

		const addButton = productBlock.getByRole( 'button', {
			name: 'Add to cart',
		} );
		await addButton.click();
		await expect( addButton ).toHaveText( /1 in cart/i ); // Wait until the button reflects the cart state.

		// Add to cart again — triggers out-of-stock error.
		await addButton.click();

		// Verify error notice is displayed.
		await expect( page.getByRole( 'alert' ) ).toBeVisible();
		await expect( page.getByRole( 'alert' ) ).toHaveText(
			/maximum quantity|You cannot add that amount/i
		);
	} );
} );
