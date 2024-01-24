const { test: baseTest, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

baseTest.describe( 'Products > Edit Product', () => {
	baseTest.use( { storageState: process.env.ADMINSTATE } );

	const test = baseTest.extend( {
		api: async ( { baseURL }, use ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );

			await use( api );
		},

		product: async ( { api }, use ) => {
			let product = {
				id: 0,
				name: `Product ${ Date.now() }`,
				type: 'simple',
				regular_price: '12.99',
			};

			await api.post( 'products', product ).then( ( response ) => {
				product = response.data;
			} );

			await use( product );

			// permanently delete the product if it still exists
			const r = await api.get( `products/${ product.id }` );
			if ( r.status !== 404 ) {
				await api.delete( `products/${ product.id }`, {
					force: true,
				} );
			}
		},
	} );

	test( 'can edit a product and save the changes', async ( {
		page,
		product,
	} ) => {
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );

		const newProduct = {
			name: `Product ${ Date.now() }`,
			description: `This product is pretty awesome ${ Date.now() }`,
			regularPrice: '100.05',
			salePrice: '99.05',
		};

		await test.step( 'edit the product name', async () => {
			await page.getByLabel( 'Product name' ).fill( newProduct.name );
		} );

		await test.step( 'edit the product description', async () => {
			await page.locator( '#content-html' ).click(); // text mode to work around iframe
			await page
				.locator( '.wp-editor-area' )
				.first()
				.fill( newProduct.description );
		} );

		await test.step( 'edit the product price', async () => {
			await page
				.getByLabel( 'Regular price ($)' )
				.fill( newProduct.regularPrice );
			await page
				.getByLabel( 'Sale price ($)' )
				.fill( newProduct.salePrice );
		} );

		await test.step( 'publish the updated product', async () => {
			await page.locator( '#publish' ).click();
		} );

		await test.step( 'verify the changes', async () => {
			await expect( page.getByLabel( 'Product name' ) ).toHaveValue(
				newProduct.name
			);
			await expect(
				page.locator( '.wp-editor-area' ).first()
			).toContainText( newProduct.description );
			await expect( page.getByLabel( 'Regular price ($)' ) ).toHaveValue(
				newProduct.regularPrice
			);
			await expect( page.getByLabel( 'Sale price ($)' ) ).toHaveValue(
				newProduct.salePrice
			);
		} );
	} );
} );
