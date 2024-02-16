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
				stock_status: 'instock',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await api.delete( `products/${ product.id }`, { force: true } );
	},
	page: async ( { page, product }, use ) => {
		await test.step( 'go to product editor, inventory tab', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await page.getByRole( 'button', { name: 'Inventory' } ).click();
		} );

		await use( page );
	},
} );

test( 'can update sku', async ( { page, product } ) => {
	const sku = `SKU_${ Date.now() }`;

	await test.step( 'update the sku value', async () => {
		await page.locator( '[name="woocommerce-product-sku"]' ).fill( sku );
	} );

	await test.step( 'update the product', async () => {
		await page.getByRole( 'button', { name: 'Update' } ).click();
		// Verify product was updated
		await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
			'Product updated'
		);
	} );

	await test.step( 'verify the change in product editor', async () => {
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

test( 'can update stock status', async ( { page, product } ) => {
	await test.step( 'update the sku value', async () => {
		await page.getByLabel( 'Out of stock' ).check();
	} );

	await test.step( 'update the product', async () => {
		await page.getByRole( 'button', { name: 'Update' } ).click();
		// Verify product was updated
		await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
			'Product updated'
		);
	} );

	await test.step( 'verify the change in product editor', async () => {
		await expect( page.getByLabel( 'Out of stock' ) ).toBeChecked();
	} );

	await test.step( 'verify the changes in the store frontend', async () => {
		// Verify image in store frontend
		await page.goto( product.permalink );

		await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
	} );
} );
