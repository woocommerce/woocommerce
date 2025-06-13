/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { test, expect, Editor, wpCLI } from '@woocommerce/e2e-utils';

test.describe( 'Product Page: error notices when adding out-of-stock products', () => {
	test( 'displays error notice when attempting to add product beyond stock limit', async ( {
		admin,
		editor,
		page,
	} ) => {
		const productName = 'A Managed Stock';

		await wpCLI(
			`wc product create --name="${ productName }" --regular_price=10 --manage_stock=true --stock_quantity=1 --user=admin`
		);
		await admin.createNewPost();
		await editor.insertBlock( { name: 'woocommerce/product-collection' } );

		const singleProduct = await editor.getBlockByName(
			'woocommerce/product-collection'
		);

		await expect(
			singleProduct.getByText( 'create your own' )
		).toBeVisible();

		await singleProduct.getByText( 'create your own' ).click();

		await editor.publishAndVisitPost();

		const productCart = page
			.locator( '.wc-block-product' )
			.filter( { hasText: productName } );

		await expect( productCart ).toBeVisible();

		// Add to cart once — succeeds.
		await productCart
			.getByRole( 'button', { name: 'Add to cart' } )
			.click();

		// Add to cart again — triggers out-of-stock error.
		await productCart.getByRole( 'button' ).click();

		// Verify error notice is displayed.
		await expect( page.getByRole( 'alert' ) ).toBeVisible();
		await expect( page.getByRole( 'alert' ) ).toHaveText(
			/maximum quantity|You cannot add that amount/i
		);
	} );
} );
