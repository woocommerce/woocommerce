const {
	test: baseTest,
} = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '../../../../fixtures/fixtures' );

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

test( 'can track stock quantity', async ( { page, product } ) => {
	await test.step( 'enable track stock quantity', async () => {
		await page.getByLabel( 'Track inventory' ).check();
		// await closeTourModal( { page, timeout: 2000 } );
		await page.getByRole( 'button', { name: 'Advanced' } ).click();
		await page.getByLabel( "Don't allow purchases" ).check();
	} );

	const quantity = '2';

	await test.step( 'update available quantity', async () => {
		await page.locator( '[name="stock_quantity"]' ).clear();
		await page.locator( '[name="stock_quantity"]' ).fill( quantity );
		await expect( page.locator( '[name="stock_quantity"]' ) ).toHaveValue(
			quantity
		);
	} );

	await test.step( 'update the product', async () => {
		await page.getByRole( 'button', { name: 'Update' } ).click();
		// Verify product was updated
		await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
			'Product updated'
		);
	} );

	await test.step( 'verify the change in product editor', async () => {
		await expect( page.locator( '[name="stock_quantity"]' ) ).toHaveValue(
			quantity
		);
	} );

	await test.step( 'verify the changes in the store frontend', async () => {
		// Verify image in store frontend
		await page.goto( product.permalink );

		await expect(
			page.getByText( `${ quantity } in stock` )
		).toBeVisible();
	} );

	await test.step( 'return to product editor', async () => {
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
		await page.getByRole( 'button', { name: 'Inventory' } ).click();
	} );

	await test.step( 'update available quantity', async () => {
		await page.locator( '[name="stock_quantity"]' ).fill( '0' );
	} );

	await test.step( 'update the product', async () => {
		await page.getByRole( 'button', { name: 'Update' } ).click();
		// Verify product was updated
		await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
			'Product updated'
		);
	} );

	await test.step( 'verify the change in product editor', async () => {
		await expect( page.locator( '[name="stock_quantity"]' ) ).toHaveValue(
			'0'
		);
	} );

	await test.step( 'verify the changes in the store frontend', async () => {
		// Verify image in store frontend
		await page.goto( product.permalink );

		await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
	} );
} );

test( 'can limit purchases', async ( { page, product } ) => {
	await test.step( 'ensure limit purchases is disabled', async () => {
		// await closeTourModal( { page, timeout: 2000 } );
		await page.getByRole( 'button', { name: 'Advanced' } ).click();
		await expect(
			page.getByLabel( 'Limit purchases to 1 item per order' )
		).not.toBeChecked();
	} );

	await test.step( 'add 2 items to cart', async () => {
		// Verify image in store frontend
		await page.goto( product.permalink );

		await page.getByLabel( 'Product quantity' ).fill( '2' );
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await expect(
			page.getByText(
				`2 × “${ product.name }” have been added to your cart.`
			)
		).toBeVisible();
	} );

	await test.step( 'return to product editor', async () => {
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
		await page.getByRole( 'button', { name: 'Inventory' } ).click();
	} );

	await test.step( 'enable limit purchases', async () => {
		await page.getByRole( 'button', { name: 'Advanced' } ).click();
		await page.getByLabel( 'Limit purchases to 1 item per order' ).check();
	} );

	await test.step( 'update the product', async () => {
		await page.getByRole( 'button', { name: 'Update' } ).click();
		// Verify product was updated
		await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
			'Product updated'
		);
	} );

	await test.step( 'verify you cannot order more than 1 item', async () => {
		// Verify image in store frontend
		await page.goto( product.permalink );

		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await expect(
			page.getByText(
				`You cannot add another "${ product.name }" to your cart.`
			)
		).toBeVisible();
	} );
} );
