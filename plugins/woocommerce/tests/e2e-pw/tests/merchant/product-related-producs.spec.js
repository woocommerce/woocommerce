const { test: baseTest, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

baseTest.describe( 'Products > Related products', () => {
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
				sale_price: '11.59',
			};

			await api.post( 'products', product ).then( ( response ) => {
				product = response.data;
			} );

			await use( product );

			// Cleanup
			await api.delete( `products/${ product.id }`, { force: true } );
		},
	} );

	test( 'add up-sells', async ( { page, product } ) => {
		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
		} );
	} );

	test( 'add cross-sells', async ( { page, productWithImage } ) => {
		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithImage.id }&action=edit`
			);
		} );
	} );
} );
