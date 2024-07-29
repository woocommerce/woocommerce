const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	products: async ( { api }, use ) => {
		const products = [];

		// Create two simple products for testing
		for ( let i = 0; i < 2; i++ ) {
			await api
				.post( 'products', {
					id: 0,
					name: `Product ${ i }_${ Date.now() }`,
					type: 'simple',
					regular_price: `${ 12.99 + i }`,
					manage_stock: true,
					stock_quantity: 10 + i,
					stock_status: 'instock',
				} )
				.then( ( response ) => {
					products.push( response.data );
				} );
		}

		await use( products );

		// Cleanup
		for ( const product of products ) {
			await api.delete( `products/${ product.id }`, { force: true } );
		}
	},
} );

test.describe(
	'Product Editing',
	{ tag: [ '@gutenberg', '@services' ] },
	() => {
		test( 'can edit product name', async ( {
			page,
			products,
		} ) => {
			await page.goto(
				`wp-admin/post.php?post=${ products[ 0 ].id }&action=edit`
			);

			const newProductName = `Product ${ Date.now() }`;

			// Edit product name
			await page.getByLabel( 'Product name' ).fill( newProductName );

			// Publish the updated product
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify the changes
			await expect( page.getByLabel( 'Product name' ) ).toHaveValue(
				newProductName
			);
		} );

		test( 'can edit product description', async ( {
			page,
			products,
		} ) => {
			await page.goto(
				`wp-admin/post.php?post=${ products[ 0 ].id }&action=edit`
			);

			const newProductDescription = `This product is pretty awesome ${ Date.now() }`;

			// Switch to text mode and edit product description
			await page.locator( '#content-html' ).click();
			await page
				.locator( '.wp-editor-area' )
				.first()
				.fill( newProductDescription );

			// Publish the updated product
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify the changes
			await expect(
				page.locator( '.wp-editor-area' ).first()
			).toContainText( newProductDescription );
		} );

		test( 'can edit product prices', async ( {
			page,
			products,
		} ) => {
			await page.goto(
				`wp-admin/post.php?post=${ products[ 0 ].id }&action=edit`
			);

			const newProductPrices = {
				regularPrice: '100.05',
				salePrice: '99.05',
			};

			// Edit product prices
			await page
				.getByLabel( 'Regular price ($)' )
				.fill( newProductPrices.regularPrice );
			await page
				.getByLabel( 'Sale price ($)' )
				.fill( newProductPrices.salePrice );

			// Publish the updated product
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify the price changes
			await expect( page.getByLabel( 'Regular price ($)' ) ).toHaveValue(
				newProductPrices.regularPrice
			);
			await expect( page.getByLabel( 'Sale price ($)' ) ).toHaveValue(
				newProductPrices.salePrice
			);
		} );
	}
);
