const { test: baseTest } = require( './block-editor-fixtures' );
const { expect } = require( '../../../../fixtures' );
const test = baseTest.extend( {
	product: async ( { api }, use ) => {
		let product;

		await api
			.post( 'products', {
				name: `Product ${ Date.now() }`,
				type: 'simple',
				regular_price: '12.99',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await api.delete( `products/${ product.id }`, { force: true } );
	},
} );

test( 'can update sku', async ( { page, product } ) => {
	await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
	await page.getByRole( 'button', { name: 'Inventory' } ).click();

	const sku = `SKU_${ Date.now() }`;
	await page.locator( '[name="woocommerce-product-sku"]' ).fill( sku );

	await test.step( 'update the product', async () => {
		await page.getByRole( 'button', { name: 'Update' } ).click();
		// Verify product was updated
		await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
			'Product updated'
		);
	} );

	await test.step( 'verify the change', async () => {
		await expect(
			page.locator( '[name="woocommerce-product-sku"]' )
		).toHaveValue( sku );
	} );

	await test.step( 'verify the changes in the store frontend', async () => {
		// Verify image in store frontend
		await page.goto( product.permalink );

		await expect( page.getByText( `SKU: ${ sku }` ) ).toBeVisible();
	} );
} );
